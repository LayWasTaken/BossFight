<?php

namespace Lay\BossFight\bossfight;

use Lay\BossFight\Loader;
use pocketmine\player\Player;

final class BossFightManager {

    const MAX_INSTANCES = 20;

    /**
     * @var BossFightInstance[]
     */
    private static array $instances = [];

    /**
     * @var PlayerSession[] Players UUID will be used to identify their active instance
     */
    private static array $playerSessions = [];

    public static function getInstances(string $class): array{
        return array_filter(self::$instances, fn($instance) => $class == $instance::class);
    }

    public static function getInstance(string $id):?BossFightInstance {
        return array_key_exists($id, self::$instances) ? self::$instances[$id] : null;
    }

    public static function instanceExists(string $id){
        return array_key_exists($id, self::$instances);
    }

    public static function addInstance(BossFightInstance $instance){
        if(self::instanceExists($instance->getId())) return false;
        self::$instances[$instance->getId()] = $instance;
        return true;
    }

    /**Should only be called inside the instance itself */
    public static function removeInstance(BossFightInstance|string $instance){
        $id = $instance instanceof BossFightInstance ? $instance->getId() : $instance;
        if(!self::instanceExists($id)) return false;
        $instance = self::$instances[$id];
        unset(self::$instances[$id]);
        $sessions = $instance->getPlayerSessions();
        if(count($sessions) > 0) foreach($sessions as $session){
            $instance->removePlayer($session);
        }
        return true;
    }

    public static function playerHasSession(Player|string $player): bool{
        return array_key_exists($player instanceof Player ? $player->getUniqueId()->toString() : $player, self::$playerSessions);
    }

    public static function removePlayerSession(Player|string $player): bool{
        if(!self::playerHasSession($player)) return false;
        unset(self::$playerSessions[$player instanceof Player ? $player->getUniqueId()->toString() : $player]);
        return true;
    }

    /**Returns false if it already exists */
    public static function createPlayerSession(Player $player): ?PlayerSession{
        if(self::playerHasSession($player)) return null;
        return self::$playerSessions[$player->getUniqueId()->toString()] = new PlayerSession($player);
    }

    public static function getPlayerSession(Player|string $player):? PlayerSession{
        if(!self::playerHasSession($player)) return null;
        return self::$playerSessions[$player instanceof Player ? $player->getUniqueId()->toString() : $player];
    }

    /**Clears any inactive sessions*/
    public static function cleanSessions(){
        $db = Loader::getInstance()->getDB();
        foreach (self::$playerSessions as $id => $session) {
            if($session->isOnline()) continue;
            $db->executeInsert("rewardsbuffer", ["player" => $id, "data" => $session->writeRewards()]);
            unset(self::$playerSessions[$id]);
        }
    }

    public static function saveAllSessions(){
        $db = Loader::getInstance()->getDB();
        foreach (self::$playerSessions as $id => $session) {
            $db->executeInsert("rewardsbuffer.save", ["player" => $id, "data" => $session->writeRewards()]);
        }
    }

    public static function query(){
        echo "sessions\n";
        print_r(array_keys(self::$playerSessions));
        echo "instances\n";
        print_r(array_keys(self::$instances));
    }

}