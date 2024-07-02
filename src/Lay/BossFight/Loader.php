<?php

declare(strict_types=1);

namespace Lay\BossFight;

use Lay\BossFight\bossfight\BossFightInstance;
use Lay\BossFight\bossfight\BossFightManager;
use Lay\BossFight\entity\EvokerBoss;
use Lay\BossFight\bossfight\instances\EvokerBossFight;
use Lay\BossFight\entity\Zombie;
use Lay\BossFight\entity\ZombieMinion;
use Lay\BossFight\listener\EventListener;
use Lay\BossFight\listener\PlayerListener;
use Lay\BossFight\util\VoidGenerator;
use Lay\BossFight\util\WorldUtils;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class Loader extends PluginBase{

    use SingletonTrait;

    public static BigEndianNbtSerializer $nbtSerializer;

    private DataConnector $db;

    public function onLoad():void {
        self::setInstance($this);
        self::$nbtSerializer = new BigEndianNbtSerializer;
        (EntityFactory::getInstance())->register(EvokerBoss::class, function (World $world, CompoundTag $nbt):EvokerBoss {
            return new EvokerBoss(EntityDataHelper::parseLocation($nbt, $world));
        }, ['evoker_boss']);
        GeneratorManager::getInstance()->addGenerator(VoidGenerator::class, "void", fn() => null, true);
    }

    public function onEnable():void {
        if(!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
        $this->initDB();
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener($this->getConfig()->get("database")["type"]), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        foreach (WorldUtils::getAllWorlds() as $worldName) {
            if(str_contains($worldName, BossFightInstance::TEMP_TAG)) WorldUtils::removeWorld($worldName);
        }
    }

    public function getDB(){
        return $this->db;
    }

    public function onDisable(): void {
        BossFightManager::saveAllSessions();
        $this->db->close();
    }

    private function initDB(){
        $this->saveDefaultConfig();
        $this->db = libasynql::create($this, $this->getConfig()->get("database"), [
            "sqlite" => "sqlite.sql",
        ]);
        $this->db->executeGeneric("rewardsbuffer.init");
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
                $this->getServer()->getWorldManager()->generateWorld($args[0], WorldCreationOptions::create()->setGeneratorClass(VoidGenerator::class)->setSpawnPosition(new Vector3(0, 65, 0)));
                $world = $this->getServer()->getWorldManager()->getWorldByName($args[0]);
                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($sender, $world){
                    $world->loadChunk(0, 0);
                    $world->setBlockAt(0, 64, 0, VanillaBlocks::STONE());
                    $sender->teleport(new Position(0, 65, 0, $world));
                    $sender->sendMessage("World ".$world->getFolderName()." has been generated.");
                }), 20);
                break;
            case 'setstone':
                $sender->getPosition()->getWorld()->setBlockAt(0, 64, 0, VanillaBlocks::STONE());
                break;
            case 'bossfight':
                if(!array_key_exists(0, $args)) {
                    $sender->sendMessage("Invalid Argument[0] must insert argument");
                    return false;
                }
                $session = BossFightManager::getPlayerSession($sender);
                if(!$session) {
                    $sender->sendMessage("Your Session doesnt exists");
                    return true;
                }
                switch ($args[0]) {
                    case 'createinstance':
                        if(!$session->canJoin()) {
                            $sender->sendMessage("You cannot join/create another boss fight, either you are in cooldown or within an active boss fight");
                            return true;
                        }
                        $bossInstance = new EvokerBossFight([], $session);
                        if(!$bossInstance) {
                            $sender->sendMessage("Something went wrong");
                            return true;
                        }
                        $bossInstance->initWorld();
                        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($bossInstance){
                            $bossInstance->enter();
                        }), 20);
                        break;
                    case 'query':
                        BossFightManager::query();
                        break;
                    case 'test':
                        $position = $sender->getPosition();
                        $aabb = new AxisAlignedBB(-2, -2, -2, 2, 2, 2);
                        var_dump($aabb->offsetCopy($position->x, $position->y, $position->z));
                        break;
                    case 'openbuffer':
                        BossFightManager::getPlayerSession($sender)->openBuffer();
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

}
