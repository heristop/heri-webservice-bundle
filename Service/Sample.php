<?php

namespace Heri\WebServiceBundle\Service;

use Heri\WebServiceBundle\ClientSoap\ClientObject;
use Doctrine\ORM\QueryBuilder;

class Sample extends ClientObject
{
    public function configure()
    {
        $this->name  = 'sample';
        $this->table = 'HeriWebServiceBundle:Sample';
        $this->func  = 'addSample';
    }
    
    public function hydrate($record)
    {  
        $this->params = array(
            'id' => $record->getId(),
        );
    }
    
    public function addColumnsToUpdate(QueryBuilder $qb)
    {
        $status = $qb->expr()->literal($this->result['publication_status']);
        $qb->set('a.publicationStatus', $status);
        
        return $qb;
    }
}