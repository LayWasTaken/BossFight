<?php

namespace Lay\BossFight\entity;

use Lay\BossFight\entity\BossEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

final class EvokerBoss extends BossEntity {

    protected function initEntity(CompoundTag $nbt): void{
        $this->initialBossName =["§r§f-§0-§f-§7§kEvoker§r§f-§0-§f-", "§r§0-§f-§0-§7E§kvoker§r§0-§f-§0-",
                                "§r§f-§0-§f-§7Ev§koker§r§f-§0-§f-", "§r§0-§f-§0-§7Evo§kker§r§0-§f-§0-",
                                "§r§f-§0-§f-§7Evok§ker§r§f-§0-§f-", "§r§0-§f-§0-§7Evoke§kr§r§0-§f-§0-",
                                "§r§f-§0-§f-§7Evoker §r§f-§0-§f-"];
        parent::initEntity($nbt);
    }

    public function getBaseAttackDamage(): int{
        return 10;
    }

    public function getBaseHealth(): int{
        return 200;
    }

    public function getName(): string{
        return "§r§f-§0-§f-§7Evoker§r§f-§0-§f-";
    }

    public static function getNetworkTypeId(): string{
        return EntityIds::EVOCATION_ILLAGER;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function onAttackTick(): void{
    }

}