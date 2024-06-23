<?php

namespace Lay\BossFight\entity\attacks;

abstract class DelayedAttack extends BaseAttack {
    
    private ?\Generator $attackSequence = null;
    
    /**
     * Yield
     * int - To skip that many ticks
     * null or finished - To cancel/finish the attack
     */
    abstract protected function createAttackSequence(): \Generator;

    public function next(){
        if(!$this->attackSequence) return null;
        if($this->attackSequence->valid()) return null;
        $this->next();
        return $this->attackSequence->current();
    }

    public function currentTicks(int $basis = 0){
        return $basis + (int) $this->attackSequence->current();
    }

    public function getAttackSequence(){
        return $this->attackSequence;
    }

    public function attack(): void{
        $this->attackSequence = $this->getAttackSequence();
    }

}