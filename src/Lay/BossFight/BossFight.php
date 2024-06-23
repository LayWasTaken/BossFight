<?php

declare(strict_types=1);

namespace Lay\BossFight;

use Lay\BossFight\bossfight\BossFightManager;
use Lay\BossFight\bossfight\instances\TestBoss;
use Lay\BossFight\entity\Zombie;
use Lay\BossFight\entity\ZombieMinion;
use Lay\BossFight\listener\WorldsListener;
use Lay\BossFight\util\VoidGenerator;
use Lay\BossFight\util\WorldUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use Ramsey\Uuid\Uuid;

class BossFight extends PluginBase{

    private static self $instance;

    public function onLoad():void {
        self::$instance = $this;
        (EntityFactory::getInstance())->register(Zombie::class, function (World $world, CompoundTag $nbt):Zombie {
            return new Zombie(EntityDataHelper::parseLocation($nbt, $world));
        }, ['zombie', 'minecraft:zombie']);
        (EntityFactory::getInstance())->register(ZombieMinion::class, function (World $world, CompoundTag $nbt):ZombieMinion {
            return new ZombieMinion(EntityDataHelper::parseLocation($nbt, $world));
        }, ['zombie_minion']);
        GeneratorManager::getInstance()->addGenerator(VoidGenerator::class, "void", fn() => null, true);
    }

    public function onEnable():void {
        $this->getServer()->getPluginManager()->registerEvents(new WorldsListener, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        if(!$sender instanceof Player) return false;
        switch ($label) {
            case 'worldg':
                if(!array_key_exists(0, $args)) {
                    $sender->sendMessage("Invalid Argument[0] must insert world name");
                    return false;
                }
                if(!!array_filter(WorldUtils::getAllWorlds(), fn($world) => $world == $args[0])) {
                    $sender->sendMessage("Invalid Argument[0] world name already exists");
                    return false;
                }
                $this->getServer()->getWorldManager()->generateWorld($args[0], WorldCreationOptions::create()->setGeneratorClass(VoidGenerator::class));
                break;
            case 'boss':
                $pos = $sender->getPosition();
                $boss = new Zombie(Location::fromObject($pos, $pos->getWorld()));
                $boss->spawnToAll();
                $boss->startInitializeBossBar();
                break;
            case 'setstone':
                $sender->getPosition()->getWorld()->setBlockAt(0, 64, 0, VanillaBlocks::STONE());
                break;
            case 'bossfight':
                if(!array_key_exists(0, $args)) {
                    $sender->sendMessage("Invalid Argument[0] must insert argument");
                    return false;
                }
                switch ($args[0]) {
                    case 'createinstance':
                        $bossInstance = TestBoss::create($sender->getPosition(), [$sender]);
                        if(!$bossInstance) {
                            $sender->sendMessage("Something went wrong");
                            return false;
                        }
                        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($bossInstance){
                            $bossInstance->start();
                        }), 120);
                        break;
                    case 'cloneworld':
                        $id = Uuid::uuid4()->toString();
                        WorldUtils::duplicateWorld('FirstBoss', $id);
                        $this->getServer()->getWorldManager()->loadWorld($id);
                        break;
                    case 'query':
                        BossFightManager::query();
                        break;
                    case 'test':
                        $position = $sender->getPosition();
                        $aabb = new AxisAlignedBB(-2, -2, -2, 2, 2, 2);
                        var_dump($aabb->offsetCopy($position->x, $position->y, $position->z));
                        break;
                    default:
                        $sender->sendMessage("Invalid Argument[0] must insert valid argument");
                        break;
                }
                break;
            default:
                break;
        }
        
        return true;
    }

    public static function getInstance():self { return self::$instance; }

}
