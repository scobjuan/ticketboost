# Compatibility

| TicketBoost | GLPI           | PHP                              |
| ----------- | -------------- | -------------------------------- |
| 0.x         | 11.0.x, 12.0.x | 8.2 to 8.5 (GLPI 12: 8.3 to 8.5) |

GLPI 10.x is not supported: it reached end of life with 10.0.28.

TicketBoost is tested in CI against GLPI 11.0.x with PHP 8.2 and 8.5, and GLPI 12.0.x
with PHP 8.3 and 8.5, on MySQL and MariaDB, and end to end in Chromium, Firefox and
WebKit.
