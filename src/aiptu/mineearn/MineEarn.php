<?php

/*
 *
 * Copyright (c) 2021 AIPTU
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

declare(strict_types=1);

namespace aiptu\mineearn;

use aiptu\mineearn\tasks\TaskHandler;
use aiptu\mineearn\utils\TypedConfig;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;
use DaPigGuy\libPiggyEconomy\providers\EconomyProvider;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\WorldException;
use function class_exists;
use function explode;
use function is_numeric;
use function rename;
use function str_replace;
use function trim;

final class MineEarn extends PluginBase {
	private const CONFIG_VERSION = 1.0;

	private array $moneyEarnt = [];

	private array $globalEarnings = [];

	private array $worldEarnings = [];

	private EconomyProvider $economyProvider;

	private TypedConfig $typedConfig;

	public function onEnable() : void {
		if (!class_exists(libPiggyEconomy::class)) {
			$this->getLogger()->error('libPiggyEconomy virion not found. Please download MineEarn from Poggit-CI or use DEVirion (not recommended).');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		if (!$this->loadConfig()) {
			$this->getLogger()->critical('An error occurred while attempting to load the config');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		libPiggyEconomy::init();
		$this->economyProvider = libPiggyEconomy::getProvider($this->typedConfig->getStringList('economy'));

		$this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
		$this->getScheduler()->scheduleRepeatingTask(new TaskHandler($this), 20);
	}

	public function getMoneyEarnt() : array {
		return $this->moneyEarnt;
	}

	public function setMoneyEarnt(Player $player, float|int $value) : void {
		$this->moneyEarnt[$player->getName()] = $value;
	}

	public function getGlobalEarnings() : array {
		return $this->globalEarnings;
	}

	public function getWorldEarnings() : array {
		return $this->worldEarnings;
	}

	public function getEconomyProvider() : EconomyProvider {
		return $this->economyProvider;
	}

	public function getTypedConfig() : TypedConfig {
		return $this->typedConfig;
	}

	public function checkItem(string $string) : Item {
		try {
			$item = LegacyStringToItemParser::getInstance()->parse($string);
		} catch (LegacyStringToItemParserException $e) {
			if (($item = StringToItemParser::getInstance()->parse(explode(':', str_replace([' ', 'minecraft:'], ['_', ''], trim($string)))[0])) === null) {
				throw $e;
			}
		}

		return $item;
	}

	public function replaceVars(string $str, array $vars) : string {
		foreach ($vars as $key => $value) {
			$str = str_replace('{' . $key . '}', (string) $value, $str);
		}

		return $str;
	}

	private function loadConfig() : bool {
		$this->saveDefaultConfig();

		if (!$this->getConfig()->exists('config-version') || ($this->getConfig()->get('config-version', self::CONFIG_VERSION) !== self::CONFIG_VERSION)) {
			$this->getLogger()->warning('An outdated config was provided attempting to generate a new one...');
			if (!rename($this->getDataFolder() . 'config.yml', $this->getDataFolder() . 'config.old.yml')) {
				$this->getLogger()->critical('An unknown error occurred while attempting to generate the new config');
				$this->getServer()->getPluginManager()->disablePlugin($this);
			}

			$this->reloadConfig();
		}

		$this->typedConfig = new TypedConfig($this->getConfig());

		$globalEarnings = $this->typedConfig->getStringList('earnings.global');
		$this->globalEarnings = [];
		foreach ($globalEarnings as $block => $earning) {
			try {
				$block = $this->checkItem($block);
			} catch (\InvalidArgumentException $e) {
				$this->getLogger()->error($e->getMessage());
				return false;
			}

			if (!is_numeric($earning)) {
				return false;
			}

			$this->globalEarnings[] = [$block, $earning];
		}

		$worldEarnings = $this->typedConfig->getStringList('earnings.worlds');
		$this->worldEarnings = [];
		foreach ($worldEarnings as $world => $earnings) {
			$permission_manager = PermissionManager::getInstance();
			$permission = new Permission('mineearn.world.' . $world, 'Allow users to earn in the world ' . $world);
			$permission_manager->addPermission($permission);
			$permission_manager->getPermission(DefaultPermissions::ROOT_OPERATOR)?->addChild($permission->getName(), true);

			foreach ($earnings as $block => $earning) {
				try {
					$block = $this->checkItem($block);
				} catch (\InvalidArgumentException $e) {
					$this->getLogger()->error($e->getMessage());
					return false;
				}

				if (!is_numeric($earning)) {
					return false;
				}

				$this->worldEarnings[$world][] = [$block, $earning];
			}

			$valid = false;
			try {
				$valid = $this->getServer()->getWorldManager()->loadWorld($world);
			} catch (WorldException $e) {
				$this->getLogger()->error($e->getMessage());
			}

			if (!$valid) {
				$this->getLogger()->error('World ' . $world . ' not found');
				return false;
			}
		}

		return true;
	}
}
