<?php

namespace Lay\BossFight\entity\attacks;

use pocketmine\entity\Entity;
use pocketmine\utils\AssumptionFailedError;

abstract class DelayedAttack extends BaseAttack {

    private \Generator $sequence;

    private int $ticks = 0;

    public function __construct(Entity $source){
        parent::__construct($source);
        $this->sequence = $this->getAttackSequence();
    }

    protected abstract function getAttackSequence(): \Generator;

    public function isFinished(){
        return !$this->sequence->valid();
    }
    
    public function attack(): void{
        if($this->isFinished()) return;
        if(--$this->ticks > 0) return;
        $this->sequence->next();
        $current = $this->sequence->current();
        if(!is_null($current)) {
            if(!is_int($current)) throw new AssumptionFailedError("The yield must be an integer");
            $this->sequence = $current;
        }
    }
}