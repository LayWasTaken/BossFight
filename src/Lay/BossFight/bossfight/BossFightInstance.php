<?php

namespace Lay\BossFight\bossfight;

use Exception;
use Generator;
use Lay\BossFight\entity\BossEntity;
use Lay\BossFight\Loader;
use Lay\BossFight\util\WorldUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\SlownessEffect;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use Ramsey\Uuid\Uuid;
use SOFe\AwaitGenerator\Await;
use SOFe\Zleep\Zleep;

abstract class BossFightInstance {

    const TEMP_TAG = "temp-";
    private const DEFAULT_POST_FINISH_TIME_LIMIT = 180;

    private const NORMAL_RANGE = 180;
    private const MEDIUM_RANGE = 120;

    // BOSS FIGHT STATES

    // The initial creation of the instance, the leader creating this instance and the same time inviting others
    public const INITIAL = 0;
    // Entered is where the players has entered the arena and has doubled down to fight the boss, though the world must be generated first
    public const ENTERED = 1;
    // Fighting the players left the safe zone and entered the battle
    public const FIGHTING = 2;

    // This is where the players won the fight, received the items and such, with a 3 mins grace period to receive the items and leave
    public const SUCCESSFUL = 5;
    // If the fight failed by time limit and/or all players die then the instance will be removed after 5-10 seconds(to be more obvious to the players). 
    public const FAILED = 6;

    // This is if the battled got canceled 
    public const CANCELED = 7;

    private string $id = "";
    private ?World $world = null;

    private int $state = 0;

    private BossEntity $boss;

    /**@var PlayerSession[] $players */
    private array $players = [];

    /**
     * @throws AssumptionFailedError if leader cannot create it
     */
    public function __construct(protected array $flags, protected PlayerSession $leader){
        if(!$leader->canJoin()) throw new AssumptionFailedError("Leader cannot create this instance");
        $worlds = WorldUtils::getAllWorlds();
        if(!array_filter($worlds, fn($world) => $world == $this->originalWorldName())) throw new Exception("Failed to find original world");
        $this->generateUUID($worlds);
        $this->players[$leader->getId()] = $leader;
        $leader->joinInstance($this);
        $this->state = 0;
        BossFightManager::addInstance($this);
    }

    private function generateUUID(array &$worlds){
        $uuid = self::TEMP_TAG . Uuid::uuid4()->toString();

        // This is just a fail safe if the UUID is SOMEHOW not unique
        if(!!array_filter($worlds, fn($id) => $uuid == $id)) return $this->generateUUID($worlds);

        $this->id = $uuid;
    }

    public function initWorld(): void{
        if(isset($this->world)) return;
        if(!isset($this->id)) {
            $worlds = WorldUtils::getAllWorlds();
            $this->generateUUID($worlds);
        }
        $originalWorld = $this->originalWorldName();
        WorldUtils::lazyUnloadWorld($originalWorld);
        WorldUtils::duplicateWorld($originalWorld, $this->id);
        WorldUtils::lazyLoadWorld($this->id);
        $this->world = Server::getInstance()->getWorldManager()->getWorldByName($this->id);
    }

    public function getCurrentState(){
        return $this->state;
    }

    /**Called after a player exited the safe area */
    public function startBattle(){
        $this->boss = $this->initBoss();
        $this->boss->start();
        Await::g2c($this->startBossTimer());
        $this->state = self::FIGHTING;
        $this->onStart();
    }

    /**Will kill the boss if it still alive */
    public function finish(){
        $this->state = self::SUCCESSFUL;
        $this->getWorld()->setBlock($this->getChestSpawn(), VanillaBlocks::CHEST());
        foreach ($this->players as $session) {
            $session->setRewardItems($this->getDrops());
        }
        if(isset($this->boss)) if($this->boss->isAlive()) $this->boss->kill();
        $this->onFinish();
        Await::g2c($this->startFinishedTimer());
    }

    /**Cancels everything and kicks all the players inside, generally for just cleaning up everything*/
    public function cancel(?string $cancelMessage = null){
        foreach ($this->players as $session) {
            $cancelMessage ? $session->getPlayer()?->sendMessage($cancelMessage) : null;
            $session->getPlayer()?->sendMessage("Boss Fight Cancelled");
            $session->exitInstance();
        }
        $this->clear();
    }

    protected function clear(){
        WorldUtils::removeWorld($this->id);
        
        BossFightManager::removeInstance($this);
    }

    // Should be called on finish
    private function startFinishedTimer(): Generator{
        $time = self::DEFAULT_POST_FINISH_TIME_LIMIT;
        try {
            while ($time > 0) {
                if($this->state !== self::SUCCESSFUL) return;
                foreach ($this->getPlayerSessions() as $session) {
                    $session->getPlayer()?->sendActionBarMessage($this->timerFormat($time--, "Will Kick After "));
                }
                yield from Zleep::sleepTicks(Loader::getInstance(), 20);
            }
            $this->cancel("Time Limit Reached");
        } catch (\Throwable $th) {
            $this->cancel();
        }
    }

    private function startBossTimer(): Generator{
        $time = $this->getTimeLimit();
        try {
            while (true) {
                if($time <= 0){
                    foreach ($this->getPlayerSessions() as $session) {
                        $session->getPlayer()?->sendActionBarMessage(TextFormat::RESET . TextFormat::RED . "--THE BOSS HAS BEEN ENRAGED--");
                    }
                    continue;
                }
                if($this->state >= self::SUCCESSFUL) return;
                foreach ($this->getPlayerSessions() as $session) {
                    $session->getPlayer()?->sendActionBarMessage($this->timerFormat($time--, "Time Left "));
                }
                yield from Zleep::sleepTicks(Loader::getInstance(), 20);
            }
        } catch (\Throwable $th) {
            $this->cancel("Something went wrong");
        }
    }

    private function timerFormat(int $seconds, string $additionalText){
        $color = $seconds >= self::NORMAL_RANGE ? TextFormat::GREEN : ($seconds >= self::MEDIUM_RANGE ? TextFormat::YELLOW : TextFormat::RED);
        $minutes = (int) floor($seconds / 60.0);
		$seconds = (int) floor(fmod($seconds, 60.0));
        return $additionalText . $color . sprintf("%d:%02d", $minutes, $seconds);
    }


    /**
     * Called when all players accepted and joined the world
     * If there are no players then the instance will be removed
     */
    public function enter(){
        if(empty($this->players)) return BossFightManager::removeInstance($this);
        if(!isset($this->world)) $this->initWorld();
        foreach ($this->players as $session) {
            $session->teleportToInstance();
        }
        $this->state = self::ENTERED;
    }

    public function addPlayer(PlayerSession $session){
        if($this->state >= self::SUCCESSFUL) return;
        if(!$session->canJoin()) {
            if($session->getActiveInstance()?->getId() == $this->getId()) $this->players[$session->getId()] = $session;
            else return $session->getPlayer()?->sendMessage("You cannot join the boss fight");
        }
        $this->players[$session->getId()] = $session;
        $session->joinInstance($this);
        if($session->getId() == $this->getLeader()->getId()) return $session->getPlayer()?->sendMessage("Boss Fight Managed");
        $session->getPlayer()?->sendMessage("You joined " . $this->getLeader()->getPlayer()->getName() . "'s Boss Fight");
    }

    public function removePlayer(PlayerSession $session){
        if(!array_key_exists($session->getId(), $this->players)) return;
        if($this->world || ($this->state == self::ENTERED)) $session->exitInstance();
        else $session->leaveInstance();
        unset($this->players[$session->getId()]);
        if(count($this->players) <= 0) {
            BossFightManager::removeInstance($this);
            return;
        }
        if($session->getId() == $this->leader->getId()) {
            $this->leader = $this->players[array_rand($this->players)];
            $session->getPlayer()?->sendMessage(TextFormat::RESET . TextFormat::GREEN . "You have become the new leader");
            return;
        }
        $session->getPlayer()?->sendMessage("You left the BossFight");
    }

    public function playerOnInstance(PlayerSession $session){
        return array_key_exists($session->getId(), $this->players);
    }

    public function getPlayerSessions(){
        return $this->players;
    }

    public function getId(){ return $this->id; }

    public function getWorld(){
        return $this->world;
    }

    public function getBoss(){
        return $this->boss;
    }

    public function getLeader(){
        return $this->leader;
    }

    /**Called when BossFightInstance::startBattle() is called, Generally used for initial dialog, camera animation, Boss Entity Spawn Animation, Boss Bar initialization*/
    protected function onStart(): void { }

    /**Called when the Boss Battle has Finished */
    protected function onFinish(): void { }

    /**Called when the Boss Battle has Failed*/
    protected function onFailure(): void { }

    /**Should generally be for setting up the boss */
    protected abstract function initBoss(): BossEntity;

    protected abstract function originalWorldName(): string;

    /**The position to where the drops should spawn*/
    public abstract function getChestSpawn(): Vector3;

    /**Used to determine the area where enemies/bosses/attacks cannot be attacked, cannot go back unless respawned */
    public abstract function getSafeArea(): AxisAlignedBB;

    /**The safe are to where the players will initially spawn, should generally be inside the safeArea */
    public abstract function getSafeSpawn(): Vector3;

    /**@return int Seconds */
    public abstract function getTimeLimit(): int;

    /** */
    public abstract function getName(): string;

    /** 
     * @return Item[] - The reward when completing the fight, will be put into a chest
    */
    public abstract function getDrops(): array;
}