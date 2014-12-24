<?php
namespace powermute;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\protocol\MessagePacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class PowerMute extends PluginBase implements CommandExecutor, Listener{
    /** @var  \SplObjectStorage*/
    private $players;
    public function onEnable(){
        $this->players = new \SplObjectStorage();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if(isset($args[0])){
            $player = $this->getServer()->getPlayer($args[0]);
            if(!($player instanceof Player)){
                $sender->sendMessage($args[0] . " is not a valid player.");
                return true;
            }
        }
        else{
            return false;
        }

        if(isset($args[1])){
            switch(strtolower($args[1])){
                case 'mute':
                case 'block':
                case 'on':
                    $sender->sendMessage($player->getName() . " is muted.");
                    $this->players->attach($player);
                    return true;
                    break;
                case 'unmute':
                case 'unblock':
                case 'off':
                    $sender->sendMessage($player->getName() . " can now talk.");
                    unset($this->players[$player]);
                    return true;
                    break;
            }
        }

        if(isset($this->players[$player])){
            $sender->sendMessage($player->getName() . " can now talk.");
            unset($this->players[$player]);
            return true;
        }
        else{
            $sender->sendMessage($player->getName() . " is muted.");
            $this->players->attach($player);
            return true;
        }

    }
    public function onPacketSend(DataPacketSendEvent $event){
        $packet = $event->getPacket();
        if($packet instanceof MessagePacket && count($this->players) > 0){
            preg_match_all("`<(.*)>`", $packet->message, $matches);
            if(isset($matches[1][0])){
                $match = $matches[1][0];
            }
            elseif($packet->message{0} === "*"){
                $match = explode(" ", $packet->message)[1];
            }
            else{
                return;
            }
            $this->getLogger()->info($match);
            $player = $this->getServer()->getPlayer($match);
            if($player instanceof Player) {
                if (isset($this->players[$player]) && $event->getPlayer()->getName() !== $match) {
                    $event->setCancelled();
                }
            }
        }
    }
}