<?php

/*
 * This file is part of HeriWebServiceBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\WebServiceBundle\Listener;

use Symfony\Component\EventDispatcher\Event;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Heri\JobQueueBundle\Service\QueueService;

class SyncListener
{
    protected $queue = null;

    /**
     *@param QueueService $queue (optional)
     */
    public function __construct(QueueService $queue = null)
    {
        $this->queue = $queue;
    }
    
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        
        if (method_exists($entity, 'setToUpdate')) {
            $entity->setToUpdate(1);
        }
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        
        if (method_exists($entity, 'synchronize') && !is_null($this->queue)) {
            $this->queue->configure($entity->synchronize(), $eventArgs->getEntityManager());
            
            $namespace = explode('\\', get_class($entity));
            $namespace = array_reverse($namespace);
            $service   = array_shift($namespace);
            
            $config = array(
                'command'   => 'webservice:load',
                'arguments' => array(
                    '--record' => $entity->getId(),
                    'service'  => $service
                ),
            );
            
            if (method_exists($entity, 'getWsConfig')) {
                $config = array_merge($config, $entity->getWsConfig());
            }
            
            $this->queue->sync($config);
        }
    }
}
