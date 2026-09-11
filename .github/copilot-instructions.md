# Cacti Evidence Plugin AI Instructions

## Project Overview
The `evidence` plugin for Cacti collects and tracks hardware/software evidence for devices: Entity MIB data (serial numbers, part numbers, hardware/firmware/software revisions), MAC addresses, IP addresses, and vendor-specific SNMP data. It stores history of changes and can notify when evidence changes (e.g. a firmware upgrade or serial number swap).

## Architecture & Core Components

### Technology Stack
The plugin is developed in PHP and integrates tightly with the Cacti monitoring platform. It leverages Cacti's existing database abstraction layer, SNMP helpers, and plugin architecture.
In this repo, the code adheres to PHP PSR-12 coding standards and best practices, while remaining compatible with PHP 7.4 (Cacti 1.2.x baseline).

## Testing Frameworks
Tests use [Pest](https://pestphp.com/) (which sits on top of PHPUnit). There is no local `composer.json`/`vendor` tree in this plugin repository; the CI workflow checks out a pinned Cacti release next to the plugin and runs Pest against Cacti's own Composer-managed vendor tree (see `.github/workflows/php-unit-tests.yml`).
- `tests/bootstrap-unit.php` verifies the checked-out Cacti version matches `tests/.cacti-version`, then stubs the Cacti global functions (`db_*`, `read_config_option`, `__`, `cacti_log`, ...) that plugin source expects to already exist.
- `tests/TestCase.php` is a small `PHPUnit\Framework\TestCase` base class available to any test that prefers a class-based fixture.
- Tests that need a database or a fully running Cacti instance should instead be exercised against a local Cacti installation with the evidence plugin enabled; non-database-dependent logic should be unit tested with Pest.
- Test files are organized by intent under `tests/`: `tests/Security/` (hardening regressions), `tests/Unit/` (isolated helper behavior), `tests/Integration/` (multi-file wiring, still without a live Cacti), and `tests/E2E/` (source-level regression checks against full plugin files). Keep new tests in the directory matching their scope so `phpunit.xml`'s `<testsuite>` picks them up.

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

## Developer Workflows

### Installation & Setup
- **Location:** Code resides in `plugins/evidence/` within the Cacti base directory.
- **Activation:** Install and enable via Cacti Plugin Management, then configure under Console -> Settings -> Evidence.

### Database Interaction
- **Abstraction:** Use Cacti's global database functions:
  - `db_execute($sql)` / `db_execute_prepared($sql, $params)` for writes.
  - `db_fetch_assoc($sql)` / `db_fetch_row($sql)` / `db_fetch_cell($sql)` (and their `_prepared` variants) for reads.
  - `api_plugin_db_table_create()` / `api_plugin_db_add_column()` for schema management.
- **Tables:** All plugin tables are prefixed with `plugin_evidence_`.

### Localization
- Use `__('String', 'evidence')` / `__esc('String', 'evidence')` for all user-facing strings to support internationalization.

## Coding Conventions
- **Naming:** All functions should be prefixed with `plugin_evidence_` (hooks) or otherwise scoped clearly to the plugin.
- **Globals:** Access Cacti configuration via the global `$config` array, e.g. `$config['base_path'] . '/plugins/evidence/...'`.
- **Pathing:** Use `$config['base_path']` for absolute file paths; never hardcode `plugins/evidence`'s location.
- **Security:** Sanitize inputs using Cacti's input validation functions (`form_input_validate`, `get_filter_request_var`, etc.), escape output with `html_escape`/`__esc`, always use `_prepared` database calls with real placeholders, and follow any `header('Location: ...')` redirect with `exit`/`die`.

## Key Files
- `setup.php`: Hook registration and install/uninstall/upgrade.
- `include/functions.php`: Hook callbacks and shared logic.
- `include/database.php`: Schema creation/upgrade.
- `include/settings.php`: Settings UI.
- `include/arrays.php`: Entity MIB / datatype label maps.
- `poller_evidence.php`: SNMP collection CLI script.

## Important Notes
- The plugin relies heavily on Cacti's built-in functions and architecture. Familiarity with Cacti development practices is essential.
- Testing changes in a safe environment is crucial, especially when dealing with database interactions and SNMP collection.
- Some database functions are provided by the Cacti project itself. Here are some of the commonly used functions:

## you can find the included file in the cacti project here:
- [Cacti DB Functions](https://github.com/Cacti/cacti/blob/1.2.x/lib/database.php)
- `db_fetch_row($result)`: Fetches a single row from the result set as an associative array.
- `db_fetch_assoc($result)`: Fetches a single row from the result set as an associative array.
- `db_query($query)`: Executes a SQL query and returns the result set.
- `db_insert($table, $data)`: Inserts a new record into the specified table.
- `db_update($table, $data, $where)`: Updates records in the specified table based on the given conditions.
- `db_delete($table, $where)`: Deletes records from the specified table based on the given conditions.
- `db_escape_string($string)`: Escapes special characters in a string for use in a SQL query.
- `db_num_rows($result)`: Returns the number of rows in the result set.
- `db_last_insert_id()`: Retrieves the ID of the last inserted record.

## web documentation
- [Cacti Documentation](https://www.github.com/Cacti/documentation)
