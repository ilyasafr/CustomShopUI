<?php
#   _______   _                   _____    _                 _   _____                       
#  |__   __| (_)                 |  __ \  (_)               | | |  __ \                      
#     | |     _   _ __    _   _  | |__) |  _  __  __   ___  | | | |  | |   ___  __   __  ____
#     | |    | | | '_ \  | | | | |  ___/  | | \ \/ /  / _ \ | | | |  | |  / _ \ \ \ / / |_  /
#     | |    | | | | | | | |_| | | |      | |  >  <  |  __/ | | | |__| | |  __/  \ V /   / / 
#     |_|    |_| |_| |_|  \__, | |_|      |_| /_/\_\  \___| |_| |_____/   \___|   \_/   /___|
#                          __/ |                                                             
#                         |___/    
#     
namespace AlexPads\ShopUI\TinyPixelDevz;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

 class Shop extends PluginBase implements Listener{

	 public $items;
	 public $category;
	 public $allshop;
	 public $name;
	 public $result;
	 public $data;
	 public $list;

	 public function onEnable()
	 {
		 foreach (['FormAPI', 'EconomyAPI'] as $depend) {
			 $plugin = $this->getServer()->getPluginManager()->getPlugin($depend);
			 $plugin = strtolower($depend);
			 if (is_null($plugin)) {
				 $this->getLogger()->error("The plugin" . $depend . " is required in order to use this plugin.");
				 $this->setEnabled(false);
			 }
		 }
		 $this->saveDefaultConfig();
		 $this->saveResource("shop.yml");
		 $this->getLogger()->info("Enabled");
	 }

	 public function onDisable()
	 {
		 $this->getLogger()->info("Disabled");
	 }

	 public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
	 {
		 $cmdd = $this->getConfig()->get("Command");
		 switch ($cmd->getName()) {
			 case "$cmdd":
				 if ($sender instanceof Player) {
					 $this->catForm($sender);
					 return true;
				 }
				 $sender->sendMessage(TF::RED . "Please use this in-game.");
				 break;
		 }
		 return true;
	 }

	 public function catForm(Player $player): void
	 {
		 $form = new SimpleForm(function (Player $player, int $data = null) {
			 $cate = $data;
			 $this->itemForm($player, $data, $cate);
		 });
		 $form->setTitle($this->getConfig()->get("Title"));
		 $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
		 $allshop = yaml_parse_file($this->getDataFolder() . "shop.yml");
		 foreach ($allshop as $name => $content) {
			 $form->addButton(ucfirst($name));
		 }
		 $form->sendToPlayer($player);
	 }

	 public function itemForm(Player $player, $data, $cate): void
	 {
		 $form = new SimpleForm(function (Player $player, int $data = null) use ($cate) {
			 $result = $data;
			 $this->buysellForm($player, $result, $cate);
		 });
		 $form->setTitle($this->getConfig()->get("Title"));
		 $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
		 $allshop = yaml_parse_file($this->getDataFolder() . "shop.yml");
		 foreach ($allshop as $categoryName => $access) {
			 $category[] = $access;
		 }
		 if ($data === null) {
			 $player->sendmessage("Thankyou for Shopping at" . " " . $this->getConfig()->get("Title"));
		 } else {
			 foreach ($category[$data] as $items) {
				 $list = explode(":", $items);
				 $form->addButton($list[3] . "  " . "$" . $list[4], $list[6], $list[7]);
			 }
			 $form->sendToPlayer($player);
		 }
	 }

	 public function buysellForm(Player $player, $result, $cate): void
	 {
		 $form = new SimpleForm(function (Player $player, int $data = null) use ($cate, $result) {
			 if ($data === 0) {
				 $allshop = yaml_parse_file($this->getDataFolder() . "shop.yml");
				 $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
				 foreach ($allshop as $categoryName => $access) {
					 $category[] = $access;
				 }
				 foreach ($category[$cate] as $items => $itemarray) {
					 $itemlist[] = $itemarray;
				 }
				 $list = explode(":", $itemlist[$result]);
				 $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
				 if ($money >= $list[4]) {
					 if ($this->getConfig()->get("Enchants") === false) {
						 $player->getInventory()->addItem(Item::get($list[0], $list[1], $list[2])->setCustomName($list[3]));
						 $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->reduceMoney($player, $list[4]);
						 $message = $this->getConfig()->getNested("messages.paid-for");
						 $vars = ["{amount}" => $list[2], "{item}" => $list[3], "{cost}" => $list[4]];
						 foreach ($vars as $var => $replacement) {
							 $message = str_replace($var, $replacement, $message);
						 }
					 } else {
						 $enchant = Enchantment::getEnchantment($list[6]);
						 $enchants = new EnchantmentInstance($enchant, $list[7]);
						 $player->getInventory()->addItem(Item::get($list[0], $list[1], $list[2])->setCustomName($list[3])->addEnchantment($enchants));
						 $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->reduceMoney($player, $list[4]);
						 $message = $this->getConfig()->getNested("messages.paid-for");
						 $vars = ["{amount}" => $list[2], "{item}" => $list[3], "{cost}" => $list[4]];
						 foreach ($vars as $var => $replacement) {
							 $message = str_replace($var, $replacement, $message);
						 }
					 }
					 $player->sendMessage($message);
					 $this->buysellForm($player, $result, $cate);
				 } else {
					 $message = $this->getConfig()->getNested("messages.not-enough-money");
					 $tags = ["{amount}" => $list[2], "{name}" => $list[3], "{cost}" => $list[4], "{missing}" => $list[4] - $money];
					 foreach ($tags as $tag => $replacement) {
						 $message = str_replace($tag, $replacement, $message);
					 }
					 $player->sendMessage($message);
					 $this->buysellForm($player, $result, $cate);
				 }
			 }
				 if ($data === 1) {
					 $allshop = yaml_parse_file($this->getDataFolder() . "shop.yml");
					 $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
					 foreach ($allshop as $categoryName => $access) {
						 $category[] = $access;
					 }
					 foreach ($category[$cate] as $items => $itemarray) {
						 $itemlist[] = $itemarray;
					 }
					 $list = explode(":", $itemlist[$result]);
					 $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
					 if ($player->getInventory()->contains(Item::get($list[0], $list[1], $list[2])) === true) {
						 $player->getInventory()->removeItem(Item::get($list[0], $list[1], $list[2]));
						 $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->addMoney($player, $list[5]);
						 $message = $this->getConfig()->getNested("messages.money-recieved");
						 $vars = ["{amount}" => $list[2], "{item}" => $list[3], "{money}" => $list[5]];
						 foreach ($vars as $var => $replacement) {
							 $message = str_replace($var, $replacement, $message);
						 }
						 $player->sendMessage($message);
						 $this->buysellForm($player, $result, $cate);
					 } else {
						 $message = $this->getConfig()->getNested("messages.not-enough-items");
						 $tags = [
							 "{amount}" => $list[2], "{name}" => $list[3], "{money}" => $list[5], "{missing}" => $list[4] - $money];
						 foreach ($tags as $tag => $replacement) {
							 $message = str_replace($tag, $replacement, $message);
						 }
						 $player->sendMessage($message);
						 $this->buysellForm($player, $result, $cate);
					 }
				 }
				 if ($data === 2) {
					 $this->catForm($player);
				 }
		 });
		 $form->setTitle($this->getConfig()->get("Title"));
		 $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
		 $allshop = yaml_parse_file($this->getDataFolder() . "shop.yml");
		 foreach ($allshop as $categoryName => $access) {
			 $category[] = $access;
		 }
		 foreach ($category[$cate] as $items => $itemarray) {
			 $itemlist[] = $itemarray;
		 }
		 if ($result === null) {
			 $this->catForm($player);
		 } else {
			 $list = explode(":", $itemlist[$result]);
			 $message = $this->getConfig()->getNested("messages.money");
			 $form->setContent("$message $money$");
			 $form->addButton("Buy" . " " . $list[2] . " " . "For" . " " . "$" . $list[4]);
			 $form->addButton("Sell" . " " . $list[2] . " " . "For" . " " . "$" . $list[5]);
			 $form->addButton(TF::RED . TF::BOLD . "Back");
			 $form->sendToPlayer($player);
		 }
	 }
 }