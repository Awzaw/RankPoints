<?php

namespace JD\RankPoints;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
//use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
//use pocketmine\utils\TextFormat;
//use pocketmine\item\Item;
use pocketmine\utils\Config;

class Main extends PluginBase {

    /** @var \_64FF00\PurePerms\PurePerms $purePerms */
    private $purePerms;
    private $ranksConfig;

    public function onEnable() {

        $this->purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");

        if (!file_exists($this->getDataFolder() . "config.yml")) {
            @mkdir($this->getDataFolder());
            file_put_contents($this->getDataFolder() . "config.yml", $this->getResource("config.yml"));
        }

        $this->ranksConfig = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));

        $num = 0;
        foreach ($this->ranksConfig["Ranks"] as $i) {
            echo $i . " - " . $this->ranksConfig["Points"][$num] . "\n";
            $num ++;
        }
    }

    public function playerRegistered($playersname) {
        $name = trim(strtolower($playersname));
        return file_exists($this->getDataFolder() . "players/" . $name . ".yml");
    }

    public function registerPlayer($playersname) {
        $name = trim(strtolower($playersname));
        @mkdir($this->getDataFolder() . "players/");
        $data = new Config($this->getDataFolder() . "players/" . $name . ".yml", Config::YAML);
        $data->set("votes", 0);
        $data->save();
        return true;
    }

    public function getPlayerData($playersname) {
        $name = trim(strtolower($playersname));
        if ($name === "") {
            return null;
        }
        $path = $this->getDataFolder() . "players/" . $name . ".yml";
        if (!file_exists($path)) {
            return null;
        } else {
            $config = new Config($path, Config::YAML);
            return $config->getAll();
        }
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {

        if (isset($args[0])) {
            $name = $args[0];
        }
        if (isset($args[1])) {
            $pointstogive = $args[1];
        }

        if (isset($name) && strtolower($name) === "help") {
            $sender->sendMessage("Rank up by voting on these sites:");
            $sender->sendMessage("minecraftlist.org");
            $sender->sendMessage("minecraftpocket-servers.com");
            return true;
        }

        if ($sender instanceof Player) {

            if (!$this->playerRegistered($sender->getName())) {
                $this->registerPlayer($sender->getName());
            }

            if (isset($name)) {//Get rank points in game for a players name
                $config = new Config($this->getDataFolder() . "players/" . strtolower($name) . ".yml", Config::YAML);
                $data = $config->getAll();

                if (!isset($data["votes"])) {
                    $sender->sendMessage("That player never voted");
                }
                $votecount = $data["votes"];
                $sender->sendMessage($name . " has a total of " . $votecount . " Rank Points");
            } else {//Get your own rank points
                $name = trim(strtolower($sender->getName()));
                $config = new Config($this->getDataFolder() . "players/" . $name . ".yml", Config::YAML);
                $data = $config->getAll();
                $votecount = $data["votes"];
                $sender->sendMessage("You have a total of " . $votecount . " Rank Points");
            }

            return true;
        }




        if (!isset($pointstogive)) {
            if (!isset($name)) {
                $sender->sendMessage("Type rankpoints playersname to show Rank Points for a player");
                return true;
            }

            if (!$this->playerRegistered($name)) {
                $sender->sendMessage("That player has never voted");
                return true;
            }

            $config = new Config($this->getDataFolder() . "players/" . strtolower($name) . ".yml", Config::YAML);

            $data = $config->getAll();

            $votecount = $data["votes"];
            $sender->sendMessage($name . " has a total of " . $votecount . " Rank Points");

            return true;
        }

        if (!$this->playerRegistered($name)) {
            $this->registerPlayer($name);
        }

        $p = $this->getServer()->getPlayer($name);

        if (!isset($p)) {
            echo "Player is not online" . "\n";
            return true;
        }


        $data = $this->getPlayerData($name);
        if (isset($data["votes"])) {
            $oldvotes = $data["votes"];
        } else {
            $oldvotes = 0;
        }
        $newvotes = $oldvotes + $pointstogive;

        $config = new Config($this->getDataFolder() . "players/" . strtolower($name) . ".yml", Config::YAML);
        $config->set("votes", $newvotes);
        $config->save();

        $currentgroup = $this->purePerms->getUserDataMgr()->getGroup($p);
        $currentgroupName = $currentgroup->getName();
        
var_dump($this->ranksConfig["Ranks"]);

        $currentRankIndex = array_search($currentgroupName, $this->ranksConfig["Ranks"]);
echo("CurrentGroup : " . $currentgroupName . "\n");
echo("CurrentRankIndex: " . $currentRankIndex . "\n");
        if ($currentRankIndex === false)
            return true;

        echo("start loop");
        $num = 0;
        foreach ($this->ranksConfig["Ranks"] as $i) {
echo($i . "\n");
            if ($newvotes >= $this->ranksConfig["Points"][$num]) {

                $configRankIndex = array_search($i, $this->ranksConfig["Ranks"]);


                if ($currentRankIndex < $configRankIndex) {
                    $newgroup = $this->purePerms->getGroup($i);
                    $this->purePerms->getUserDataMgr()->setGroup($p, $newgroup, null);
                    $p->sendMessage("Thanks for voting - you are now " . $i . ". Keep voting to rank up!");
                }
            }
            $num ++;
        }


        return true;
    }

}

?>