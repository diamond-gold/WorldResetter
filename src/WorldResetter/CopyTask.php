<?php

namespace WorldResetter;

use pocketmine\scheduler\AsyncTask;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

class CopyTask extends AsyncTask{
	
	private $src;
	private $dst;
	private $owner;
	
	public function __construct(Plugin $owner, $src, $dst, $worldName){
		$this->owner = $owner;
		$this->src = $src;
		$this->dst = $dst;
		$this->worldName = $worldName;
	}
	
	public function onRun(){
		$this->recurse_copy($this->src,$this->dst);
	}
	
	public function recurse_copy($src,$dst){ 
		/*
		
		Credits to gimmicklessgpt at gmail dot com
		http://php.net/manual/en/function.copy.php
		
		*/
		$dir = opendir($src); 
		@mkdir($dst); 
		while(false !== ( $file = readdir($dir)) ) { 
			if (( $file != '.' ) && ( $file != '..' )) { 
				if ( is_dir($src . '/' . $file) ) { 
					$this->recurse_copy($src . '/' . $file,$dst . '/' . $file); 
				} 
				else { 
					copy($src . '/' . $file,$dst . '/' . $file); 
				} 
			} 
		} 
		closedir($dir);
	}
	
	public function onCompletion(Server $server){
		if($this->owner->config["Load-Level-After-Restore"] === true && $this->worldName !== ""){
			$server->loadLevel($this->worldName);
		}
	}

}
