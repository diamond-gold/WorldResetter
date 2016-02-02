<?php

namespace WorldResetter;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\utils\Config;

class Main extends PluginBase{

    public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config = (new Config($this->getDataFolder() . "config.yml", Config::YAML,["Run-From-Console-Only" => false,"Load-Level-After-Restore" => true]))->getAll();
    }
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($sender instanceof Player && $this->config["Run-From-Console-Only"] === true){
			$sender->sendMessage(TextFormat::RED."This command can only be run from the console");
			return true;
		}elseif(!$sender->hasPermission("wr.command")){
			$sender->sendMessage(TextFormat::RED."You do not have permission to run this command");
			return true;
		}
		if(count($args) === 2){
			$world = $this->getServer()->getLevelByName($args[1]);
			if(!is_file($this->getServer()->getDataPath()."worlds/$args[1]/level.dat")){
				$sender->sendMessage(TextFormat::YELLOW."World does not exist");
			}else{
				if(strtolower($args[0]) === "restore"){
					if(!$this->backupExists($args[1])){
						$sender->sendMessage(TextFormat::YELLOW."No backup for that world can be found");
						return true;
					}
					if(strtolower($args[1]) === strtolower($this->getServer()->getDefaultLevel()->getName())){
						$sender->sendMessage(TextFormat::YELLOW."The default level could not be unloaded, thus could not be restored");
						return true;
					}
					if($this->reset($world,$args[1])){
						$sender->sendMessage(TextFormat::GREEN."Restore Complete".($this->config["Load-Level-After-Restore"] === true ? " and level loaded" : ""));
					}else{
						$sender->sendMessage(TextFormat::RED."Oops! Something went wrong while restoring world, please check console messages");
					}
				}elseif(strtolower($args[0]) === "backup"){
					if($world) $world->save();
					if($this->backup($args[1])){
						$sender->sendMessage(TextFormat::GREEN."Backup Complete");
					}else{
						$sender->sendMessage(TextFormat::RED."Oops! Something went wrong while backing up world, please check console messages");
					}
				}else return false;
			}
			return true;
		}else return false;
	}
	
	public function backupExists($wname){
		return is_file($this->getServer()->getDataPath()."worlds/$wname - Backup/level.dat");
	}
	
	public function reset($world,$wname){
		if($world instanceof Level){
			$wname = $world->getName();
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			foreach($world->getPlayers() as $p){
				$p->teleport($spawn);
				$p->sendMessage(TextFormat::GOLD."- Restoring current world, teleporting you to spawn");
			}
			$this->getServer()->unloadLevel($world);
		}
		$path = $this->getServer()->getDataPath();
		$this->recurse_copy($path."worlds/$wname - Backup",$path."worlds/$wname",$wname);
		return true;
	}
	
	public function backup($worldname){
		$path = $this->getServer()->getDataPath();
		$this->recurse_copy($path."worlds/$worldname",$path."worlds/$worldname - Backup");
		return true;
	}
	
	public function recurse_copy($src,$dst,$worldName = "") { 
		$this->getServer()->getScheduler()->scheduleAsyncTask(new CopyTask($this,$src,$dst,$worldName));
    }
}
?>
