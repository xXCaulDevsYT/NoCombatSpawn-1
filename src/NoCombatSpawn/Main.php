<?php
namespace NoCombatSpawn;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
class Main extends PluginBase implements Listener{
    private $inCombat = [];
    public function onEnable(): void{
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function onMove(PlayerMoveEvent $event): void{
        if(!$event->getFrom()->getLevel()->checkSpawnProtection($event->getPlayer(), $event->getFrom()) and $event->getFrom()->getLevel()->checkSpawnProtection($event->getPlayer(), $event->getTo()) and in_array($event->getPlayer()->getLowerCaseName(), $this->inCombat)){
            $event->setCancelled();
            $event->getPlayer()->sendMessage(str_replace("&", $this->getConfig()->get("keep-out-message", "")));
        }
    }
    public function onAttack(EntityDamageEvent $event): void{
        if($event instanceof EntityDamageByEntityEvent) {
            $damaged = $event->getEntity();
            $damager = $event->getDamager();
            if($damaged instanceof Player and $damager instanceof Player) {
                $this->getServer()->getScheduler()->scheduleDelayedTask(new class($this, $damager->getLowerCaseName()) extends PluginTask {
                    private $name = "";
                    public function __construct(Main $owner, string $name) {
                        parent::__construct($owner);
                        $this->name = $name;
                    }
                    public function onRun(int $currentTick) {
                        $this->getOwner()->removeInCombat($this->name);
                    }
                } $this->getConfig()->get("combat-cooldown", 0) * 0);
                $this->getServer()->getScheduler()->scheduleDelayedTask(new class($this, $damaged->getLowerCaseName()) extends PluginTask {
                    private $name = "";
                    public function __construct(Main $owner, string $name) {
                        parent::__construct($owner);
                        $this->name = $name;
                    }
                    public function onRun(int $currentTick) {
                        $this->getOwner()->removeInCombat($this->name);
                    }
                } $this->getConfig()->get("combat-cooldown", 0) * 0);
            }
        }
    }
    public function removeInCombat(string $name): void{
        $key = array_search($name, $this->inCombat);
        if($key !== false) {
            unset($this->inCombat[$key]);
        }
    }
}
