<?php

namespace Lay\BossFight\entity\attacks;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

abstract class BaseAttack { 

    protected int $cooldown = 0;

    public function __construct(protected Entity $source){}

    public abstract function attack(): void;

    public function attackWithCooldown(int $amount): bool{
        if(!$this->isValid()) return false;
        $this->setCooldown($amount);
        $this->attack();
        return true;
    }

    public function baseAttack(Entity $target, float $damage, int $cause = EntityDamageEvent::CAUSE_ENTITY_ATTACK){
        $target->attack(new EntityDamageEvent($this->source, $cause, $damage));
    }

    public function getCooldown(){
        return $this->cooldown;
    }

    public function setCooldown(int $amount){
        $this->cooldown = time() + $amount;
        return $this;
    }

    public function resetCooldown(){
        $this->cooldown = 0;
    }

    /**
     * Checks if the attack is valid to attack based on its cooldown
     */
    public function isValid(): bool{
        return $this->cooldown <= time();
    }

}