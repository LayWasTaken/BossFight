<?php

namespace Lay\BossFight\bossfight;

use Lay\BossFight\Loader;
use Lay\BossFight\util\WorldUtils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\block\Air;
use pocketmine\entity\Location;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NBT;
use pocketmine\world\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\UuidInterface;

final class PlayerSession {

    public const NEW = 0;
    public const RESET = 1;

    private const TIME_INCREASE = 30;
    private const TAG_INVENTORY =  "inventory";

    private ?BossFightInstance $activeInstance = null;
    private int $nextBattleTime = 0;

    /**An option for the player to teleport to the last position or to the hub, unless they want to create another position */
    private Location $lastLocation;

    /** 
     * Used to create the inventory used for receiving the items
     */
    private InvMenu $rewardInventory;

    private string $id;
    private string $name;

    public function __construct(private Player $player){
        $this->rewardInventory = InvMenu::create(InvMenu::TYPE_CHEST)
            ->setListener(function(InvMenuTransaction $transaction):InvMenuTransactionResult {
                $action = $transaction->getAction();
                if($action->getTargetItem()->getName() != "Air") return $transaction->discard();
                return $transaction->continue();
            })
            ->setName(TextFormat::RESET . TextFormat::GREEN . "Boss Battle Rewards");
        $this->id = $player->getUniqueId()->toString();
        $this->name = $player->getName();
    }

    /**
     * Returns null if the player is offline
     */
    public function getPlayer():?Player { 
        if($this->player->isOnline()) return $this->player;
        if($player = Server::getInstance()->getPlayerByRawUUID($this->player->getUniqueId())) return $this->player = $player;
        return null;
    }

    public function battleTimestamp(int $config = self::NEW){
        if($config == 0){
            $this->nextBattleTime = time() + self::TIME_INCREASE;
        }else{
            $this->nextBattleTime = 0;
        }
    }

    public function canJoin(){
        return $this->isOnline() ? ($this->activeInstance ? false : $this->nextBattleTime <= time()) : false;
    }

    public function joinInstance(BossFightInstance $instance): bool{
        if(!$this->canJoin()) return false;
        $this->activeInstance = $instance;
        $instance->addPlayer($this);
        return true;
    }

    public function leaveInstance(){
        $this->activeInstance = null;
    }

    public function getActiveInstance(){
        return $this->activeInstance;
    }

    /**
     * The Player will receive all the items within the reward buffer 
     * @return Item[] The rests of the items that hasnt been given
     */
    public function claimBuffer(){
        $itemsLeft = [];
        $player = $this->getPlayer();
        if(!$player) return $this->rewardInventory;
        $inventory = $this->player->getInventory();
        foreach ($this->rewardInventory as $item) {
            if($inventory->canAddItem($item)) {
                $inventory->addItem($item);
                continue;
            }
            $itemsLeft[] = $item;
        }
        $this->rewardInventory = $itemsLeft;
        return $this->rewardInventory;
    }

    public function exitInstance(?Position $position = null){
        $player = $this->getPlayer();
        if(!$player) return;
        $player->teleport(($position ?? $this->lastLocation) ?? WorldUtils::getDefaultWorldNonNull()->getSafeSpawn());
        $this->battleTimestamp(self::NEW);
        $this->leaveInstance();
    }

    public function teleportToInstance(){
        if(!$this->activeInstance) return;
        if(!$world = $this->activeInstance->getWorld()) return;
        if(!$player = $this->getPlayer()) return;
        $this->lastLocation = $player->getLocation();
        $player->teleport(Position::fromObject($this->activeInstance->getSafeSpawn(), $world));
    }

    public function setRewardItems(array $items){
        $this->rewardInventory->getInventory()->setContents($items);
    }

    public function hasItems(): bool{
        return !!$this->rewardInventory->getInventory()->getContents();
    }

    public function clearBuffer(){
        $this->rewardInventory->getInventory()->setContents([]);
    }

    public function openBuffer(){
        if($player = $this->getPlayer()) $this->rewardInventory->send($player);
    }

    public function getId(){
        return $this->id;
    }

    public function getName(){
        return $this->name;
    }

    public function isOnline(){
        return !!$this->getPlayer();;
    }

    public function readRewards(string $data){
        $contents = [];
		$inventoryTag = Loader::$nbtSerializer->read(zlib_decode($data))->mustGetCompoundTag()->getListTag(self::TAG_INVENTORY);
		/** @var CompoundTag $tag */
		foreach($inventoryTag as $tag){
			$contents[$tag->getByte("Slot")] = Item::nbtDeserialize($tag);
		}

		$inventory = $this->rewardInventory->getInventory();
		$inventory->setContents($contents);
    }

    public function writeRewards(): string{
        $contents = [];
		foreach($this->rewardInventory->getInventory()->getContents() as $slot => $item){
			$contents[] = $item->nbtSerialize($slot);
		}

		return zlib_encode(Loader::$nbtSerializer->write(new TreeRoot(CompoundTag::create()
			->setTag(self::TAG_INVENTORY, new ListTag($contents, NBT::TAG_Compound))
		)), ZLIB_ENCODING_GZIP);
    }

}