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
 * Registers this plugin's Cacti hooks (device edit links, tab display,
 * device removal cleanup, settings, poller_bottom, host edit page) and
 * the evidence.php/evidence_tab.php realm, then creates the plugin's
 * database tables. Invoked by the Cacti plugin framework when the
 * plugin is installed/enabled.
 *
 * @return void
 */
function plugin_evidence_install() {
	api_plugin_register_hook('evidence', 'device_edit_top_links', 'plugin_evidence_device_edit_top_links', 'include/functions.php');
	api_plugin_register_hook('evidence', 'top_header_tabs', 'evidence_show_tab', 'include/functions.php');
	api_plugin_register_hook('evidence', 'top_graph_header_tabs', 'evidence_show_tab', 'include/functions.php');
	api_plugin_register_hook('evidence', 'device_remove', 'plugin_evidence_device_remove', 'include/functions.php');
	api_plugin_register_hook('evidence', 'config_settings', 'plugin_evidence_config_settings', 'include/settings.php');
	api_plugin_register_hook('evidence', 'poller_bottom', 'plugin_evidence_poller_bottom', 'include/functions.php');
	api_plugin_register_hook('evidence', 'host_edit_bottom', 'plugin_evidence_host_edit_bottom', 'include/functions.php');

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

	include_once($config['base_path'] . '/plugins/evidence/include/database.php');
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

	include_once($config['base_path'] . '/plugins/evidence/include/database.php');
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
