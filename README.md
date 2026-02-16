# plugin_evidence for Cacti

## Evidence plugin can be useful when you need to find serial number, firmware change,
 or problematic firmware. Plugin can collect information about:
- Entity MIB - serial numbers, part numbers, version, firmware, ...
- MAC addresses
- IP addresses
- vendor specific information

The plugin also stores history and can notify you when a change occurs.

## Author
Petr Macek (petr.macek@kostax.cz)

Based on SNVer plugin 0.6

## Installation
Copy directory plugin_evidence to plugins directory (keep lowercase)
Check file permission (Linux/unix - readable for www server)
Enable plugin (Console -> Plugin management)
Configure plugin (Console -> Settings -> Evidence tab

## How to use?
You will see information about serial numbers and version on each supported device
You can use the Evidence tab or link on edit device page

## Upgrade
Copy and rewrite files
Check file permission (Linux/unix - readable for www server)
Disable and deinstall old version (Console -> Plugin management)
Import ent.sql (described in data/README.md)
Install and enable new version (Console -> Plugin management)

## Possible Bugs or any ideas?
If you find a problem, let me know via github or https://forums.cacti.net

## Changelog
--- 0.3 ---
* Add generic snmp info

--- 0.2 ---
* Better data display

--- 0.1 ---
* Beginning

-----------------------------------------------------------------------------
Copyright (c) 2004-2026 - The Cacti Group, Inc.
