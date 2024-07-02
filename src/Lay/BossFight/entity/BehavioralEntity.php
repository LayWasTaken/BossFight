<?php

namespace Lay\BossFight\entity;

use pocketmine\entity\Living;
use pocketmine\utils\AssumptionFailedError;

abstract class BehavioralEntity extends Living {

    private const DEFAULT_BASE_DAMAGE = 5;

    protected int $baseDamage = self::DEFAULT_BASE_DAMAGE;
    protected bool $invulnerable = false;
    private bool $movable = true;
    private int $attackCooldown = 0;

    private ?ProceduralAttack $proceduralAttack = null;

    /**@var \Generator[] $backgroundAttacks Attacks that is happening without being disturbed*/
    private array $backgroundAttacks = [];

    public function isInvulnerable(){
        return $this->invulnerable;
    }

    public function invulnerable(bool $isInvulnerable = true){
        $this->invulnerable = $isInvulnerable;
        return $this;
    }

    public function getBaseAttackDamage(){
        return $this->baseDamage;
    }

    public function isMovable(){
        return $this->movable;
    }

    public function movable(bool $isMovable = true){
        $this->movable = $isMovable;
        return $this;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool{
        if($this->isMovable()) $this->onMovementAvailable();
        foreach ($this->backgroundAttacks as $ticksLeft => $attacks) {
            if($ticksLeft > 0){
                $this->backgroundAttacks[--$ticksLeft] = $attacks;
                continue;
            }
            foreach ($attacks as $attack) {
                $current = $ticksLeft;
                $attack->next();
                if(!$attack->valid()) continue;
                $current = $attack->current();
                if(!is_int($current)) throw new AssumptionFailedError("Invalid yield, must be an int");
                $this->backgroundAttacks[$current][] = $attack;
            }
        }
        if(!$this->proceduralAttack?->next()) {
            if($this->attackCooldown <= 0) $this->onAttackAvailable();
            else $this->attackCooldown--;
            $this->proceduralAttack = null;
        }
        return parent::entityBaseTick($tickDiff);
    }

    /**
     * @param \Generator $attack yield the amount of ticks to sleep the attack, if the generator reaches invalid then the attack is removed
     * @param int $attackCooldown Optional, if wanted to increase the attack coooldown
     */
    public function addBackgroundAttack(\Generator $attack, int $attackCooldown = 0){
        $current = $attack->current();
        if(!is_int($current)) throw new AssumptionFailedError("Invalid initial yield, must be an int");
        $this->backgroundAttacks[$current] = $attack;
        $this->increaseAttackCooldown($attackCooldown);
        return $this;
    }

    public function increaseAttackCooldown(int $amountTicks){
        $this->attackCooldown += ($amountTicks < 0 ? 0 : $amountTicks);
        return $this;
    }

    // Called every tick when the attack cooldown is finished, use this to call a new attack
    protected function onAttackAvailable(): void { }

    // Called when the movement is available every tick
    protected function onMovementAvailable(): void { }

    /**
     * An attack that can have multiple delays but the attack cooldown will not be finished until the attack is finished
     * Useful for long and heavy/extensive attacks
     */
    protected function setProceduralAttack(\Generator $attack){
        if($this->proceduralAttack) return false;
        $this->proceduralAttack = $attack;
    }

}

final class ProceduralAttack {
    public int $ticks = 0;
    public \Generator $attack;

    public function __construct(){
        $this->ticks = $this->attack->current();
    }

    public function next(){
        if(!$this->attack->valid()) return false;
        if(--$this->ticks <= 0){
            $this->attack->next();
            if(!$this->attack->valid()) return false;
            $this->ticks = $this->attack->current();
        }
        return true;
    }
}