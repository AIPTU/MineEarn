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

namespace aiptu\mineearn\tasks;

use aiptu\mineearn\MineEarn;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use function number_format;

final class TaskHandler extends Task {
	public function __construct(private MineEarn $plugin) {}

	public function getPlugin() : MineEarn {
		return $this->plugin;
	}

	public function onRun() : void {
		foreach ($this->getPlugin()->getMoneyEarnt() as $player => $money) {
			$player = $this->getPlugin()->getServer()->getPlayerExact($player);
			if ($player === null) {
				continue;
			}

			$typedConfig = $this->getPlugin()->getTypedConfig();
			$economyProvider = $this->getPlugin()->getEconomyProvider();
			if ($money > 0) {
				$economyProvider->giveMoney($player, $money, function (bool $success) use ($economyProvider, $money, $player, $typedConfig) : void {
					if (!$success) {
						$player->sendMessage(TextFormat::colorize($typedConfig->getString('messages.generic-error', '&cAn unexpected error has occurred.')));
						return;
					}

					$player->sendPopup(TextFormat::colorize($this->getPlugin()->replaceVars($typedConfig->getString('messages.received', '&eYou have received &6{MONETARY_UNIT}{MONEY}'), [
						'MONEY' => number_format($money),
						'MONETARY_UNIT' => $economyProvider->getMonetaryUnit(),
					])));
				});
				$this->getPlugin()->setMoneyEarnt($player, 0);
			}
		}
	}
}
