# MineEarn

A PocketMine-MP plugin that can earn by mining

# Features

- Custom items.
- Support enchantment silk touch and fortune.
- Per world support with permission.
- Supports `&` as formatting codes.
- Lightweight and open source ❤️

# Supported Economy Providers

- [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) by cooldogedev
- Experience (PocketMine-MP)

# Permissions

**Notes: Global earnings does not require any permission and {WORLD} is the name of your world folder listed in the config**

- Permission `mineearn.world.{WORLD}`: Allow users to earn in the world {WORLD}, (Default is `OP`).

# Default Config
```yaml
# MineEarn Configuration

# Do not change this (Only for internal use)!
config-version: 1.0

# Messages settings.
# Use "&" as formatting codes.
messages:
  generic-error: "&cAn unexpected error has occurred."
  received: "&eYou have received &6{MONETARY_UNIT}{MONEY} &efor &6{BLOCK}"

# Economy settings.
# Possible providers: bedrockeconomy, xp
economy:
  provider: BedrockEconomy

# Earnings settings.
# If you are using the "xp" provider, 1 unit = 1 level of experience.
earnings:
  settings:
    enable_fortune_bonus: true  # Enable or disable bonus for Fortune enchantment
    fortune_bonus_percentage: 10  # The percentage bonus per Fortune level
    fortune_bonus_chance: 0.5  # Chance (0.0 to 1.0) of applying the Fortune bonus
    enable_silk_touch_check: true  # Enable or disable check for Silk Touch enchantment
    ignored_worlds: []  # List of worlds to ignore for earnings
    
  # Global earnings.
  # These apply to all worlds unless overridden by per-world earnings.
  global:
    "coal_ore": 80
    "gold_ore": 150
    "iron_ore": 100
    "diamond_ore": 300
    "emerald_ore": 350
    "lapis_ore": 200
    "redstone_ore": 250
    "quartz_ore": 180

  # Per-world earnings.
  # These override global earnings for specific worlds.
  worlds:
    world: # World folder name
      "diamond_ore": 350
      "emerald_ore": 300
      "lapis_ore": 280
      "quartz_ore": 200
      "redstone_ore": 250

```

# Upcoming Features

- Currently none planned. You can contribute or suggest for new features.

# Additional Notes

- If you find bugs or want to give suggestions, please visit [here](https://github.com/AIPTU/MineEarn/issues).
- We accept all contributions! If you want to contribute, please make a pull request in [here](https://github.com/AIPTU/MineEarn/pulls).
- Icons made from [www.flaticon.com](https://www.flaticon.com)
