<?php

namespace Lay\BossFight\entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\utils\TextFormat;

class ZombieMinion extends BossMinion {

    public function getName(): string{
        return "Zombie Minion"; 
    }

    public static function getNetworkTypeId() : string{ return EntityIds::ZOMBIE; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.8, 0.6); //TODO: eye height ??
	}

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);
        $this->setNameTag(TextFormat::GREEN . "GayLord's Minion");
    }

}