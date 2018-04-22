<?php

namespace Authors\PixelgamesTapToDo;

use pocketmine\utils\Config;

class ConfigUpdater{

    /** @var Config  */
    private $config;

    /** @var TapToDo  */
    private $tapToDo;

    const CONFIG_VERSION = 1;

    public function __construct(Config $config, PixelgamesTapToDo $tapToDo){
        $this->config = $config;
        $this->tapToDo = $tapToDo;
        $this->version = $this->config->get("version", 0);
    }

    public function checkConfig(){
        if($this->version > ConfigUpdater::CONFIG_VERSION){
            $this->tapToDo->getLogger()->warning("Die geladene Config wird nicht unterstützt. Sie könnte nicht richtig funktionieren!");
        }

        while($this->version < ConfigUpdater::CONFIG_VERSION){

            switch($this->version){

                case 0:
                    $this->tapToDo->getLogger()->info("Die Config wird von Version 0 auf 1 geupdatet...");
                    $blocks = $this->config->getAll();

                    foreach($blocks as $id => $block){
                        foreach($block["commands"] as $i => $command){

                            if(strpos($command, "%safe") === false && strpos($command, "%op") === false){
                                $command .= "%pow";
                            }
                            $block["commands"][$i] = str_replace("%safe", "", $command);
                        }
                        $blocks[$id] = $block;
                    }

                    unlink($this->tapToDo->getDataFolder() . "blocks.yml");
                    $this->tapToDo->saveResource("blocks.yml");
                    $this->config = new Config($this->tapToDo->getDataFolder() . "blocks.yml", Config::YAML);
                    $this->config->set("version", 1);
                    $this->config->set("blocks", $blocks);
                    $this->config->save();
                    $this->version = 1;
                    break;
            }
        }
        return $this->config;
    }
}