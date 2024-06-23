<?php

namespace Lay\BossFight\entity;

use Lay\BossFight\BossFight;
use pocketmine\entity\Living;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\player\Player;
use Lay\BossFight\bossfight\BossFightInstance;
use Lay\BossFight\entity\attacks\AttackManager;
use Lay\BossFight\entity\attacks\DelayedAttack;
use Lay\BossFight\tasks\InitializeBossBar;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;

abstract class BossEntity extends Living{

    private const ATTACK_TICKS = 5;
    private const AREA_CHECK_TICKS = 3;

    private $active = false;
    private ?BossFightInstance $instance = null;
    private bool $invincible = false;

    protected AttackManager $attackManager;

    /**
     * @var DelayedAttack[][]
     */
    private array $delayedAttacks = [];

    public function __construct(Location $location, ?CompoundTag $nbt = null, ?BossFightInstance $instance = null){
        parent::__construct($location, $nbt);
        $this->attackManager = $this->attackManager ?? new AttackManager;
        $this->instance = $instance;
    }

    public function start(bool $skipBossFightInstance = false): bool{
        if(!$skipBossFightInstance) 
            if(!$this->instance) return false;

        $this->active = true;
        $this->sendBossBar();
        return true;
    }

    public function startInitializeBossBar(){
        $this->setMaxHealth(200);
        $this->setHealth(200);
        BossFight::getInstance()->getScheduler()->scheduleRepeatingTask(new InitializeBossBar($this, $this->getName()), 10);
    }

    public function sendBossBar(string $text = "", ?float $health = null){
        $world = $this->getWorld();
        foreach($world->getPlayers() as $player){
            $session = $player->getNetworkSession();
            $session->sendDataPacket(BossEventPacket::show($this->getId(), $text ?? $this->getName(), $this->getHealthPercentage()));
            $session->sendDataPacket(BossEventPacket::title($this->getId(), $text ?? $this->getName()), true);
            $session->sendDataPacket(BossEventPacket::healthPercent($this->getId(), $health ?? $this->getHealthPercentage()), true);
        }
    }

    public function isInvincible(): bool{
        return $this->invincible;
    }

    public function setInvincibility(bool $invincible){
        $this->invincible = $invincible;
    }

    private function getHealthPercentage(){
        return $this->getHealth() / $this->getMaxHealth();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool{
        if(!$this->active) return parent::entityBaseTick($tickDiff);
        $this->sendBossBar($this->getName());
        $attacksLeft = [];
        foreach ($this->delayedAttacks as $ticks => $attacks) {
            if($ticks > $this->ticksLived) break;
            unset($this->delayedAttacks[$ticks]); 
            foreach ($attacks as $attack) {
                $attack->next();
                $this->addDelayedAttack($attack, true);
            }
        }
        ksort($this->delayedAttacks);
        $this->delayedAttacks = $attacksLeft;
        $this->onAttackTick();
		return parent::entityBaseTick($tickDiff);
    }

    protected function onDeath(): void{
        parent::onDeath();
        if($instance = $this->instance) BossFight::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($instance){
            $instance->finish();
        }), 20 * 4);
    }

    /**
     * Called when a specific tick for the Boss to attack is called
     */
    protected function onAttackTick(): void { }

    protected function addDelayedAttack(DelayedAttack $delayedAttack, bool $skipSort = false){
        $current = $delayedAttack->currentTicks($this->ticksLived);
        if($current <= 0) return;
        $this->delayedAttacks[$current][] = $delayedAttack;
        if(!$skipSort) ksort($this->delayedAttacks);
    }

    protected function specialAttack(){

    }

    /**
     * On each tick the specified AABB range will check if the players changed from last check
     * @param Player[] $players
     */
    public function onNearbyPlayersUpdate(array $players){}

    public abstract function getBaseAttackDamage(): int;

    public abstract function getBaseHealth(): int;

}