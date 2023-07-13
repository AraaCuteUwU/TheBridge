<?php

namespace bofoiii\TheBridge\command\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use jackmd\scorefactory\ScoreFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use bofoiii\TheBridge\game\Game;
use bofoiii\TheBridge\TheBridge;

class QuitSubCommand extends BaseSubCommand{

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
            if(($game = TheBridge::getInstance()->getPlayerGame($sender)) instanceof Game){
                $game->removePlayer($sender);
                ScoreFactory::removeObjective($sender);
                $sender->teleport($game->getHub());
                $sender->getInventory()->clearAll();
                $sender->getArmorInventory()->clearAll();
            } else {
                $sender->sendMessage(TextFormat::RED . "You not in arena!");
            }
        }
    }
}