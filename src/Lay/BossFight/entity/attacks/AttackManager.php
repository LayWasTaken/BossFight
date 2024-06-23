<?php

namespace Lay\BossFight\entity\attacks;

final class AttackManager {

    /** @var BaseAttack[] $attacks All the attacks*/
    private array $attacks = [];

    /** @var BaseAttack[] $attackPool How frequent these attacks are */
    private array $attackPool = [];

    /**
     * @var int[]
     */
    private array $attacksOnCooldown = [];

    public static function create(){ return new self; }

    public function __construct(){}

    public function setAttack(string $id, BaseAttack $attack){
        $this->attacks[$id] = $attack;
        return $this;
    }

    /**
     * @param string $id
     * @param bool $skipCooldown If tue it will check if the attack is available
     * @return ?BaseAttack
     */
    public function getAttack(string $id, bool $skipCooldown = true){
        if(!$this->attackExists($id)) return null;
        if($skipCooldown) return $this->attacks[$id];
        $this->updateCooldown();
        return array_key_exists($id, $this->attacksOnCooldown) ? null : $this->attacks[$id];
    }

    /**
     * Returns a random available attack that is not on Cooldown
     */
    public function getAvailableAttack(){
        $this->updateCooldown();
        return array_rand(array_diff_key($this->attacks, $this->attacksOnCooldown));
    }

    public function isAttackOnCooldown(string $id){
        $this->updateCooldown();
        return array_key_exists($id, $this->attacksOnCooldown);
    }

    public function updateCooldown(){
        $time = time();
        ksort($this->attacksOnCooldown);
        foreach ($this->attacksOnCooldown as $id) {
            $cooldown = $this->attacksOnCooldown[$id];
            if($cooldown > $time) return;
            unset($this->attacksOnCooldown[$id]);
        }
    }

    public function attackExists(string $id){
        return array_key_exists($id, $this->attacks);
    }

    /**
     * If the attack is already in cooldown it will override it
     */
    public function setCooldown(string $id, int $cooldown = 1){
        if(!$this->attackExists($id)) return false;
        return $this->attacksOnCooldown[$id] = time() + $cooldown;
    }

}