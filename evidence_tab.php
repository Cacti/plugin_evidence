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

chdir('../../');
include_once('./include/auth.php');
include_once('./lib/snmp.php');
include_once('./plugins/evidence/include/functions.php');
include_once('./plugins/evidence/include/arrays.php');

set_default_action();

$selectedTheme = get_selected_theme();

switch (html_escape_request_var('action')) {
	case 'ajax_hosts':
		$sql_where = '';
		get_allowed_ajax_hosts(true, 'applyFilter', $sql_where);
		break;

	case 'setting':
		evidence_save_settings();
		break;

	case 'find':
		general_header();
		evidence_display_form();
		evidence_find();
		bottom_footer();
		break;

	default:
		general_header();
		evidence_display_form();
		evidence_stats();
		bottom_footer();
		break;
}


function evidence_display_form() {
	global $config, $entities, $datatypes;

	$evidence_records   = read_config_option('evidence_records');
	$evidence_frequency = read_config_option('evidence_frequency');

	print get_md5_include_js($config['base_path'] . '/plugins/evidence/js/evidence.js');

	$host_where = '';

	$host_id = get_filter_request_var('host_id');
	$template_id = get_filter_request_var('template_id');

	if (get_nfilter_request_var('scan_date') == -1 || get_nfilter_request_var('scan_date') == -2) {
		$scan_date = get_nfilter_request_var('scan_date');
	} else {
		$scan_date = get_filter_request_var ('scan_date', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', 'default' => -1)));
	}

	$find_text = get_filter_request_var('find_text', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z0-9_\-\.:\+ ]+)$/', 'default' => 'INCORRECT: ' . html_escape('find_text'))));
	form_start(html_escape(basename($_SERVER['PHP_SELF'])), 'form_evidence');

	html_start_box('<strong>' . __('Evidence', 'evidence') . '</strong>', '100%', '', '3', 'center', '');

	print "<tr class='even noprint'>";
	print "<td>";
	print "<form id='form_devices'>";
	print "<table class='filterTable'>";
	print "<tr>";

	print html_host_filter($host_id, 'applyFilter', $host_where, false, true);

	print "<td>";
	print __('Template', 'evidence');
	print "</td>";
	print "<td>";

	print "<select id='template_id' name='template_id'>";
	print "<option value='-1'" . (get_filter_request_var('template_id') == '-1' ? ' selected' : '') . '>' . __('Any', 'evidence') . '</option>';

	$templates = db_fetch_assoc('SELECT id, name FROM host_template');

	if (cacti_sizeof($templates)) {
		foreach ($templates as $template) {
			print '<option value="' . $template['id'] . '"' .
			(get_filter_request_var('template_id') == $template['id'] ? ' selected="selected"' : '') . '>' .
			html_escape($template['name']) . '</option>';
		}
	}

	print '</select>';
	print '</td>';

	print '<td>';
	print __('Scan date', 'evidence');
	print '</td>';
	print '<td>';

	print '<select id="scan_date" name="scan_date">';
	print '<option value="-2" ' . ($scan_date == -2 ? 'selected="selected"' : '') . '>' . __('Only last changed states', 'evidence') . '</option>';
	print '<option value="-1" ' . ($scan_date == -1 ? 'selected="selected"' : '') . '>' . __('All history', 'evidence') . '</option>';

	$scan_dates = db_fetch_assoc('SELECT DATE(scan_date) AS scan_date, COUNT(*) AS records
		FROM (
			SELECT scan_date FROM plugin_evidence_snmp_info
			UNION ALL
			SELECT scan_date FROM plugin_evidence_entity
			UNION ALL
			SELECT scan_date FROM plugin_evidence_mac
			UNION ALL
			SELECT scan_date FROM plugin_evidence_ip
			UNION ALL
			SELECT scan_date FROM plugin_evidence_vendor_specific
		) AS all_records
		GROUP BY DATE(scan_date)
		ORDER BY scan_date DESC');

	if (cacti_sizeof($scan_dates)) {
		foreach ($scan_dates as $sdate) {
			print '<option value="' . $sdate['scan_date'] . '" ' . 
				($scan_date == $sdate['scan_date'] ? ' selected="selected"' : '') . 
				'>' . $sdate['scan_date'] . ' (' . $sdate['records'] . ' ' . __('records changed', 'evidence') . ')</option>';
		}
	}

	print '</select>';

	print '</td>';
	print '<td>';
	print '<input type="submit" class="ui-button ui-corner-all ui-widget" id="refresh" value="' . __('Go') . '" title="' . __esc('Find') . '">';
	print '<input type="button" class="ui-button ui-corner-all ui-widget" id="clear" value="' . __('Clear') . '" title="' . __esc('Clear Filters') . '">';
	print '<input type="hidden" name="action" value="find">';
	print '</td>';
	print '</tr>';
	print '</table>';

	print "<table class='filterTable'>";
	print '<tr>';
	print '<td>';
	print __('Search', 'evidence');
	print '</td>';
	print '<td>';
	print '<input type="text" name="find_text" id="find_text" value="' . html_escape($find_text) . '">';
	print '</td>';
	print '<td>';
	print __('You can search serial number, firmware version, ip, mac address,...', 'evidence');
	print '</td>';
	print '</tr>';
	print '</table>';

	print "<table class='filterTable'>";
	print '<tr>';
	print '<td>';

	if (get_nfilter_request_var('action') == 'find') {
		evidence_show_checkboxes();
	}

	print '</td>';
	print '</tr>';
	print '</table>';

	form_end(false);

	html_end_box();
}


function evidence_find() {
	global $entities;

	$templates = db_fetch_assoc('SELECT id, name FROM host_template');

	if (in_array(get_filter_request_var('host_id'), plugin_evidence_get_allowed_devices($_SESSION['sess_user_id'], true))) {
		$host_id = get_filter_request_var('host_id');
	}

	if (get_nfilter_request_var('scan_date') == -1 || get_nfilter_request_var('scan_date') == -2) {
		$scan_date = get_nfilter_request_var('scan_date');
	} else {
		$scan_date = get_filter_request_var ('scan_date', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', 'default' => -1)));
	}

	if (in_array(get_filter_request_var('template_id'), array_column($templates, 'id'))) {
		$template_id = get_filter_request_var('template_id');
	}

	$find_text = get_filter_request_var ('find_text', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z0-9_\-\.:\+ ]+)$/')));
	if (empty($find_text)) {
		unset($find_text);
	}

	if (isset($find_text) && (strlen($find_text) < 3 || strlen($find_text) > 20)) {
		print __('Search string must be 3-20 characters', 'evidence');
		return false;
	}

	if (isset($host_id)) {
		evidence_show_host_data($host_id, $scan_date);
	} else if (isset($template_id)) {
		$hosts = db_fetch_assoc_prepared('SELECT id FROM host
			WHERE host_template_id = ?',
			array($template_id));

		if (cacti_sizeof($hosts) > 0) {
			foreach ($hosts as $host) {
				evidence_show_host_data($host['id'], $scan_date);
			}
		}
	}

	if (isset($find_text)) {
		plugin_evidence_find();
	}

	if (!isset($host_id) && !isset($template_id) && $scan_date > 0) {
		print "<h3>$scan_date</h3>";

		$ids_info   = array_column(db_fetch_assoc_prepared('SELECT distinct(host_id) FROM plugin_evidence_snmp_info WHERE date(scan_date) = ?', array($scan_date)), 'host_id');
		$ids_entity = array_column(db_fetch_assoc_prepared('SELECT distinct(host_id) FROM plugin_evidence_entity WHERE date(scan_date) = ?', array($scan_date)), 'host_id');
		$ids_ip     = array_column(db_fetch_assoc_prepared('SELECT distinct(host_id) FROM plugin_evidence_ip WHERE date(scan_date) = ?', array($scan_date)), 'host_id');
		$ids_mac    = array_column(db_fetch_assoc_prepared('SELECT distinct(host_id) FROM plugin_evidence_mac WHERE date(scan_date) = ?', array($scan_date)), 'host_id');
		$ids_vendor = array_column(db_fetch_assoc_prepared('SELECT distinct(host_id) FROM plugin_evidence_vendor_specific WHERE date(scan_date) = ?', array($scan_date)), 'host_id');

		$merged = array_unique(array_merge($ids_info, $ids_entity, $ids_ip, $ids_mac, $ids_mac));

		foreach ($merged as $item) {
			evidence_show_host_data($item, $scan_date);
		}
	}

	if (!isset($host_id) && !isset($template_id) && !isset($find_text) && $scan_date < 0) {
		print __('Select any device, template, select any scan date or try search', 'evidence');
	}

}

function evidence_stats() {
	global $config;

	$evidence_records   = read_config_option('evidence_records');
	$evidence_frequency = read_config_option('evidence_frequency');

	if ($evidence_frequency == 0 || $evidence_records == 0) {
		print __('No data. Allow periodic scan and store history in settings', 'evidence');
	}

	print '<br/><br/>';
	print __('Device or template - You can display information about specific host, all devices with the same template.', 'evidence') . '<br/><br/>';

	print __('Scan date (Only last changes or All history) - You can choose whether to retrieve only the most recent change or the entire history.', 'evidence') . '<br/>';
	print __('If you use only `Scan Date`, the changes for all devices found during this scan will be displayed', 'evidence') . '<br/><br/>';

	print __('You can search any string in all data.', 'evidence') . '<br/>';

	$dev = db_fetch_cell ('SELECT SUM(total) from ( SELECT COUNT(DISTINCT(host_id)) AS total FROM plugin_evidence_entity 
		UNION SELECT COUNT(DISTINCT(host_id)) AS total FROM plugin_evidence_mac
		UNION SELECT COUNT(DISTINCT(host_id)) AS total FROM plugin_evidence_ip 
		UNION SELECT COUNT(DISTINCT(host_id)) AS total FROM plugin_evidence_vendor_specific) AS t1');
	$vnd = db_fetch_cell ('SELECT count(distinct(organization_id)) FROM plugin_evidence_entity');
	$ent = db_fetch_cell ('SELECT COUNT(*) FROM plugin_evidence_entity');
	$mac = db_fetch_cell ('SELECT COUNT(distinct(mac)) FROM plugin_evidence_mac');
	$ip  = db_fetch_cell ('SELECT COUNT(distinct(ip_mask)) FROM plugin_evidence_ip');
	$ven = db_fetch_cell ('SELECT COUNT(*) FROM plugin_evidence_vendor_specific');
	$old = db_fetch_cell ('SELECT MIN(scan_date) FROM plugin_evidence_entity');

	print '<br/><br/>';
	print '<strong>' . __('Number of records', 'evidence') . ':</strong><br/>';
	print __('Devices: %d records', $dev, 'evidence') . '<br/>';
	print __('Entity MIB: %d records, %d vendors', $ent, $vnd, 'evidence') . '<br/>';
	print __('Unique MAC addresses: %d', $mac, 'evidence') . '<br/>';
	print __('Unique IP addresses: %d', $ip, 'evidence') . '<br/>';
	print __('Vendor specific data: %d', $ven, 'evidence') . '<br/>';
	print __('Oldest record: %d', $old, 'evidence') . '<br/>';
	print '<br/><br/>';

	$treemap = array(
		'label' => array(),
		'data'  => array(),
	);

	$vendors = db_fetch_assoc('SELECT count(distinct(host_id)) AS `count`, organization
		FROM plugin_evidence_entity AS pee
		JOIN plugin_evidence_organization AS peo
		ON pee.organization_id = peo.id
		GROUP BY pee.organization_id');

	if (cacti_sizeof($vendors)) {

		$data = array();

		foreach ($vendors as $vendor) {
			array_push($treemap['label'], $vendor['organization']);
			array_push($treemap['data'], $vendor['count']);
		}
		
		print '<strong>' . __('Vendors', 'evidence') . ':</strong><br />';
		evidence_treemap('Vendors', $treemap);
	}
}

function evidence_show_checkboxes() {
	global $datatypes;

	print "<table class='filterTable'>";

	print '<tr>';
	print '<td>' . __('Show or hide', 'evidence') . ':</td>';

	foreach ($datatypes as $key => $value) {
		print '<td>';
		print '<input type="checkbox" id="ch_' . $key . '" name="ch_' . $key . '" value="1" ' . (read_user_setting('evidence_display_' . $key, true) ? ' checked="checked" ' : '') . '>';
		print '<label for="ch_' . $key . '">' . $value . '</label>';
		print '</td>';
	}

	print '<td>';
	print '</td>';
	print '<td>';
	print '<input type="checkbox" id="ch_expand" name="ch_expand" value="1">';
	print '<label for="ch_expand" class="bold">' . __('Expand all dates', 'evidence') . '</label>';
	print '<input type="checkbox" id="ch_expand_latest" name="ch_expand_latest" value="1">';
	print '<label for="ch_expand_latest" class="bold">' . __('Expand latest date', 'evidence') . '</label>';
	print '</td>';
	print '</tr>';
	print '</table>';
}


function evidence_treemap($title, $data) {

	$xid = 'treemap_x'. substr(md5($title), 0, 7);

	print '<style>';
	print '#' . $xid . ' text {';
	print 'transform: translate(0, 20px) !important;}';
	print '</style>';

	print '<div class="chart_wrapper center" id="' . $xid . '"></div>';
	print '<script type="text/javascript">';
	print $xid . ' = bb.generate({';
	print ' bindto: "#' . $xid . '",';

	print ' size: {';
	print '  width: 700,';
	print '  height: 350';
	print ' },';

	print ' data: {';
	print '  columns: [';

	foreach ($data['data'] as $key => $value) {
		print "['" . $data['label'][$key] . "', " . $value . "],";
	}

	print '  ],';
	print '  type: "treemap",';
	print '  labels: {';
	print '    colors: "#fff"';
	print '  }';
	print '  },';

	print '  treemap: {';
	print '    label: {';
	print '      threshold: 0.03, show: true';
	print '    },';
	print '  },';

	print '});';
	print '</script>';
}


function evidence_save_settings() {
	switch (get_nfilter_request_var('what')) {
		case 'info':
			if (read_user_setting('evidence_display_info', true)) {
				set_user_setting('evidence_display_info', '');
			} else {
				set_user_setting('evidence_display_info', 'on');
			}
			break;

		case 'entity':
			if (read_user_setting('evidence_display_entity', true)) {
				set_user_setting('evidence_display_entity', '');
			} else {
				set_user_setting('evidence_display_entity', 'on');
			}
			break;

		case 'mac':
			if (read_user_setting('evidence_display_mac', true)) {
				set_user_setting('evidence_display_mac', '');
			} else {
				set_user_setting('evidence_display_mac', 'on');
			}
			break;

		case 'ip':
			if (read_user_setting('evidence_display_ip', true)) {
				set_user_setting('evidence_display_ip', '');
			} else {
				set_user_setting('evidence_display_ip', 'on');
			}
			break;

		case 'spec':
			if (read_user_setting('evidence_display_spec', true)) {
				set_user_setting('evidence_display_spec', '');
			} else {
				set_user_setting('evidence_display_spec', 'on');
			}
			break;

		case 'opt':
			if (read_user_setting('evidence_display_opt', true)) {
				set_user_setting('evidence_display_opt', '');
			} else {
				set_user_setting('evidence_display_opt', 'on');
			}
			break;
	}
}