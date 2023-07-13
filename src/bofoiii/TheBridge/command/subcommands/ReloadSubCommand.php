<?php

namespace bofoiii\TheBridge\command\subcommands;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use bofoiii\TheBridge\TheBridge;

class ReloadSubCommand extends BaseSubCommand{

    protected function prepare(): void
    {
        $this->setPermission("sthebridge.cmd.set");
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $count = 0;
        foreach (TheBridge::getInstance()->getGames() as $game){
            $game->reload();
            ++$count;
        }
        $sender->sendMessage("Successfully reload " . $count . " arenas");
    }
}
