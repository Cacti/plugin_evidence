# GitHub Copilot Instructions

## Priority Guidelines

When generating code for this repository:

1. **Version Compatibility**: This is a Cacti plugin (`evidence`, version 0.3) targeting Cacti 1.2.x
2. **Context Files**: Prioritize patterns and standards defined in this file (`.github/copilot-instructions.md`)
3. **Codebase Patterns**: When context files don't provide specific guidance, scan the codebase for established patterns
4. **Architectural Consistency**: Maintain plugin-based architecture extending Cacti core
5. **Code Quality**: Prioritize security, maintainability, and compatibility in all generated code

## Technology Stack

### Core Technologies
- **PHP**: Minimum PHP 8.1
- **Platform**: Cacti Plugin Architecture (Cacti 1.2.x)
- **Database**: MySQL/MariaDB with InnoDB engine
- **SNMP**: Cacti's SNMP library (`lib/snmp.php`, `cacti_snmp_get()`/`cacti_snmp_walk()`) for device polling

### Key Dependencies
- Cacti core framework (functions like `api_plugin_*`, `db_*`, `cacti_snmp_*`)
- PHP extensions: `snmp`, `mysqli`
- Optional: `gettext` for internationalization

## Project Structure

```
evidence/                  # Repository root (install to plugins/evidence/ in Cacti)
├── include/
│   ├── functions.php       # Core polling, display and hook logic
│   ├── database.php        # Table creation and upgrade logic
│   ├── settings.php        # Plugin config_settings hook
│   ├── arrays.php          # Configuration arrays (entities, datatypes)
│   └── index.php           # Access protection
├── data/                   # SQL seed data (enterprise-numbers.sql) and prep scripts
├── images/                 # Tab icons and UI images
├── evidence.php             # Main standalone/console page
├── evidence_tab.php         # Device tab integration page
├── evidence.js               # Client-side JS for device edit page
├── poller_evidence.php       # Background poller entry point (CLI)
├── setup.php                 # Plugin install/uninstall/upgrade hooks
├── INFO                      # Plugin metadata (name, version, compat)
├── README.md                  # Feature overview, installation and usage
└── CHANGELOG.md               # Version history
```

## Naming Conventions

### Function Names

#### Plugin Hook Functions
Functions that integrate with Cacti's plugin system MUST be prefixed with `plugin_evidence_`:

```php
function plugin_evidence_install() { }
function plugin_evidence_poller_bottom() { }
function plugin_evidence_config_settings() { }
function plugin_evidence_device_remove($device_id) { }
```

#### Internal/Display Functions
Other functions MUST be prefixed with `evidence_`:

```php
function evidence_show_tab() { }
function evidence_show_host_info($data, $host_id) { }
```

**IMPORTANT**: Match the existing prefix used by the function you are editing; do not introduce a third naming scheme.

### Database Tables
All database tables MUST be prefixed with `plugin_evidence_`:

```
plugin_evidence_organization
plugin_evidence_snmp_info
plugin_evidence_specific_query
plugin_evidence_entity
plugin_evidence_mac
plugin_evidence_ip
plugin_evidence_vendor_specific
```

### Variables and Constants
- Use snake_case for variables: `$host_id`, `$evidence_records`, `$snmp_info`
- Global configuration arrays use descriptive names: `$entities`, `$datatypes`

## Code Style

### Indentation and Formatting
- **Tabs**: Use tabs (not spaces) for indentation throughout all PHP files
- **Braces**: Opening brace on same line for functions and control structures
- **Spacing**: Space after control structure keywords (`if`, `foreach`, `while`)

```php
function evidence_example($param) {
	if ($param > 0) {
		foreach ($items as $item) {
			// code here
		}
	}
}
```

### File Headers
ALL PHP files MUST include the standard GPL v2 license header used throughout this repository:

```php
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or          |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2         |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/
```

## Security Standards

### SQL Query Security
**ALWAYS use prepared statements** for database operations - never concatenate user input into SQL:

```php
// CORRECT - Always use prepared statements
$host = db_fetch_row_prepared('SELECT * FROM host WHERE id = ?', array($id));

$count = db_fetch_assoc_prepared('SELECT count(*) FROM plugin_evidence_entity
	WHERE host_id = ?', array($id));

db_execute_prepared('DELETE FROM plugin_evidence_mac WHERE host_id = ?', array($device_id));

// WRONG - Never do this
$result = db_fetch_row("SELECT * FROM host WHERE id = $id");
```

### Input Validation
Use Cacti's built-in input validation functions:

```php
// For filtered request variables with validation
$id = get_filter_request_var('host_id');

// Check permission before returning data for a device
$allowed = plugin_evidence_get_allowed_devices($_SESSION['sess_user_id'], true);
if (is_array($allowed) && in_array($id, $allowed)) {
	// proceed
} else {
	print __('Permission issue', 'evidence');
}
```

### SNMP Data Handling
Always suppress and check SNMP results defensively, since devices may not support every OID:

```php
$data = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.11',
	$h['snmp_version'], $h['snmp_username'], $h['snmp_password'],
	$h['snmp_auth_protocol'], $h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
	$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);

if (!is_array($data)) {
	// treat as unavailable rather than erroring
}
```

## Database Operations

### Table Creation
Use Cacti's `api_plugin_db_table_create()` function with proper structure:

```php
$data = array();
$data['columns'][] = array('name' => 'host_id', 'type' => 'int(11)', 'NULL' => false);
$data['columns'][] = array('name' => 'sysdescr', 'type' => 'varchar(255)', 'default' => null);
$data['type'] = 'InnoDB';
$data['comment'] = 'evidence snmp info';

api_plugin_db_table_create('evidence', 'plugin_evidence_snmp_info', $data);
```

### Upgrade Handling
Version-gate schema changes in `plugin_evidence_upgrade_database()` (`include/database.php`) using `cacti_version_compare()`, and always update the stored version at the end:

```php
function plugin_evidence_upgrade_database() {
	global $config;

	$info    = parse_ini_file($config['base_path'] . '/plugins/evidence/INFO', true);
	$info    = $info['info'];
	$current = $info['version'];
	$oldv    = db_fetch_cell('SELECT version FROM plugin_config WHERE directory = "evidence"');

	if (!cacti_version_compare($oldv, $current, '=')) {
		if (cacti_version_compare($oldv, '0.3', '<')) {
			// create/alter tables here
		}

		db_execute_prepared("UPDATE plugin_config
			SET version = ?, author = ?, webpage = ?
			WHERE directory = 'evidence'",
			array($info['version'], $info['author'], $info['homepage']));
	}
}
```

## Internationalization

### Translation Wrapping
ALL user-facing strings MUST use the `__()` function with the `'evidence'` text domain:

```php
// CORRECT
print __('Disabled/down device. No actual data', 'evidence') . '<br/>';
$datatypes = array(
	'info'   => __('SNMP info', 'evidence'),
	'entity' => __('Entity MIB', 'evidence'),
);

// WRONG - Never use plain strings for user-facing text
print 'Disabled/down device';  // Missing translation
```

## Plugin Architecture

### Plugin Hooks
Register all plugin hooks in `plugin_evidence_install()` (`setup.php`):

```php
function plugin_evidence_install() {
	api_plugin_register_hook('evidence', 'device_edit_top_links', 'plugin_evidence_device_edit_top_links', 'include/functions.php');
	api_plugin_register_hook('evidence', 'top_header_tabs', 'evidence_show_tab', 'include/functions.php');
	api_plugin_register_hook('evidence', 'top_graph_header_tabs', 'evidence_show_tab', 'include/functions.php');
	api_plugin_register_hook('evidence', 'host_device_remove', 'plugin_evidence_device_remove', 'include/functions.php');
	api_plugin_register_hook('evidence', 'config_settings', 'plugin_evidence_config_settings', 'include/settings.php');
	api_plugin_register_hook('evidence', 'poller_bottom', 'plugin_evidence_poller_bottom', 'include/functions.php');
	api_plugin_register_hook('evidence', 'host_edit_bottom', 'plugin_evidence_host_edit_bottom', 'include/functions.php');

	api_plugin_register_realm('evidence', 'evidence.php,evidence_tab.php,', 'Plugin evidence - view', 1);

	plugin_evidence_setup_database();
}
```

### Poller Integration
Background collection runs via `poller_evidence.php`, invoked from `plugin_evidence_poller_bottom()` using `exec_background()` and gated by `plugin_evidence_time_to_run()` (which honors the `evidence_frequency` and `evidence_base_time` settings):

```php
function plugin_evidence_poller_bottom() {
	global $config;

	if (plugin_evidence_time_to_run()) {
		include_once($config['library_path'] . '/poller.php');
		$command_string = trim(read_config_option('path_php_binary'));

		if (trim($command_string) == '') {
			$command_string = 'php';
		}

		$extra_args = ' -q ' . $config['base_path'] . '/plugins/evidence/poller_evidence.php --id=all';

		exec_background($command_string, $extra_args);
	}
}
```

## Configuration Arrays

Define configuration in `include/arrays.php` and settings in `include/settings.php`:

```php
// include/arrays.php
$datatypes = array(
	'info'   => __('SNMP info', 'evidence'),
	'entity' => __('Entity MIB', 'evidence'),
	'mac'    => __('Mac addresses', 'evidence'),
	'ip'     => __('IP addresses', 'evidence'),
);

// include/settings.php - registered via the config_settings hook
$settings['evidence'] = array(
	'evidence_frequency' => array(
		'friendly_name' => 'How often gather data',
		'method'        => 'drop_array',
		'array'         => array('0' => 'Disabled', '6' => 'Every 6 hours', '24' => 'Every day', '168' => 'Every week'),
		'default'       => '24',
	),
);
```

## Best Practices

### 1. Consistency Over Innovation
- Match existing code patterns exactly
- Don't introduce new patterns without documented reason
- Follow established naming conventions without exception

### 2. Security First
- Always use prepared statements for SQL
- Validate all user input using Cacti's validation functions (`get_filter_request_var()`, etc.)
- Verify device access via `plugin_evidence_get_allowed_devices()` before displaying host data
- Never trust user input in file operations

### 3. Cacti Integration
- Use Cacti's API functions (`api_plugin_*`, `db_*`, `cacti_snmp_*`)
- Follow Cacti's plugin architecture requirements
- Respect Cacti's configuration options and settings

### 4. Internationalization
- Wrap ALL user-facing strings with `__('text', 'evidence')`
- Never use plain strings for labels, messages, or UI text
- Keep text domain consistent (`evidence`)

### 5. SNMP Resilience
- Suppress and check SNMP calls (`@cacti_snmp_get()`/`@cacti_snmp_walk()`) since not all devices support every OID
- Treat missing/failed SNMP data as "not available" rather than a hard error

### 6. Performance
- Limit history retention based on the `evidence_records` setting
- Avoid unnecessary polling; respect `evidence_frequency`/`evidence_base_time`

## Common Pitfalls to Avoid

### ❌ NEVER Do This
```php
// Don't concatenate SQL queries
$sql = "SELECT * FROM host WHERE id = $id";  // WRONG

// Don't use hardcoded strings for UI
print 'Permission issue';  // WRONG

// Don't use spaces for indentation
    if ($condition) {  // WRONG (spaces used)

// Don't skip input validation
$id = $_GET['host_id'];  // WRONG
```

### ✅ ALWAYS Do This
```php
// Use prepared statements
$host = db_fetch_row_prepared('SELECT * FROM host WHERE id = ?', array($id));  // CORRECT

// Translate all user-facing strings
print __('Permission issue', 'evidence');  // CORRECT

// Use tabs for indentation
	if ($condition) {  // CORRECT (tabs used)

// Always validate input
$id = get_filter_request_var('host_id');  // CORRECT
```

## Version Control

### Changelog Maintenance
Document all changes in `CHANGELOG.md`:

```markdown
--- 0.3 ---
* Add generic snmp info
```

### Commit Messages
Follow the established pattern from git history:
- Use descriptive commit messages
- Reference issue numbers when applicable
- Group related changes logically

## References

- Cacti Plugin Development Guide
- Cacti API Documentation
- Project README.md for feature descriptions
- CHANGELOG.md for version history
