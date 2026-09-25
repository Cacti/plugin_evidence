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
 * Creates all of this plugin's database tables (enterprise organizations,
 * SNMP system info, vendor-specific data-query definitions, Entity MIB
 * data, vendor-specific results, MAC addresses, IP addresses) and seeds
 * the vendor-specific data-query table with built-in OID definitions for
 * several common vendors (Aruba/HPE, Cisco, Fortinet, Mikrotik, QNAP,
 * Synology). Called from plugin_evidence_setup_database() during plugin
 * installation.
 *
 * @return void
 */
function plugin_evidence_initialize_database() {
	$data              = [];
	$data['columns'][] = ['name' => 'id', 'type' => 'int(11)', 'NULL' => false];
	$data['columns'][] = ['name' => 'organization', 'type' => 'varchar(200)', 'NULL' => false];
	$data['primary']   = 'id';
	$data['type']      = 'InnoDB';
	$data['comment']   = 'evidence organizations';
	api_plugin_db_table_create('evidence', 'plugin_evidence_organization', $data);

	$data              = [];
	$data['columns'][] = ['name' => 'host_id', 'type' => 'int(11)', 'NULL' => false];
	$data['columns'][] = ['name' => 'sysdescr', 'type' => 'varchar(255)', 'default' => null];
	$data['columns'][] = ['name' => 'syscontact', 'type' => 'varchar(255)', 'default' => null];
	$data['columns'][] = ['name' => 'sysname', 'type' => 'varchar(255)', 'default' => null];
	$data['columns'][] = ['name' => 'syslocation', 'type' => 'varchar(255)', 'default' => null];
	$data['columns'][] = ['name' => 'scan_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00'];
	$data['type']      = 'InnoDB';
	$data['comment']   = 'evidence snmp info';
	api_plugin_db_table_create('evidence', 'plugin_evidence_snmp_info', $data);

	$data              = [];
	$data['columns'][] = ['name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true];
	$data['columns'][] = ['name' => 'org_id', 'type' => 'int(11)', 'NULL' => false];
	$data['columns'][] = ['name' => 'sysobjectid', 'type' => 'varchar(255)', 'default' => null];
	$data['columns'][] = ['name' => 'description', 'type' => 'varchar(255)', 'NULL' => false];
	$data['columns'][] = ['name' => 'oid', 'type' => 'varchar(255)', 'NULL' => false];
	$data['columns'][] = ['name' => 'result', 'type' => 'varchar(255)', 'NULL' => false];
	$data['columns'][] = ['name' => 'method', 'type' => 'enum("get", "walk", "info", "table")', 'default' => 'get', 'NULL' => false];
	$data['columns'][] = ['name' => 'table_items', 'type' => 'varchar(100)', 'default' => null, 'NULL' => true];
	$data['columns'][] = ['name' => 'mandatory', 'type' => 'enum("yes","no")', 'default' => 'yes', 'NULL' => false];
	$data['primary']   = 'id';
	$data['type']      = 'InnoDB';
	$data['comment']   = 'evidence specific';
	api_plugin_db_table_create('evidence', 'plugin_evidence_specific_query', $data);

	$data              = [];
	$data['columns'][] = ['name' => 'host_id', 'type' => 'int(11)', 'NULL' => false];
	$data['columns'][] = ['name' => 'organization_id', 'type' => 'int(11)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'organization_name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'index', 'type' => 'int(11)', 'NULL' => false];
	$data['columns'][] = ['name' => 'descr', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'hardware_rev', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'firmware_rev', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'software_rev', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'serial_num', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'mfg_name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'model_name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'alias', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'asset_id', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'mfg_date', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'uuid', 'type' => 'varchar(255)', 'NULL' => false, 'default' => null];
	$data['columns'][] = ['name' => 'scan_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00'];
	$data['type']      = 'InnoDB';
	$data['comment']   = 'evidence entity mib data';
	api_plugin_db_table_create('evidence', 'plugin_evidence_entity', $data);

	$data              = [];
	$data['columns'][] = ['name' => 'host_id', 'type' => 'int(11)', 'NULL' => false];
	$data['columns'][] = ['name' => 'oid', 'type' => 'varchar(255)', 'default' => null];
	$data['columns'][] = ['name' => 'description', 'type' => 'varchar(255)', 'default' => null];
	$data['columns'][] = ['name' => 'value', 'type' => 'text', 'default' => null];
	$data['columns'][] = ['name' => 'mandatory', 'type' => 'enum("yes","no")', 'default' => 'yes', 'NULL' => false];
	$data['columns'][] = ['name' => 'scan_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00'];
	$data['type']      = 'InnoDB';
	$data['comment']   = 'evidence vendor specific data';
	api_plugin_db_table_create('evidence', 'plugin_evidence_vendor_specific', $data);

	$data              = [];
	$data['columns'][] = ['name' => 'host_id', 'type' => 'int(11)', 'NULL' => false];
	$data['columns'][] = ['name' => 'mac', 'type' => 'varchar(17)', 'default' => null];
	$data['columns'][] = ['name' => 'scan_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00'];
	$data['type']      = 'InnoDB';
	$data['comment']   = 'evidence entity mac address';
	api_plugin_db_table_create('evidence', 'plugin_evidence_mac', $data);

	$data              = [];
	$data['columns'][] = ['name' => 'host_id', 'type' => 'int(11)', 'NULL' => false];
	$data['columns'][] = ['name' => 'ip_mask', 'type' => 'varchar(79)', 'default' => null];
	$data['columns'][] = ['name' => 'scan_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00'];
	$data['type']      = 'InnoDB';
	$data['comment']   = 'evidence entity ip address';
	api_plugin_db_table_create('evidence', 'plugin_evidence_ip', $data);

	// vendor specific

	// Aruba/HPE
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[14823, 'Serial numbers', '.1.3.6.1.4.1.14823.2.3.3.1.2.1.1.4', '.*', 'walk']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[14823, 'version', '.1.3.6.1.4.1.14823.2.3.3.1.1.4.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[14823, 'hw model', '.1.3.6.1.4.1.14823.2.3.3.1.2.1.1.6', '.*', 'walk']);

	// Aruba instant AP cluster
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items)
		VALUES (?,?,?,?,?,?)',
		[14823, 'APs', '.1.3.6.1.4.1.14823.2.3.3.1.2.1.1', '.*', 'table', '1-mac,2-name,3-ip,4-serial,6-model']);

	// Aruba ap uptime is problem for history - so optional
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items, mandatory)
		VALUES (?,?,?,?,?,?,?)',
		[14823, 'APs_uptime', '.1.3.6.1.4.1.14823.2.3.3.1.2.1.1', '.*', 'table', '1-mac,2-name,9-uptime', 'no']);

	// Aruba Clearpass
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, sysobjectid, description, oid, result, method)
		VALUES (?,?,?,?,?,?)',
		[14823, '.1.3.6.1.4.1.14823.1.6.1', 'model', '.1.3.6.1.4.1.14823.1.6.1.1.1.1.1.1.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, sysobjectid, description, oid, result, method)
		VALUES (?,?,?,?,?,?)',
		[14823, '.1.3.6.1.4.1.14823.1.6.1', 'serial number', '.1.3.6.1.4.1.14823.1.6.1.1.1.1.1.2.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, sysobjectid, description, oid, result, method)
		VALUES (?,?,?,?,?,?)',
		[14823, '.1.3.6.1.4.1.14823.1.6.1', 'version', '.1.3.6.1.4.1.14823.1.6.1.1.1.1.1.3.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, sysobjectid, description, oid, result, method)
		VALUES (?,?,?,?,?,?)',
		[14823, '.1.3.6.1.4.1.14823.1.6.1', 'nodetype', '.1.3.6.1.4.1.14823.1.6.1.1.1.1.1.5.0', '.*', 'get']);

	// Cisco
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items)
		VALUES (?,?,?,?,?,?)',
		[9, 'switch', '.1.3.6.1.4.1.9.9.500.1.2.1.1', '.*', 'table', '3-role,4-priority,7-mac,8-swimage']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items)
		VALUES (?,?,?,?,?,?)',
		[5771, 'switch', '.1.3.6.1.4.1.9.9.500.1.2.1.1', '.*', 'table', '3-role,4-priority,7-mac,8-swimage']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items)
		VALUES (?,?,?,?,?,?)',
		[5842, 'switch', '.1.3.6.1.4.1.9.9.500.1.2.1.1', '.*', 'table', '3-role,4-priority,7-mac,8-swimage']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items)
		VALUES (?,?,?,?,?,?)',
		[53683, 'switch', '.1.3.6.1.4.1.9.9.500.1.2.1.1', '.*', 'table', '3-role,4-priority,7-mac,8-swimage']);

	// Cisco - mac on ports
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[9, 'Port mac addr', '.1.3.6.1.4.1.9.9.500.1.2.1.1.7', '.*', 'walk']);

	// Cisco - chassis
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items)
		VALUES (?,?,?,?,?,?)',
		[9, 'chassis', '.1.3.6.1.4.1.9.5.1.2', '.*', 'table', '16-chassis_model,17-chassis_sn,19-chassis_sn_string']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items)
		VALUES (?,?,?,?,?,?)',
		[9, 'chassis', '.1.3.6.1.4.1.9.3.6', '.*', 'table', '1-chassis_type,2-chassis_ver,3-chassis_id,5-chassis_romsysver']);

	// Fortinet
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[12356, 'serial', '.1.3.6.1.4.1.12356.100.1.1.1.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[12356, 'version', '.1.3.6.1.4.1.12356.101.4.1.1.0', '.*', 'get']);

	// Mikrotik
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[14988, 'serial', '.1.3.6.1.4.1.14988.1.1.7.3.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[14988, 'SW version', '.1.3.6.1.4.1.14988.1.1.4.4.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[14988, 'Firmware version', '.1.3.6.1.4.1.14988.1.1.7.4.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[14988, 'SW version', '.1.3.6.1.4.1.14988.1.1.17.1.1.4.1', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[14988, 'hw', '.1.3.6.1.2.1.47.1.1.1.1.2.65536', '([a-zA-Z0-9_-]){1,20}$', 'get']);

	// QNAP
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items)
		VALUES (?,?,?,?,?,?)',
		[24681, 'hw disks', '.1.3.6.1.4.1.24681.1.3.11.1', '.*', 'table', '2-name,5-type']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method, table_items, mandatory)
		VALUES (?,?,?,?,?,?,?)',
		[24681, 'hw disks info', '.1.3.6.1.4.1.24681.1.3.11.1', '.*', 'table', '2-name,3-temp,7-smart', 'no']);

	// Synology - Info - Synology has OrgID 6574, but uses 8072
	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[8072, 'serial', '.1.3.6.1.4.1.6574.1.5.2.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[8072, 'version', '.1.3.6.1.4.1.6574.1.5.3.0', '.*', 'get']);

	db_execute_prepared('INSERT INTO plugin_evidence_specific_query
		(org_id, description, oid, result, method)
		VALUES (?,?,?,?,?)',
		[8072, 'hw model', '.1.3.6.1.4.1.6574.1.5.1.0', '.*', 'get']);
}

/**
 * Applies any pending database schema migrations for this plugin, based
 * on comparing the installed version recorded in plugin_config against
 * the current INFO file version, then updates the recorded version.
 * Called from plugin_evidence_check_config() on every page load.
 *
 * @return void
 *
 * @global array $config Cacti global configuration array; used to
 *                       locate the plugin's INFO file.
 */
function plugin_evidence_upgrade_database() {
	global $config;

	$info = parse_ini_file($config['base_path'] . '/plugins/evidence/INFO', true);
	$info = isset($info['info']) && is_array($info['info']) ? $info['info'] : [];

	if (!isset($info['version'], $info['author'], $info['homepage'])) {
		cacti_log('ERROR: evidence plugin INFO file is missing required fields, skipping upgrade check', false, 'EVIDENCE');

		return;
	}

	$current = $info['version'];
	$oldv    = db_fetch_cell('SELECT version FROM plugin_config WHERE directory = "evidence"');

	if (!cacti_version_compare($oldv, $current, '=')) {
		if (cacti_version_compare($oldv, '0.3', '<')) {
			$data              = [];
			$data['columns'][] = ['name' => 'host_id', 'type' => 'int(11)', 'NULL' => false];
			$data['columns'][] = ['name' => 'sysdescr', 'type' => 'varchar(255)', 'default' => null];
			$data['columns'][] = ['name' => 'syscontact', 'type' => 'varchar(255)', 'default' => null];
			$data['columns'][] = ['name' => 'sysname', 'type' => 'varchar(255)', 'default' => null];
			$data['columns'][] = ['name' => 'syslocation', 'type' => 'varchar(255)', 'default' => null];
			$data['columns'][] = ['name' => 'scan_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00'];
			$data['type']      = 'InnoDB';
			$data['comment']   = 'evidence snmp info';
			api_plugin_db_table_create('evidence', 'plugin_evidence_snmp_info', $data);
		}

		// Set the new version
		db_execute_prepared("UPDATE plugin_config
			SET version = ?, author = ?, webpage = ?
			WHERE directory = 'evidence'",
			[
				$info['version'],
				$info['author'],
				$info['homepage']
			]
		);
	}
}
