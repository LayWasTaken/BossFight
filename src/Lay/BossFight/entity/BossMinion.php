<?php

namespace Lay\BossFight\entity;

use Lay\BossFight\entity\attacks\MinionSpawnAttack;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;

abstract class BossMinion extends Living {

    public function __construct(Location $location, ?CompoundTag $tag = null, private ?MinionSpawnAttack $spawnAttack = null){
        parent::__construct($location, $tag);
    }

    protected function onDeath(): void{
        $this->spawnAttack?->onMinionKill();
        parent::onDeath();
    }

}