<?php

namespace Lay\BossFight\entity\attacks\custom;

use Generator;
use Lay\BossFight\entity\attacks\DelayedAttack;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\ParticleIds;

final class FangsAttack extends DelayedAttack {

    public function __construct(Entity $source, private int $length, private int $width){
        parent::__construct($source);
    }   

    protected function attackSequence(): Generator{
        yield 1;
    }

}