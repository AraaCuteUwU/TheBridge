<?php

namespace bofoiii\TheBridge\game;

use jackmd\scorefactory\ScoreFactory;
use pocketmine\block\BlockTypeIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use bofoiii\TheBridge\utils\Utils;

class GameListener implements Listener
{

    public function __construct(private Game $game)
    {
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event) : void
    {
        $player = $event->getPlayer();
        if(!$this->game->isInGame($player)) {
           return;
        }
        $this->game->broadcastCustomMessage(TextFormat::RED . $player->getName() . " disconnected!");
        $this->game->removePlayer($player);
    }

    /**
     * @param BlockPlaceEvent $event
     */
    public function onPlace(BlockPlaceEvent $event)  : void
    {
        $player = $event->getPlayer();
        if(!$this->game->isInGame($player)) {
           return;
        }
        if ($this->game->phase !== Game::PHASE_RUNNING) {
            $event->cancel();
            return;
        }

        $blocks = $event->getTransaction()->getBlocks();
        $current = $blocks->current();
        /** @var Block $currentBlock */
        $currentBlock = $current[3];

        foreach (["red", "blue"] as $team) {
            if ($currentBlock->getPosition()->distance($this->game->getPureArenaInfo()[$team . "goal"]) < 10) {
                $event->cancel();
                $player->sendMessage(TextFormat::RED . "You cant place block here!");
                return;
            }
        }
        $this->game->placedblock[Utils::vectorToString($currentBlock->getPosition()->asVector3())] = $currentBlock->getPosition()->asVector3();
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event)  : void
    {
        $player = $event->getPlayer();
        if(!$this->game->isInGame($player)) {
           return;
        }
        if (isset($this->game->placedblock[Utils::vectorToString($event->getBlock()->getPosition()->asVector3())])) {
            unset($this->game->placedblock[Utils::vectorToString($event->getBlock()->getPosition()->asVector3())]);
        } else {
            $event->cancel();
        }
    }
    /**
     * @param PlayerExhaustEvent $event
     */
    public function onExhaust(PlayerExhaustEvent $event)  : void
    {
        $player = $event->getPlayer();
        if(!$this->game->isInGame($player)) {
           return;
        }
        $event->cancel();
        $event->getPlayer()->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
    }

    /**
     * @param EntityDamageEvent $event
     */
    public function onDamage(EntityDamageEvent $event)  : void
    {
        $player = $event->getEntity();
        if ($this->game->phase !== Game::PHASE_RUNNING) {
            $event->cancel();
            return;
        }
        if ($event->getCause() == $event::CAUSE_FALL) {
            $event->cancel();
            return;
        }
        if (!$player instanceof Player) {
            return;
        }

        if(!$this->game->isInGame($player)) {
           return;
        }
        
        if ($event instanceof EntityDamageByEntityEvent) {
            if (($damager = $event->getDamager()) instanceof Player && $this->game->isInGame($damager)) {
                $this->game->playerinfo[strtolower($player->getName())]["damager"] = $damager;
            }
            if ($event->getFinalDamage() >= $player->getHealth()) {
                $this->game->handleDeath($player, $event);
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) : void
    { 
        $player = $event->getPlayer();
        if(!$this->game->isInGame($player)) {
           return;
        }
        $this->game->broadcastMessage($player, $event->getMessage());
        $event->cancel();
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event) : void
    {
        $player = $event->getPlayer();
        if(!$this->game->isInGame($player)) {
           return;
        }

        if ($this->game->phase !== Game::PHASE_RUNNING) {
            return;
        }
        if ($player->getPosition()->getY() <= 1) {
            $event->cancel();
            $this->game->respawnPlayer($player, true);
            return;
        }

        /** @var Vector3 $owngoal */
        $owngoal = $this->game->getPureArenaInfo()[$this->game->getTeam($player) . "goal"];
        /** @var Vector3 $enemygoal */
        $enemygoal = $this->game->getPureArenaInfo()[Utils::getEnemyTeam($this->game->getTeam($player)) . "goal"];
        if ($player->getLocation()->distance($owngoal) <= 3) {
            $player->sendMessage(TextFormat::RED . "You cant score to own goal!");
            $this->game->respawnPlayer($player, true);
            return;
        }

        if ($player->getLocation()->distance($enemygoal) <= 3) {
            if ($this->game->addGoal($player)) {
                $this->game->scoredname = $player->getName();
                $this->game->sendAllCages();
            }
        }
    }

    /**
     * @param PlayerItemUseEvent $event
     */
    public function onUse(PlayerItemUseEvent $event) : void
    {
        $player = $event->getPlayer();
        if(!$this->game->isInGame($player)) {
           return;
        }
        if ($event->getItem()->getTypeId() == -BlockTypeIds::BED) {
            $this->game->removePlayer($player);
            ScoreFactory::removeObjective($player);
            $player->teleport($this->game->getHub());
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
        }
    }
}
