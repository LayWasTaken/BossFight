<?php

namespace Lay\BossFight\entity\attacks;

/**Optional if you want to just spawn entities, but if it is required to kill then this is the class for it */
abstract class MinionSpawnAttack extends DelayedAttack {
    private array $minions = [];

    public function onMinionKill(){
        
    }

    protected function spawnMinion(){

    }
}