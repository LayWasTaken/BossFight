<?php

namespace Lay\BossFight\entity;

use Lay\BossFight\entity\attacks\custom\FangTrap;
use Lay\BossFight\entity\BossEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

final class EvokerBoss extends BossEntity {

    public function getBaseAttackDamage(): int{
        return 10;
    }

    public function getBaseHealth(): int{
        return 200;
    }

    public function getName(): string{
        return "EvokerBoss";
    }

    public static function getNetworkTypeId(): string{
        return EntityIds::EVOCATION_ILLAGER;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function onAttackTick(): void{
        // if(!$this->attackManager->isAvailable()) return;
        // $this->attackManager->addDelayedAttack(new FangTrap($this), 30, true);
        // echo "added delayed attack \n";
    }

}