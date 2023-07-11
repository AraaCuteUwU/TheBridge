<?php

namespace bofoiii\TheBridge\game;

use jackmd\scorefactory\ScoreFactory;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\PopSound;
use pocketmine\world\World;
use bofoiii\TheBridge\task\GameTask;
use bofoiii\TheBridge\TheBridge;
use bofoiii\TheBridge\utils\Utils;
use pocketmine\block\utils\DyeColor;
use pocketmine\entity\object\ItemEntity;

class Game
{

    const PHASE_LOBBY = "LOBBY";
    const PHASE_COUNTDOWN = "COUNTDOWN";
    const PHASE_RUNNING = "RUNNING";
    const PHASE_RESTARTING = "RESTARTING";
    const PHASE_OFFLINE = "OFFLINE";

    /** @var bool */
    private bool $start = false;

    /** @var array */
    private array $arenainfo;

    /** @var Task|null */
    private ?Task $task = null;

    /** @var string */
    public string $phase = self::PHASE_OFFLINE;

    /** @var Player[] */
    private array $players = [];

    /** @var Vector3[] */
    public array $placedblock = [];

    /** @var string[] */
    private array $teams = [];

    /** @var int */
    private int $countdown = 15;

    /** @var bool */
    private bool $cage = false;

    /** @var int */
    private int $cagecountdown = 5;

    /** @var array */
    public array $playerinfo = [];

    /** @var int */
    private int $timer = 900; //15 minutes

    /** @var string|null */
    public ?string $scoredname = null;

    /** @var int */
    private int $restartcountdown = 10;

    /**
     * @param Vector3|null $bluespawn
     * @param Vector3|null $redspawn
     * @param Vector3|null $bluegoal
     * @param Vector3|null $redgoal
     * @param string|null $worldname
     * @param string|null $arenaname
     * @param Position|null $hub
     */
    public function __construct(?Vector3 $bluespawn = null, ?Vector3 $redspawn = null, ?Vector3 $bluegoal = null, ?Vector3 $redgoal = null, ?string $worldname = null, ?string $arenaname = null, ?Position $hub = null)
    {
        $this->arenainfo["bluespawn"] = $bluespawn;
        $this->arenainfo["redspawn"] = $redspawn;
        $this->arenainfo["bluegoal"] = $bluegoal;
        $this->arenainfo["redgoal"] = $redgoal;
        $this->arenainfo["worldname"] = $worldname;
        $this->arenainfo["arenaname"] = $arenaname;
        $this->arenainfo["hub"] = $hub;
        if ($this->isValidArena()) {
            $this->startArena();
        }
    }

    /**
     * @return bool
     */
    public function isValidArena(): bool
    {
        if (($this->arenainfo["bluespawn"] instanceof Vector3) and ($this->arenainfo["redspawn"] instanceof Vector3) and ($this->arenainfo["bluegoal"] instanceof Vector3) and ($this->arenainfo["redgoal"] instanceof Vector3) and (is_string($this->arenainfo["worldname"]) and (is_string($this->arenainfo["arenaname"]))) and ($this->arenainfo["hub"] instanceof Position)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $team
     * @param Vector3 $pos
     */
    public function setSpawnPos(string $team, Vector3 $pos)
    {
        $this->arenainfo[$team . "spawn"] = $pos;
    }

    /**
     * @param string $team
     * @param Vector3 $pos
     */
    public function setGoalPos(string $team, Vector3 $pos)
    {
        $this->arenainfo[$team . "goal"] = $pos;
    }

    /**
     * @param World $world
     */
    public function setWorld(World $world)
    {
        $this->arenainfo["worldname"] = $world->getFolderName();
    }

    private function startArena()
    {
        $this->start = true;
        $this->phase = Game::PHASE_LOBBY;
        TheBridge::getInstance()->getServer()->getPluginManager()->registerEvents(new GameListener($this), TheBridge::getInstance());
        TheBridge::getInstance()->getScheduler()->scheduleRepeatingTask($this->task = new GameTask($this), 20);
    }

    /**
     * @param bool $lobby
     * @return bool
     */
    public function isRunning(bool $lobby = true): bool
    {
        if ($lobby) {
            return $this->start && $this->phase == Game::PHASE_LOBBY;
        }
        return $this->start;
    }

    /**
     * @return array
     */
    public function getArenaInfo(): array
    {
        $arr = [];
        foreach ($this->arenainfo as $i => $k) {
            if ($k instanceof Position) {
                $arr[$i] = Utils::PositionToString($k);
            } elseif ($k instanceof Vector3) {
                $arr[$i] = Utils::vectorToString($k);
            } else {
                $arr[$i] = $k;
            }
        }
        return $arr;
    }

    /**
     * @return int
     */
    public function getCountdown(): int
    {
        return $this->countdown;
    }

    /**
     * @return void
     */
    public function decrementCountdown(): void
    {
        --$this->countdown;
    }

    /**
     * @return int
     */
    public function getCageCountdown(): int
    {
        return $this->cagecountdown;
    }

    /**
     * @return void
     */
    public function decrementCageCountdown(): void
    {
        --$this->cagecountdown;
    }

    /**
     * @return void
     */
    public function resetCageCountdown(): void
    {
        $this->cagecountdown = 5;
    }

    public function getTimer(): int
    {
        return $this->timer;
    }

    /**
     * @return void
     */
    public function decrementTimer(): void
    {
        --$this->timer;
    }

    public function getRestartCountdown(): int
    {
        return $this->restartcountdown;
    }

    /**
     * @return void
     */
    public function decrementtRestartCountdown(): void
    {
        --$this->restartcountdown;
    }

    /**
     * @return bool
     */
    public function hasCage(): bool
    {
        return $this->cage;
    }

    /**
     * @param bool $cage
     * @return void
     */
    public function setCage(bool $cage): void
    {
        $this->cage = $cage;
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getPlayerKills(Player $player): int
    {
        return $this->playerinfo[strtolower($player->getName())]["kills"];
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getPlayerGolas(Player $player): int
    {
        return $this->playerinfo[strtolower($player->getName())]["goals"];
    }

    /**
     * Save all arena data
     * @return void
     */
    public function reload(): void
    {
        $config = new Config(TheBridge::getInstance()->getDataFolder() . "arenas/" . $this->arenainfo["arenaname"] . ".json", Config::JSON, $this->getArenaInfo());
        try {
            $config->save();
        } catch (\JsonException) {
        }
        if ($this->isValidArena()) {
            $this->startArena();
        }
    }

    /** @return string */
    public function getName(): string
    {
        return $this->arenainfo["arenaname"];
    }

    /**
     * @param Player $player
     * @return void
     */
    public function addPlayer(Player $player): void
    {
        if (count($this->players) == 2) {
            return;
        }
        $this->players[strtolower($player->getName())] = $player;
        $this->playerinfo[strtolower($player->getName())] = ["kills" => 0, "goals" => 0, "damager" => null];
        $player->setGamemode(GameMode::ADVENTURE());
        $this->setTeam($player);
        $player->teleport(Position::fromObject($this->arenainfo[$this->getTeam($player) . "spawn"], Server::getInstance()->getWorldManager()->getWorldByName($this->arenainfo["worldname"])));
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setHealth(20);
        $player->getHungerManager()->setFood(20);
        $player->getInventory()->setItem(8, VanillaBlocks::BED()->setColor(DyeColor::RED())->asItem()->setCustomName("Leave"));
        $this->broadcastCustomMessage($player->getName() . " Joined");
        if (count($this->players) == 2) {
            $this->phase = Game::PHASE_COUNTDOWN;
        }
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * @param Player $player
     * @return string
     */
    public function getTeam(Player $player): string
    {
        return $this->teams[strtolower($player->getName())];
    }

    /**
     * @param string $playerName
     * @return string
     */
    public function getTeamByPlayerName(String $playerName): string
    {
        return $this->teams[$playerName];
    }

    public function getPointByTeam(String $team): int
    {
        return $this->playerinfo[array_search($team, $this->teams)]["goals"];
    }

    /**
     * @param Player $player
     * @return void
     */
    private function setTeam(Player $player): void
    {
        if (count($this->teams) > 0) {
            foreach ($this->teams as $k) {
                if ($k == "red") {
                    $this->teams[strtolower($player->getName())] = "blue";
                } else {
                    $this->teams[strtolower($player->getName())] = "red";
                }
            }
        } else {
            $this->teams[strtolower($player->getName())] = "red";
        }
    }


    /**
     * @param Player $player
     * @return bool
     */
    public function isInGame(Player $player): bool
    {
        return isset($this->players[strtolower($player->getName())]);
    }

    /**
     * @param Player $player
     * @return void
     */
    public function removePlayer(Player $player): void
    {

        // fix isu crash on restart, remove this condition if you dont need count of goal
        if ($this->phase !== self::PHASE_RESTARTING && $this->phase !== self::PHASE_RUNNING) {
            unset($this->teams[strtolower($player->getName())]);
        }

        unset($this->players[strtolower($player->getName())]);
        $this->checkCountdown();
    }


    /**
     * @return void
     */
    public function stop(): void
    {
        if ($this->task instanceof Task) {
            $handler = $this->task->getHandler();
            if($handler != null) {
                $this->task->getHandler()->cancel();
            }
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->arenainfo["worldname"]);
        //remove all placed block
        foreach ($this->placedblock as $pos) {
            $world->setBlock($pos, VanillaBlocks::AIR());
        }

        //remove drop item
        foreach ($world->getEntities() as $entity) {
            if ($entity instanceof ItemEntity) {
                $entity->close();
            }
        }

        foreach ($this->players as $player) {
            if ($player->isOnline()) {
                $player->getInventory()->clearAll();
                $player->getArmorInventory()->clearAll();
                $player->teleport($this->getHub());
            }
        }

        $this->phase = Game::PHASE_RUNNING;
        $this->placedblock = [];
        $this->teams = [];
        $this->task = null;
        $this->players = [];
        $this->playerinfo = [];
        $this->timer = 900;
        $this->countdown = 15;
        $this->restartcountdown = 10;
        $this->scoredname = null;
    }

    public function broadcastMessage(Player $player, string $message)
    {
        foreach ($this->players as $p) {
            $p->sendMessage($this->getTeamChatFormat($player) . " " . TextFormat::WHITE . $player->getName() . ": " . $message);
        }
    }

    /**
     * @param Player $player
     * @return string
     */
    private function getTeamChatFormat(Player $player): string
    {
        if ($this->teams[strtolower($player->getName())] == "blue") {
            return TextFormat::BLUE . "[BLUE]";
        }
        return TextFormat::RED . "[RED]";
    }

    /**
     * @param string $message
     * @return void
     */
    public function broadcastCustomMessage(string $message): void
    {
        foreach ($this->players as $p) {
            $p->sendMessage($message);
        }
    }

    /**
     * @return void
     */
    private function checkCountdown(): void
    {
        if (count($this->players) < 2) {
            if ($this->phase == Game::PHASE_COUNTDOWN) {
                $this->phase = Game::PHASE_LOBBY;
                $this->countdown = 15;
                return;
            }
            if ($this->phase == Game::PHASE_RUNNING) {
                foreach ($this->players as $player) {
                    $this->sendVictory($player);
                }
            }
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function sendVictory(Player $player): void
    {
        $this->phase = Game::PHASE_RESTARTING;
        $this->respawnPlayer($player);
        $player->sendTitle(TextFormat::GOLD . TextFormat::BOLD . "VICTORY!");
    }

    /**
     * @param Player $player
     * @param bool $survival
     * @return void
     */
    public function respawnPlayer(Player $player, bool $survival = false): void
    {
        $player->teleport(Position::fromObject($this->arenainfo[$this->getTeam($player) . "spawn"], Server::getInstance()->getWorldManager()->getWorldByName($this->arenainfo["worldname"])));
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        if ($survival) {
            $player->setGamemode(GameMode::SURVIVAL());
        } else {
            $player->setGamemode(GameMode::ADVENTURE());
        }
        $player->setHealth(20);
        $player->getArmorInventory()->setChestplate(VanillaItems::LEATHER_TUNIC()->setCustomColor(Utils::colorIntoObject($this->getTeam($player))));
        $player->getArmorInventory()->setLeggings(VanillaItems::LEATHER_PANTS()->setCustomColor(Utils::colorIntoObject($this->getTeam($player))));
        $player->getArmorInventory()->setBoots(VanillaItems::LEATHER_BOOTS()->setCustomColor(Utils::colorIntoObject($this->getTeam($player))));
        $player->getInventory()->setItem(0, VanillaItems::IRON_SWORD());
        $player->getInventory()->setItem(1, VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER())));
        $player->getInventory()->setItem(2, VanillaItems::DIAMOND_PICKAXE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2)));
        $player->getInventory()->addItem(VanillaBlocks::STAINED_CLAY()->setColor(Utils::teamToDyeColor($this->getTeam($player)))->asItem()->setCount(64 * 2));
        $player->getInventory()->setItem(7, VanillaItems::GOLDEN_APPLE()->setCount(8));
        $player->getInventory()->setItem(8, VanillaItems::ARROW());
        $player->getHungerManager()->setFood(20);
    }

    /**
     * @param Vector3 $pos
     * @param bool $v
     * @param int $dis
     * @param int $ad
     * @param string|null $team
     * @return void
     */
    public function sendCage(Vector3 $pos, bool $v, int $dis, int $ad, ?string $team)
    {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->arenainfo["worldname"]);
        $yy = $v ? 1 : 3;
        $yy += $ad;
        for ($x = $pos->getFloorX() - $dis; $x <= $pos->getFloorX() + $dis; $x++) {
            for ($y = $pos->getFloorY() + $yy; $y >= $pos->getFloorY() - 1; $y--) {
                for ($z = $pos->getFloorZ() + $dis; $z >= $pos->getFloorZ() - $dis; $z--) {
                    if ($v) {
                        $world->setBlockAt($x, $y, $z, VanillaBlocks::AIR());
                    } else {
                        $world->setBlockAt($x, $y, $z, VanillaBlocks::BED()->setColor(Utils::teamToDyeColor($team)));
                    }
                }
            }
        }
        if (!$v) {
            $this->sendCage($pos->add(0, 1, 0), true, $dis - 1, $ad, $team);
        }
    }

    /**
     * @return void
     */
    public function removeAllCages(): void
    {
        $this->sendCage($this->arenainfo["bluespawn"], true, 2, 4, null);
        $this->sendCage($this->arenainfo["redspawn"], true, 2, 4, null);
        foreach ($this->players as $player) {
            $player->setGamemode(GameMode::SURVIVAL());
            $player->sendTitle(TextFormat::GREEN . "FIGHT!");
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function addKill(Player $player): void
    {
        ++$this->playerinfo[strtolower($player->getName())]["kills"];
    }


    /**
     * @param Player $player
     * @return bool
     */
    public function addGoal(Player $player): bool
    {
        if ($this->playerinfo[strtolower($player->getName())]["goals"] <= 5) {
            ++$this->playerinfo[strtolower($player->getName())]["goals"];
            $this->broadcastCustomMessage(Utils::teamToColor($this->teams[strtolower($player->getName())]) . $player->getName() . TextFormat::GRAY . " scored");
            if ($this->playerinfo[strtolower($player->getName())]["goals"] >= 5) {
                $this->sendVictory($player);
                return false;
            }
        }
        return true;
    }

    /**
     * @return void
     */
    public function sendAllCages(): void
    {
        foreach ($this->players as $player) {
            $this->respawnPlayer($player);
            $this->sendCage($this->arenainfo[$this->getTeam($player) . "spawn"], false, 2, 0, $this->getTeam($player));
        }
        $this->cage = true;
    }

    /**
     * @return array
     */
    public function getPureArenaInfo(): array
    {
        return $this->arenainfo;
    }

    /**
     * @return void
     */
    public function restart(): void
    {
        $this->stop();
        $this->startArena();
    }

    /**
     * @param Player $player
     * @param EntityDamageEvent $event
     * @return void
     */
    public function handleDeath(Player $player, EntityDamageEvent $event): void
    {
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $this->playerinfo[strtolower($player->getName())]["damager"];
            if ($damager instanceof Player) {
                $this->broadcastCustomMessage(Utils::teamToColor($this->teams[strtolower($player->getName())]) . $player->getName() . TextFormat::YELLOW . " Was killed by " . Utils::teamToColor($this->teams[strtolower($damager->getName())]) . $damager->getName());
                $this->playerinfo[strtolower($player->getName())]["damager"] = null;
                ++$this->playerinfo[strtolower($damager->getName())]["kills"];
            } else {
                $this->broadcastCustomMessage(Utils::teamToColor($this->teams[strtolower($player->getName())]) . $player->getName() . TextFormat::YELLOW . " Died!");
            }
        }
        $this->respawnPlayer($player, true);
    }

    /**
     * @return Position
     */
    public function getHub(): Position
    {
        return $this->arenainfo["hub"];
    }

    /**
     * @return void
     */
    public function setHub(Position $pos): void
    {
        $this->arenainfo["hub"] = $pos;
        
    }
}
