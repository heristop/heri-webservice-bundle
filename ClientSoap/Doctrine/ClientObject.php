<?php

/*
 * This file is part of HeriWebServiceBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\WebServiceBundle\ClientSoap\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Send data to WebServices
 */
abstract class ClientObject extends \Heri\WebServiceBundle\ClientSoap\ClientObject
{
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
    
}