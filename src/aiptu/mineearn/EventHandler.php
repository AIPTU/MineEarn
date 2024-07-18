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

use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function in_array;
use function mt_getrandmax;
use function mt_rand;
use function number_format;

final class EventHandler implements Listener {
	public function __construct(private MineEarn $plugin) {}

	public function onBlockBreak(BlockBreakEvent $event) : void {
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$world = $player->getWorld()->getFolderName();

		if (in_array($world, $this->plugin->getIgnoredWorlds(), true)) {
			return;
		}

		if (!$player->hasFiniteResources()) {
			return;
		}

		$item = $event->getItem();
		if ($this->plugin->isSilkTouchCheckEnabled() && $item->hasEnchantment(VanillaEnchantments::SILK_TOUCH())) {
			return;
		}

		$money = $this->calculateEarnings($block, $player, $item);

		if ($money > 0) {
			$messages = $this->plugin->getMessages();
			$economyProvider = $this->plugin->getEconomyProvider();

			$economyProvider->giveMoney($player, $money, function (bool $success) use ($block, $economyProvider, $money, $player, $messages) : void {
				if (!$success) {
					$player->sendMessage(TextFormat::colorize($messages['generic-error']));
					return;
				}

				$player->sendPopup(TextFormat::colorize($this->plugin->replaceVars($messages['received'], [
					'BLOCK' => $block->getName(),
					'MONEY' => number_format($money),
					'MONETARY_UNIT' => $economyProvider->getMonetaryUnit(),
				])));
			});
		}
	}

	private function calculateEarnings(Block $block, Player $player, Item $item) : float {
		$blockItem = $block->asItem();

		$worldEarnings = $this->getWorldEarnings($blockItem, $player);
		$earnings = $worldEarnings ?? $this->calculateGlobalEarnings($blockItem);

		if ($this->plugin->isFortuneBonusEnabled() && $item->hasEnchantment(VanillaEnchantments::FORTUNE())) {
			$fortuneLevel = $item->getEnchantmentLevel(VanillaEnchantments::FORTUNE());
			$earnings = $this->applyFortuneModifier($earnings, $fortuneLevel);
		}

		return $earnings;
	}

	private function applyFortuneModifier(float $earnings, int $fortuneLevel) : float {
		// Apply bonus based on Fortune level with a chance
		if (mt_rand() / mt_getrandmax() <= $this->plugin->getFortuneBonusPercentage()) {
			return $earnings * (1 + $this->plugin->getFortuneBonusChance() / 100 * $fortuneLevel);
		}

		return $earnings;
	}

	private function getWorldEarnings(Item $blockItem, Player $player) : ?float {
		$world = $player->getWorld()->getFolderName();
		$worldEarnings = $this->plugin->getWorldEarnings();

		if (isset($worldEarnings[$world]) && $player->hasPermission('mineearn.world.' . $world)) {
			foreach ($worldEarnings[$world] as [$earnBlock, $earning]) {
				if ($blockItem->equals($earnBlock, true, false)) {
					return (float) $earning;
				}
			}
		}

		return null;
	}

	private function calculateGlobalEarnings(Item $blockItem) : float {
		$money = 0.0;
		$globalEarnings = $this->plugin->getGlobalEarnings();

		foreach ($globalEarnings as [$earnBlock, $earning]) {
			if ($blockItem->equals($earnBlock, true, false)) {
				$money += $earning;
			}
		}

		return $money;
	}
}
