<?php

/*
 * This file is part of HeriWebServiceBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\WebServiceBundle\ClientSoap;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Send data to WebServices
 */
abstract class ClientDispatcher
{
    protected
        $connection,
        $logger,
        $client,
        $config
    ;
    
    /**
     * @param  array $config
     * @param  Symfony\Component\Console\Input\InputInterface $input
     */
    public function configure(array $config, InputInterface $input)
    {
        $service = $input->getArgument('service');
        $record  = $input->getOption('record');
        
        $className = null;
        foreach ($config['namespaces'] as $namespace) {
            if (class_exists($namespace.'\\'.$service)) {
                $className = $namespace.'\\'.$service;
            }
        }
        
        if (is_null($className)) {
            throw new \Exception(sprintf("The class %s doesn't exist.", $service));
        }
        
        $this->client = new $className($this, $input);
        $this->config = $config;
    }
    
    public function getClient()
    {
        return $this->client;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    public function getLogger()
    {
        return $this->logger;
    }
    
    public function getConfiguration()
    {
        return $this->config;
    }
    
    /**
     *
     * @param Entity $record
     * @param ClientObject $client
     * @return array
     */
    protected function pushClient($record, ClientObject $client)
    {
        $result = array();
        try {
            $client->reset();
            $client->rehydrate($record);
            $client->push();
            
            $result = $client->postSynchronize($result);
        }
        catch (SoapException $e) {
            $client->errorOnCallSoapClient($e->getMessage(), $result);
        }
        
        return (array) $result;
    }
}