# MineEarn

A PocketMine-MP plugin that can earn by mining

# Features

- Custom items.
- Per world support with permission.
- Supports `&` as formatting codes.
- Lightweight and open source ❤️

# Supported Economy Providers

- [EconomyAPI](https://poggit.pmmp.io/p/EconomyAPI) by onebone/poggit-orphanage
- [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) by cooldogedev
- Experience (PocketMine-MP)

# Permissions

**Notes: Global earnings does not require any permission and {WORLD} is the name of your world folder listed in the config**

- Permission `mineearn.world.{WORLD}`: Allow users to earn in the world {WORLD}, (Default is `OP`).

# Default Config
```yaml
---
# Do not change this (Only for internal use)!
config-version: 1.0

# Messages settings.
# Use "&" as formatting codes.
messages:
  generic-error: "&cAn unexpected error has occurred."
  received: "&eYou have received &6{MONETARY_UNIT}{MONEY}"

# Economy settings.
# Possible providers: economyapi, bedrockeconomy, xp
economy:
  provider: BedrockEconomy

# Earnings settings.
# If you are using xp provider, 1 = 1 level of experience.
earnings:
  # Global earnings.
  global:
    "coal_ore": 80
    "gold_ore": 150
    "iron_ore": 100
    # Oak log.
    "log": 50
    # Spruce log.
    "log:1": 60
 # Per-world earnings.
  worlds:
    world: # World folder name
      "diamond_ore": 350
      "emerald_ore": 300
      "lapis_lazuli": 280
      "quartz_ore": 200
      "redstone_ore": 250
...

```

# Upcoming Features

- Currently none planned. You can contribute or suggest for new features.

# Additional Notes

- If you find bugs or want to give suggestions, please visit [here](https://github.com/AIPTU/MineEarn/issues).
- We accept all contributions! If you want to contribute, please make a pull request in [here](https://github.com/AIPTU/MineEarn/pulls).
- Icons made from [www.flaticon.com](https://www.flaticon.com)
