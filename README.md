# plugin_evidence for Cacti

Evidence collects device inventory through SNMP and keeps a history of changes
to serial numbers, hardware, firmware, MAC/IP addresses and vendor-specific data.
Use the Evidence tab or the link on a device’s edit page to inspect that history.

## Versions and compatibility

This README describes the `main` source branch.

| Code | Plugin version | Minimum Cacti declared in `INFO` |
| --- | --- | --- |
| [Source branch](INFO) | 0.4 | 1.2.32 |
| [Published v0.2](https://github.com/Cacti/plugin_evidence/releases/tag/v0.2) | 0.1 | 1.0.0 |

Use the published release’s assets and tagged documentation when installing that
package. Branch code can include later changes, even when its version number
matches the release. Cacti’s own PHP and database requirements also apply.

The release tag and embedded plugin version differ; the table quotes the
metadata shipped in that tag.

Install this plugin in **`<cacti-root>/plugins/evidence`**, using the directory
name `evidence` rather than `plugin_evidence`.

See [CHANGELOG.md](CHANGELOG.md) for the version history.

## Author
Petr Macek (petr.macek@kostax.cz)

Based on SNVer plugin 0.6

## Installation
1. Copy the plugin into Cacti’s `plugins` directory as `evidence`.
2. Make its files readable by the web-server and poller accounts.
3. Install and enable Evidence in Plugin Management, grant its viewing realm,
   and configure **Settings > Evidence**.
4. Check SNMP access to a supported device and confirm a poller cycle records it.

## How to use?
You will see information about serial numbers and version on each supported device
You can use the Evidence tab or link on edit device page

## Upgrade
Back up the Cacti database and plugin configuration before replacing the files.
Keep the directory named `evidence`. The plugin’s configuration check runs its
database upgrade, including repointing hooks from the old `include/` directory
to `includes/`. A normal upgrade does not require uninstalling the plugin.

The earlier uninstall/reinstall and `ent.sql` import instructions were for legacy
installations. Check the documentation for the version you are migrating from
before applying that procedure; choosing data removal during uninstall drops
the Evidence tables.

## Possible Bugs or any ideas?
If you find a problem, let me know via github or https://forums.cacti.net

## Changelog
See [CHANGELOG.md](CHANGELOG.md)

-----------------------------------------------------------------------------
Copyright (c) 2004-2026 - The Cacti Group, Inc.
