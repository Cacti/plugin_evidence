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

chdir('../../');
include_once('./include/auth.php');
include_once('./lib/snmp.php');
include_once('./plugins/evidence/include/functions.php');

$evidence_records = read_config_option('evidence_records');

$id = get_filter_request_var('host_id');

$allowed = plugin_evidence_get_allowed_devices($_SESSION['sess_user_id'], true);

if (is_array($allowed) && in_array($id, $allowed)) {

	$host = db_fetch_row_prepared ('SELECT *
		FROM host
		WHERE id = ?',
		array($id));

	$count_entity = db_fetch_assoc_prepared('SELECT count(*)
		FROM plugin_evidence_entity
		WHERE host_id = ?',
		array($id));

	$count_mac = db_fetch_assoc_prepared('SELECT count(*)
		FROM plugin_evidence_mac
		WHERE host_id = ?',
		array($id));

	$count_ip = db_fetch_assoc_prepared('SELECT count(*)
		FROM plugin_evidence_ip
		WHERE host_id = ?',
		array($id));

	$count_vendor = db_fetch_assoc_prepared('SELECT count(*)
		FROM plugin_evidence_vendor_specific
		WHERE host_id = ?',
		array($id));

	if ($host['disabled'] == 'on' || ($host['status'] != 2 && $host['status'] != 3)) {
		print __('Disabled/down device. No actual data', 'evidence') . '<br/>';
	} else {
		evidence_show_actual_data(plugin_evidence_actual_data($host));
	}

	if ($evidence_records > 0 && ($count_entity > 0 || $count_mac > 0 || $count_ip > 0 || $count_vendor > 0)) {
		print '<br/><br/><a href="' . $config['url_path'] . 'plugins/evidence/evidence_tab.php?host_id=' .
			$id . '&action=find">' . __('Show older records', 'evidence') . '</a><br/>';
	} else {
		print '<br/><br/>' . __('History data store disabled', 'evidence') . '<br/><br/>';
	}
} else {
	print __('Permission issue', 'evidence');
}

