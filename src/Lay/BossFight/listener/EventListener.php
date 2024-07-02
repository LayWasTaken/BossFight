<?php

namespace Lay\BossFight\listener;

use Lay\BossFight\entity\BehavioralEntity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;

final class EventListener implements Listener {

    /**
     * @priority HIGHEST
     */
    public function onEntityDamage(EntityDamageEvent $event){
        if($event->isCancelled()) return;
        $entity = $event->getEntity();
        if($entity instanceof BehavioralEntity) 
            if($entity->isInvulnerable()) return $event->cancel();
    }

}