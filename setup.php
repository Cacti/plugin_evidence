<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2021-2024 Petr Macek                                      |
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

function plugin_evidence_install () {

	api_plugin_register_hook('evidence', 'device_edit_top_links', 'plugin_evidence_device_edit_top_links', 'include/functions.php');
	api_plugin_register_hook('evidence', 'top_header_tabs', 'evidence_show_tab', 'include/functions.php');
	api_plugin_register_hook('evidence', 'top_graph_header_tabs', 'evidence_show_tab', 'include/functions.php');
	api_plugin_register_hook('evidence', 'host_device_remove', 'plugin_evidence_device_remove', 'include/functions.php');
	api_plugin_register_hook('evidence', 'config_settings', 'plugin_evidence_config_settings', 'include/settings.php');
	api_plugin_register_hook('evidence', 'poller_bottom', 'plugin_evidence_poller_bottom', 'include/functions.php');

	// only for jquery script
	api_plugin_register_hook('evidence', 'host_edit_bottom', 'plugin_evidence_host_edit_bottom', 'include/functions.php');

	api_plugin_register_realm('evidence', 'evidence.php,evidence_tab.php,', 'Plugin evidence - view', 1);

	plugin_evidence_setup_database();
}


function plugin_evidence_uninstall () {

	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_evidence_specific_query'")) > 0 ) {
		db_execute("DROP TABLE `plugin_evidence_specific_query`");
	}

	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_evidence_organization'")) > 0 ) {
		db_execute("DROP TABLE `plugin_evidence_organization`");
	}

	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_evidence_entity'")) > 0 ) {
		db_execute("DROP TABLE `plugin_evidence_entity`");
	}

	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_evidence_mac'")) > 0 ) {
		db_execute("DROP TABLE `plugin_evidence_mac`");
	}

	if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_evidence_vendor_specific'")) > 0 ) {
		db_execute("DROP TABLE `plugin_evidence_vendor_specific`");
	}
}


function plugin_evidence_version() {
	global $config;

	$info = parse_ini_file($config['base_path'] . '/plugins/evidence/INFO', true);
	return $info['info'];
}


function plugin_evidence_check_config () {
	global $config;

	include_once($config['base_path'] . '/plugins/evidence/include/database.php');
	plugin_evidence_upgrade_database();

	return true;
}


function plugin_evidence_setup_database() {
	global $config;

	include_once($config['base_path'] . '/plugins/evidence/include/database.php');
	plugin_evidence_initialize_database();
}
