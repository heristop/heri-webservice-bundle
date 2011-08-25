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
                'No function to call in ' . get_class($this)
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
            $qb->where('to_update = true');
        }
        
        if (!is_null($this->recordId)) {
            $qb->where('a.'.$this->primaryKey . ' = :record');
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
            $this->result = $this->callSoapClient($this->name, $this->params, true);
        }
        catch (SoapException $e) {
            $this->errorOnCallSoapClient($e->getMessage());
        }
    }
    
    /**
     * Adds condition to retrieves the next record
     *
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
            throw new \Exception(__CLASS_ . ' ' . $e->getMessage());
        }
        
        return (array) $this->result;
    }

    /**
     * @param     Doctrine\ORM\QueryBuilder $qb
     * @access    public
     */
    protected function setAsUpdated()
    {
        $em = $this->container->getEntityManager();
        
        $qb = $em->createQueryBuilder()
            ->select('a')
            ->from($this->table, 'a');
        
        if (in_array('to_update', $this->columns)) {
            $qb->set('a.to_update = false');
        }
        
        $qb->addColumnsToUpdate($qb);
        
        $qb->where('a.'.$this->pkColumn.' = :pk_value');
        $qb->setParameter('pk_value', $this->record->{$this->pkColumn});
        $query = $qb->getQuery();
        $query->execute();
    }
    
    /**
     * Adds columns to set after synchronize
     *
     * @param     Doctrine\ORM\QueryBuilder $qb
     * @access    public
     */
    public function addColumnsToUpdate($qb)
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
    
    /**
     * Throws WebService exceptions
     * 
     * @param string message
     * @param mixed $result
     */
    final public function errorOnCallSoapClient($message, $result = null)
    {
        if (!is_null($result) && !empty($result)) {
            $result = (array) $result;
            $message .= sprintf("%s\n", print_r($result, true));
        }
        
        throw new SoapException(SoapException::TYPE_ANSWER, $message, $this, $this->params);
    }
    
    /**
     * Calls the WebService
     * 
     * @return mixed (Change returns stdClass by default)
     */ 
    final protected function callSoapClient($name, $data)
    {
        $config = $this->container->getConfiguration();
        $authentication = $config['authentication'];
        $webserviceConf = $config['webservices'][$name];
        
        if (!$this->client) {
            $this->client = $this->container->getConnection()->getSoapClient($name, array(
                'authentication' => $webserviceConf['authentication'],
                'cache_enabled'  => $webserviceConf['cache_enabled'],
                'soap_url'       => $webserviceConf['url'],
                'login'          => $authentication['login'],
                'password'       => $authentication['password']
            ));
        }
        
        try {
            $record = $this->client->__call($this->func, array($data));
            $resultFunction = "{$this->func}Result";
            $result = $record->$resultFunction;
        }
        catch (\SoapFault $fault) {
            throw new SoapException(
                SoapException::TYPE_CALL,
                "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})",
                $this
            );
        }
        
        return $result;
    }
}