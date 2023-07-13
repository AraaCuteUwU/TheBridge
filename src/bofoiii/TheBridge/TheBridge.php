<?php

namespace bofoiii\TheBridge;

use CortexPE\Commando\PacketHooker;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use bofoiii\TheBridge\command\TheBridgeCommand;
use bofoiii\TheBridge\game\Game;
use bofoiii\TheBridge\utils\Utils;

class TheBridge extends PluginBase
{

    /** @var TheBridge */
    private static TheBridge $instance;

    /** @var Game[] */
    private array $game = [];

    /** @return TheBridge */
    public static function getInstance(): TheBridge
    {
        return self::$instance;
    }

    public function onLoad(): void
    {
        self::$instance = $this;
    }

    public function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }
        $this->saveDefaultConfig();
        @mkdir($this->getDataFolder() . "arenas/");
        $this->getServer()->getCommandMap()->register("S-TheBridge", new TheBridgeCommand($this, "sthebridge", "TheBridge Command", ["stb", "tb"]));
        foreach (glob($this->getDataFolder() . "arenas/*.json") as $location) {
            $fileContents = file_get_contents($location);
            $json = json_decode($fileContents, true);
            $worldname = explode(":", $json["hub"]);
            if ($worldname[3] !== null) {
                $this->getServer()->getWorldManager()->loadWorld($worldname[3]);
            }

            if (is_string($json["worldname"])) {
                $this->getServer()->getWorldManager()->loadWorld($json["worldname"]);
            }

            $this->game[$json["arenaname"]] = new Game(Utils::stringToVector(":", $json["bluespawn"]), Utils::stringToVector(":", $json["redspawn"]), Utils::stringToVector(":", $json["bluegoal"]), Utils::stringToVector(":", $json["redgoal"]), $json["worldname"], $json["arenaname"], Utils::stringToPosition($json["hub"]));
        }
    }

    protected function onDisable(): void
    {
        foreach ($this->getGames() as $game) {
            $game->stop();
        }
    }

    /**
     * @param string $arena
     * @return bool
     */
    public function createArena(string $arena): bool
    {
        if ($this->getGame($arena) !== null) {
            return false;
        }
        $this->game[$arena] = new Game(arenaname: $arena);
        return true;
    }

    /**
     * @param string $name
     * @return Game|null
     */
    public function getGame(string $name): ?Game
    {
        return $this->game[$name] ?? null;
    }

    /** @return Game[] */
    public function getGames(): array
    {
        return $this->game;
    }

    /**
     * @param Player $player
     * @return Game|null
     */
    public function getPlayerGame(Player $player): ?Game
    {
        foreach ($this->getGames() as $game) {
            if ($game->isInGame($player)) {
                return $game;
            }
        }
        return null;
    }
}
