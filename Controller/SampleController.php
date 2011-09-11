<?php

namespace Heri\WebServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SampleController extends Controller
{
    public function indexAction(Request $request)
    {
        if ($request->query->has('wsdl')) {
            $server = new \Zend_Soap_AutoDiscover();
            $server->setClass('\\Heri\\WebServiceBundle\\Tests\\Server\\Sample');
        }
        else {
            $server = new \Zend_Soap_Server('sample.wsdl');
            $server->setClass('\\Heri\\WebServiceBundle\\Tests\\Server\\Sample');
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        $server->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }
}
