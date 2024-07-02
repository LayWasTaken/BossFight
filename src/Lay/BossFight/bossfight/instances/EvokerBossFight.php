<?php

namespace Lay\BossFight\bossfight\instances;

use Lay\BossFight\bossfight\BossFightInstance;
use Lay\BossFight\entity\BossEntity;
use Lay\BossFight\entity\EvokerBoss;
use Lay\BossFight\Loader;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;
use SOFe\Zleep\Zleep;

final class EvokerBossFight extends BossFightInstance {

    protected function initBoss(): BossEntity{
        return new EvokerBoss(Location::fromObject($this->getChestSpawn()->add(0.5, 0, 0.5), $this->getWorld()), null, $this);
    }

    public function getChestSpawn(): Vector3{
        return new Vector3(-16, 65, -1);
    }

    public function getSafeArea(): AxisAlignedBB{
        return (new AxisAlignedBB(-3, -3, -3, 3, 3, 3))->addCoord(-1, 65, -1);
    }

    public static function getMaxInstances(): int{
        return 20;
    }

    protected function originalWorldName(): string{
        return "EvokerDungeon";
    }

    public function getName(): string{
        return "EvokerGodFight";
    }

    public function getDrops(): array{
        return [VanillaItems::REDSTONE_DUST()->setCount(20), VanillaItems::DIAMOND()->setCount(49), VanillaItems::TOTEM()];
    }

    public function getSafeSpawn(): Vector3{
        return new Vector3(-.5, 65, -.5);
    }

    public function getTimeLimit(): int{
        return 190;
    }

    protected function onStart(): void{
        $boss = $this;
        Await::f2c(function() use ($boss){
            try {
                $world = $boss->getWorld();
                foreach ($world->getPlayers() as $player) {
                    $player->sendMessage(TextFormat::RESET . TextFormat::GREEN . "Storm, earth and fire" );
                }
                yield from Zleep::sleepTicks(Loader::getInstance(), 20 * 5);
                $center = $boss->getChestSpawn();
                $lightnings = [$center->add(2, 0, 2), $center->add(-2, 0, -2), $center->add(-2, 0, 2), $center->add(2, 0, -2)];
                foreach($lightnings as $vector) { 
                    $packet = AddActorPacket::create(Entity::nextRuntimeId(), 
                        1, "minecraft:lightning_bolt", $vector, null, 
                        0.0, 0.0, 0.0, 0.0, [], [], new PropertySyncData([], []), []
                    );
                    $world->broadcastPacketToViewers($vector, $packet);
                }
                foreach ($world->getPlayers() as $player) {
                    $player->sendMessage(TextFormat::RESET . TextFormat::GREEN . "HEED MY CALL" );
                }
            } catch (\Throwable $th) {
            }
        });
        $this->getBoss()->spawnToAll();
        $this->getBoss()->showBossBar();
    }

}