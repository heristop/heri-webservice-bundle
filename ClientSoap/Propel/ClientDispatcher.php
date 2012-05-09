<?php

/*
 * This file is part of HeriWebServiceBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\WebServiceBundle\ClientSoap\Propel;

use Heri\WebServiceBundle\ClientSoap\Connection;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Send data to WebServices
 */
class ClientDispatcher extends \Heri\WebServiceBundle\ClientSoap\ClientDispatcher
{
    /**
     * @param  Connection $connection
     * @param  Logger $logger
     */
    public function __construct(Connection $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }
    
}