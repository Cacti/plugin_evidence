<?php
/* vim: ts=4
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group, Inc.                           |
 | Copyright (C) 2004-2024 Petr Macek                                      |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
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
 | https://github.com/xmacan/                                              |
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/**
 * Return the CSP nonce attribute for inline <script> tags, safely across
 * Cacti versions. Newer Cacti releases enforce a Content-Security-Policy that
 * requires a per-request nonce on parser-inserted scripts; older releases lack
 * the CactiSecureHeaders class, so this returns an empty string there.
 *
 * @return string The nonce attribute when supported, otherwise empty string.
 */
function plugin_evidence_csp_nonce(): string {
	if (class_exists('CactiSecureHeaders')) {
		return CactiSecureHeaders::getNonceAttribute();
	}

	return '';
}

/**
 * Registers this plugin's Cacti hooks (device edit links, tab display,
 * device removal cleanup, settings, poller_bottom, host edit page) and
 * the evidence.php/evidence_tab.php realm, then creates the plugin's
 * database tables. Invoked by the Cacti plugin framework when the
 * plugin is installed/enabled.
 *
 * @return void
 */
function plugin_evidence_install() {
	api_plugin_register_hook('evidence', 'device_edit_top_links', 'plugin_evidence_device_edit_top_links', 'includes/functions.php');
	api_plugin_register_hook('evidence', 'top_header_tabs', 'evidence_show_tab', 'includes/functions.php');
	api_plugin_register_hook('evidence', 'top_graph_header_tabs', 'evidence_show_tab', 'includes/functions.php');
	api_plugin_register_hook('evidence', 'device_remove', 'plugin_evidence_device_remove', 'includes/functions.php');
	api_plugin_register_hook('evidence', 'config_settings', 'plugin_evidence_config_settings', 'includes/settings.php');
	api_plugin_register_hook('evidence', 'poller_bottom', 'plugin_evidence_poller_bottom', 'includes/functions.php');
	api_plugin_register_hook('evidence', 'host_edit_bottom', 'plugin_evidence_host_edit_bottom', 'includes/functions.php');

	api_plugin_register_realm('evidence', 'evidence.php,evidence_tab.php,', 'Plugin evidence - view', 1);

	plugin_evidence_setup_database();
}

/**
 * No-op uninstall hook; this plugin does not remove its database tables
 * on uninstall (see plugin_evidence_remove_data() for that). Invoked by
 * the Cacti plugin framework when the plugin is uninstalled.
 *
 * @return bool Always true.
 */
function plugin_evidence_uninstall() {
	return true;
}

/**
 * Reads this plugin's version/author metadata from its INFO file.
 * Invoked by the Cacti plugin framework to display plugin information,
 * and called directly by poller_evidence.php's display_version().
 *
 * @return array The plugin's INFO file 'info' section (name, version,
 *               author, etc.).
 */
function plugin_evidence_version() {
	global $config;

	$info = parse_ini_file($config['base_path'] . '/plugins/evidence/INFO', true);

	return isset($info['info']) && is_array($info['info']) ? $info['info'] : [];
}

/**
 * Runs any pending database schema upgrades for this plugin. Invoked by
 * the Cacti plugin framework on every page load to keep the plugin's
 * schema current.
 *
 * @return bool Always true.
 *
 * @global array $config Cacti global configuration array; used to
 *                       locate include/database.php.
 */
function plugin_evidence_check_config() {
	global $config;

	require_once($config['base_path'] . '/plugins/evidence/includes/database.php');
	plugin_evidence_upgrade_database();

	return true;
}

/**
 * Creates this plugin's database tables via include/database.php's
 * plugin_evidence_initialize_database(). Called from
 * plugin_evidence_install() during plugin installation.
 *
 * @return void
 *
 * @global array $config Cacti global configuration array; used to
 *                       locate include/database.php.
 */
function plugin_evidence_setup_database() {
	global $config;

	require_once($config['base_path'] . '/plugins/evidence/includes/database.php');
	plugin_evidence_initialize_database();
}

/**
 * Indicates that this plugin stores data that a user may want to remove
 * on uninstall. Invoked by the Cacti plugin framework to decide whether
 * to offer a data-removal option during uninstall.
 *
 * @return bool Always true.
 */
function plugin_evidence_has_data() {
	return true;
}

/**
 * Drops all of this plugin's database tables. Invoked by the Cacti
 * plugin framework when the user opts to remove plugin data during
 * uninstall.
 *
 * @return bool Always true.
 */
function plugin_evidence_remove_data() {
	db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_specific_query`');
	db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_organization`');
	db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_entity`');
	db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_mac`');
	db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_ip`');
	db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_vendor_specific`');
	db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_snmp_info`');

	return true;
}

/**
 * Removes files and directories that a previous version of this plugin
 * shipped but that have since moved or been deleted, using the tombstone
 * and whitelist lists in manifest.json. Whitelisted (user-data) paths and
 * any VCS metadata (.git*) are never touched; the dev-only tests/ tree is
 * removed. Any path that resolves outside the plugin directory (a tampered
 * manifest.json) is refused, and any file/directory that cannot be removed
 * (e.g. read-only) is reported to the Cacti log. Any top-level entry that is
 * neither expected nor a tombstone nor whitelisted is logged to the Cacti
 * log and left in place. Called on a plugin version change.
 *
 * @return void
 *
 * @global array $config Cacti global configuration array; used to resolve
 *                       the plugin directory.
 */
function plugin_evidence_prune_files(): void {
	global $config;

	$plugin_dir    = $config['base_path'] . '/plugins/evidence';
	$manifest_path = $plugin_dir . '/manifest.json';

	if (!is_readable($manifest_path)) {
		return;
	}

	$manifest = json_decode((string) file_get_contents($manifest_path), true);

	if (!is_array($manifest)) {
		cacti_log('WARNING: evidence manifest.json could not be parsed; skipping file prune', false, 'EVIDENCE');

		return;
	}

	$tombstones = isset($manifest['tombstones']) && is_array($manifest['tombstones']) ? $manifest['tombstones'] : [];
	$expected   = isset($manifest['expected'])   && is_array($manifest['expected'])   ? $manifest['expected']   : [];
	$whitelist  = isset($manifest['whitelist'])  && is_array($manifest['whitelist'])  ? $manifest['whitelist']  : [];

	$protected = function (string $rel) use ($whitelist): bool {
		if (strncmp($rel, '.git', 4) === 0 || strncmp($rel, '.md', 3) === 0) {
			return true;
		}

		foreach ($whitelist as $entry) {
			$entry = trim((string) $entry, '/');

			if ($entry !== '' && ($rel === $entry || strncmp($rel, $entry . '/', strlen($entry) + 1) === 0)) {
				return true;
			}
		}

		return false;
	};

	// Security: resolve the plugin directory so a tampered manifest.json
	// cannot steer the prune outside of it.
	$plugin_real = realpath($plugin_dir);

	// Remove tombstoned (moved/deleted) paths plus the dev-only tests/
	// tree and the phpunit.xml test configuration.
	$remove   = $tombstones;
	$remove[] = 'tests/';
	$remove[] = 'phpunit.xml';

	foreach ($remove as $rel) {
		$rel = trim((string) $rel, '/');

		if ($rel === '' || $protected($rel)) {
			continue;
		}

		$path = $plugin_dir . '/' . $rel;

		if (!is_link($path) && !file_exists($path)) {
			continue;
		}

		// Refuse any path that, after resolving symlinks and ../ segments,
		// escapes the plugin directory (protects user data from a tampered
		// manifest.json).
		$anchor = is_link($path) ? dirname($path) : $path;
		$real   = realpath($anchor);

		if ($real === false || ($real !== $plugin_real && strncmp($real, $plugin_real . DIRECTORY_SEPARATOR, strlen((string) $plugin_real) + 1) !== 0)) {
			cacti_log(sprintf('WARNING: evidence prune refused to remove %s: path resolves outside the plugin directory (tampered manifest.json?)', $rel), false, 'EVIDENCE');

			continue;
		}

		if (is_dir($path) && !is_link($path)) {
			$removed = plugin_evidence_rmtree($path);
		} else {
			$removed = @unlink($path);
		}

		if (!$removed) {
			cacti_log(sprintf('WARNING: evidence upgrade could not remove %s (check file/directory permissions)', $rel), false, 'EVIDENCE');
		}
	}

	// Surface any top-level entry the manifest does not account for.
	$known = [];

	foreach (array_merge($expected, $tombstones) as $entry) {
		$top = explode('/', trim((string) $entry, '/'))[0];

		if ($top !== '') {
			$known[$top] = true;
		}
	}

	$entries = scandir($plugin_dir);

	foreach (($entries !== false ? $entries : []) as $entry) {
		if ($entry === '.' || $entry === '..' || $entry === 'tests' || $entry === 'phpunit.xml' || $protected($entry) || isset($known[$entry])) {
			continue;
		}

		cacti_log(sprintf('WARNING: evidence upgrade found a file/directory not described in manifest.json: %s (left in place)', $entry), false, 'EVIDENCE');
	}
}

/**
 * Recursively deletes a directory and its contents. Symlinks are removed
 * without being followed. Helper for plugin_evidence_prune_files().
 *
 * @param string $dir Absolute path to the directory to remove.
 *
 * @return bool True if the directory and everything under it was removed;
 *              false if any entry could not be deleted.
 */
function plugin_evidence_rmtree(string $dir): bool {
	$entries = scandir($dir);
	$ok      = true;

	foreach (($entries !== false ? $entries : []) as $entry) {
		if ($entry === '.' || $entry === '..') {
			continue;
		}

		$path = $dir . '/' . $entry;

		if (is_dir($path) && !is_link($path)) {
			if (!plugin_evidence_rmtree($path)) {
				$ok = false;
			}
		} elseif (!@unlink($path)) {
			$ok = false;
		}
	}

	if (!@rmdir($dir)) {
		$ok = false;
	}

	return $ok;
}
