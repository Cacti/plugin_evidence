# plugin_evidence for Cacti

## Try find serial number, version and important information about devices

A lot of vendors support SNMP Entity MIB (HPE, Synology, Cisco, Mikrotik, Fortinet, ...).
There are information about serial numbers, part numbers, versions, firmware, ..
For few vendors I added vendor specific OIDs (Aruba, Mikrotik, Synology, ..).
It can be useful when you need to find serial number, firmware change, problematic firmware, ...

## Author
Petr Macek (petr.macek@kostax.cz)


## Installation
Copy directory evidence to plugins directory (keep lowercase)
Check file permission (Linux/unix - readable for www server)
Enable plugin (Console -> Plugin management)
Configure plugin (Console -> Settings -> Evidence tab

## How to use?
You will see information about serial numbers and version on each supported device
You can use Evidence tab or link on edit device page

## Upgrade
Copy and rewrite files
Check file permission (Linux/unix - readable for www server)
Disable and deinstall old version (Console -> Plugin management)
Import ent.sql (described in data/README.md)
Install and enable new version (Console -> Plugin management)

## Possible Bugs or any ideas?
If you find a problem, let me know via github or https://forums.cacti.net


## Changelog
	--- 0.1
		Beginning

	--- Based on SNVer plugin 0.6
