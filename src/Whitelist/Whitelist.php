<?php
namespace Whitelist;

use pocketmine\command\{Command, CommandSender};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Whitelist extends PluginBase {

    //public $pre = "§l§e[ §f시스템 §e]§r§e";
    public $pre = "§e•";

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["whitelist" => false, "player" => []]);
        $this->data = $this->config->getAll();
        $this->login = new Config($this->getDataFolder() . "login.yml", Config::YAML);
        $this->ldata = $this->login->getAll();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function onDisable() {
        $this->save();
    }

    public function save() {
        $this->config->setAll($this->data);
        $this->config->save();
        $this->login->setAll($this->ldata);
        $this->login->save();
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, $args): bool {
        if ($cmd->getName() == "white") {
            if (!$sender->isOp()) {
                $sender->sendMessage("{$this->pre} 권한이 없습니다.");
                return false;
            } elseif (!isset($args[0])) {
                $sender->sendMessage("--- 화이트리스트 도움말 1 / 1 ---");
                $sender->sendMessage("{$this->pre} /whitelist on | 화이트리스트를 켭니다.");
                $sender->sendMessage("{$this->pre} /whitelist off | 화이트리스트를 끕니다.");
                $sender->sendMessage("{$this->pre} /whitelist add <닉네임> | 플레이어를 화이트리스트에 추가합니다.");
                $sender->sendMessage("{$this->pre} /whitelist remove <닉네임> | 플레이어를 화이트리스트에서 제거합니다.");
                $sender->sendMessage("{$this->pre} /whitelist list | 화이트리스트 목록을 불러옵니다.");
                return false;
            } else {
                switch ($args[0]) {
                    case "on":
                        $this->data["whitelist"] = true;
                        $sender->sendMessage("{$this->pre} 화이트리스트가 켜졌습니다.");
                        foreach ($this->getServer()->getOnlinePlayers() as $player) {
                            if (!$player->isOp() && !in_array(strtolower($player->getName()), $this->data["player"])) {
                                $player->save();
                                $player->close(false, "서버가 §c점검중§f입니다.\n자세한 내용은 §a커뮤니티§f를 확인해주세요.");
                            } elseif ($player->isOp() && $player->getName() !== $sender->getName())
                                $player->sendMessage("{$this->pre} {$sender->getName()}님이 화이트리스트를 켰습니다.");
                        }
                        if ($sender instanceof Player)
                            $this->getServer()->getLogger()->notice("{$this->pre} {$sender->getName()}님이 화이트리스트를 켰습니다.");
                        $this->save();
                        break;

                    case "off":
                        $this->data["whitelist"] = false;
                        $sender->sendMessage("{$this->pre} 화이트리스트가 꺼졌습니다.");
                        foreach ($this->getServer()->getOnlinePlayers() as $player) {
                            if ($player->isOp() && $player->getName() !== $sender->getName())
                                $player->sendMessage("{$this->pre} {$sender->getName()}님이 화이트리스트를 껐습니다.");
                        }
                        if ($sender instanceof Player)
                            $this->getServer()->getLogger()->notice("{$this->pre} {$sender->getName()}님이 화이트리스트를 껐습니다.");
                        $this->save();
                        break;

                    case "add":
                    case "a":
                        if (!isset($args[1])) {
                            $sender->sendMessage("{$this->pre} /whitelist add <닉네임>");
                            return false;
                        } else {
                            unset($args[0]);
                            $name = mb_strtolower(implode(" ", $args));
                            if (in_array(strtolower($name), $this->data["player"])) {
                                $sender->sendMessage("{$this->pre} 이미 추가되었습니다.");
                                return false;
                            } else {
                                array_push($this->data["player"], $name);
                                $sender->sendMessage("{$this->pre} {$name}님을 화이트리스트에 추가하였습니다.");
                                foreach ($this->getServer()->getOnlinePlayers() as $player) {
                                    if ($player->isOp() && $player->getName() !== $sender->getName())
                                        $player->sendMessage("{$this->pre} {$sender->getName()}님이 {$name}님을 화이트리스트에 추가했습니다.");
                                }
                                if ($sender instanceof Player)
                                    $this->getServer()->getLogger()->notice("{$this->pre} {$sender->getName()}님이 {$name}님을 화이트리스트에 추가했습니다.");
                            }
                        }
                        $this->save();
                        break;

                    case "remove":
                    case "r":
                        if (!isset($args[1])) {
                            $sender->sendMessage("{$this->pre} /whitelist remove <닉네임>");
                            return false;
                        } else {
                            unset($args[0]);
                            $name = implode(" ", $args);
                            if (!in_array(strtolower($name), $this->data["player"])) {
                                $sender->sendMessage("{$this->pre} {$name}님은 화이트리스트에 존재하지 않습니다.");
                                return false;
                            } else {
                                unset($this->data["player"][array_search($name, $this->data["player"])]);
                                $sender->sendMessage("{$this->pre} {$name}님을 화이트리스트에서 제거하였습니다.");
                                foreach ($this->getServer()->getOnlinePlayers() as $player) {
                                    if ($player->isOp() && $player->getName() !== $sender->getName())
                                        $player->sendMessage("{$this->pre} {$sender->getName()}님이 {$name}님을 화이트리스트에서 제거하였습니다.");
                                }
                                if ($sender instanceof Player)
                                    $this->getServer()->getLogger()->notice("{$this->pre} {$sender->getName()}님이 {$name}님을 화이트리스트에서 제거하였습니다.");
                            }
                        }
                        $this->save();
                        break;

                    case "list":
                    case "l":
                        if (isset($args[1]) && $args[1] == "login") {
                            $msg = "§7";
                            foreach ($this->ldata as $key => $player) {
                                $msg .= "<{$player}> ";
                            }
                            $count = count($this->ldata);
                        } else {
                            $msg = "§7";
                            foreach ($this->data["player"] as $key => $player) {
                                $msg .= "<{$player}> ";
                            }
                            $count = count($this->data["player"]);
                        }
                        $sender->sendMessage("{$this->pre} 화이트리스트 | §a{$count}§e명");
                        $sender->sendMessage($msg);
                        break;

                    default:
                        $sender->sendMessage("--- 화이트리스트 도움말 1 / 1 ---");
                        $sender->sendMessage("{$this->pre} /whitelist on | 화이트리스트를 켭니다.");
                        $sender->sendMessage("{$this->pre} /whitelist off | 화이트리스트를 끕니다.");
                        $sender->sendMessage("{$this->pre} /whitelist add <닉네임> | 플레이어를 화이트리스트에 추가합니다.");
                        $sender->sendMessage("{$this->pre} /whitelist remove <닉네임> | 플레이어를 화이트리스트에서 제거합니다.");
                        $sender->sendMessage("{$this->pre} /whitelist list | 화이트리스트 목록을 불러옵니다.");
                        break;

                    case "login":
                        if (!isset($args[1])) {
                            $sender->sendMessage("{$this->pre} /whitelist login <닉네임>");
                            return false;
                        }
                        unset($args[0]);
                        $name = mb_strtolower(implode(" ", $args));
                        $this->ldata[] = $name;
                        $sender->sendMessage("{$this->pre} {$name}님을 화이트리스트에 추가했습니다.");
                        break;

                    case "logout":
                        if (!isset($args[1])) {
                            $sender->sendMessage("{$this->pre} /whitelist logout <닉네임>");
                            return false;
                        }
                        unset($args[0]);
                        $name = mb_strtolower(implode(" ", $args));
                        if (!in_array(strtolower($name), $this->ldata)) {
                            $sender->sendMessage("{$this->pre} {$name}님은 화이트리스트에 존재하지 않습니다.");
                            return false;
                        } else {
                            unset($this->ldata[array_search($name, $this->ldata)]);
                            $sender->sendMessage("{$this->pre} {$name}님을 화이트리스트에서 제거하였습니다.");
                        }
                }
                return true;
            }
            return true;
        }
    }
}
