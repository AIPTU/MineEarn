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

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

final class EventHandler implements Listener {
	public function __construct(private MineEarn $plugin) {}

	public function getPlugin() : MineEarn {
		return $this->plugin;
	}

	public function onBlockBreak(BlockBreakEvent $event) : void {
		$block = $event->getBlock();
		$player = $event->getPlayer();

		$money = 0;

		$global = $this->getPlugin()->getGlobalEarnings();
		foreach ($global as $data) {
			[$earnBlock, $earning] = $data;
			if ($block->asItem()->equals($earnBlock, true, false)) {
				$money += $earning;
			}
		}

		$world = $player->getWorld()->getFolderName();
		$worlds = $this->getPlugin()->getWorldEarnings();
		if (isset($worlds[$world])) {
			foreach ($worlds[$world] as $data) {
				[$earnBlock, $earning] = $data;
				if ($block->asItem()->equals($earnBlock, true, false)) {
					if ($player->hasPermission('minesell.world.' . $world)) {
						$money += $earning;
					}
				}
			}
		}

		if ($money > 0) {
			$this->getPlugin()->setMoneyEarnt($player, $money);
		}
	}
}
