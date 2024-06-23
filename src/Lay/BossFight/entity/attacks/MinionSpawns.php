<?php

namespace Lay\BossFight\entity\attacks;

use Generator;
use Lay\BossFight\entity\attacks\DelayedAttack;
use Lay\BossFight\entity\BossEntity;
use Lay\BossFight\entity\BossMinion;
use pocketmine\entity\Location;
use pocketmine\entity\Zombie;

class MinionSpawns extends DelayedAttack{

    protected int $count = 0;

    public function __construct(protected BossEntity $bossEntity, protected bool $requiredKilled = false){}

    protected function createAttackSequence(): Generator{
        $pos = $this->bossEntity->getPosition();
        $location1 = Location::fromObject($pos->add(2, 0, 2), $pos->getWorld(), mt_rand(0, 35) * 100);
        yield 20;
        (new Zombie($location1))->spawnToAll();
        $this->count+=1;
        yield 20;
        (new Zombie($location1))->spawnToAll();
        $this->count+=1;
        yield 20;
        (new Zombie($location1))->spawnToAll();
        $this->count+=1;
        $this->bossEntity->setInvincibility(false);
        yield 20;
    }

    public function killMinion(BossMinion $minion){
        if($minion->isAlive()) return $minion->kill();
        if(--$this->count < 0) $this->count = 0;
        $this->onMinionKill($minion);
    }

    protected function onMinionKill(BossMinion $minion):void {}

}