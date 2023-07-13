<?php

namespace bofoiii\TheBridge\command\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use bofoiii\TheBridge\TheBridge;

class RandomJoinSubCommand extends BaseSubCommand{

    protected function prepare(): void
    {
        $this->setPermission("sthebridge.cmd");
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if($sender instanceof Player) {
           foreach (TheBridge::getInstance()->getGames() as $game){
               if($game->isRunning(true)){
                   $game->addPlayer($sender);
                   return;
               }
           }
        }
    }
}
