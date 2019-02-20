<?php

namespace Authors\PixelgamesTapToDo;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class PixelgamesTapToDo extends PluginBase implements CommandExecutor, Listener{

    public $sessions;

    /** @var  Block[] */
    public $blocks;

    /** @var  Config */
    private $blocksConfig;

    public function onLoad() {
        $this->getLogger()->info("Laden...");
    }
    
    public function onEnable(){
        $this->sessions = [];
        $this->blocks = [];
        $this->saveResource("blocks.yml");
        $this->blocksConfig = (new ConfigUpdater(new Config($this->getDataFolder() . "blocks.yml", Config::YAML, array()), $this))->checkConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->parseBlockData();
        $this->getLogger()->info("Aktiviert");
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        if($cmd->getName() == "tr"){
            if(isset($args[1])){
                if($sender->hasPermission("pgtaptodo.command." . $args[1])){

                    if (!isset($args[1])) {
                        $sender->sendMessage("§c[PGTapToDo] Benutzung: /tr <Name> <add <Befehl>|del <Befehl>|delall|name|list>");
                        return true;
                    }
                    
                    switch($args[1]){

                        case "add":
                        case "a":  

                            $i = 0;
                            $name = array_shift($args);
                            array_shift($args);

                            foreach($this->getBlocksByName($name) as $block){
                                $block->addCommand(implode(" ", $args));
                                $i++;
                            }
                            $sender->sendMessage("§a[PGTapToDo] Befehl zu $i Blöcken hinzugefügt.");
                            return true;
                            break;

                        case "del":
                        case "d":

                            $i = 0;
                            $name = array_shift($args);
                            array_shift($args);

                            foreach($this->getBlocksByName($name) as $block){
                                if(($block->deleteCommand(implode(" ", $args))) !== false){
                                    $i++;
                                }
                            }
                            $sender->sendMessage("§a[PGTapToDo] Befehl von $i Blöcken entfernt.");
                            return true;
                            break;

                        case "delall":
                        case "da":

                            $i = 0;

                            foreach($this->getBlocksByName($args[0]) as $block){
                                $this->deleteBlock($block);
                                $i++;
                            }
                            $sender->sendMessage("§a[PGTapToDo] Alle Befehle von $i Blöcken entfernt.");
                            return true;
                            break;

                        case "name":
                        case "n":
                        case "rename":

                            $i = 0;

                            foreach($this->getBlocksByName($args[0]) as $block){
                                $block->setName($block);
                                $i++;
                            }
                            $sender->sendMessage("§a[PGTapToDo] Name von $i Blöcken geändert.");
                            return true;
                            break;

                        case "list":
                        case "ls":
                        case "l":

                            $i = 0;

                            foreach($this->getBlocksByName($args[0]) as $block){
                                $pos = $block->getPosition();
                                $sender->sendMessage("§6[PGTapToDo] Befehle für Block bei X:" . $pos->getX() . " Y:" . $pos->getY() . " Z:" . $pos->getY() . " Welt:" . $pos->getLevel()->getDisplayName());

                                foreach($block->getCommands() as $cmd){
                                    $sender->sendMessage("§6[PGTapToDo] - $cmd");
                                }
                                $i++;
                            }
                            $sender->sendMessage("§6[PGTapToDo] $i Blöcke aufgelistet.");
                            return true;
                            break;
                        
                        default:
                            return false;
                            break;
                    }
                }

                else {
                    $sender->sendMessage("§c[PGTapToDo] Du hast nicht das Recht, diesen Befehl auszuführen!");
                    return true;
                }
            }

            else{
                $sender->sendMessage("§c[PGTapToDo] Benutzung: /tr <Name> <add <Befehl>|del <Befehl>|delall|name|list>");
                $sender->sendMessage("§c[PGTapToDo] Benutzung: /t <add <Befehl>|del <Befehl>|delall|name <Name>|list>");
                $sender->sendMessage("§6[PGTapToDo] Benutzung: /taptodo <info|help>");
                return true;
            }
        }

        elseif ($cmd->getName () == "t") {

            if($sender instanceof Player){
                if(isset($args[0])){
                    if($sender->hasPermission("pgtaptodo.command." . $args[0])){
                        $this->sessions[$sender->getName()] = $args;
                        $sender->sendMessage("§e[PGTapToDo] Tippe auf einen Block, um die Aktion auszuführen...");
                        return true;
                    }

                    else{
                        $sender->sendMessage("§c[PGTapToDo] Du hast nicht das Recht, diese Aktion auszuführen!");
                        return true;
                    }
                }
                
                else {
                    $sender->sendMessage("§c[PGTapToDo] Benutzung: /t <add <Befehl>|del <Befehl>|delall|name|list>");
                    $sender->sendMessage("§c[PGTapToDo] Benutzung: /tr <Name> <add <Befehl>|del <Befehl>|delall|name <Name>|list>");
                    $sender->sendMessage("§6[PGTapToDo] Benutzung: /taptodo <info|help>");
                }
            }

            else{
                $sender->sendMessage("§4[PGTapToDo] Dieser Befehl muss ingame ausgeführt werden");
                return true;
            }
        }
        
        elseif ($cmd->getName() == "taptodo") {
            if(!isset($args[0])) {
                $sender->sendMessage("§c[PGTapToDo] Benutzung: /taptodo <info|help>");
                return true;
            }
            switch ($args[0]) {
                case "info":
                    
                    $sender->sendMessage("§e---------------------------------");
                    $sender->sendMessage("§ePlugin von Falk, iStrafeNubzHDyt");
                    $sender->sendMessage("§bName: PixelgamesTapToDo");
                    $sender->sendMessage("§bOriginal: TapToDo");
                    $sender->sendMessage("§bVersion: 3.3#");
                    $sender->sendMessage("§bFür PocketMine-API: 3.0.0-ALPHA12, 3.0.0, 4.0.0");
                    $sender->sendMessage("§6Permissions: pgtaptodo, pgtaptodo.command, pgtaptodo.command.add, pgtaptodo.command.del, pgtaptodo.command.delall, pgtaptodo.command.name, pgtaptodo.command.list, pgtaptodo.taptodo, pgtaptodo.tap");
                    $sender->sendMessage("§eSpeziell für PIXELGAMES entwickelt");
                    $sender->sendMessage("§e---------------------------------");
                    return true;
                    break;
                
                case "help":
                    
                    $sender->sendMessage("§9---§aTapToDo-Plugin§9---");
                    $sender->sendMessage("§a/t add <Befehl> §b-> Fügt einem Block einen Befehl hinzu");
                    $sender->sendMessage("§a/t del <Befehl> §b-> Entfernt einen bestimmten Befehl von einem Block");
                    $sender->sendMessage("§a/t delall §b-> Entfernt alle Befehle von einem Block");
                    $sender->sendMessage("§a/t name <Name> §b-> Benennt einen Block");
                    $sender->sendMessage("§a/t list §b-> Listet alle Befehle eines Blocks auf");
                    $sender->sendMessage("§a/tr <Name> <add/a <Befehl>|del/d <Befehl>|delall/da|name/n/rename <Name>|list/ls/l> §b-> Benannte TapToDo-Blöcke können ferngesteuert eingestellt werden");
                    $sender->sendMessage("§6/taptodo info §b-> Zeigt Details über das Plugin");
                    $sender->sendMessage("§6/taptodo help §b-> Zeigt dieses Hilfemenü an");
                    return true;
                    break;

                default:
                    return false;
                    break;
            }
        }
        return true;
    }

    public function onInteract(PlayerInteractEvent $event){
        if(isset($this->sessions[$event->getPlayer()->getName()])){
            $args = $this->sessions[$event->getPlayer()->getName()];

            switch($args[0]){

                case "add":

                    if(isset($args[1])){
                        if(($b = $this->getBlock($event->getBlock(), null, null, null)) instanceof Block){
                            array_shift($args);
                            $b->addCommand(implode(" ", $args));
                            $event->getPlayer()->sendMessage("§a[PGTapToDo] Befehl hinzugefügt.");
                        }

                        else{
                            array_shift($args);
                            $this->addBlock($event->getBlock(), implode(" ", $args));
                            $event->getPlayer()->sendMessage("§a[PGTapToDo] Befehl hinzugefügt.");
                        }
                    }

                    else{
                        $event->getPlayer()->sendMessage("§c[PGTapToDo] Du musst einen Befehl angeben.");
                    }
                    break;

                case "del":
                    
                    if(isset($args[1])){
                        if(($b = $this->getBlock($event->getBlock(), null, null, null)) instanceof Block){
                            array_shift($args);

                            if(($b->deleteCommand(implode(" ", $args))) !== false){
                                $event->getPlayer()->sendMessage("§a[PGTapToDo] Befehl vom Block entfernt.");
                            }

                            else{
                                $event->getPlayer()->sendMessage("§c[PGTapToDo] Der Befehl konnte nicht gefunden werden.");
                            }

                        }
                        else{
                            $event->getPlayer()->sendMessage("§c[PGTapToDo] Hier existiert kein TapToDo-Block.");
                        }
                    }

                    else{
                        $event->getPlayer()->sendMessage("§c[PGTapToDo] Du musst einen Befehl angeben.");
                    }
                    break;

                case "delall":

                    if(($b = $this->getBlock($event->getBlock(), null, null, null)) instanceof Block){
                        $this->deleteBlock($b);
                        $event->getPlayer()->sendMessage("§a[PGTapToDo] Alle Befehle vom Block entfernt.");
                    }

                    else{
                        $event->getPlayer()->sendMessage("§c[PGTapToDo] Hier existiert kein TapToDo-Block.");
                    }
                    break;

                case "name":

                    if(isset($args[1])){
                        if(($b = $this->getBlock($event->getBlock(), null, null, null)) instanceof Block){
                            $b->setName($args[1]);
                            $event->getPlayer()->sendMessage("§a[PGTapToDo] Block benannt.");
                        }

                        else{
                            $event->getPlayer()->sendMessage("§c[PGTapToDo] Hier existiert kein TaptoDo-Block.");
                        }
                    }

                    else{
                        $event->getPlayer()->sendMessage("§c[PGTaptoDo] Du musst einen Namen angeben.");
                    }
                    break;

                case "list":

                    if(($b = $this->getBlock($event->getBlock(), null, null, null)) instanceof Block){
                        foreach($b->getCommands() as $cmd){
                            $event->getPlayer()->sendMessage($cmd);
                        }
                    }

                    else{
                        $event->getPlayer()->sendMessage("§c[PGTapToDo] Hier existiert kein TapToDo-Block.");
                    }
                    break;
            }
            unset($this->sessions[$event->getPlayer()->getName()]);
        }

        else{

            if(($b = $this->getBlock($event->getBlock(), null, null, null)) instanceof Block && $event->getPlayer()->hasPermission("pgtaptodo.tap")){
                $b->executeCommands($event->getPlayer());
            }
        }
    }

    public function onLevelLoad(LevelLoadEvent $event){
        $this->getLogger()->info("Die Blöcke werden neu geladen, da die Welt " . $event->getLevel()->getDisplayName() . " geladen wird...");
        $this->parseBlockData();
    }


    /**
     * @param $name
     * @return Block[]
     */

    public function getBlocksByName($name){
        
        $ret = [];

        foreach($this->blocks as $block){

            if ($block->getName() === $name) {
                $ret[] = $block;
            }
        }
        return $ret;
    }


    /**
     * @param $x
     * @param $y
     * @param $z
     * @param $level
     * @return Block
     */

    public function getBlock($x, $y, $z, $level){
        if ($x instanceof Position) {
            return (isset($this->blocks[$x->getX() . ":" . $x->getY() . ":" . $x->getZ() . ":" . $x->getLevel()->getDisplayName()]) ? $this->blocks[$x->getX() . ":" . $x->getY() . ":" . $x->getZ() . ":" . $x->getLevel()->getDisplayName()] : false);
            
        } else {
            return (isset($this->blocks[$x . ":" . $y . ":" . $z . ":" . $level]) ? $this->blocks[$x . ":" . $y . ":" . $z . ":" . $level] : false);
        }
    }

    /**
     *
     */

    public function parseBlockData(){

        $this->blocks = [];

        foreach($this->blocksConfig->get("blocks") as $i => $block){
            if($this->getServer()->isLevelLoaded($block["level"])){
                $pos = new Position($block["x"], $block["y"], $block["z"], $this->getServer()->getLevelByName($block["level"]));
                $key = $block["x"] . ":" . $block["y"] . ":" . $block["z"] . ":" . $block["level"];

                if (isset($block["name"])) {
                    $this->blocks[$key] = new Block($pos, $block["commands"], $this, $block["name"]);
                    
                } else {
                    $this->blocks[$key] = new Block($pos, $block["commands"], $this, $i);
                }
            }

            else{

                $this->getLogger()->warning("Blöcke in der Welt " . $block["level"] . " konnten nicht geladen werden, da die Welt noch nicht geladen wurde");
            }
        }
    }


    /**
     * @param Block $block
     */

    public function deleteBlock(Block $block){

        $blocks = $this->blocksConfig->get("blocks");

        unset($blocks[$block->id]);
        $this->blocksConfig->set("blocks", $blocks);
        $this->blocksConfig->save();
        $this->parseBlockData();
    }

    /**
     * @param Position $p
     * @param $cmd
     * @return Block
     */

    public function addBlock(Position $p, $cmd){
        $block = new Block(new Position($p->getX(), $p->getY(), $p->getZ(), $p->getLevel()), [$cmd], $this, count($this->blocksConfig->get("blocks")));
        $this->saveBlock($block);
        $this->blocksConfig->save();
        return $block;
    }


    /**
     * @param Block $block
     */

    public function saveBlock(Block $block){
        $this->blocks[$block->getPosition()->getX() . ":" . $block->getPosition()->getY() . ":" . $block->getPosition()->getZ() . ":" . $block->getPosition()->getLevel()->getDisplayName()] = $block;
        $blocks = $this->blocksConfig->get("blocks");
        $blocks[$block->id] = $block->toArray();
        $this->blocksConfig->set("blocks", $blocks);
        $this->blocksConfig->save();
    }

    /**
     *
     */

    public function onDisable(){
        $this->getLogger()->info("Speichert alle Blöcke...");
        $this->getLogger()->info("Deaktiviert");

        foreach($this->blocks as $block){
            $this->saveBlock($block);
        }
        $this->blocksConfig->save();
    }
}
