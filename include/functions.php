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
 | https://github.com/cacti/                                               |
 | https://www.cacti.net/                                                  |
 +-------------------------------------------------------------------------+
*/


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
	} else {
	}
}


function plugin_evidence_device_remove($device_id) {
	db_execute_prepared('DELETE FROM plugin_evidence_entity WHERE host_id = ?', array($device_id));
	db_execute_prepared('DELETE FROM plugin_evidence_mac WHERE host_id = ?', array($device_id));
	db_execute_prepared('DELETE FROM plugin_evidence_ip WHERE host_id = ?', array($device_id));
	db_execute_prepared('DELETE FROM plugin_evidence_vendor_specific WHERE host_id = ?', array($device_id));
}


function evidence_show_tab () {
	global $config;

	if (api_user_realm_auth('evidence.php')) {
		$cp = false;
		if (basename($_SERVER['PHP_SELF']) == 'evidence.php') {
			$cp = true;
		}

		print '<a href="' . $config['url_path'] . 'plugins/evidence/evidence_tab.php"><img src="' . $config['url_path'] . 'plugins/evidence/images/tab_evidence' . ($cp ? '_down': '') . '.gif" alt="evidence" align="absmiddle" border="0"></a>';
	}
}


function plugin_evidence_device_edit_top_links (){
	print "<br/><span class='linkMarker'>* </span><a id='evidence_info' data-evidence_id='" . get_filter_request_var('id') . "' href=''>" . __('Evidence') . "</a>";
}


function plugin_evidence_host_edit_bottom () {
	global $config;
	print get_md5_include_js($config['base_path'] . '/plugins/evidence/evidence.js');
}


/*
	plugin needs enterprise numbers, import cat take longer time
	so import is started first poller run
*/

function evidence_import_enterprise_numbers() {
	global $config;

	$i = 0;

	$file = fopen($config['base_path'] . '/plugins/evidence/data/enterprise-numbers.sql','r');

	if ($file) {

		while(!feof($file)) {
			$line = fgets($file);
			db_execute($line);
			$i++;
		}
	} else {
		return false;
	}

	fclose ($file);

	return $i;
}


function plugin_evidence_get_allowed_devices($user_id, $array = false) {

	$x  = 0;
	$us = read_user_setting('hide_disabled', false, false, $user_id);

	if ($us == 'on') {
		set_user_setting('hide_disabled', '', $user_id);
	}

	$allowed = get_allowed_devices('', 'null', -1, $x, $user_id);

	if ($us == 'on') {
		set_user_setting('hide_disabled', 'on', $user_id);
	}

	if (cacti_count($allowed)) {
		if ($array) {
			return(array_column($allowed, 'id'));
		}
		return implode(',', array_column($allowed, 'id'));
	} else {
		return false;
	}
}


function plugin_evidence_find_organization ($h) {

	cacti_oid_numeric_format();

	$sys_object_id = @cacti_snmp_get($h['hostname'], $h['snmp_community'],
		'.1.3.6.1.2.1.1.2.0', $h['snmp_version'],
		$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
		$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
		$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout'],0);

	if (!isset($sys_object_id) || $sys_object_id == 'U') {
		return false;
	}

	preg_match('/^([a-zA-Z0-9\.: ]+)\.1\.3\.6\.1\.4\.1\.([0-9]+)[a-zA-Z0-9\. ]*$/', $sys_object_id, $match);
	return $match[2];
}


/* get data from entity mib */

function plugin_evidence_get_entity_data($h) {
	global $config;

	$entity = array();

	// gathering data from entity mib
	$indexes = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.1',
		$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], 
		$h['snmp_auth_protocol'], $h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], 
		$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);

	/* Some devides doesn't use index, trying normal data */
	if (!cacti_sizeof($indexes)) { 

		$data_descr = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.2',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'],$h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		if (cacti_sizeof($data_descr) > 0) {
			$i = 0;
			foreach ($data_descr as $key => $val) {
				
				$tmp = substr(strrchr($val['oid'], '.'),1);
				$indexes[$i]['oid'] = '.1.3.6.1.2.1.47.1.1.1.1.2.' . $tmp;
				$indexes[$i]['value'] = $tmp;
				$i++;
			}
		}
	}

	if (cacti_sizeof($indexes) > 0 || cacti_sizeof($data_descr)) {

		$data_descr = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.2',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'],$h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_name = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.7',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_hardware_rev = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.8',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_firmware_rev = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.9',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_software_rev = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.10',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_serial_num = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.11',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_mfg_name = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.12',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_model_name = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.13',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_alias = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.14',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_asset_id = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.15',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_mfg_date = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.17',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		$data_uuid = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.47.1.1.1.1.19',
			$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
			$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'], $h['snmp_context'],
			$h['snmp_port'], $h['snmp_timeout']);

		foreach ($indexes as $key => $val) {
			$date = '';

			if (isset($data_mfg_date[$key])) {
				$data_mfg_date[$key]['value'] = str_replace(' ' ,'', $data_mfg_date[$key]['value']);
				$man_year = hexdec(substr($data_mfg_date[$key]['value'], 0, 4));
				$man_month = str_pad(hexdec(substr($data_mfg_date[$key]['value'], 4, 2)), 2, '0', STR_PAD_LEFT);
				$man_day = str_pad(hexdec(substr($data_mfg_date[$key]['value'], 6, 2)), 2, '0', STR_PAD_LEFT);
				if ($man_year != 0) {
					$date = $man_year . '-' . $man_month . '-' . $man_day;
				}
			}

			$entity[] = array (
				'index'        => isset($indexes[$key]['value']) ? $indexes[$key]['value'] : '',
				'descr'        => isset($data_descr[$key]['value']) ? $data_descr[$key]['value'] : '',
				'name'         => isset($data_name[$key]['value']) ? $data_name[$key]['value'] : '',
				'hardware_rev' => isset($data_hardware_rev[$key]['value']) ? $data_hardware_rev[$key]['value'] : '',
				'firmware_rev' => isset($data_firmware_rev[$key]['value']) ? $data_firmware_rev[$key]['value'] : '',
				'software_rev' => isset($data_software_rev[$key]['value']) ? $data_software_rev[$key]['value'] : '',
				'serial_num'   => isset($data_serial_num[$key]['value']) ? $data_serial_num[$key]['value'] : '',
				'mfg_name'     => isset($data_mfg_name[$key]['value']) ? $data_mfg_name[$key]['value'] : '',
				'model_name'   => isset($data_model_name[$key]['value']) ? $data_model_name[$key]['value'] : '',
				'alias'        => isset($data_alias[$key]['value']) ? $data_alias[$key]['value'] : '',
				'asset_id'     => isset($data_asset_id[$key]['value']) ? $data_asset_id[$key]['value'] : '',
				'mfg_date'     => $date,
				'uuid'         => isset($data_uuid[$key]['value']) ? $data_uuid[$key]['value'] : ''
			);
		}
	}

	return $entity;
}


/* try to find if device are using any mac addresses */
function plugin_evidence_get_mac ($h) {

	$return = array();

	$macs = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.2.2.1.6',
		$h['snmp_version'],$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
		$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],$h['snmp_context'], 
		$h['snmp_port'], $h['snmp_timeout']);

	foreach ($macs as $mac) {
		if (strlen($mac['value']) > 1) {
			if ($mac['value'] == '0:0:0:0:0:0:0:e0') { // windows server reports this nonsense
				continue;
			}
			
			$mac = plugin_evidence_normalize_mac($mac['value']);
			if (!in_array($mac, $return)) {
				$return[] = $mac;
			}
		}
	}

	sort($return);

	return $return;
}


/* try to find if device are using any IPv4/IPv6 addresses */

function plugin_evidence_get_ip ($h) {

	cacti_oid_numeric_format();

	$return = array();

/*
	snmpwalk is:
	.1.3.6.1.2.1.4.34.1.3.1.4.10.21.160.222.32 = INTEGER: 32
	.1.3.6.1.2.1.4.34.1.3.1.4.10.253.255.254.30 = INTEGER: 30
	.1.3.6.1.2.1.4.34.1.3.1.4.169.254.1.1.7 = INTEGER: 7
	need parse IP from OID
	
	mask is here - value is reference to masktable, but a lot of devices doesn't support OID ....32.1.5
	so parse mask length from oid (last number)
	.1.3.6.1.2.1.4.34.1.5.1.4.10.21.160.222.32 = OID: .1.3.6.1.2.1.4.32.1.5.32.1.10.21.160.0.21
	.1.3.6.1.2.1.4.34.1.5.1.4.10.253.255.254.30 = OID: .1.3.6.1.2.1.4.32.1.5.30.1.10.253.240.0.20
	.1.3.6.1.2.1.4.34.1.5.1.4.169.254.1.1.7 = OID: .1.3.6.1.2.1.4.32.1.5.7.1.169.254.1.0.24
*/

	$ips = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.4.34.1.3',
		$h['snmp_version'],$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
		$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],$h['snmp_context'], 
		$h['snmp_port'], $h['snmp_timeout']);

	$mask_oid = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.4.34.1.5',
		$h['snmp_version'],$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
		$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],$h['snmp_context'], 
		$h['snmp_port'], $h['snmp_timeout']);

	$masks = array();
	foreach ($mask_oid as $k => $v) {
		$masks[$v['oid']] = $v['value'];
	}

	foreach ($ips as $ip) {

		$pos = strpos($ip['oid'], '.1.3.6.1.2.1.4.34.1.3.1.4');

		if ($pos !== false) {
			$pos = strrpos($ip['oid'], '.');

			$fin_ip = substr($ip['oid'], 26, strlen($ip['oid']) - $pos+26);

			if (isset($masks['.1.3.6.1.2.1.4.34.1.5.1.4.' . $fin_ip])) {
				$pos = strrpos($masks['.1.3.6.1.2.1.4.34.1.5.1.4.' . $fin_ip], '.');
				
				if (substr_count($fin_ip, '.') == 4) {	// some devices (fortigate) have index on last position
					$return[] = substr($fin_ip, 0, strrpos($fin_ip, '.')) . '/' . substr($masks['.1.3.6.1.2.1.4.34.1.5.1.4.' . $fin_ip], ++$pos);
				} else {
					$return[] = $fin_ip . '/' . substr($masks['.1.3.6.1.2.1.4.34.1.5.1.4.' . $fin_ip], ++$pos);
				}
			}
		}
		/* fortigate issue, returns .1.3.6.1.2.1.4.34.1.5.1.192.168.12.254.16 = OID: .0.0.0
		    betterdiscard this and use old deprecated OIDs
		*/

		$pos = strpos($ip['oid'], '.1.3.6.1.2.1.4.34.1.3.1');
		if ($pos !== false) {
			$pos = strrpos($ip['oid'], '.');
			$fin_ip = substr($ip['oid'], 24, strlen($ip['oid']) - $pos+24);
			/*
			if (isset($masks['.1.3.6.1.2.1.4.34.1.5.1.' . $fin_ip])) {
				$pos = strrpos($masks['.1.3.6.1.2.1.4.34.1.5.1.' . $fin_ip], '.');

				if (substr_count($fin_ip, '.') == 4) {	// some devices (fortigate) have index on last position
					$return[] = substr($ip, 0, strrpos($fin_ip, '.')) . '/' . substr($masks['.1.3.6.1.2.1.4.34.1.5.1.' . $fin_ip], ++$pos);
				} else {
					$return[] = $fin_ip . '/' . substr($masks['.1.3.6.1.2.1.4.34.1.5.1.' . $fin_ip], ++$pos);
				}
			}
			*/
		} else {
			$pos = strpos($ip['oid'], '.1.3.6.1.2.1.4.34.1.3.2.16');
			if ($pos !== false) {
				$fin_ip = substr($ip['oid'], 27);
				$return[] = $fin_ip;
			} else {
				cacti_log('Device ' . $h['id'] . ' - cannot parse IP address from ' . $ip['oid'], 'evidence');
			}
		}
	}

	if (cacti_sizeof($return)) {
		sort($return);
		return ($return);
	}

/*	IP MIB contains deprecated IP table. A lot of devices are using deprecated instead of table above
	here is fall back */

	$ips = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.4.20.1.1',
	$h['snmp_version'],$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
	$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],$h['snmp_context'], 
	$h['snmp_port'], $h['snmp_timeout']);

	$masks = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], '.1.3.6.1.2.1.4.20.1.3',
	$h['snmp_version'],$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
	$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],$h['snmp_context'], 
	$h['snmp_port'], $h['snmp_timeout']);

	foreach ($ips as $k => $v) {
		$ip = $v['value'];

		if (isset($masks[$k])) {
			$return[] = $ip . '/' . $masks[$k]['value'];
		}
	}

	sort($return);
	return $return;
}



/* try to find vendor specific data
optional = false - This data doesn't change much over time, so it can be used for comparison
optional = true - There may be interesting information in this data, but it changes frequently.
		Therefore, they are not used for comparison, only for display
*/

function plugin_evidence_get_data_specific ($h, $optional = false) {

	$data_spec = array();

	if ($optional) {
		$cond = 'no';
	} else {
		$cond = 'yes';
	}

	$steps = db_fetch_assoc_prepared ('SELECT * FROM plugin_evidence_specific_query
		WHERE org_id = ? AND mandatory = ?
		ORDER BY method',
		array($h['org_id'], $cond));

	$i = 0;

	foreach ($steps as $step) {
		if ($step['method'] == 'get') {
			$data_spec[$i]['description'] = $step['description'];
			$data_spec[$i]['oid'] = $step['oid'];

			$data = @cacti_snmp_get($h['hostname'], $h['snmp_community'], $step['oid'],
				$h['snmp_version'], $h['snmp_username'], $h['snmp_password'], 
				$h['snmp_auth_protocol'], $h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
				$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);

			if (preg_match ('#' . $step['result'] . '#', $data, $matches) !== false) {
				$data_spec[$i]['value'] = $matches[0];
			} else {
				$data_spec[$i]['value'] = $data . ' (cannot find specified regexp, so display all ';
			}
			
			$i++;
		}
		elseif ($step['method'] == 'walk') {
			$data_spec[$i]['description'] = $step['description'];
			$data_spec[$i]['oid'] = $step['oid'];

			$data = @cacti_snmp_walk($h['hostname'], $h['snmp_community'], $step['oid'],
				$h['snmp_version'],$h['snmp_username'], $h['snmp_password'],
				$h['snmp_auth_protocol'], $h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
				$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);

			if (cacti_sizeof($data) > 0) {

				foreach ($data as $row) {
					if (preg_match ('#' . $step['result'] . '#', $row['value'], $matches) !== false) {
						if (strlen($matches[0]) > 0) {
							$data_spec[$i]['value'][] = $matches[0];
						}
					} else {
						$data_spec[$i]['value'][] = $data . ' (cannot find specified regexp, so display all ';
					}
				}
				
				if (is_array($data_spec[$i]['value'])) {
					$data_spec[$i]['value'] = implode(',', $data_spec[$i]['value']);
				}
			}

			$i++;
		} elseif ($step['method'] == 'table') {

			$ind_des = explode (',', $step['table_items']);

			foreach ($ind_des as $a) {
				list ($in,$d) = explode ('-', $a);
				$oid_suff[] = $in;
				$desc[] = $d;
			}

			foreach ($oid_suff as $key => $in) {
				$data_spec[$i]['description'] = $desc[$key];
				$data_spec[$i]['oid'] = $step['oid'] . '.' . $in;

				$data[$in] = @cacti_snmp_walk($h['hostname'], $h['snmp_community'],
					$step['oid'] . '.' . $in, $h['snmp_version'],
					$h['snmp_username'], $h['snmp_password'], $h['snmp_auth_protocol'],
					$h['snmp_priv_passphrase'], $h['snmp_priv_protocol'],
					$h['snmp_context'], $h['snmp_port'], $h['snmp_timeout']);

				if (cacti_sizeof($data[$in])) {
					$data_spec[$i]['value'] = implode(',', array_column($data[$in],'value'));

				}
				$i++;
			}
		}
	}

	return $data_spec;
}

function plugin_evidence_normalize_mac ($mac) {

	$mac = trim($mac);

	if (preg_match('/^([a-fA-F0-9]{4}\.){2}[a-fA-F0-9]{4}$/', $mac)) { // 1234.5678.abcd
		$mac = str_replace('.','', $mac);
		$tmp_mac = str_split($mac, 2);
		$mac = implode(':', $tmp_mac);
		return strtoupper($mac);
	} elseif (preg_match('/^[a-fA-F0-9]{12}$/', $mac)) { // 12345678abcd
		$tmp_mac = str_split($mac, 2);
		$mac = implode(':', $tmp_mac);
		return strtoupper($mac);
	} elseif (preg_match('/^[a-fA-F0-9]{11}$/', $mac)) { // 0345678abcd
		$tmp_mac = str_split('0' . $mac, 2);
		$mac = implode(':', $tmp_mac);
		return strtoupper($mac);
	} elseif (preg_match('/^([a-fA-F0-9]{2}\-){5}[a-fA-F0-9]{2}$/', $mac)) { // 12-34-56-78-ab-cd
		$mac = str_replace('-', ':', $mac);
		return strtoupper($mac);
	} elseif (preg_match('/^([a-fA-F0-9]{1,2}\-){5}[a-fA-F0-9]{1,2}$/', $mac)) { // 0-34-0-78-ab-cd
		$words = explode ('-', $mac);
		foreach ($words as $word) {
			if (strlen($word) == 1) {
				$tmp_mac[] = '0' . $word;
			} else {
				$tmp_mac[] = $word;
			}
		}
		return strtoupper(implode(':', $tmp_mac));
	} elseif (preg_match('/^([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}$/', $mac)) { // 12:34:56:78:ab:cd
		return strtoupper($mac);
	} elseif (preg_match('/^([a-fA-F0-9]{1,2}:){5}[a-fA-F0-9]{1,2}$/', $mac)) { // 0:34:0:78:ab:cd
		$tmp_mac = array();
		$words = explode(':', $mac);
		foreach ($words as $word) {
			if (strlen($word) == 1) {
				$tmp_mac[] = '0' . $word;
			} else {
				$tmp_mac[] = $word;
			}
		}
		return strtoupper(implode(':', $tmp_mac));
	} else {
		cacti_log('Unknown MAC address format "' . $mac . '"', 'evidence');
		return ($mac);
	}
}




/*
	return all (entity, mac, ip, vendor specific and vendor optional) information
	scan_date is index
*/

function plugin_evidence_history ($host_id) {
	$out = array();

	$data = db_fetch_assoc_prepared("SELECT *
		FROM plugin_evidence_entity
		WHERE host_id = ?
		ORDER BY scan_date DESC, 'index'",
		array($host_id));

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			$out['entity'][$row['scan_date']][] = $row;
			$out['dates'][] = $row['scan_date'];
		}
	}

	$data = db_fetch_assoc_prepared('SELECT *
		FROM plugin_evidence_mac
		WHERE host_id = ?
		ORDER BY scan_date DESC',
		array($host_id));

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			$out['mac'][$row['scan_date']][] = $row['mac'];
			$out['dates'][] = $row['scan_date'];
		}
	}

	$data = db_fetch_assoc_prepared('SELECT *
		FROM plugin_evidence_ip
		WHERE host_id = ?
		ORDER BY scan_date DESC',
		array($host_id));

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			$out['ip'][$row['scan_date']][] = $row['ip_mask'];
			$out['dates'][] = $row['scan_date'];
		}
	}

	$data = db_fetch_assoc_prepared('SELECT *
		FROM plugin_evidence_vendor_specific
		WHERE host_id = ? AND
		mandatory = "yes"
		ORDER BY scan_date DESC',
		array($host_id));

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			$out['spec'][$row['scan_date']][] = $row;
			$out['dates'][] = $row['scan_date'];
		}
	}

	$data = db_fetch_assoc_prepared('SELECT *
		FROM plugin_evidence_vendor_specific
		WHERE host_id = ? AND
		mandatory = "no"
		ORDER BY scan_date DESC',
		array($host_id));

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			$out['opt'][$row['scan_date']][] = $row;
			$out['dates'][] = $row['scan_date'];
		}
	}

	return $out;
}



function plugin_evidence_find() {
	global $config;

	if (read_config_option('evidence_records') == 0) {
		print 'Store history is not allowed. Nothing to do ...';
		return false;
	}

	$f = get_request_var('find_text');

	$sql_where = "descr RLIKE '" . $f . "'
		OR name RLIKE '" . $f . "'
		OR hardware_rev RLIKE '" . $f . "'
		OR firmware_rev RLIKE '" . $f . "'
		OR software_rev RLIKE '" . $f . "'
		OR serial_num RLIKE '" . $f . "'
		OR mfg_date RLIKE '" . $f . "'
		OR model_name RLIKE '" . $f . "'
		OR alias RLIKE '" . $f . "'
		OR asset_id RLIKE '" . $f . "'
		OR mfg_date RLIKE '" . $f . "'
		OR uuid RLIKE '" . $f . "' ";

	$data = db_fetch_assoc ('SELECT host_id, COUNT(scan_date) AS `count` FROM plugin_evidence_entity
		WHERE ' . $sql_where . ' GROUP BY host_id');


	print '<br/><b>Entity MIB:</b><br/>';

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			$desc = db_fetch_cell_prepared ('SELECT description FROM host WHERE id = ?', array($row['host_id']));
			print '<a href="' . $config['url_path'] .
				'plugins/evidence/evidence_tab.php?action=find&datatype=all&host_id=' . $row['host_id'] . '">' .
				$desc . '</a> (ID: ' . $row['host_id'] . '), found in ' . $row['count'] . ' records<br/>';
		}
	} else {
		print __('Not found', 'evidence') . '<br/>';
	}

	$data = db_fetch_assoc_prepared ("SELECT host_id, COUNT(scan_date) AS `count` FROM plugin_evidence_mac
		WHERE mac RLIKE '" . $f . "' GROUP BY host_id");

	print '<br/><b>MAC addresses:</b><br/>';

	if (cacti_sizeof($data)) {

		foreach ($data as $row) {
			$desc = db_fetch_cell_prepared ('SELECT description FROM host WHERE id = ?', array($row['host_id']));
			print '<a href="' . $config['url_path'] . 
				'plugins/evidence/evidence_tab.php?action=find&datatype=all&host_id=' . $row['host_id'] . '">' .
				$desc . '</a> (ID: ' . $row['host_id'] . '), found in ' . $row['count'] . ' records<br/>';
		}
	} else {
		print __('Not found', 'evidence') . '<br/>';
	}

	print '<br/><b>IP addresses:</b><br/>';
	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			$desc = db_fetch_cell_prepared ('SELECT description FROM host WHERE id = ?', array($row['host_id']));
			print '<a href="' . $config['url_path'] . 
				'plugins/evidence/evidence_tab.php?action=find&datatype=all&host_id=' . $row['host_id'] . '">' .
				$desc . '</a> (ID: ' . $row['host_id'] . '), found in ' . $row['count'] . ' records<br/>';
		}
	} else {
		print __('Not found', 'evidence') . '<br/>';
	}


	$sql_where = "oid RLIKE '" . $f . "'
		OR description RLIKE '" . $f . "'
		OR value RLIKE '" . $f . "' ";

	$data = db_fetch_assoc ('SELECT host_id, COUNT(scan_date) AS `count` FROM plugin_evidence_vendor_specific
		WHERE ' . $sql_where . ' GROUP BY host_id');

	print '<br/><b>Vendor specific data:</b><br/>';

	if (cacti_sizeof($data)) {
		foreach ($data as $row) {
			$desc = db_fetch_cell_prepared ('SELECT description FROM host WHERE id = ?', array($row['host_id']));
			print '<a href="' . $config['url_path'] . 
				'plugins/evidence/evidence_tab.php?action=find&datatype=all&host_id=' . $row['host_id'] . '">' .
				$desc . '</a> (ID: ' . $row['host_id'] . '), found in ' . $row['count'] . ' records<br/>';
		}
	} else {
		print __('Not found', 'evidence') . '<br/>';
	}
}


/* query for actual data */

function plugin_evidence_actual_data ($host) {

	$out = array();

	$out['entity'] = plugin_evidence_get_entity_data($host);
	$out['mac'] = plugin_evidence_get_mac($host);
	$out['ip'] = plugin_evidence_get_ip($host);
	$org_id = plugin_evidence_find_organization($host);
	$out['org_id'] = $org_id;

	if ($org_id) { // we know vendor so we can try vendor specific query
		$org_name = db_fetch_cell_prepared ('SELECT organization
			FROM plugin_evidence_organization
			WHERE id = ?',
			array($org_id));

		$out['org_name'] = $org_name;
		$host['org_id'] = $org_id;

		$count = db_fetch_cell_prepared ('SELECT count(*) FROM plugin_evidence_specific_query
			WHERE org_id = ? AND
			mandatory = "yes"',
			array($org_id));

		if ($count > 0) {
			$data_spec = plugin_evidence_get_data_specific($host, false);

			foreach ($data_spec as $key => $val) {
				$data_spec_x[$key]['description'] = $val['description'];
				$data_spec_x[$key]['oid'] = $val['oid'];

				if (is_array($val['value'])) {
					$data_spec_x[$key]['value'][] = $val['value'];
				} else {
					$data_spec_x[$key]['value'] = $val['value'];
				}
			}

			$out['spec'] = $data_spec_x;
		}

		$count = db_fetch_cell_prepared ('SELECT count(*) FROM plugin_evidence_specific_query
			WHERE org_id = ? AND
			mandatory = "no"',
			array($org_id));

		if ($count > 0) {
			$data_opt = plugin_evidence_get_data_specific($host, true);
			foreach ($data_opt as $key => $val) {
				$data_opt_x[$key]['description'] = $val['description'];
				$data_opt_x[$key]['oid'] = $val['oid'];

				if (is_array($val['value'])) {
					$data_opt_x[$key]['value'][] = $val['value'];
				} else {
					$data_opt_x[$key]['value'] = $val['value'];
				}
			}

			$out['opt'] = $data_opt_x;
		}
	}

	return $out;
}


function plugin_evidence_time_to_run() {

	$lastrun   = read_config_option('plugin_evidence_lastrun');
	$frequency = read_config_option('evidence_frequency') * 3600;
	$basetime  = strtotime(read_config_option('evidence_base_time'));
	$baseupper = $basetime + 300;
	$baselower = $basetime - 300;
	$now       = time();

//	cacti_log("LastRun:'$lastrun', Frequency:'$frequency' sec, BaseTime:'" . date('Y-m-d H:i:s', $basetime) . "', BaseUpper:'$baseupper', BaseLower:'$baselower', Now:'" . date('Y-m-d H:i:s', $now) . "'", false, 'EVIDENCE', POLLER_VERBOSITY_HIGH);
	cacti_log("LastRun:'$lastrun', Frequency:'$frequency' sec, BaseTime:'" . date('Y-m-d H:i:s', $basetime) . "', BaseUpper:'$baseupper', BaseLower:'$baselower', Now:'" . date('Y-m-d H:i:s', $now) . "'", false, 'EVIDENCE');

	if ($frequency > 0 && ($now - $lastrun > $frequency)) {
		if (empty($lastrun) && ($now < $baseupper) && ($now > $baselower)) {

//			cacti_log('Time to first run', false, 'EVIDENCE', POLLER_VERBOSITY_HIGH);
			cacti_log('Time to first run', false, 'EVIDENCE');
			set_config_option('plugin_evidence_lastrun', time());

			return true;
		} elseif (($now - $lastrun > $frequency) && ($now < $baseupper) && ($now > $baselower)) {
//			cacti_log('Time to periodic Run', false, 'EVIDENCE', POLLER_VERBOSITY_HIGH);
			cacti_log('Time to periodic Run', false, 'EVIDENCE');
			set_config_option('plugin_evidence_lastrun', time());

			return true;
		} else {
//			cacti_log('Not Time to Run', false, 'EVIDENCE', POLLER_VERBOSITY_HIGH);
	cacti_log('Not Time to Run', false, 'EVIDENCE');

			return false;
		}
	} else {
//		cacti_log('Not time to Run', false, 'EVIDENCE', POLLER_VERBOSITY_HIGH);
		cacti_log('Not time to Run', false, 'EVIDENCE');

		return false;
	}
}

function evidence_show_host_data ($host_id, $datatype, $scan_date) {
	global $config, $entities, $datatypes;

	$evidence_records   = read_config_option('evidence_records');
	$evidence_frequency = read_config_option('evidence_frequency');
	$data_compare_entity = array();
	$data_compare_mac    = array();
	$data_compare_ip     = array();
	$data_compare_spec   = array();

	$host = db_fetch_row_prepared ('SELECT host.*, host_template.name as `template_name`
		FROM host
		LEFT JOIN host_template
		ON host.host_template_id = host_template.id
		WHERE host.id = ?',
		array($host_id));
		
	print '<h3>' . $host['description'] . ' (' . $host['hostname'] . ', ' . $host['template_name'] . ')</h3>';

	print '<dl>';

	if (!get_filter_request_var('actual')) {
		print '<a href="' . $config['url_path'] . 'plugins/evidence/evidence_tab.php?host_id=' .
		$host_id . '&template=&actual=1&action=find&datatype=' . $datatype . '">' . __('Show actual', 'evidence') . '</a>';
		print '<br/><br/>';
	} else {	// show actual data
		unset_request_var('actual');
		$data = plugin_evidence_actual_data($host);

		if (isset($data['org_name'])) {
			print $data['org_name'];
		}

		if (isset($data['org_id'])) {
			print ' (ID ORG: ' . $data['org_id'] . ')' . '<br/>';
		}

		if ((array_key_exists($datatype, $entities) && isset($data['entity'])) || $datatype == 'all') {

			print '<br/><b>Entity MIB:</b><br/>';
			print '<table class="cactiTable"><tr>';

			foreach ($data['entity'] as $row) {
				print '<td>';
				foreach ($row as $key => $value) {

					if ($datatype == 'all') { 
						print $key . ': ' . $value . '<br/>';
					} else if ($key == $datatype) {
						print $key . ': ' . $value . '<br/>';
					}
				}
				print '</td>';
			}
			print '</tr></table>';
			$data_compare_entity = $data['entity'];
		}

		if (($datatype == 'all' || $datatype == 'mac') && isset($data['mac'])) {
			$count = 0;
			print '<br/><b>MAC:</b><br/>';
			print '<table class="cactiTable"><tr>';

			foreach ($data['mac'] as $mac) {
				print '<td>' . $mac . '</td>';
				$count++;
				if ($count > 5) {
					$count = 0;
					print '</tr><tr>';
				}
			}
			print '</tr></table>';
			$data_compare_mac = $data['mac'];
		}

		if (($datatype == 'all' || $datatype == 'ip') && isset($data['ip'])) {
			$count = 0;
			print '<br/><b>IP:</b><br/>';
			print '<table class="cactiTable"><tr>';

			foreach ($data['ip'] as $ip) {
				print '<td>' . $ip . '</td>';
				$count++;
				if ($count > 5) {
					$count = 0;
					print '</tr><tr>';
				}
			}
			print '</tr></table>';
			$data_compare_ip = $data['ip'];
		}

		if (($datatype == 'all' || $datatype == 'spec') && isset($data['spec'])) {

			print '<br/><b>Vendor specific:</b><br/>';

			foreach ($data['spec'] as $row) {
				if (!is_array($row['value'])) {
					print $row['description'] . ': ' . $row['value'] . display_tooltip('OID: ' . $row['oid']) . '</br>';
				} else {
					print $row['description'] . display_tooltip('OID: ' . $row['oid']) . ':</br>';
					foreach ($row['value'] as $a) {
						if (is_array($a)) {
							print implode(', ', $a);
						} else {
							print $a . '</br>';
						}
					}
					print '</br>';
				}
			}
			$data_compare_spec = $data['spec'];
		}

		if (($datatype == 'all' || $datatype == 'opt') && isset($data['opt'])) {
			$count = 0;
			print '<br/><b>Vendor optional:</b><br/>';

			foreach ($data['opt'] as $row) {
				if (!is_array($row['value'])) {
					print $row['description'] . ': ' . $row['value'] . display_tooltip('OID: ' . $row['oid']) . '</br>';
				} else {
					print $row['description'] . display_tooltip('OID: ' . $row['oid']) . ':</br>';
					foreach ($row['value'] as $a) {
						if (is_array($a)) {
							print implode(', ', $a);
						} else {
							print $a . '</br>';
						}
					}
					print '</br>';
				}
			}
		}
	}

	print '</dt>';
	print '</dd><br/><br/>';

	if ($evidence_records > 0) {

		$data = plugin_evidence_history($host_id);

		if (!isset($data['dates'])) {
			print __('No older data yet', 'evidence');
			return true;
		} else {

			$dates = array_unique($data['dates']);

			foreach ($dates as $date) {

				$change = false;
				$where = '';

				if (isset($scan_date) && $scan_date != $date) {
					continue;
				}

				if (cacti_sizeof($data_compare_entity) || cacti_sizeof($data_compare_mac) ||
					cacti_sizeof($data_compare_ip) || cacti_sizeof($data_compare_spec)) {

					if (isset($data_compare_entity) && cacti_sizeof($data_compare_entity)) {
						foreach ($data_compare_entity as &$row) {
							unset($row['scan_date']);
						}
					}

					if (isset($data_compare_mac) && cacti_sizeof($data_compare_mac)) {
						foreach ($data_compare_mac as &$row) {
							if (isset($row['scan_date'])) {
								unset($row['scan_date']);
							}
						}
					}

					if (isset ($data_compare_ip) && cacti_sizeof($data_compare_ip)) {
						foreach ($data_compare_ip as &$row) {
							if (isset($row['scan_date'])) {
								unset($row['scan_date']);
							}
						}
					}

					if (isset($data_compare_spec) && cacti_sizeof($data_compare_spec)) {
						foreach ($data_compare_spec as &$row) {
							if (isset($row['scan_date'])) {
								unset($row['scan_date']);
							}
						}
					}

					if (isset($data['entity'][$date]) && cacti_sizeof($data['entity'][$date])) {
						foreach ($data['entity'][$date] as &$row) {
							unset($row['scan_date']);
						}
					}

					if (isset($data['mac'][$date]) && cacti_sizeof($data['mac'][$date])) {
						sort($data_compare_mac);
						sort($data['mac'][$date]);
					}

					if (isset($data['ip'][$date]) && cacti_sizeof($data['ip'][$date])) {
						sort($data_compare_ip);
						sort($data['ip'][$date]);
					}

					if (isset($data['spec'][$date]) && cacti_sizeof($data['spec'][$date])) {
						foreach ($data['spec'][$date] as &$row) {
							unset($row['scan_date']);
						}
					}

					if (cacti_sizeof($data_compare_entity) > 0 && isset($data['entity'][$date]) && $data_compare_entity != $data['entity'][$date]) {
						$change = true;
						$where = __('Entity', 'evidence');
					}

					if (cacti_sizeof($data_compare_mac) > 0 && isset($data['mac'][$date]) && $data_compare_mac != $data['mac'][$date]) {
						$change = true;
						$where = __('MAC addresses', 'evidence');
					}

					if (cacti_sizeof($data_compare_ip) > 0 && isset($data['ip'][$date]) && $data_compare_ip != $data['ip'][$date]) {
						$change = true;
						$where = __('IP addresses', 'evidence');
					}

					if (cacti_sizeof($data_compare_spec) > 0 && isset($data['spec'][$date]) && $data_compare_spec != $data['spec'][$date]) {

						$change = true;
						$where = __('Vendor specific', 'evidence');
					}
				}

				if ($change) {
					print '<dt><b>' . $date . ' ' . __('Changed', 'evidence') .' - ' .$where . '</b></dt>';
				} else {
					print '<dt><b>' . $date . '</b></dt>';
				}
				print '<dd>';

				if ((array_key_exists($datatype, $entities) && isset($data['entity'][$date])) || $datatype == 'all') {

					print 'Entity MIB:<br/>';
		
					$data_compare_entity = $data['entity'][$date];

					foreach($data['entity'][$date] as $entity) {
						if ($datatype == 'all') {

							unset($entity['scan_date']);

							unset($entity['host_id']);
							unset($entity['organization_id']);
							unset($entity['organization_name']);

							foreach ($entity as $key => $value) {
								if ($value != '') {
									print $key . ': ' . $value . ' | ';
								}
							}

							print '<br/>';
						} else if (array_key_exists($datatype, $entity)) {
							print $datatype . ': ';
							print $entity[$datatype];
							print '<br/>';
						}
					}
				} else {
					$data_compare_entity = array();
				}

				if (($datatype == 'all' || $datatype == 'mac') && isset($data['mac'][$date])) {
					$count = 0;

					$data_compare_mac = $data['mac'][$date];

					print '<br/>MAC addresses:<br/>';
					print '<table class="cactiTable"><tr>';

					foreach($data['mac'][$date] as $mac) {
						print '<td>' . $mac . '</td>';
						$count++;
						if ($count > 5) {
							$count = 0;
							print '</tr><tr>';
						}
					}
					print '</tr></table>';
					
				} else {
					$data_compare_mac = array();
				}

				if (($datatype == 'all' || $datatype == 'ip') && isset($data['ip'][$date])) {
					$count = 0;

					$data_compare_ip = $data['ip'][$date];

					print '<br/>IP addresses:<br/>';
					print '<table class="cactiTable"><tr>';

					foreach($data['ip'][$date] as $ip) {
						print '<td>' . $ip . '</td>';
						$count++;
						if ($count > 5) {
							$count = 0;
							print '</tr><tr>';
						}
					}
					print '</tr></table>';
					
				} else {
					$data_compare_ip = array();
				}

				if (($datatype == 'all' || $datatype == 'spec') && isset($data['spec'][$date])) {
					$data_compare_spec = $data['spec'][$date];

					print '<br/>Vendor specific:<br/>';
					foreach($data['spec'][$date] as $spec) {
						unset($spec['host_id']);
						unset($spec['mandatory']);
						unset($spec['scan_date']);

						print $spec['description'] . ': ';
						print display_tooltip('OID: ' . $spec['oid']);

						if (!is_array($spec['value'])) {
							print $spec['value'];
						} else {
							// nested array
							foreach ($spec['value'] as $key => $value) {
								print implode(', ', $value);
							}
						}

						print '<br/>';
					}
				} else {
					$data_compare_spec = array();
				}

				if (($datatype == 'all' || $datatype == 'opt') && isset($data['opt'][$date])) {
					print '<br/>Vendor optional:<br/>';

					foreach($data['opt'][$date] as $opt) {
						unset($opt['host_id']);
						unset($opt['mandatory']);
						unset($opt['scan_date']);

						print $opt['description'] . ': ';
						print display_tooltip('OID: ' . $opt['oid']);

						if (!is_array($opt['value'])) {
							print $opt['value'];
						} else {
							// nested array
							foreach ($opt['value'] as $key => $value) {
								print implode(', ', $value);
							}
						}

						print '<br/>';
					}

				}
				print '</dd>';
			}
		}
	} else {
		print __('History data store disabled', 'evidence');
	}
	print '</dl>';
}



function evidence_show_actual_data ($data) {
	global $config;

	include_once($config['base_path'] . '/plugins/evidence/include/arrays.php');

	if (isset($data['org_name'])) {
		print $data['org_name'];
	}

	if (isset($data['org_id'])) {
		print ' (' . $data['org_id'] . ')';
	}

	if (isset($data['entity'])) {
		print '<br/><b>Entity MIB:</b><br/>';
		print '<table class="cactiTable"><tr>';

		foreach ($data['entity'] as $row) {
			print '<td>';
			foreach ($row as $key => $value) {
				if ($value != '') {
					print $key . ': ' . $value . '<br/>';
				}
			}
			print '</td>';

		}
		print '</tr></table>';
	}

	if (isset($data['mac'])) {
		$count = 0;
		print '<br/><b>MAC:</b><br/>';
		print '<table class="cactiTable"><tr>';

		foreach ($data['mac'] as $mac) {
			print '<td>' . $mac . '</td>';
			$count++;
			if ($count > 4) {
				$count = 0;
				print '</tr><tr>';
			}
		}
		print '</tr></table>';
	}

	if (isset($data['ip'])) {
		$count = 0;
		print '<br/><b>IP:</b><br/>';
		print '<table class="cactiTable"><tr>';

		foreach ($data['ip'] as $ip) {
			print '<td>' . $ip . '</td>';
			$count++;
			if ($count > 4) {
				$count = 0;
				print '</tr><tr>';
			}
		}
		print '</tr></table>';
	}

	if (isset($data['spec'])) {
		print '<br/><b>Vendor specific:</b><br/>';

		foreach ($data['spec'] as $row) {
			print $row['description'] . ': ';
			print display_tooltip('OID: ' . $row['oid']);

			if (!is_array($row['value'])) {
				print $row['value'] . '</br>';
			} else {
				// nested array
				foreach ($row['value'] as $key => $value) {
					print implode(', ', $value);
				}
				print '<br/>';
			}
		}
	}

	if (isset($data['opt'])) {
		print '<br/><b>Vendor optional:</b><br/>';

		foreach ($data['opt'] as $row) {
			print $row['description'] . ': ';
			print display_tooltip('OID: ' . $row['oid']);

			if (!is_array($row['value'])) {
				print $row['value'] . '</br>';
			} else {
				// nested array
				foreach ($row['value'] as $key => $value) {
					print implode(', ', $value);
				}
				print '<br/>';
			}
		}
	}
}

function plugin_evidence_array_to_table ($array, $columns = 1) {

	$output = '';
	$col = 0;

	if (cacti_sizeof($array)) {
		$output .= '<table>';
			$output .= '<tr>';

		foreach ($array as $item) {


			if (is_array($item)) {
				foreach ($item as $key => $value) {
					$output .= '<td>' . $key . ' = ' . $value  . '</td>';
					$col++;
					if ($col >= $columns) {
						$output .= '</tr><tr>';
						$col = 0;
					}
				}
			} else {
					$output .= '<td>' . $item . '</td>';
			}
			
			$col++;

			if ($col >= $columns) {
				$output .= '</tr><tr>';
				$col = 0;
			}
		}
		$output .= '</table>';
	}

	return $output;
}
