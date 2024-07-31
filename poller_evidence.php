<?php
/* vim: ts=4
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2024 The Cacti Group, Inc.                           |
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

$dir = dirname(__FILE__);
chdir($dir);

include('../../include/cli_check.php');
include_once($config['library_path'] . '/snmp.php');
include_once($config['base_path'] . '/plugins/evidence/include/functions.php');


/* let PHP run just as long as it has to */
ini_set('max_execution_time', '0');

error_reporting(E_ALL);

/* record the start time */
$poller_start = microtime(true);
$start_date   = date('Y-m-d H:i:s');
$force        = false;
$debug        = false;
$devices      = 0;
$host_id      = '';

global $config, $database_default;

$run_from_poller = true;

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);


if (cacti_sizeof($parms)) {
	foreach($parms as $parameter) {
		if (strpos($parameter, '=')) {
			list($arg, $value) = explode('=', $parameter, 2);
		} else {
			$arg = $parameter;
			$value = '';
		}

		switch($arg) {

			case '--id':
				$host_id = $value;

				break;

			case '--force':
				$force = true;

				break;
			case '--debug':
				$debug = true;

				break;
			case '--version':
			case '--V':
			case '--v':
				display_version();
				exit(0);
			case '--help':
			case '--H':
			case '--h':
				display_help();
				exit(0);

			default:
				print "ERROR: Invalid Argument: ($arg)" . PHP_EOL . PHP_EOL;
				display_help();
				exit(1);
		}
	}
} else {
	print 'ERROR: You must supply input parameters' . PHP_EOL . PHP_EOL;
	display_help();
	exit(1);
}

if (strtolower($host_id) == 'all') {
	$sql_where = '';
} elseif (is_numeric($host_id) && $host_id > 0) {
	$sql_where = ' AND id = ' . $host_id;
} else {
	print 'ERROR: You must specify either a host_id or \'all\' to proceed.' . PHP_EOL;

	display_help();
	exit;
}

/* silently end if the registered process is still running, or process table missing */
if (function_exists('register_process_start')) {
	if (!register_process_start('evidence', 'master', $config['poller_id'], read_config_option('evidence_timeout'))) {
		evidence_debug('Another Evidence Process Still Running');
		exit(0);
	}
}


/* import enterprise numbers */
$num_count = db_fetch_cell ('SELECT count(id) FROM plugin_evidence_organization');
cacti_log('Plugin Evidence - checking table enterprise numbers');
evidence_debug('Checking table enterprise numbers');

if ($num_count < 10) {
	cacti_log('Plugin Evidence - enterprise numbers table is empty, importing. It can take few minutes');
	evidence_debug('Enterprise numbers table is empty, importing. It can take few minutes');
	
	$result = evidence_import_enterprise_numbers();
	if (!$result || $result == 0) {
		evidence_debug('Import enterprise numbers failed. Cannot continue.');
		if (function_exists('unregister_process')) {
			unregister_process('evidence', 'master', $config['poller_id']);
		}
		exit(0);
	} else {
		evidence_debug('Import enterprise numbers OK, imported lines: ' . $result);
	}
}

$evidence_records = read_config_option('evidence_records');
if ($evidence_records == 0) {
	evidence_debug('Evidence history is disabled, nothing to do');
	if (function_exists('unregister_process')) {
		unregister_process('evidence', 'master', $config['poller_id']);
	}
	exit(0);
}

$scan_date  = date('Y-m-d H:i:s');
$rec_entity = 0;
$rec_mac    = 0;
$rec_ip     = 0;
$rec_spec   = 0;
$rec_opt    = 0;

evidence_debug('scan date is ' .  $scan_date);

$hosts = db_fetch_assoc ("SELECT * FROM host
	WHERE disabled != 'on' AND
	host.status BETWEEN 2 AND 3 AND
	snmp_version != 0 " .
	$sql_where);

evidence_debug('Found ' . cacti_sizeof($hosts) . ' devices');

if (cacti_sizeof($hosts) > 0) {

	foreach ($hosts as $host) {
		$data_entity = array();
		$data_mac    = array();
		$data_ip    = array();
		$data_spec   = array();
		$data_opt    = array();
		$old_data    = false;
		$data_entity_his = array();
		$data_mac_his    = array();
		$data_ip_his    = array();
		$data_spec_his   = array();

		evidence_debug('Host ' . $host['id'] . ' trying ENTITY MIB');

		$data_entity = plugin_evidence_get_entity_data($host);
		evidence_debug('Host ' . $host['id'] . ' returned ' . cacti_sizeof($data_entity) . ' records');

		$data_mac = plugin_evidence_get_mac($host);
		evidence_debug('Host ' . $host['id'] . ' gathering MAC addresses, returned ' . cacti_sizeof($data_mac) . ' records');

		$data_ip = plugin_evidence_get_ip($host);
		evidence_debug('Host ' . $host['id'] . ' gathering IP addresses, returned ' . cacti_sizeof($data_ip) . ' records');

		$org_id = plugin_evidence_find_organization($host);

		if ($org_id) {
			$org_name = db_fetch_cell_prepared ('SELECT organization
				FROM plugin_evidence_organization
				WHERE id = ?',
				array($org_id));

			$host['org_id'] = $org_id;

			evidence_debug('Host ' . $host['id'] . ' find organization: ' . $org_id . ', name: ' . $org_name);

			$count = db_fetch_cell_prepared ('SELECT count(*) FROM plugin_evidence_specific_query
				WHERE org_id = ? AND
				mandatory = "yes"',
				array($org_id));

			if ($count > 0) {
				$data_spec = plugin_evidence_get_data_specific($host, false);
				evidence_debug('Host ' . $host['id'] . ' supports specific values, returned ' . cacti_sizeof($data_spec) . ' records');
			}

			$count = db_fetch_cell_prepared ('SELECT count(*) FROM plugin_evidence_specific_query
				WHERE org_id = ? AND
				mandatory = "no"',
				array($org_id));

			if ($count > 0) {
				$data_opt = plugin_evidence_get_data_specific($host, true);
				evidence_debug('Host ' . $host['id'] . ' supports specific optional values, returned ' . cacti_sizeof($data_opt) . ' records');
			}
		}

		evidence_debug('Host ' . $host['id'] . ' data gathering finished');

		$old_scan_date = db_fetch_cell_prepared('SELECT MAX(scan_date)
			FROM plugin_evidence_entity
			WHERE host_id = ?',
			array($host['id']));

		if ($old_scan_date) {
			$old_data = true;

			$data_entity_his = db_fetch_assoc_prepared ('SELECT `index`, descr, name, hardware_rev, firmware_rev, software_rev,
				serial_num, mfg_name, model_name, alias, asset_id, mfg_date, uuid
				FROM plugin_evidence_entity
				WHERE host_id = ? AND
				scan_date = ?
				ORDER BY `index`',
				array($host['id'], $old_scan_date));
		}

		$old_scan_date = db_fetch_cell_prepared('SELECT MAX(scan_date)
			FROM plugin_evidence_mac
			WHERE host_id = ?',
			array($host['id']));

		if ($old_scan_date) {
			$old_data = true;

			$data_mac_his = array_column(db_fetch_assoc_prepared ('SELECT mac FROM plugin_evidence_mac
				WHERE host_id = ? AND
				scan_date = ?
				ORDER BY mac',
				array($host['id'], $old_scan_date)), 'mac');
		}

		$old_scan_date = db_fetch_cell_prepared('SELECT MAX(scan_date)
			FROM plugin_evidence_ip
			WHERE host_id = ?',
			array($host['id']));

		if ($old_scan_date) {
			$old_data = true;

			$data_ip_his = array_column(db_fetch_assoc_prepared ('SELECT ip_mask FROM plugin_evidence_ip
				WHERE host_id = ? AND
				scan_date = ?
				ORDER BY ip_mask',
				array($host['id'], $old_scan_date)), 'ip_mask');
		}

		$old_scan_date = db_fetch_cell_prepared('SELECT MAX(scan_date)
			FROM plugin_evidence_vendor_specific
			WHERE host_id = ? AND
			mandatory = "yes"',
			array($host['id']));

		if ($old_scan_date) {
			$old_data = true;

			$data_spec_his = db_fetch_assoc_prepared ('SELECT description, oid, value FROM plugin_evidence_vendor_specific
				WHERE host_id = ? AND
				mandatory = "yes" AND
				scan_date = ?',
				array($host['id'], $old_scan_date));
		}

		if (!$old_data) {
			evidence_debug('Host ' . $host['id'] . ' history records not found, only store new data');
		}

		/* comparasion with old data */
		if ($old_data && (cacti_sizeof($data_entity_his) > 0 || cacti_sizeof($data_mac_his) > 0 ||
			cacti_sizeof($data_ip_his) > 0 || cacti_sizeof($data_spec_his) > 0)) {

			evidence_debug('Host ' . $host['id'] . ' comparing with old data');

			$diff = array(
				'entity' => false,
				'mac'    => false,
				'ip'     => false,
				'spec'   => false
			);

			if ($data_entity !== $data_entity_his) {
				$diff['entity'] = true;
			}

			if ($data_mac != $data_mac_his) {
				$diff['mac'] = true;
			}

			if ($data_ip != $data_ip_his) {
				$diff['ip'] = true;
			}

			if ($data_spec !== $data_spec_his) {
				$diff['spec'] = true;
			}

			if (!$diff['entity'] && !$diff['mac'] && !$diff['ip'] && !$diff['spec']) {
				evidence_debug('Host ' . $host['id'] . ' data is the same, nothing to do');
			} else {
				evidence_debug('Host ' . $host['id'] . ' different data, maybe notification');

				$excluded_templates = explode(',', read_config_option('evidence_email_notify_exclude_templates'));

				$excluded = explode(',', read_config_option('evidence_email_notify_exclude_hosts'));

				if (read_config_option('evidence_email_notify') == 'on') {
					if (in_array($host['id'], $excluded) || in_array($host['host_template_id'], $excluded_templates)) {
						cacti_log('Plugin evidence - host changed (id:' . $host['id'] . '),  Device ID or Template ID is excluded from notification');
						evidence_debug('Host ' . $host['id'] . ' Device ID or Template ID is excluded from notification');
					} else {
						evidence_debug('Host ' . $host['id'] . ' sending notification');

						$emails = db_fetch_cell_prepared ('SELECT emails, host.*
							FROM plugin_notification_lists
							LEFT JOIN host ON plugin_notification_lists.id = host.thold_host_email
							WHERE host.id = ?',
							array($host['id']));

						$text = 'I have found any HW/serial number change on host ' . $host['description'] .
							' (' . $host['hostname'] . ')<br/><br/>' . PHP_EOL;

						if (isset($data_entity)) {
							$text .= $diff['entity'] ? '<font color="red">' : '';
							$text .= 'Actual entity data:' . plugin_evidence_array_to_table($data_entity) . '<br/><br/>' . PHP_EOL .
								'Older entity data:' . plugin_evidence_array_to_table($data_entity_his) . '<br/><br/>' . PHP_EOL;
							$text .= $diff['entity'] ? '</font>' : '';
						}

						if (isset($data_mac)) {
							$text .= $diff['mac'] ? '<font color="red">' : '';
							$text .= 'Actual MAC adresses:' . plugin_evidence_array_to_table($data_mac, 5) . '<br/><br/>' . PHP_EOL .
								'Older MAC adresses:' . plugin_evidence_array_to_table($data_mac_his, 5) . '<br/><br/>' . PHP_EOL;
							$text .= $diff['mac'] ? '</font>' : '';
						}
/*
var_dump($data_mac);
echo plugin_evidence_array_to_table($data_mac, 3);
die();
*/
						if (isset($data_ip)) {
							$text .= $diff['ip'] ? '<font color="red">' : '';
							$text .= 'Actual IP adresses:' . plugin_evidence_array_to_table($data_ip, 3) . '<br/><br/>' . PHP_EOL .
								'Older IP adresses:' . plugin_evidence_array_to_table($data_ip_his, 3) . '<br/><br/>' . PHP_EOL;
							$text .= $diff['ip'] ? '</font>' : '';
						}

						if (isset($data_spec)) {
							$text .= $diff['spec'] ? '<font color="red">' : '';
							$text .= 'Actual vendor specific data:' . plugin_evidence_array_to_table($data_spec) . '<br/><br/>' . PHP_EOL .
								'Older vendor specific data:' . plugin_evidence_array_to_table($data_spec_his) . '<br/><br/>' . PHP_EOL;
							$text .= $diff['spec'] ? '</font>' : '';
						}

						send_mail($emails, read_config_option('settings_from_email'),
							'Plugin evidence - device ' . $host['description'] . ' changed',
							$text, '', '', true);

						cacti_log('Plugin evidence - host changed (id:' . $host['id'] . '), sending email notification');
					}

				} else { // only log
					evidence_debug('Host ' . $host['id'] . ' notification disabled, only logging');

					cacti_log('Plugin evidence - host changed (id:' . $host['id'] . '),  only logging');
				}
			}
			
		}

		if (!$old_data || $diff['entity'] || $diff['mac'] || $diff['ip'] || $diff['spec']) { /* saving new data */
			/* store data from entity mib */
			if (cacti_sizeof($data_entity) > 0) {
				foreach ($data_entity as $l) {
					db_execute_prepared('INSERT INTO plugin_evidence_entity
						(host_id, organization_id, organization_name,
						`index`, `descr`, `name`,
						hardware_rev, firmware_rev, software_rev,
						serial_num, mfg_name, model_name,
						alias, asset_id, mfg_date, uuid,
						scan_date)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
						array($host['id'], $org_id, $org_name,
							$l['index'], $l['descr'], $l['name'],
							$l['hardware_rev'], $l['firmware_rev'], $l['software_rev'],
							$l['serial_num'], $l['mfg_name'], $l['model_name'],
							$l['alias'], $l['asset_id'], $l['mfg_date'], $l['uuid'],
							$scan_date));
				}
			}

			/* store mac addresses */
			if (cacti_sizeof($data_mac) > 0) {
				foreach ($data_mac as $mac) {
					db_execute_prepared('INSERT INTO plugin_evidence_mac
						(host_id, mac, scan_date)
						VALUES (?, ?, ?)',
						array($host['id'], $mac, $scan_date));
				}
			}

			/* store IP addresses */
			if (cacti_sizeof($data_ip) > 0) {
				foreach ($data_ip as $ip) {
					db_execute_prepared('INSERT INTO plugin_evidence_ip
						(host_id, ip_mask, scan_date)
						VALUES (?, ?, ?)',
						array($host['id'], $ip, $scan_date));
				}
			}

			/* store vendor specific mandatory */
			if (cacti_sizeof($data_spec) > 0) {
				foreach ($data_spec as $key => $val) {
					if (isset($val['value'])) {
						db_execute_prepared('INSERT INTO plugin_evidence_vendor_specific
							(host_id, oid, description, value, mandatory, scan_date)
							VALUES (?, ?, ?, ?, "yes", ?)',
							array($host['id'], $val['oid'], $val['description'], $val['value'], $scan_date));
					}
				}
			}

			/* store vendor specific optional */
			if (cacti_sizeof($data_opt) > 0) {
				foreach ($data_opt as $key => $val) {
					if (isset($val['value'])) {
						db_execute_prepared('INSERT INTO plugin_evidence_vendor_specific
							(host_id, oid, description, value, mandatory, scan_date)
							VALUES (?, ?, ?, ?, "no", ?)',
							array($host['id'], $val['oid'], $val['description'], $val['value'], $scan_date));
					}
				}
			}

			/* delete old data */

			$old_date_limit = db_fetch_cell_prepared('SELECT DISTINCT(scan_date)
				FROM plugin_evidence_entity
				WHERE host_id = ?
				ORDER BY scan_date DESC
				LIMIT ' . $evidence_records . ' , 1',
				array($host['id']));

			if ($old_date_limit) {
				db_execute_prepared ('DELETE FROM plugin_evidence_entity
					WHERE host_id = ? AND scan_date <= ?',
					array($host['id'], $old_date_limit));
			}

			$old_date_limit = db_fetch_cell_prepared('SELECT DISTINCT(scan_date)
				FROM plugin_evidence_mac
				WHERE host_id = ?
				ORDER BY scan_date DESC
				LIMIT ' . $evidence_records . ' , 1',
				array($host['id']));

			if ($old_date_limit) {
				db_execute_prepared ('DELETE FROM plugin_evidence_mac
					WHERE host_id = ? AND scan_date <= ?',
					array($host['id'], $old_date_limit));
			}

			$old_date_limit = db_fetch_cell_prepared('SELECT DISTINCT(scan_date)
				FROM plugin_evidence_ip
				WHERE host_id = ?
				ORDER BY scan_date DESC
				LIMIT ' . $evidence_records . ' , 1',
				array($host['id']));

			if ($old_date_limit) {
				db_execute_prepared ('DELETE FROM plugin_evidence_ip
					WHERE host_id = ? AND scan_date <= ?',
					array($host['id'], $old_date_limit));
			}

			$old_date_limit = db_fetch_cell_prepared('SELECT DISTINCT(scan_date)
				FROM plugin_evidence_vendor_specific
				WHERE host_id = ?
				ORDER BY scan_date DESC
				LIMIT ' . $evidence_records . ' , 1',
				array($host['id']));

			if ($old_date_limit) {
				db_execute_prepared ('DELETE FROM plugin_evidence_vendor_specific
					WHERE host_id = ? AND scan_date <= ?',
					array($host['id'], $old_date_limit));
			}
		}

		$rec_entity += cacti_sizeof($data_entity);
		$rec_mac    += cacti_sizeof($data_mac);
		$rec_ip     += cacti_sizeof($data_ip);
		$rec_spec   += cacti_sizeof($data_spec);
		$rec_opt    += cacti_sizeof($data_opt);
		$devices++;
	}
}


$poller_end = microtime(true);

$pstats = 'Time:' . round($poller_end-$poller_start, 2) . ', Devices:' . $devices . ' Entity:' . $rec_entity .
	' Mac:' . $rec_mac . ' IP:' . $rec_ip . ' Specific: ' . $rec_spec . ' Optional:' . $rec_opt;

cacti_log('EVIDENCE STATS: ' . $pstats, false, 'SYSTEM');
set_config_option('plugin_evidence_stats', $pstats);

if (function_exists('unregister_process')) {
	unregister_process('evidence', 'master', $config['poller_id']);
}

exit(0);


function evidence_debug($message) {
	global $debug;

	if ($debug) {
		print trim($message) . PHP_EOL;
	}
}

function display_version() {
	global $config;

	if (!function_exists('plugin_evidence_version')) {
		include_once($config['base_path'] . '/plugins/evidence/setup.php');
	}

	$info = plugin_evidence_version();
	print 'Cacti Evidence Poller, Version ' . $info['version'] . ', ' . COPYRIGHT_YEARS . PHP_EOL;
}

/*
 * display_help
 * displays the usage of the function
 */
function display_help() {
	display_version();

	print PHP_EOL;
	print 'usage: poller_evidence.php [--force] [--debug]' . PHP_EOL . PHP_EOL;
	print '  --id=N        - run for a specific Device or \'all\' for all devices' . PHP_EOL;
	print '  --force       - force execution, e.g. for testing' . PHP_EOL;
	print '  --debug       - debug execution, e.g. for testing' . PHP_EOL . PHP_EOL;
}


