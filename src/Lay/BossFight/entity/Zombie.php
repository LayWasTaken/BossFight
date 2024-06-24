<?php

namespace Lay\BossFight\entity;

use Lay\BossFight\bossfight\BossFightInstance;
use Lay\BossFight\entity\attacks\AOEAttack;
use Lay\BossFight\entity\attacks\BasicDamageAttack;
use Lay\BossFight\entity\attacks\custom\LaserAttack;
use Lay\BossFight\entity\attacks\custom\Stomp;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Zombie extends BossEntity {

	private const AOE_ATTACK = "large_aoe";
	private const LONG_RANGE_ATTACK = "long_range";

	public function __construct(Location $location, CompoundTag $tag = null, ?BossFightInstance $instance = null){
		parent::__construct($location, $tag, $instance);
	}

    public static function getNetworkTypeId() : string{ return EntityIds::ZOMBIE; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.8, 0.6); //TODO: eye height ??
	}

	public function getName() : string{
		return "GAYLORD";
	}

	public function getBaseAttackDamage(): int{
		return 10;
	}

	public function getBaseHealth(): int{
		return 40;
	}

	protected function getTicksToAttack(): int{
		return 20;
	}

	protected function onAttackTick(): void{
		if(!$this->attackManager->isAvailable()) return;
		switch (mt_rand(1, 3)) {
			case 1:
				$this->attackManager->callAttack(new Stomp($this, 10, AOEAttack::createSquaredArea(5, -1, 2)), 2);
				echo "Big Stomp\n";
				break;
			case 2:
				$this->attackManager->callAttack(new Stomp($this, 5, AOEAttack::createSquaredArea(3, -1, 2)), 2);
				echo "Small Stomp\n";
				break;
			default:
			case 3: 
				$this->attackManager->callAttack((new BasicDamageAttack($this, 10))->setTargets($this->getWorld()->getPlayers()), 1);
				echo "Attacked \n";
			break;
		}
	}

}