<?php
namespace Whitelist;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class EventListener implements Listener {
    public function __construct(Whitelist $plugin) {
        $this->plugin = $plugin;
    }

    public function onMove(PlayerMoveEvent $ev) {
        if ($this->plugin->data["whitelist"] && !$ev->getPlayer()->isOp() && !in_array(strtolower($ev->getPlayer()->getName()), $this->plugin->data["player"])) {
            $ev->setCancelled(true);
            $this->plugin->getScheduler()->scheduleDelayedTask(
                    new class($ev->getPlayer()) extends Task {
                        public function __construct(Player $player) {
                            $this->player = $player;
                        }

                        public function onRun($currentTick) {
                            if ($this->player instanceof Player) {
                                $this->player->kick("서버가 §c점검중§f입니다.\n자세한 내용은 §a커뮤니티§f를 확인해주세요.", false);
                            }
                        }
                    }, 1);
        }
    }

    public function onLogin(PlayerLoginEvent $ev) {
        $player = $ev->getPlayer();
        if (in_array(mb_strtolower($ev->getPlayer()->getName()), $this->plugin->ldata)) {
            $ev->setKickMessage("월드에 연결할 수 없습니다.");
            $ev->setCancelled(true);
        } elseif ($this->plugin->data["whitelist"] && !$ev->getPlayer()->isOp() && !in_array(strtolower($ev->getPlayer()->getName()), $this->plugin->data["player"])) {
            $ev->setKickMessage("서버가 §c점검중§f입니다.\n자세한 내용은 §a커뮤니티§f를 확인해주세요.");
            $ev->setCancelled(true);
        }
    }

    public function onJoin(PlayerJoinEvent $ev) {
        if ($this->plugin->data["whitelist"] && !$ev->getPlayer()->isOp() && !in_array(strtolower($ev->getPlayer()->getName()), $this->plugin->data["player"])) {
            $this->plugin->getScheduler()->scheduleDelayedTask(
                    new class($ev->getPlayer()) extends Task {
                        public function __construct(Player $player) {
                            $this->player = $player;
                        }

                        public function onRun($currentTick) {
                            if ($this->player instanceof Player) {
                                $this->player->kick("서버가 §c점검중§f입니다.\n자세한 내용은 §a커뮤니티§f를 확인해주세요.", false);
                            }
                        }
                    }, 20);
        }
    }

    public function onCmd_Player(PlayerCommandPreprocessEvent $ev) {
        if (substr($ev->getMessage(), 0, 1) == "/" && explode(" ", $ev->getMessage())[0] == "/whitelist") {
            if ($ev->isCancelled())
                return;
            $ev->setCancelled(true);
            $args = explode(" ", $ev->getMessage());
            unset($args[0]);
            $args = implode(" ", $args);
            $this->plugin->getServer()->dispatchCommand($ev->getPlayer(), "white " . $args);
        }
    }

    public function onCmd_Console(ServerCommandEvent $ev) {
        if (explode(" ", $ev->getCommand())[0] == "whitelist") {
            $args = explode(" ", $ev->getCommand());
            unset($args[0]);
            $args = implode(" ", $args);
            $ev->setCommand("white " . $args);
        }
    }

}
