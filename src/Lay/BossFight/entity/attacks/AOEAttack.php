<?php

namespace Lay\BossFight\entity\attacks;

use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class AOEAttack extends BasicDamageAttack {

    private AxisAlignedBB $aabb;

    protected Vector3 $targetPoint;

    public static function createSquared(Entity $source, int $damage, int $fromSquare, int $minY, int $maxY){;
        return new self($source, $damage, self::createSquaredArea($fromSquare, $minY, $maxY));
    }

    public static function createSquaredArea(int $fromSquare, int $minY, int $maxY){
        return new AxisAlignedBB(-$fromSquare, $minY, -$fromSquare, $fromSquare, $maxY, $fromSquare);
    }

    public function __construct(Entity $source, int|float $damage, AxisAlignedBB $area, ?Vector3 $targetPoint = null){
        parent::__construct($source, $damage);
        $this->targetPoint = $targetPoint ?? Vector3::zero();
        $this->aabb = $area;
    }

    public function getAABBFromVector(Vector3 $vector3){
        return $this->aabb->offsetCopy($vector3->x, $vector3->y, $vector3->z);
    }

    public function setArea(AxisAlignedBB $area){
        $this->aabb = $area;
        return $this;
    }

    public function getBaseAABB(){
        return $this->aabb;
    }

    public function setTargetPoint(Vector3 $targetPoint){
        $this->targetPoint = $targetPoint;
        return $this;
    }

    public function attack():void {
        $area = $this->getAABBFromVector($this->targetPoint);
        $id = $this->source->getId();
        foreach ($this->source->getWorld()->getNearbyEntities($area) as $entity) {
            if($entity->getId() == $id) continue;
            if(!$entity instanceof Player) continue;
            $this->baseAttack($entity, $this->damage);
        }
    }
}