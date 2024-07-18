<?php

/*
 * Copyright (c) 2021-2024 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/MineEarn
 */

declare(strict_types=1);

namespace aiptu\mineearn;

use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use function array_map;
use function array_merge;
use function class_exists;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function str_replace;

final class MineEarn extends PluginBase {
	private const CONFIG_VERSION = 1.0;

	private array $globalEarnings = [];
	private array $worldEarnings = [];
	private array $messages = [];
	private bool $enableFortuneBonus;
	private int $fortuneBonusPercentage;
	private float $fortuneBonusChance;
	private bool $enableSilkTouchCheck;
	private array $ignoredWorlds;
	private EconomyProvider $economyProvider;

	public function onEnable() : void {
		if (!class_exists(libPiggyEconomy::class)) {
			$this->getLogger()->error('libPiggyEconomy virion not found. Please download MineEarn from Poggit-CI or use DEVirion (not recommended).');
			throw new DisablePluginException();
		}

		try {
			$this->loadConfig();
		} catch (\Throwable $e) {
			$this->getLogger()->critical('An error occurred while attempting to load the config: ' . $e->getMessage());
			throw new DisablePluginException();
		}

		libPiggyEconomy::init();

		$economyConfig = $this->getConfig()->get('economy');
		if (!is_array($economyConfig) || !isset($economyConfig['provider'])) {
			$this->getLogger()->critical('Invalid or missing "economy" configuration. Please provide an array with the key "provider".');
			throw new DisablePluginException();
		}

		try {
			$this->economyProvider = libPiggyEconomy::getProvider($economyConfig);
		} catch (\Throwable $e) {
			$this->getLogger()->critical('Failed to get economy provider: ' . $e->getMessage());
			throw new DisablePluginException();
		}

		$this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
	}

	public function getEconomyProvider() : EconomyProvider {
		return $this->economyProvider;
	}

	public function getGlobalEarnings() : array {
		return $this->globalEarnings;
	}

	public function getWorldEarnings() : array {
		return $this->worldEarnings;
	}

	public function getMessages() : array {
		return $this->messages;
	}

	public function isFortuneBonusEnabled() : bool {
		return $this->enableFortuneBonus;
	}

	public function getFortuneBonusPercentage() : int {
		return $this->fortuneBonusPercentage;
	}

	public function getFortuneBonusChance() : float {
		return $this->fortuneBonusChance;
	}

	public function isSilkTouchCheckEnabled() : bool {
		return $this->enableSilkTouchCheck;
	}

	public function getIgnoredWorlds() : array {
		return $this->ignoredWorlds;
	}

	public function replaceVars(string $str, array $vars) : string {
		foreach ($vars as $key => $value) {
			$str = str_replace('{' . $key . '}', (string) $value, $str);
		}

		return $str;
	}

	private function loadConfig() : void {
		$this->checkConfig();

		$config = $this->getConfig();

		$globalEarnings = $config->getNested('earnings.global');
		if (!is_array($globalEarnings)) {
			throw new \InvalidArgumentException('Invalid or missing "earnings.global" value in the configuration. Please provide an array.');
		}

		$this->globalEarnings = $this->parseEarnings($globalEarnings);

		$worldEarnings = $config->getNested('earnings.worlds', []);
		if (!is_array($worldEarnings)) {
			throw new \InvalidArgumentException('Invalid or missing "earnings.worlds" value in the configuration. Please provide an array.');
		}

		$this->worldEarnings = [];
		foreach ($worldEarnings as $world => $earnings) {
			if (!is_array($earnings)) {
				throw new \InvalidArgumentException("Earnings for world '{$world}' is not an array.");
			}

			$permissionManager = PermissionManager::getInstance();
			$permission = new Permission('mineearn.world.' . $world, 'Allow users to earn in the world ' . $world);
			$permissionManager->addPermission($permission);
			$permissionManager->getPermission(DefaultPermissions::ROOT_OPERATOR)?->addChild($permission->getName(), true);

			$parsedEarnings = $this->parseEarnings($earnings);
			$this->worldEarnings[$world] = $parsedEarnings;

			if (!$this->getServer()->getWorldManager()->isWorldGenerated($world)) {
				throw new \InvalidArgumentException("Invalid world name '{$world}' in 'earnings.worlds'. This world does not exist on the server.");
			}
		}

		$messages = $config->get('messages', []);
		if (!is_array($messages)) {
			throw new \InvalidArgumentException('Invalid or missing "messages" value in the configuration. Please provide an array.');
		}

		$defaultMessages = [
			'generic-error' => '&cAn unexpected error has occurred.',
			'received' => '&eYou have received &6{MONETARY_UNIT}{MONEY} for {BLOCK}',
		];
		$messages = array_merge($defaultMessages, $messages);

		$this->messages = array_map('strval', $messages);

		$enableFortuneBonus = $config->getNested('earnings.settings.enable_fortune_bonus');
		if (!is_bool($enableFortuneBonus)) {
			throw new \InvalidArgumentException('Invalid or missing "enable_fortune_bonus" value in the configuration. Please provide a boolean (true/false) value.');
		}

		$this->enableFortuneBonus = $enableFortuneBonus;

		$fortuneBonusPercentage = $config->getNested('earnings.settings.fortune_bonus_percentage');
		if (!is_int($fortuneBonusPercentage) || $fortuneBonusPercentage < 0 || $fortuneBonusPercentage > 100) {
			throw new \InvalidArgumentException('Invalid or missing "fortune_bonus_percentage" value in the configuration. Please provide an integer between 0 and 100.');
		}

		$this->fortuneBonusPercentage = $fortuneBonusPercentage;

		$fortuneBonusChance = $config->getNested('earnings.settings.fortune_bonus_chance');
		if (!is_float($fortuneBonusChance) || $fortuneBonusChance < 0.0 || $fortuneBonusChance > 1.0) {
			throw new \InvalidArgumentException('Invalid or missing "fortune_bonus_chance" value in the configuration. Please provide a float between 0.0 and 1.0.');
		}

		$this->fortuneBonusChance = $fortuneBonusChance;

		$enableSilkTouchCheck = $config->getNested('earnings.settings.enable_silk_touch_check');
		if (!is_bool($enableSilkTouchCheck)) {
			throw new \InvalidArgumentException('Invalid or missing "enable_silk_touch_check" value in the configuration. Please provide a boolean (true/false) value.');
		}

		$this->enableSilkTouchCheck = $enableSilkTouchCheck;

		$ignoredWorlds = $config->getNested('earnings.settings.ignored_worlds');
		if (!is_array($ignoredWorlds)) {
			throw new \InvalidArgumentException('Invalid or missing "ignored_worlds" value in the configuration. Please provide an array of world names.');
		}

		foreach ($ignoredWorlds as $worldName) {
			if (!$this->getServer()->getWorldManager()->isWorldGenerated($worldName)) {
				throw new \InvalidArgumentException("Invalid world name '{$worldName}' in 'ignored_worlds'. This world does not exist on the server.");
			}
		}

		$this->ignoredWorlds = $ignoredWorlds;
	}

	private function checkConfig() : void {
		$config = $this->getConfig();

		if (!$config->exists('config-version') || $config->get('config-version') !== self::CONFIG_VERSION) {
			$this->getLogger()->warning('An outdated config was provided. Attempting to generate a new one...');
			$filesystem = new Filesystem();

			try {
				$filesystem->rename(Path::join($this->getDataFolder(), 'config.yml'), Path::join($this->getDataFolder(), 'config.old.yml'));
				$this->reloadConfig();
			} catch (IOException $e) {
				$this->getLogger()->critical('Failed to rename old config file: ' . $e->getMessage());
				throw new DisablePluginException();
			}
		}
	}

	private function parseEarnings(array $earnings) : array {
		$parsedEarnings = [];
		foreach ($earnings as $block => $earning) {
			$blockItem = StringToItemParser::getInstance()->parse($block);
			if (!$blockItem instanceof Item) {
				throw new \InvalidArgumentException("Invalid item '{$block}'");
			}

			if (!is_numeric($earning)) {
				throw new \InvalidArgumentException("Earning value for block '{$block}' is not numeric: '{$earning}'");
			}

			$parsedEarnings[] = [$blockItem, (float) $earning];
		}

		return $parsedEarnings;
	}
}
