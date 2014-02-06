<?php

namespace Heri\Bundle\WebServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SampleController extends Controller
{
    public function indexAction(Request $request)
    {
        if ($request->query->has('wsdl')) {
            $autodiscover = new \Zend\Soap\AutoDiscover();
            $autodiscover->setClass('\\Heri\\WebServiceBundle\\Tests\\Server\\Sample');
            $autodiscover->setUri('http://my-local/sample/index');
            $autodiscover->generate();
            
            return new Response($autodiscover->toXml(), 200, array('Content-Type' => 'application/xml'));
        }
        else {
            $server = new \Zend\Soap\Server('sample.wsdl');
            $server->setClass('\\Heri\\WebServiceBundle\\Tests\\Server\\Sample');
            $server->handle();
            
            return new Response("", 200, array('Content-Type' => 'application/xml'));
        }
    }
}
