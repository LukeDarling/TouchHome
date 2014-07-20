<?php
namespace LDX\TouchHome;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
class Main extends PluginBase implements Listener {
  public function onLoad() {
    $this->getLogger()->info(TextFormat::YELLOW . "Loading TouchHome v2.0 by LDX...");
  }
  public function onEnable() {
    $this->getLogger()->info(TextFormat::YELLOW . "Enabling TouchHome...");
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    if(!file_exists($this->getDataFolder() . "config.yml")) {
      @mkdir($this->getDataFolder());
      file_put_contents($this->getDataFolder() . "config.yml",$this->getResource("config.yml"));
    }
    $c = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
    $this->item = $c["item"];
  }
  public function onCommand(CommandSender $p,Command $cmd,$label,array $args) {
    if(strtolower($cmd->getName()) == "home" && $p instanceof Player) {
      if(isset($args[0])) {
        if($p->hasPermission("touchhome") || $p->hasPermission("touchhome.home") || $p->hasPermission("touchhome.home.others")) {
          if($this->getServer()->getPlayer($args[0]) instanceof Player && $this->homeExists($this->getServer()->getPlayer($args[0])->getName())) {
            $this->home($this->getServer()->getPlayer($args[0])->getName(),$p);
          } else if($this->homeExists($args[0])) {
            $this->home($args[0],$p);
          } else {
            $p->sendMessage("That player doesn't have a home!");
          }
        } else {
          $p->sendMessage("You don't have permission to do this.");
        }
      } else {
        if($p->hasPermission("touchhome") || $p->hasPermission("touchhome.home") || $p->hasPermission("touchhome.home.self")) {
          $this->home($p->getName(),$p);
        } else {
          $p->sendMessage("You don't have permission to do this.");
        }
      }
    } else if(strtolower($cmd->getName()) == "sethome" && $p instanceof Player) {
      if(isset($args[0])) {
        if($p->hasPermission("touchhome") || $p->hasPermission("touchhome.sethome") || $p->hasPermission("touchhome.sethome.others")) {
          if($this->getServer()->getPlayer($args[0]) instanceof Player) {
            $this->sethome($this->getServer()->getPlayer($args[0])->getName(),$p);
          } else {
            $this->sethome($args[0],$p);
          }
        } else {
          $p->sendMessage("You don't have permission to do this.");
        }
      } else {
        if($p->hasPermission("touchhome") || $p->hasPermission("touchhome.sethome") || $p->hasPermission("touchhome.sethome.self")) {
          $this->sethome($p->getName(),$p);
        } else {
          $p->sendMessage("You don't have permission to do this.");
        }
      }
    } else if(strtolower($cmd->getName()) == "delhome") {
      if(!isset($args[0]) && $p instanceof Player) {
        if($p->hasPermission("touchhome") || $p->hasPermission("touchhome.delhome") || $p->hasPermission("touchhome.delhome.self")) {
          $this->delhome($p->getName(),$p);
        } else {
          $p->sendMessage("You don't have permission to do this.");
        }
      } else {
        if(($p instanceof Player) || isset($args[0])) {
          if($p->hasPermission("touchhome") || $p->hasPermission("touchhome.delhome") || $p->hasPermission("touchhome.delhome.others")) {
            if($this->getServer()->getPlayer($args[0]) instanceof Player) {
              $this->delhome($this->getServer()->getPlayer($args[0])->getName(),$p);
            } else {
              $this->delhome($args[0],$p);
            }
          } else {
            $p->sendMessage("You don't have permission to do this.");
          }
        } else {
          $p->sendMessage(TextFormat::RED . "Usage: /delhome <player>");
        }
      }
    } else {
      $p->sendMessage(TextFormat::RED . "This command must be used in-game.");
    }
    return true;
  }
  /**
  * @param PlayerInteractEvent $event
  *
  * @priority HIGHEST
  * @ignoreCancelled true
  */
  public function onTouch(PlayerInteractEvent $event) {
    $p = $event->getPlayer();
    $i = $event->getItem();
    if(($i->getID() == $this->item) && ($p->hasPermission("touchhome") || $p->hasPermission("touchhome.home") || $p->hasPermission("touchhome.home.touch"))) {
      $this->home($p->getName(),$p);
      $event->setCancelled();
    }
  }
  public function home($o,$p) {
    if($this->homeExists($o)) {
      $name = strtolower($o);
      $this->checkHome($name);
      $p->sendMessage("Teleporting...");
      $p->teleport($this->home[$name]);
    } else {
      $p->sendMessage("You don't have a home set!");
    }
  }
  public function sethome($o,$p) {
    $name = strtolower($o);
    $this->home[$name] = new Vector3($p->x,$p->y,$p->z);
    $this->saveHome($name);
    $p->sendMessage("Home set!");
  }
  public function delhome($o,$p) {
    $name = strtolower($o);
    if(file_exists($this->getDataFolder() . "homes/$name.yml")) {
      unlink($this->getDataFolder() . "homes/$name.yml");
      $p->sendMessage("Home deleted!");
    } else {
      $p->sendMessage("Home not found.");
    }
  }
  public function checkHome($n) {
    $p = strtolower($n);
    if(file_exists($this->getDataFolder() . "homes/$p.yml")) {
      $c = yaml_parse(file_get_contents($this->getDataFolder() . "homes/$p.yml"));
      $this->home[$p] = new Vector3($c["X"],$c["Y"],$c["Z"]);
    } else {
      if(isset($this->home[$p])) {
        unset($this->home[$p]);
      }
    }
  }
  public function saveHome($n) {
    $p = strtolower($n);
    $x = $this->home[$p]->getX();
    $y = $this->home[$p]->getY();
    $z = $this->home[$p]->getZ();
    @mkdir($this->getDataFolder() . "homes/");
    file_put_contents($this->getDataFolder() . "homes/$p.yml",yaml_emit(array("V" => 1.0,"X" => $x,"Y" => $y,"Z" => $z)));
  }
  public function homeExists($n) {
    $p = strtolower($n);
    $this->checkHome($p);
    if(isset($this->home[$p])) {
      return true;
    } else {
      return false;
    }
  }
  public function onDisable() {
    $this->getLogger()->info(TextFormat::YELLOW . "Disabling TouchHome...");
  }
}
?>
