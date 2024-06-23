<?php

namespace Lay\BossFight\entity;

use Lay\BossFight\bossfight\BossFightInstance;
use Lay\BossFight\entity\attacks\MinionSpawns;
use Lay\BossFight\entity\attacks\AOEAttack;
use Lay\BossFight\entity\attacks\BaseAttack;
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

	private int $ticks = 0;

	private Stomp $largeStomp;
	private LaserAttack $laserAttack;

	public function __construct(Location $location, CompoundTag $tag = null, ?BossFightInstance $instance = null){
		parent::__construct($location, $tag, $instance);
		$this->largeStomp = new Stomp($this, 20, Stomp::createSquaredArea(5, 2, 3));
		$this->laserAttack = new LaserAttack($this, 20);
	}

    public static function getNetworkTypeId() : string{ return EntityIds::ZOMBIE; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.8, 0.6); //TODO: eye height ??
	}

	public function getName() : string{
		return "GAYLORD";
	}

	public function getDrops() : array{
		$drops = [
			VanillaItems::ROTTEN_FLESH()->setCount(mt_rand(0, 2))
		];

		if(mt_rand(0, 199) < 5){
			switch(mt_rand(0, 2)){
				case 0:
					$drops[] = VanillaItems::IRON_INGOT();
					break;
				case 1:
					$drops[] = VanillaItems::CARROT();
					break;
				case 2:
					$drops[] = VanillaItems::POTATO();
					break;
			}
		}

		return $drops;
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
		if(++$this->ticks < 40) return;
		$this->ticks = 0;
		switch(mt_rand(0, 1)) {
			case 0:
				$this->largeStomp->attackWithCooldown(10);
			break;

			default:
			case 1:
				$this->laserAttack->attackWithCooldown(3);
			break;
		}
	}

}