<?php

namespace Heri\WebServiceBundle\Tests\Server;

class Sample
{
    /**
     * @param  int $id
     * @return array
     */
    public function addSample($id)
    {
        if (!$id) {
            throw new \SoapFault('PHPUNIT', 'Id is a mandatory param');
        }
        
        return array(
            'id' => $id,
            'publication_status' => 'PUBLISHED'
        );
    }
}