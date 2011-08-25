<?php

/*
 * This file is part of HeriWebServiceBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\WebServiceBundle;

use Symfony\Component\EventDispatcher\Event;

class SyncListener
{
    protected $queue;

    /**
     *@param QueueService $queue
     */
    public function __construct(\Heri\JobQueueBundle\QueueService $queue)
    {
        $this->queue = $queue;
    }
    
    public function prePersist(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        
        if (method_exists($entity, 'setToUpdate')) {
            $entity->setToUpdate(1);
        }
    }

    public function postPersist(\Doctrine\ORM\Event\LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        
        if (method_exists($entity, 'synchronize')) {
            $this->queue->configure($entity->synchronize(), $eventArgs->getEntityManager());
            
            $namespace = explode('\\', get_class($entity));
            $namespace = array_reverse($namespace);
            $service = array_shift($namespace);
            
            $config = array(
                'command'   => 'webservice:load',
                'arguments' => array(
                    '--record' => $entity->getId(),
                    'service'  => $service
                ),
            );
            
            $this->queue->sync($config);
        }
    }
}
