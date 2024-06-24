<?php

namespace Lay\BossFight\entity\attacks;

use pocketmine\entity\Entity;

/**
 * Mainly used for inheriting
 */
class BasicDamageAttack extends BaseAttack{

    /**@var Entity[] */
    protected array $targets = [];

    public function __construct(Entity $source, protected float $damage){
        parent::__construct($source);
    }

    public function getSource(){
        return $this->source;
    }

    public function getDamage(){
        return $this->damage;
    }

    public function setDamage(float $damage){
        $this->damage = $damage;
        return $this;
    }
    
    public function setTargets(array $targets){
        $this->targets = $targets;
        return $this;
    }

    public function attack(): void{
        foreach ($this->targets as $target) {
            $this->baseAttack($target, $this->damage);
        }
    }

}