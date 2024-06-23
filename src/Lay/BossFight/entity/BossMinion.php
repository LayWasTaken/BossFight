<?php

namespace Lay\BossFight\entity;

use Lay\BossFight\entity\attacks\MinionSpawns;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;

abstract class BossMinion extends Living {

    public function __construct(Location $location, ?CompoundTag $tag = null, protected ?MinionSpawns $minionAttack = null){
        parent::__construct($location, $tag);
    }

    protected function onDeath(): void{
        if($this->minionAttack)
            $this->minionAttack->killMinion($this);
    }

}