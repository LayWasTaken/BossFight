<?php

namespace Lay\BossFight\bossfight;

use pocketmine\player\Player;
use pocketmine\Server;

final class BossFightManager {

    const MAX_INSTANCES = 20;

    /**
     * @var BossFightInstance[]
     */
    private static array $instances = [];

    /**
     * @var BossFightInstance[] Players xuid will be used to identify their active instance
     */
    private static array $playersActiveInstance = [];

    public static function getInstances(string $class): array{
        return array_filter(self::$instances, fn($instance) => $class == $instance::class);
    }

    public static function getInstance(string $id):?BossFightInstance {
        return array_key_exists($id, self::$instances) ? self::$instances[$id] : null;
    }

    public static function instanceExists(string $id){
        return array_key_exists($id, self::$instances);
    }

    public static function addInstance(BossFightInstance $instance):bool {
        $max = $instance::getMaxInstances();
        if(count(self::$instances) == $max || array_key_exists($instance->getId(), self::$instances)){
            $instance->cancel();
            return false;
        }
        foreach ($instance->getPlayers() as $player) {
            if(self::playerHasActiveInstance($player)) {
                $instance->cancel();
                return false;
            }
        }
        foreach ($instance->getPlayers() as $player) {
            self::$playersActiveInstance[$player->getUniqueId()->toString()] = $instance;
        }
        self::$instances[$instance->getId()] = $instance;
        return true;
    }

    public static function finishInstance(string $id): bool{
        $instance = self::getInstance($id);
        if(!$instance) return false;
        $instance->finish();
        foreach ($instance->getPlayers() as $player) {
            self::removePlayerActiveInstance($player);
        }
        self::removeInstance($instance->getId());
        return true;
    }

    public static function cancelInstance(string $id): bool{
        $instance = self::getInstance($id);
        if(!$instance) return false;
        $instance->cancel();
        self::removeInstance($instance->getId());
        return true;
    }

    public static function removeInstance(string $id): bool{
        if(!array_key_exists($id, self::$instances)) return false;
        $instance = self::$instances[$id];
        foreach ($instance->getPlayers() as $player) {
            self::removePlayerActiveInstance($player);
        }
        unset(self::$instances[$id]);
        return false;
    }

    public static function playerHasActiveInstance(Player|string $player): bool{
        return array_key_exists($player instanceof Player ? $player->getUniqueId()->toString() : $player, self::$playersActiveInstance);
    }

    public static function removePlayerActiveInstance(Player|string $player): bool{
        if(!self::playerHasActiveInstance($player)) return false;
        unset(self::$playersActiveInstance[$player->getUniqueId()->toString()]);
        return true;
    }

    public static function addPlayerToInstance(Player|string $player, string $instanceID): bool{
        if(self::playerHasActiveInstance($player)) return false;
        if((!($player instanceof Player) && !($player = Server::getInstance()->getPlayerByRawUUID($player)))) return false;
        if(array_key_exists($instanceID, self::$instances)) return false;
        self::$instances[$instanceID] = $player;
        return true;
    }

    public static function query(){
        echo "Instances\n";
        print_r(array_keys(self::$instances));
        echo "\nPlayerInstances";
        print_r(array_keys(self::$playersActiveInstance));
    }

}