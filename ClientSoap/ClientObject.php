<?php

/*
 * This file is part of HeriWebServiceBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\WebServiceBundle\ClientSoap;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Send data to Front's WebServices
 */
abstract class ClientObject
{
    protected
        $client                = null,    // client Soap
        $container             = null,
        $name                  = null,    // name of WS
        $data                  = null,    // data to send
        $table                 = null,
        $func                  = null,    // function name to call
        $params                = null,    // function's parameters
        $record                = null,    // related object
        $recordId              = null,
        $primaryKey            = 'id',
        $columns               = array()
    ;
    
    public
        $input = array();
    
    /**
     * Initializes configuration and hydrates the first object if there is one
     * Checks if 'func' has been specified in configure method
     *
     * @param   ClientDispatcher $container
     * @param   Symfony\Component\Console\Input\InputInterface $input
     * @access  public
     */
    public function __construct(ClientDispatcher $container, InputInterface $input)
    {
        $this->container = $container;
        
        $this->configure();
        
        if (is_null($this->func)) {
            throw new SoapException(
                SoapException::TYPE_CALL,
                'No function to call in ' . get_class($this),
                $this->getContainer()
            );
        }
        
        if (!is_null($input->getOption('record'))) {
            $this->recordId = $input->getOption('record');
        }
    }
    
    public function __toString()
    {
        return $this->name;
    }
    
    /**
     * Configures attributes 'function' and 'table'
     *
     * @access    abstract public
     * @return void
     */
    abstract public function configure();
    
    /**
     * Hydrates the object before to send it to WebService
     * 
     * @access    abstract public
     * @param     Entity $record
     * @return    void
     */
    abstract public function hydrate($record);
    
    /**
     * Rehydrates the object to use an unique instanciation of wsObject
     *
     * @param Entity $record
     * @return void
     */
    public function rehydrate($record)
    {
        $this->data = array();
        
        $this->record = $record;
        $this->configure();
        
        return $this->hydrate($record);
    }
    
    /**
     * Retrieves the next record (must be call in a recursive function to make the loop)
     * 
     * @return mixed
     */
    public function getNextRecord()
    {
        $em = $this->container->getEntityManager();
        
        $descriptor = $em->getClassMetadata($this->table);
        $columns    = (array) $descriptor->fieldNames;
        $this->columns = array_keys($columns);
        
        $qb = $em->createQueryBuilder()
            ->select('a')
            ->from($this->table, 'a');
        
        $qb = $this->addCriteria($qb);
        
        if (in_array('to_update', $this->columns)) {
            $qb->andWhere('a.toUpdate = :updated');
            $qb->setParameter('updated', true);
        }
        
        if (!is_null($this->recordId)) {
            $qb->andWhere('a.'.$this->primaryKey . ' = :record');
            $qb->setParameter('record', $this->recordId);
        }
        
        $query = $qb->getQuery();
        
        $record = $this->fetch($query);

        return $record ? $record : false;
    }
    
    public function fetch(Query $query)
    {
        return $query->getOneOrNullResult();
    }
    
    /**
     * Send data to WebService
     * It must call $this->callSoapClient(...);
     *
     * @return    array
     */
    public function push()
    {
        try {
            $this->result = $this->callSoapClient($this->name, $this->params);
        }
        catch (SoapException $e) {
            $this->errorOnCallSoapClient($e->getMessage());
        }
    }
    
    /**
     * Adds condition to retrieves the next record
     *
     * @uses      Doctrine
     * @param     Doctrine\ORM\QueryBuilder $qb
     * @access    public
     */
    public function addCriteria(QueryBuilder $qb)
    {
        return $qb;
    }
    
    public function postSynchronize()
    {
        try {
            $this->setAsUpdated();
        }
        catch (\Exception $e) {
            throw new \Exception(__CLASS__. ' ' . $e->getMessage());
        }
        
        return (array) $this->result;
    }
    
    /**
     * Adds columns to set after synchronize
     *
     * @uses      Doctrine
     * @param     Doctrine\ORM\QueryBuilder $qb
     * @access    public
     */
    public function addColumnsToUpdate(QueryBuilder $qb)
    {
        return $qb;
    }
    
    /**
     * Getter 'table'
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
    
    public function reset()
    {
        $this->client = false;
        $this->container->getConnection()->resetClientSingleton();
    }
    
    public function setContainer($container)
    {
        $this->container = $container;
    }
    
    public function setFunction($function)
    {
        $this->func = $function;
    }
    
    /**
     * Retrieves data to send to WebService
     * 
     * @access    public
     * @return    array
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Retrieves params to send to WebService
     * 
     * @access    public
     * @return    array
     */
    public function getParams()
    {
        return $this->params;
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function getContainer()
    {
        return $this->container;
    }
    
    public function getFunctions()
    {
        $this->configure();
        
        $this->getSoapClient($this->name);
        
        return $this->client->getFunctions();
    }
    
    /**
     * @uses      Doctrine
     * @access    protected
     */
    protected function setAsUpdated()
    {
        $em = $this->container->getEntityManager();
        
        $qb = $em->createQueryBuilder()
            ->update($this->table, 'a');
        
        if (in_array('to_update', $this->columns)) {
            $qb->set('a.toUpdate', $qb->expr()->literal(false));
        }

        $qb = $this->addColumnsToUpdate($qb);
        
        $qb->where('a.'.$this->primaryKey.' = :pk_value');
        $method = 'get' . ucfirst($this->primaryKey);
        $qb->setParameter('pk_value', $this->record->$method());
        $query = $qb->getQuery();
        $query->execute();
    }
    
    /**
     * Throws WebService exceptions
     * 
     * @param string message
     * @param mixed $result
     */
    public function errorOnCallSoapClient($message, $result = null)
    {
        if (!is_null($result) && !empty($result)) {
            $result = (array) $result;
            $message .= sprintf("%s\n", print_r($result, true));
        }
        
        throw new SoapException(SoapException::TYPE_ANSWER, $message, $this->getContainer());
    }
    
    protected function getSoapClient($name)
    {
        $config = $this->container->getConfiguration();
        
        $wsConf = null;
        $webservices = $config['webservices'];
        foreach ($webservices as $webservice) {
            if (isset($webservice['name'][$name])) {
                $wsConf = $webservice;
                break;
            }
        }
        
        if (is_null($wsConf)) {
            throw new \Exception(sprintf("Configuration for '%s' webservice not found.", $name));
        }
        
        $options = array(
            'authentication' => $wsConf['authentication'],
            'cache_enabled'  => $wsConf['cache_enabled'],
            'soap_url'       => $wsConf['url'],
        );
        
        if (isset($config['authentication'])) {
            $otpions = array_merge($options, $config['authentication']);
        }
        
        $this->client = $this->container->getConnection()->getSoapClient($name, $options);
    }
    
    /**
     * Calls the WebService
     *
     * @param string $name
     * @param array  $params
     * @return mixed
     */ 
    protected function callSoapClient($name, array $params = array())
    {
        if (!$this->client) {
            $this->getSoapClient($name);
        }
        
        try {
            $result = $this->callFunction($this->func, $params);
        }
        catch (\SoapFault $fault) {
            
            throw new SoapException(
                SoapException::TYPE_CALL,
                "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})",
                $this->getContainer()
            );
        }
        
        return $result;
    }
    
    /**
     * @param string $func
     * @param array  $params
     */
    protected function callFunction($func, array $params = array())
    {
        return $this->client->__call($func, $params);
    }
}