<?php

namespace Lay\BossFight\entity\attacks\custom;

use Generator;
use Lay\BossFight\entity\attacks\DelayedAttack;
use Lay\BossFight\entity\BossEntity;
use pocketmine\block\VanillaBlocks;

final class FangTrap extends DelayedAttack {

    public function __construct(BossEntity $bossEntity){
        parent::__construct($bossEntity);
        $this->source = $bossEntity;
    }

    protected function attackSequence(): Generator{
        $world = $this->source->getPosition()->getWorld();
        if(!$this->source instanceof BossEntity) return;
        $origin = $this->source->getBossFightInstance()->getBossSpawnPosition()->floor();
        $numbersX = range(-10, 10);
        $numbersZ = range(-10, 10);
        shuffle($numbersX);
        shuffle($numbersZ);
        for ($i=0; $i < 5; $i++) {
            $y = $world->getHighestBlockAt($origin->x + $numbersX[$i], $origin->z + $numbersZ[$i]);
            if(!$y) yield 5; 
            $world->setBlock($origin->add($numbersX[$i], $y, $numbersZ[$i]), VanillaBlocks::DIAMOND());
            yield 20;
        }
    }

}