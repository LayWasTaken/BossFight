<?php

namespace Lay\BossFight\tasks;

use Lay\BossFight\entity\BossEntity;
use pocketmine\scheduler\Task;

final class InitializeBossBar extends Task {

    const VARIABLE = "{TEXT}";

    private int $currentPlacement = 0;
    private array $letters = [];
    private int $count = 0;

    public function __construct(private BossEntity $boss, private string $finalText, private string $variableTexts = "-- {TEXT} --"){
        $this->letters = str_split($finalText);
        $this->count = count($this->letters);
    }

    public function onRun(): void{
        if(!$this->boss->isAlive()) {
            $this->getHandler()->cancel();
            return;
        }
        $text = $this->getNewText();
        $text = str_replace(self::VARIABLE, $this->getNewText() . "§r", $this->variableTexts);
        if(!$text) {
            $this->getHandler()->cancel();
            return;
        }
        $this->boss->sendBossBar($text);
    }

    private function getNewText():string|false {
        $text = "";
        foreach ($this->letters as $key => $letter) {
            if($this->currentPlacement == $key) 
                $text .= "§k";
            $text .= $letter;
        }
        if($this->currentPlacement++ > ($this->count - 1)) {
            $this->getHandler()?->cancel();
        }
        return $text;
    }

    public function onCancel(): void{
        $this->boss->start();
    }
}