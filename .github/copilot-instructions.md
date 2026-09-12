# GitHub Copilot Instructions

## Project Overview
The `evidence` plugin for Cacti collects and tracks hardware/software evidence for devices: Entity MIB data (serial numbers, part numbers, hardware/firmware/software revisions), MAC addresses, IP addresses, and vendor-specific SNMP data. It stores history of changes and can notify when evidence changes (e.g. a firmware upgrade or serial number swap).

## Priority Guidelines

When generating code for this repository:

1. **Version Compatibility**: This is a Cacti plugin (`evidence`) targeting Cacti 1.2.x
2. **Context Files**: Prioritize patterns and standards defined in this file (`.github/copilot-instructions.md`)
3. **Codebase Patterns**: When context files don't provide specific guidance, scan the codebase for established patterns
4. **Architectural Consistency**: Maintain plugin-based architecture extending Cacti core
5. **Code Quality**: Prioritize security, maintainability, and compatibility in all generated code

## Technology Stack

### Core Technologies
- **PHP**: Minimum PHP 8.2; CI verifies compatibility through PHP 8.4. `tests/Security/Php74CompatibilityTest.php` still guards `setup.php` against PHP 8.0+ only syntax (e.g. `str_contains()`, nullsafe operator `?->`, `match`, union types, constructor property promotion) as a legacy regression check.
- **Platform**: Cacti Plugin Architecture (Cacti 1.2.x)
- **Database**: MySQL/MariaDB with InnoDB engine
- **SNMP**: Cacti's SNMP library (`lib/snmp.php`, `cacti_snmp_get()`/`cacti_snmp_walk()`) for device polling

### Key Dependencies
- Cacti core framework (functions like `api_plugin_*`, `db_*`, `cacti_snmp_*`)
- PHP extensions: `snmp`, `mysqli`
- Optional: `gettext` for internationalization

## Testing Frameworks
Tests use [Pest](https://pestphp.com/) (which sits on top of PHPUnit). There is no local `composer.json`/`vendor` tree in this plugin repository; the project's CI workflow checks out a pinned Cacti release next to the plugin and runs Pest against Cacti's own Composer-managed vendor tree.
- `tests/bootstrap-unit.php` verifies the checked-out Cacti version matches `tests/.cacti-version`, then stubs the Cacti global functions (`db_*`, `read_config_option`, `__`, `cacti_log`, ...) that plugin source expects to already exist.
- `tests/TestCase.php` is a small `PHPUnit\Framework\TestCase` base class available to any test that prefers a class-based fixture.
- Tests that need a database or a fully running Cacti instance should instead be exercised against a local Cacti installation with the evidence plugin enabled; non-database-dependent logic should be unit tested with Pest.
- Test files are organized by intent under `tests/`: `tests/Security/` (hardening regressions), `tests/Unit/` (isolated helper behavior), `tests/Integration/` (multi-file wiring, still without a live Cacti), and `tests/E2E/` (source-level regression checks against full plugin files). Keep new tests in the directory matching their scope so `phpunit.xml`'s `<testsuite>` picks them up.

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
├── tests/                  # Pest/PHPUnit test suite (Security, Unit, Integration, E2E)
├── evidence.php             # Main standalone/console page
├── evidence_tab.php         # Device tab integration page
├── evidence.js               # Client-side JS for device edit page
├── poller_evidence.php       # Background poller entry point (CLI)
├── setup.php                 # Plugin install/uninstall/upgrade hooks
├── INFO                      # Plugin metadata (name, version, compat)
├── README.md                  # Feature overview, installation and usage
└── CHANGELOG.md               # Version history
```

### Plugin Structure
- **Entry Point:** `setup.php` registers all Cacti hooks (`api_plugin_register_hook`) and owns install/uninstall/upgrade (`plugin_evidence_install`, `plugin_evidence_uninstall`, `plugin_evidence_version`, `plugin_evidence_check_config`).
- **Core Logic:** `include/functions.php` contains the hook callbacks (device edit links, header tabs, poller bottom, host edit bottom) and the bulk of the plugin's business logic.
- **Database Schema:** `include/database.php` creates and upgrades the plugin's tables (`plugin_evidence_organization`, `plugin_evidence_snmp_info`, `plugin_evidence_entity`, `plugin_evidence_specific_query`, ...).
- **Settings:** `include/settings.php` registers the "Evidence" settings tab (`plugin_evidence_config_settings`), controlling polling frequency, base time, and history retention.
- **Static Data:** `include/arrays.php` defines the Entity MIB field labels and SNMP data type labels used throughout the UI.
- **Polling Integration:** `poller_evidence.php` is the CLI script invoked from the `poller_bottom` hook (see `include/functions.php`) to gather SNMP evidence data per device.
- **UI:** `evidence.php` and `evidence_tab.php` render the plugin's pages and the per-device Evidence tab.

### Data Flow
1. **Data Collection:** Cacti's poller triggers `plugin_evidence_poller_bottom` (in `include/functions.php`), which schedules `poller_evidence.php`.
2. **Collection:** `poller_evidence.php` walks the configured SNMP OIDs (Entity MIB, MACs, IPs, vendor-specific data) for each device.
3. **Storage:** Results are written to the `plugin_evidence_*` tables via `include/database.php`; changes are diffed against prior history.
4. **Display:** `evidence.php` / `evidence_tab.php` present current and historical evidence to the user.

### Installation & Setup
- **Location:** Code resides in `plugins/evidence/` within the Cacti base directory.
- **Activation:** Install and enable via Cacti Plugin Management, then configure under Console -> Settings -> Evidence.

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
- Access Cacti configuration via the global `$config` array, e.g. `$config['base_path'] . '/plugins/evidence/...'`; never hardcode `plugins/evidence`'s location

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

### Output Escaping and Redirects
- Escape output with `html_escape()`/`__esc()` before printing anything derived from user or device data.
- Follow any `header('Location: ...')` redirect with `exit`/`die` so execution never continues past a redirect.

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

### Quick Reference
- `db_execute($sql)` / `db_execute_prepared($sql, $params)` for writes.
- `db_fetch_assoc($sql)` / `db_fetch_row($sql)` / `db_fetch_cell($sql)` (and their `_prepared` variants) for reads.
- `api_plugin_db_table_create()` / `api_plugin_db_add_column()` for schema management.
- All plugin tables are prefixed with `plugin_evidence_`.

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

### Reference: Cacti Database Functions
Some database functions are provided by the Cacti project itself (see [Cacti DB Functions](https://github.com/Cacti/cacti/blob/1.2.x/lib/database.php)):
- `db_fetch_row($result)` / `db_fetch_assoc($result)`: Fetch a single row from a result set as an associative array.
- `db_query($query)`: Executes a SQL query and returns the result set.
- `db_insert($table, $data)`: Inserts a new record into the specified table.
- `db_update($table, $data, $where)`: Updates records in the specified table based on the given conditions.
- `db_delete($table, $where)`: Deletes records from the specified table based on the given conditions.
- `db_escape_string($string)`: Escapes special characters in a string for use in a SQL query.
- `db_num_rows($result)`: Returns the number of rows in the result set.
- `db_last_insert_id()`: Retrieves the ID of the last inserted record.

## Internationalization

### Translation Wrapping
ALL user-facing strings MUST use the `__()` function (or `__esc()` when the value needs to be escaped for output) with the `'evidence'` text domain:

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
- Validate all user input using Cacti's validation functions (`form_input_validate()`, `get_filter_request_var()`, etc.)
- Verify device access via `plugin_evidence_get_allowed_devices()` before displaying host data
- Never trust user input in file operations

### 3. Cacti Integration
- Use Cacti's API functions (`api_plugin_*`, `db_*`, `cacti_snmp_*`)
- Follow Cacti's plugin architecture requirements
- Respect Cacti's configuration options and settings

### 4. Internationalization
- Wrap ALL user-facing strings with `__('text', 'evidence')` / `__esc('text', 'evidence')`
- Never use plain strings for labels, messages, or UI text
- Keep text domain consistent (`evidence`)

### 5. SNMP Resilience
- Suppress and check SNMP calls (`@cacti_snmp_get()`/`@cacti_snmp_walk()`) since not all devices support every OID
- Treat missing/failed SNMP data as "not available" rather than a hard error

### 6. Performance
- Limit history retention based on the `evidence_records` setting
- Avoid unnecessary polling; respect `evidence_frequency`/`evidence_base_time`

### 7. Testing & Safety
- Test changes in a safe environment, especially anything touching database interactions or SNMP collection
- Prefer Pest unit tests for non-database-dependent logic; validate database/live-poller behavior against a real Cacti install with the plugin enabled

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
- [Cacti API / DB Functions](https://github.com/Cacti/cacti/blob/1.2.x/lib/database.php)
- [Cacti Documentation](https://www.github.com/Cacti/documentation)
- Project README.md for feature descriptions
- CHANGELOG.md for version history
