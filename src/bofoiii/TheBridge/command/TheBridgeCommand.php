<?php

namespace bofoiii\TheBridge\command;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use bofoiii\TheBridge\command\subcommands\CreateSubCommand;
use bofoiii\TheBridge\command\subcommands\HelpSubCommand;
use bofoiii\TheBridge\command\subcommands\JoinSubCommand;
use bofoiii\TheBridge\command\subcommands\QuitSubCommand;
use bofoiii\TheBridge\command\subcommands\RandomJoinSubCommand;
use bofoiii\TheBridge\command\subcommands\ReloadSubCommand;
use bofoiii\TheBridge\command\subcommands\SetGoalSubCommand;
use bofoiii\TheBridge\command\subcommands\SetHubSubCommand;
use bofoiii\TheBridge\command\subcommands\SetSpawnSubCommand;
use bofoiii\TheBridge\command\subcommands\SetWorldSubCommand;

class TheBridgeCommand extends BaseCommand{

    protected function prepare(): void
    {
        $this->registerSubCommand(new HelpSubCommand("help", "Help Command"));
        $this->registerSubCommand(new CreateSubCommand("create", "Create arena command"));
        $this->registerSubCommand(new SetSpawnSubCommand("setspawn", "Setspawn position command"));
        $this->registerSubCommand(new SetGoalSubCommand("setgoal", "Set goal position command"));
        $this->registerSubCommand(new SetWorldSubCommand("setworld", "Set world arena"));
        $this->registerSubCommand(new JoinSubCommand("join", "Join to arena"));
        $this->registerSubCommand(new RandomJoinSubCommand("random", "Random join to arena"));
        $this->registerSubCommand(new ReloadSubCommand("reload", "Reload arenas"));
        $this->registerSubCommand(new QuitSubCommand("quit", "Quit from arena"));
        $this->registerSubCommand(new SetHubSubCommand("sethub", "Set hub arena"));
        $this->setPermission($this->getPermission());
    }

    public function getPermission()
    {
        return "sthebridge.cmd";
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $this->sendUsage();
    }
}