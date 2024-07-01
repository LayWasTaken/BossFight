<?php

namespace Lay\BossFight\listener;

use Lay\BossFight\Loader;
use Lay\BossFight\bossfight\BossFightManager;
use Lay\BossFight\util\BinaryStringParser;
use Lay\BossFight\util\BinaryStringParserInstance;
use pocketmine\block\Chest;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;

/**All things that requires listening on player events, why? because why not*/
final class PlayerListener implements Listener {

    private BinaryStringParserInstance $parser;

    public function __construct(string $type){
        $this->parser = BinaryStringParser::fromDatabase($type);
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer(); 
        if(BossFightManager::playerHasSession($player)) return;
        $session = BossFightManager::createPlayerSession($player);
        if(!$session) return;
        Loader::getInstance()->getDB()->executeSelect("rewardsbuffer.get", ["player" => $player->getUniqueId()->toString()], function(array $rows) use ($session): void {
            if(isset($rows[0])) $session->readRewards($this->parser->decode($rows[0]["data"]));
        });
    }

    public function onQuit(PlayerQuitEvent $event){
    }

    public function onPlayerMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if(!$session = BossFightManager::getPlayerSession($player)) return;
        if(!$instance = $session->getActiveInstance()) return;
        if($instance->isActive()) return;
        if(!$instance->getWorld()) return;
        if($instance->getId () != $player->getWorld()->getFolderName()) return;
        if($instance->getSafeArea()->intersectsWith($player->boundingBox)) return;
        $instance->startBattle();
    }

    public function playerInteract(PlayerInteractEvent $event){
        if($event->getAction() == PlayerInteractEvent::LEFT_CLICK_BLOCK) return $event->cancel();
        $block = $event->getBlock();
        if(!$block instanceof Chest) return;
        $pos = $block->getPosition();
        $instance = BossFightManager::getInstance($pos->getWorld()->getFolderName());
        if(!$instance) return;
        if(!$instance->getChestSpawn()->equals($pos)) return;
        BossFightManager::getPlayerSession($event->getPlayer())?->openBuffer();
        $event->cancel();
    }

}