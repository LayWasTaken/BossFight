<?php

namespace Lay\BossFight\entity\attacks;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

abstract class DelayedDamageAttack extends DelayedAttack {

    public function __construct(protected Entity $source, protected int|float $damage, protected int $ticks = 0){}

    public function getSource(){
        return $this->source;
    }

    public function getBaseDamage(){
        return $this->damage;
    }
}