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

use Symfony\Component\Console\Input\InputInterface;

/**
 * Send data to WebServices
 */
abstract class ClientObject extends \Heri\WebServiceBundle\ClientSoap\ClientObject
{
    /**
     * Retrieves the next record (must be call in a recursive function to make the loop)
     * 
     * @return mixed
     */
    public function getNextRecord()
    {
        $query = $this->getQuery();
        $record = $this->fetch($query);

        return $record ? $record : false;
    }
    
    public function fetch($query)
    {
        return $query->findOne();
    }
    
    /**
     * Adds condition to retrieves the next record
     *
     * @uses      Propel
     * @param     Query $query
     * @access    public
     */
    public function addCriteria($query)
    {
        return $query;
    }
    
    public function addColumnsToUpdate()
    {
        return array();
    }
    
    protected function getQuery()
    {
        $queryClass = "{$this->table}Query";
        $query = $queryClass::create();
        
        return $this->addCriteria($query);
    }
    
    /**
     * @uses      Propel
     * @access    protected
     */
    protected function setAsUpdated()
    {
        $query = $this->getQuery();
        $query->filterById($this->record->getPrimaryKey());
        
        $updatedColumns = $this->addColumnsToUpdate();
        $query->update($updatedColumns);
    }
    
}