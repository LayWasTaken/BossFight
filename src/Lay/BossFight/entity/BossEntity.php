<?php

namespace Lay\BossFight\entity;

use pocketmine\network\mcpe\protocol\BossEventPacket;
use Lay\BossFight\bossfight\BossFightInstance;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;

abstract class BossEntity extends BehavioralEntity{

    private $active = false;
    private ?BossFightInstance $instance = null;

    private bool $showBossBar = false;
    protected array $initialBossName = [];
    private float $bossHealthAnimation = 0;

    public function __construct(Location $location, ?CompoundTag $nbt = null, ?BossFightInstance $instance = null){
        parent::__construct($location, $nbt);
        $this->instance = $instance;
        $this->setMaxHealth($this->getBaseHealth());
        $this->setHealth($this->getBaseHealth());
    }

    public function getBossFightInstance(){
        return $this->instance;
    }

    public function showBossBar(bool $show = true){
        $this->showBossBar = $show;
    }

    public function canShowBossBar(){
        return $this->showBossBar;
    }

    public function start(bool $skipBossFightInstance = false): bool{
        if(!$skipBossFightInstance) 
            if(!$this->instance) return false;
        $this->active = true;
        return true;
    }

    public function sendBossBar(string $text = "", ?float $health = null){
        $world = $this->getWorld();
        if(!empty($this->initialBossName)){
            $this->invulnerable();
            if($this->ticksLived % 10) return;
            $text = array_shift($this->initialBossName);
        }
        elseif($this->bossHealthAnimation <= 1){
            if($this->ticksLived % 3) return;
            $health = $this->bossHealthAnimation += 0.1;
            if($this->bossHealthAnimation >= 1) $this->invulnerable(false);
        }
        foreach($world->getPlayers() as $player){
            $session = $player->getNetworkSession();
            if($this->showBossBar){
                $session->sendDataPacket(BossEventPacket::show($this->getId(), $text ?? $this->getName(), $this->getHealthPercentage()));
                $session->sendDataPacket(BossEventPacket::title($this->getId(), $text ?? $this->getName()), true);
                $session->sendDataPacket(BossEventPacket::healthPercent($this->getId(), $health ?? $this->getHealthPercentage()), true);
            }else $session->sendDataPacket(BossEventPacket::hide($this->getId()));
        }
    }

    private function getHealthPercentage(){
        return $this->getHealth() / $this->getMaxHealth();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool{
        if(!$this->active) return parent::entityBaseTick($tickDiff);
        $this->sendBossBar($this->getName());
		return parent::entityBaseTick($tickDiff);
    }

    protected function onDeath(): void{
        parent::onDeath();
        if($instance = $this->instance) 
            $instance->finish();
    }

    public abstract function getBaseHealth(): int;

}