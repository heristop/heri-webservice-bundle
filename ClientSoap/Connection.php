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

/**
 * Class to manage soap connection
 */
class Connection
{
    private static $client;
    
    protected
        $login,
        $password
    ;
    
    /**
     * Retrieves Soap Client via a singleton
     *
     * @param string $name
     * @access public static
     * @return object SoapClient 
     */ 
    public function getSoapClient($name, array $options = array())
    {
        if (! self::$client)
        {
            $this->createSoapClient($name, $options);
        }
        
        return self::$client;
    }
    
    public function resetClientSingleton()
    {
        self::$client = false;
    }
    
    public function getLogin()
    {
        return $this->login;
    }
    
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Soap Client webservices creation, catched with SoapException
     *
     * @param string $name
     * @access private static
     * @return boolean 
     */ 
    protected function createSoapClient($name, $options)
    {
        // catch Soap error connection
        $soapUrl = $options['soap_url'] . '?wsdl';
        
        $wsdl = @file_get_contents($soapUrl);
        if (!$wsdl)
        {
            throw new SoapException(SoapException::TYPE_CONNECT, 'Unable to reach webservice');
        }
        
        if (strpos($wsdl, 'definitions') === false)
        {
            throw new SoapException(SoapException::TYPE_CONNECT, 'Wrong WSDL format');
        }
        
        ini_set("soap.wsdl_cache_enabled", $options['cache_enabled']);
        
        $configuration = array(
            'soap_version' => SOAP_1_2,
            'compression'  => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
        );
        
        if ($options['authentication'])
        {
            $this->login = $options['login'];
            $this->password = $options['password'];
            
            $configuration = array_merge($configuration, array(
                'login'    => $this->login,
                'password' => $this->password
            ));
        }

        try
        {
            self::$client = new \Zend\Soap\Client($soapUrl, $configuration);
        }
        catch(SoapException $e)
        {
            throw new SoapException(SoapException::TYPE_CONNECT, $e->getMessage(), $this->logger);
        }

        return true;
    }
}
