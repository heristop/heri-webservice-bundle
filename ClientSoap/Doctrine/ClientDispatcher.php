<?php

/*
 * This file is part of HeriWebServiceBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\WebServiceBundle\ClientSoap\Doctrine;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Send data to WebServices
 */
class ClientDispatcher extends \Heri\Bundle\WebServiceBundle\ClientSoap\ClientDispatcher
{
    protected $em;
    
    /**
     * @param  EntityManager $em
     * @param  Connection $connection
     * @param  Logger $logger
     */
    public function __construct(EntityManager $em, Connection $connection, Logger $logger)
    {
        $this->em = $em;
        $this->connection = $connection;
        $this->logger = $logger;
    }
    
    public function getEntityManager()
    {
        return $this->em;
    }

}