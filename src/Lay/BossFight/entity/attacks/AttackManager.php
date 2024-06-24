<?php

namespace Lay\BossFight\entity\attacks;

final class AttackManager {

    private const HALT_KEY = "halt";

    private int $cooldown = 0;
    private bool $freeze = false;

    /**@var DelayedAttack[] $delayedAttacks */
    private array $delayedAttacks = [];

    public static function create(){ return new self; }

    public function __construct(){}

    /**
     * Recommend to call AttackManager::isAvailable() before calling this
     * @param bool $skipCooldown Set true to directly skip the cooldown and directly call the attack
     */
    public function callAttack(BaseAttack $attack, int $cooldown = 0, bool $skipCooldown = false){
        if($this->freeze) return false;
        if((!$skipCooldown) && (!$this->isOnCooldown())) return false;
        $this->cooldown = time() + $cooldown;
        $attack->attack();
        return true;
    }

    public function isOnCooldown(){
        return $this->cooldown <= time();
    }

    public function isAvailable(){
        return $this->freeze ? false : $this->isOnCooldown();
    }

    private function setFreeze(bool $freeze){
        $this->freeze = $freeze;
    }

    /**
     * @param bool $haltSequence - If true then all other attacks will not be called, except for other delayed attacks
     */
    public function addDelayedAttack(DelayedAttack $delayedAttack, bool $haltSequence = false){
        if(!$this->isAvailable()) return false;
        if($haltSequence){
            $this->setFreeze(true);
            $this->delayedAttacks[self::HALT_KEY] = $delayedAttack;
        }else{
            $this->delayedAttacks[] = $delayedAttack;
        }
        return true;
    }

    public function removeDelayedAttack(int|string $key){
        if(!array_key_exists($key, $this->delayedAttacks)) return false;
        unset($this->delayedAttacks[$key]);
        if($key == self::HALT_KEY) $this->setFreeze(false);
        return true;
    }

    public function getDelayedAttacks(){
        return $this->delayedAttacks;
    }

}