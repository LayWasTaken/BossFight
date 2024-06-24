<?php

namespace Lay\BossFight\bossfight;

use Lay\BossFight\BossFight;
use Lay\BossFight\entity\BossEntity;
use Lay\BossFight\exceptions\ArenaException;
use Lay\BossFight\exceptions\BossFightException;
use Lay\BossFight\util\WorldUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use Ramsey\Uuid\Nonstandard\Uuid;

abstract class BossFightInstance {

    const HARD = 3;
    const NORMAL = 2;
    const EASY = 1;
    
    const TEMP_TAG = "temp-";

    private ?BossEntity $boss = null;

    private World $world;

    /**@var Player[] */
    private array $players = [];

    private string $id = "";

    private bool $finished = false;

    public static function create(Position $safeFinish, array $players, int $difficulty = self::EASY): ?BossFightInstance{
        $instance = new static($safeFinish, $players, $difficulty);
        if(BossFightManager::addInstance($instance)) return $instance;
        return null;
    }

    public function __construct(private Position $safeFinish, array $players, private int $difficulty = self::EASY){
        $originalWorld = $this->getOriginalWorldFolderName();
        $worlds = WorldUtils::getAllWorlds();
        if(!array_filter($worlds, fn($world) => $world == $this->getOriginalWorldFolderName())) throw new ArenaException("World must be generated");
        $this->setUniqueNameDuplicateWorld($originalWorld, $worlds);
        Server::getInstance()->getWorldManager()->loadWorld($this->id);
        $this->world = WorldUtils::getLoadedWorldByName($this->id);
        if(array_filter($players, fn($player) => !$player instanceof Player)) throw new BossFightException("Must insert players");
        $this->players = $players;
    }

    public function start(){
        if($this->finished) return false;
        if($this->boss) return false;
        if(BossFightManager::instanceExists($this->getId()))
        $this->boss = $this->initBoss();
        $this->boss->setMaxHealth($this->difficulty * $this->boss->getBaseHealth());
        $this->boss->startInitializeBossBar();
        $pos = $this->getPlayerSpawnPosition();
        foreach ($this->players as $player) {
            $player->teleport($pos);
        }
    }

    public function cancel(){
        if($this->finished) return;
        $this->clear("Boss Fight Canceled");
    }

    public function finish(){
        if($this->finished) return;
        $this->clear("Boss Fight Finished");
    }

    private function clear(string $additionalMessage = ""){
        $this->finished = true;
        foreach ($this->players as $player) {
            if($additionalMessage) $player->sendMessage($additionalMessage);
            $player->sendMessage("Boss Fight Cleared");
            $player->teleport($this->safeFinish->world->isLoaded() ? $this->safeFinish : WorldUtils::getDefaultWorldNonNull()->getSafeSpawn());
        }
        WorldUtils::removeWorld($this->id);
        BossFightManager::removeInstance($this->getId());
    }

    private function setUniqueNameDuplicateWorld(string $world, array &$worlds){
        $uuid = self::TEMP_TAG . Uuid::uuid4()->toString();
        if(!!array_filter($worlds, fn($id) => $uuid == $id)) return $this->setUniqueNameDuplicateWorld($world, $worlds);;
        WorldUtils::duplicateWorld($world, $uuid);
        $this->id = $uuid;
    }

    public function getWorld(): World{
        return $this->world;
    }

    /**
     * Deletes the world
     */
    public function clean(): void{
        WorldUtils::removeWorld($this->world->getFolderName());
    }

    public function getPlayerSpawnPosition():Position {
        if(!$this->world) return null;
        return Position::fromObject($this->getPlayersSpawnVector(), $this->world);
    }

    public function getBossSpawnPosition():Position {
        if(!$this->world) return null;
        return Position::fromObject($this->getBossSpawnVector(), $this->world);
    }

    public function getCenterPosition(){
        return new Position(0, 100, 0, $this->getWorld());
    }

    public function getId():string {
        return $this->id;
    }

    public function getPlayers(){
        return $this->players;
    }

    public function addPlayer(Player $player){
        $this->players[] = $player;
    }

    protected function onCancel(){}

    protected abstract function initBoss(): BossEntity;

    public abstract function getOriginalWorldFolderName(): string;

    protected abstract function getPlayersSpawnVector(): Vector3;

    protected abstract function getBossSpawnVector(): Vector3;

    public static abstract function getMaxInstances():int;

}