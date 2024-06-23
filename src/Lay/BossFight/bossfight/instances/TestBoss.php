<?php

namespace Lay\BossFight\bossfight\instances;

use Lay\BossFight\bossfight\BossFightInstance;
use Lay\BossFight\entity\BossEntity;
use Lay\BossFight\entity\Zombie;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;

class TestBoss extends BossFightInstance {

    protected function initBoss(): BossEntity{
        return new Zombie(Location::fromObject($this->getBossSpawnPosition(), $this->getWorld()), null, $this);
    }

    public function getOriginalWorldFolderName(): string{
        return "FirstBoss";
    }

    protected function getBossSpawnVector(): Vector3{
        return new Vector3(0, 65, 0);
    }

    protected function getPlayersSpawnVector(): Vector3{
        return new Vector3(8, 65, -1);
    }

    public static function getMaxInstances(): int{
        return 10;
    }

}