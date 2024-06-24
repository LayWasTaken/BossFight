<?php

namespace Lay\BossFight\entity\attacks;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

abstract class BaseAttack { 

    public function __construct(protected Entity $source){}

    public abstract function attack(): void;

    public function baseAttack(Entity $target, float $damage, int $cause = EntityDamageEvent::CAUSE_ENTITY_ATTACK){
        $target->attack(new EntityDamageEvent($this->source, $cause, $damage));
    }

}