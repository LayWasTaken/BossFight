<?php

namespace Lay\BossFight\entity\attacks\custom;

use Lay\BossFight\entity\attacks\BasicDamageAttack;
use pocketmine\math\Vector3;
use pocketmine\world\particle\FlameParticle;

final class LaserAttack extends BasicDamageAttack {

    public function attack(): void{
        echo "attacked";
        foreach ($this->getSource()->getWorld()->getPlayers() as $player) {
            $pointA = $this->source->getPosition();
            $pointB = $player->getPosition();
            $world = $pointA->getWorld();
            $steps = $pointA->distance($pointB) / 0.2;
            $incX = ($pointA->x - $pointB->x) / $steps;
            $incY = ($pointA->y - $pointB->y) / $steps;
            $incZ = ($pointA->z - $pointB->z) / $steps;
            for ($i=0; $i < $steps; $i++) {
                $world->addParticle(new Vector3($pointA->x + $incX * $i, $pointA->y + $incY * $i, $pointA->z + $incZ * $i), new FlameParticle);
            }
            $this->baseAttack($player, $this->damage);
        }
    }

}