<?php

namespace Lay\BossFight\entity\attacks\custom;

use Lay\BossFight\entity\attacks\AOEAttack;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\math\AxisAlignedBB;

final class Stomp extends AOEAttack {

    public function __construct(Entity $source, int|float $damage, AxisAlignedBB $area){
        parent::__construct($source, $damage, $area, $source->getPosition());
    }

    public function attack(): void{
        $area = $this->getAABBFromVector($this->targetPoint);
        $id = $this->source->getId();
        foreach ($this->source->getWorld()->getNearbyEntities($area) as $entity) {
            if($entity->getId() == $id) continue;
            if(!$entity instanceof Living) continue;
            $entity->setMotion($entity->getMotion()->add(0, 1, 0));
            $entity->jump();
            $this->baseAttack($entity, $this->damage);
        }
    }

}