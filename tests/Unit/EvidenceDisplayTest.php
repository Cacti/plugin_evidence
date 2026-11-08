<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the HTML-rendering and SNMP-collection helpers in
 * includes/functions.php that drive the device edit page preview
 * (plugin_evidence_host_edit_bottom, evidence_show_host_edit_tables), the
 * evidence tab search and detail views (plugin_evidence_find,
 * evidence_show_host_data, plugin_evidence_display_paragraph) and a single
 * SNMP scan (plugin_evidence_get_snmp_info).
 *
 * These functions only print markup and never return a value, so the tests
 * exercise each decision branch and assert on the emitted HTML. The Cacti
 * rendering, SNMP and tooltip primitives they call are declared here as
 * record-free stubs; the data and SNMP results are supplied through the
 * bootstrap's db/config/request/user-setting seams so each branch is reached
 * deterministically without a live Cacti or network.
 */

if (!function_exists('get_md5_include_js')) {
	function get_md5_include_js($path) {
		return '<script src="' . $path . '"></script>';
	}
}

if (!function_exists('html_start_box')) {
	function html_start_box($title, $width, $div, $cell_padding, $align, $url) {
		print '<div class="box">';
	}
}

if (!function_exists('html_end_box')) {
	function html_end_box($trailing_br = true, $width = '100%') {
		print '</div>';
	}
}

if (!function_exists('html_header')) {
	function html_header($headers, $last_item_colspan = 1, $resizable = true, $sort_column = false, $sort_direction = false, $url = '') {
		print '<tr><th>' . implode('</th><th>', array_map('strval', $headers)) . '</th></tr>';
	}
}

if (!function_exists('form_alternate_row')) {
	function form_alternate_row($id = '', $light = false, $disabled = false, $is_fixed = false) {
		print '<tr>';
	}
}

if (!function_exists('form_end_row')) {
	function form_end_row() {
		print '</tr>';
	}
}

if (!function_exists('display_tooltip')) {
	function display_tooltip($text, $left_align = false) {
		return '<span class="tooltip">' . $text . '</span>';
	}
}

if (!function_exists('cacti_count')) {
	function cacti_count($array) {
		return is_array($array) ? count($array) : 0;
	}
}

if (!function_exists('cacti_oid_numeric_format')) {
	function cacti_oid_numeric_format() {
	}
}

if (!function_exists('cacti_snmp_get')) {
	function cacti_snmp_get(...$args) {
		return array_key_exists('__test_snmp_get', $GLOBALS) ? $GLOBALS['__test_snmp_get'] : 'U';
	}
}

if (!function_exists('cacti_snmp_walk')) {
	function cacti_snmp_walk(...$args) {
		return array_key_exists('__test_snmp_walk', $GLOBALS) ? $GLOBALS['__test_snmp_walk'] : array();
	}
}

beforeAll(function () {
	require_once __DIR__ . '/../../includes/functions.php';

	// find() reads the $datatypes labels as a global without loading
	// includes/arrays.php itself; publish them the way Cacti would.
	evidence_test_load($GLOBALS['config']['base_path'] . '/plugins/evidence/includes/arrays.php');
});

beforeEach(function () {
	evidence_test_reset_db_mocks();
	evidence_test_reset_inputs();
	$GLOBALS['__test_db_calls'] = array();
	$GLOBALS['__test_allowed_devices'] = [['id' => 1], ['id' => 2], ['id' => 3]];
	$GLOBALS['__test_allowed_devices_calls'] = 0;
	$_SESSION['sess_user_id'] = 1;
	plugin_evidence_allowed_lookup(true);
	unset($GLOBALS['__test_snmp_get'], $GLOBALS['__test_snmp_walk']);
});

it('collects the MIB-II system fields for a device scan', function () {
	$host = evidence_test_host();

	$GLOBALS['__test_snmp_get'] = 'router-1';

	$info = plugin_evidence_get_snmp_info($host);

	expect($info)->toBeArray()
		->and($info[0])->toHaveKeys(['sysdescr', 'syscontact', 'sysname', 'syslocation'])
		->and($info[0]['sysname'])->toBe('router-1');
});

it('renders every evidence section, the entity overflow footer and the empty-table state', function () {
	$data = [
		'org_name'  => 'Acme Networks',
		'org_id'    => '9',
		'snmp_info' => [
			['sysname' => 'sw1', 'unmapped_field' => 'keep'],
		],
		'entity'    => [
			['index' => 1, 'descr' => 'Port 1'],
			['index' => 2, 'descr' => 'Port 2'],
			['index' => 3, 'descr' => 'Port 3'],
			['index' => 4, 'descr' => 'Port 4'],
		],
		'mac'       => [],
		'ip'        => ['10.0.0.1/24', '10.0.0.2/24'],
		'spec'      => [
			['description' => 'Scalar', 'oid' => '.1.1', 'value' => 'plain'],
			['description' => 'Nested', 'oid' => '.1.2', 'value' => ['a' => ['x', 'y']]],
		],
		'opt'       => [
			['description' => 'Optional', 'oid' => '.2.1', 'value' => 'opt-value'],
		],
	];

	ob_start();
	evidence_show_host_edit_tables($data, 7);
	$html = ob_get_clean();

	expect($html)->toContain('Acme Networks')
		->and($html)->toContain('sw1')
		->and($html)->toContain('Port 1')
		// Entity overflow footer: four items, preview capped at three.
		->and($html)->toContain('evidence_tab.php?host_id=7')
		// Empty mac section falls back to the "No data" row.
		->and($html)->toContain('No data')
		->and($html)->toContain('plain')
		->and($html)->toContain('opt-value');
});

it('previews a live scan at the bottom of the device edit page', function () {
	evidence_test_set_config_option('evidence_show_host_data', 'on');
	evidence_test_set_request_var('id', 7);

	evidence_test_mock_db('db_fetch_row_prepared', 'FROM host', array_merge(evidence_test_host(), [
		'id'            => 7,
		'description'   => 'Core Switch',
		'hostname'      => '10.0.0.1',
		'template_name' => 'Generic',
	]));

	ob_start();
	plugin_evidence_host_edit_bottom();
	$html = ob_get_clean();

	expect($html)->toContain('evidence.js')
		->and($html)->toContain('<div class="box">');
});

it('skips the edit-page preview when the host data setting is disabled', function () {
	// evidence_show_host_data defaults to '' (off); only the JS include runs.
	ob_start();
	plugin_evidence_host_edit_bottom();
	$html = ob_get_clean();

	expect($html)->toContain('evidence.js')
		->and($html)->not->toContain('<div class="box">');
});

it('groups search matches per evidence category', function () {
	evidence_test_set_config_option('evidence_records', 5);
	evidence_test_set_request_var('find_text', 'needle');

	// Every category query returns no rows, so each section prints "Not found".
	ob_start();
	$result = plugin_evidence_find();
	$html = ob_get_clean();

	expect($result)->toBeNull()
		->and($html)->toContain('paragraph_info')
		->and($html)->toContain('paragraph_entity')
		->and($html)->toContain('paragraph_mac')
		->and($html)->toContain('paragraph_ip')
		->and($html)->toContain('paragraph_spec')
		->and($html)->toContain('paragraph_opt')
		->and(substr_count($html, 'Not found'))->toBeGreaterThan(0);
});

it('stops the search immediately when history storage is disabled', function () {
	evidence_test_set_config_option('evidence_records', 0);

	ob_start();
	$result = plugin_evidence_find();
	$html = ob_get_clean();

	expect($result)->toBeFalse()
		->and($html)->toContain('Nothing to do');
});

it('links each device in a search-result paragraph', function () {
	$GLOBALS['__test_allowed_devices'] = [['id' => 3]];
	ob_start();
	plugin_evidence_display_paragraph([
		['host_id' => 3, 'scan_date' => '2024-05-01 08:30:00', 'record_count' => 4],
		['host_id' => 9, 'scan_date' => '2024-05-01 08:30:00', 'record_count' => 8],
	]);
	$html = ob_get_clean();

	expect($html)->toContain('host_id=3')
		->and($html)->not->toContain('host_id=9')
		->and($html)->toContain('scan_date=2024-05-01');
});

it('renders no search links when the user has no allowed devices', function () {
	$GLOBALS['__test_allowed_devices'] = [];
	ob_start();
	plugin_evidence_display_paragraph([
		['host_id' => 3, 'scan_date' => '2024-05-01 08:30:00', 'record_count' => 4],
	]);
	$html = ob_get_clean();

	expect($html)->toContain('Not found')->not->toContain('host_id=3');
});

it('does not display evidence details for a device outside the user permissions', function () {
	$GLOBALS['__test_allowed_devices'] = [['id' => 9]];
	ob_start();
	evidence_show_host_data(3, -1);
	$html = ob_get_clean();

	expect($html)->toBe('')
		->and($GLOBALS['__test_db_calls'])->toBeEmpty();
});

it('limits template and scan-date evidence results to allowed devices', function () {
	$GLOBALS['__test_allowed_devices'] = [['id' => 4]];
	evidence_test_set_request_var('template_id', 5);
	evidence_test_set_request_var('scan_date', -1);
	evidence_test_mock_db('db_fetch_assoc', 'FROM host_template', [['id' => 5, 'name' => 'Router']]);
	evidence_test_mock_db('db_fetch_assoc_prepared', 'SELECT id FROM host', function ($sql, $params) {
		expect($sql)->toContain('id IN (4)');
		expect($params)->toBe([5]);
		return [['id' => 4], ['id' => 9]];
	});
	ob_start();
	evidence_find();
	$template_html = ob_get_clean();
	expect($template_html)->toContain('host_id=4')->not->toContain('host_id=9');

	evidence_test_reset_db_mocks();
	evidence_test_reset_inputs();
	plugin_evidence_allowed_lookup(true);
	$GLOBALS['__test_allowed_devices'] = [['id' => 4]];
	evidence_test_set_request_var('scan_date', '2024-05-01');
	evidence_test_mock_db('db_fetch_assoc_prepared', 'SELECT distinct(host_id)', [['host_id' => 4], ['host_id' => 9]]);
	evidence_test_mock_db('db_fetch_row_prepared', 'FROM host', ['id' => 4, 'description' => 'Allowed']);
	ob_start();
	evidence_find();
	$date_html = ob_get_clean();
	expect($date_html)->toContain('host_id=4')->not->toContain('host_id=9');
});

it('reports a failed search query as not found', function () {
	// A search such as '+++' passes the find_text filter but is an invalid
	// RLIKE pattern, so the database layer returns false instead of rows.
	ob_start();
	plugin_evidence_display_paragraph(false);
	$html = ob_get_clean();

	expect($html)->toContain('Not found');
});

it('computes the allowed devices once per request', function () {
	$GLOBALS['__test_allowed_devices'] = [['id' => 3]];
	ob_start();
	plugin_evidence_display_paragraph([['host_id' => 3, 'scan_date' => '2024-05-01 08:30:00', 'record_count' => 1]]);
	plugin_evidence_display_paragraph([['host_id' => 3, 'scan_date' => '2024-05-01 08:30:00', 'record_count' => 1]]);
	evidence_show_host_data(9, -1);
	ob_end_clean();

	expect($GLOBALS['__test_allowed_devices_calls'])->toBe(1);
});

it('shows an allowed device requested by host_id', function () {
	$GLOBALS['__test_allowed_devices'] = [['id' => 3]];
	evidence_test_set_request_var('host_id', 3);
	evidence_test_set_request_var('scan_date', -1);
	evidence_test_mock_db('db_fetch_row_prepared', 'FROM host', ['id' => 3, 'description' => 'Allowed']);
	ob_start();
	evidence_find();
	$html = ob_get_clean();

	expect($html)->toContain('host_id=3');
});

it('ignores a requested host_id outside the user permissions', function () {
	$GLOBALS['__test_allowed_devices'] = [['id' => 3]];
	evidence_test_set_request_var('host_id', 9);
	evidence_test_set_request_var('scan_date', -1);
	ob_start();
	evidence_find();
	$html = ob_get_clean();

	expect($html)->toContain('Select any device')->not->toContain('host_id=9');
});

it('falls back to no scan date when the requested date is malformed', function () {
	evidence_test_set_request_var('scan_date', '2024-5-1');
	ob_start();
	evidence_find();
	$html = ob_get_clean();

	expect($html)->toContain('Select any device')->not->toContain('<h3>');
});

it('rejects search text the filter does not accept', function () {
	evidence_test_set_request_var('scan_date', -1);
	evidence_test_set_request_var('find_text', "a'b");

	expect(fn () => evidence_find())->toThrow(RuntimeException::class);
});

it('rejects search text outside the allowed length', function () {
	evidence_test_set_request_var('scan_date', -1);
	evidence_test_set_request_var('find_text', 'ab');
	ob_start();
	$result = evidence_find();
	$html = ob_get_clean();

	expect($result)->toBeFalse()
		->and($html)->toContain('Search string must be 3-20 characters');
});

it('runs the evidence search for valid search text', function () {
	evidence_test_set_config_option('evidence_records', 5);
	evidence_test_set_request_var('scan_date', -1);
	evidence_test_set_request_var('find_text', '+++');
	evidence_test_mock_db('db_fetch_assoc_prepared', 'plugin_evidence_snmp_info', false);
	ob_start();
	evidence_find();
	$html = ob_get_clean();

	expect($html)->toContain('paragraph_info')->toContain('Not found');
});

it('accepts only the exact scan_date mode values', function ($raw, $expected) {
	evidence_test_set_request_var('scan_date', $raw);

	expect(plugin_evidence_get_scan_date())->toBe($expected);
})->with([
	['-1', -1],
	['-2', -2],
	[-2, -2],
	[' -1', -1],
	['-1e0', -1],
	['-2 ', -1],
	['2024-05-01', '2024-05-01'],
	['2024-05-01<x>', -1],
]);

it('links actual data with the validated scan date', function () {
	evidence_test_set_request_var('scan_date', '-2e0');
	evidence_test_set_request_var('template_id', 5);
	evidence_test_mock_db('db_fetch_row_prepared', 'FROM host', ['id' => 3, 'description' => 'Allowed']);
	ob_start();
	evidence_show_host_data(3, plugin_evidence_get_scan_date());
	$html = ob_get_clean();

	expect($html)->toContain('host_id=3&template_id=5&scan_date=-1&actual=1')
		->not->toContain('-2e0');
});

it('reports an empty search-result paragraph', function () {
	ob_start();
	plugin_evidence_display_paragraph([]);
	$html = ob_get_clean();

	expect($html)->toContain('Not found');
});

it('lists stored history and flags changed scans across dates', function () {
	evidence_test_set_config_option('evidence_records', 5);

	evidence_test_mock_db('db_fetch_row_prepared', 'FROM host', [
		'id'            => 1,
		'description'   => 'Edge Router',
		'hostname'      => '10.1.1.1',
		'template_name' => 'Router',
	]);

	// Two SNMP-info snapshots with differing content: the older scan is
	// compared against the newer one and marked as changed.
	evidence_test_mock_db('db_fetch_assoc_prepared', function ($sql, $params) {
		return strpos($sql, 'plugin_evidence_snmp_info') !== false;
	}, [
		['host_id' => 1, 'scan_date' => '2024-05-02 09:00:00', 'sysname' => 'edge-new'],
		['host_id' => 1, 'scan_date' => '2024-05-01 09:00:00', 'sysname' => 'edge-old'],
	]);

	ob_start();
	evidence_show_host_data(1, -1);
	$html = ob_get_clean();

	expect($html)->toContain('Edge Router')
		->and($html)->toContain('drillDown')
		->and($html)->toContain('Changed');
});

it('merges actual scan data into the history listing when requested', function () {
	evidence_test_set_config_option('evidence_records', 5);
	evidence_test_set_request_var('actual', 1);

	evidence_test_mock_db('db_fetch_row_prepared', 'FROM host', array_merge(evidence_test_host(), [
		'id'            => 2,
		'description'   => 'Access Switch',
		'hostname'      => '10.2.2.2',
		'template_name' => 'Switch',
	]));

	evidence_test_mock_db('db_fetch_assoc_prepared', function ($sql, $params) {
		return strpos($sql, 'plugin_evidence_snmp_info') !== false;
	}, [
		['host_id' => 2, 'scan_date' => '2024-05-01 09:00:00', 'sysname' => 'access-1'],
	]);

	// find_organization() sees 'U' and returns false, so the live scan stays
	// limited to the system/entity/mac/ip collectors (all returning nothing).
	$GLOBALS['__test_snmp_get'] = 'U';

	ob_start();
	evidence_show_host_data(2, -1);
	$html = ob_get_clean();

	expect($html)->toContain('Access Switch')
		->and($html)->toContain('drillDown');
});

it('renders a table with the array-to-table helper', function () {
	$html = plugin_evidence_array_to_table(['alpha', ['k' => 'v']], 1);

	expect($html)->toContain('<table>')
		->and($html)->toContain('alpha')
		->and($html)->toContain('k = v');
});

/**
 * A representative Cacti host row with every SNMP connection field the
 * collectors read, so the stubbed cacti_snmp_* calls never touch an
 * undefined index under the suite's strict error reporting.
 *
 * @return array<string, string>
 */
function evidence_test_host() {
	return [
		'id'                   => '1',
		'hostname'             => '127.0.0.1',
		'snmp_community'       => 'public',
		'snmp_version'         => '2',
		'snmp_username'        => '',
		'snmp_password'        => '',
		'snmp_auth_protocol'   => '',
		'snmp_priv_passphrase' => '',
		'snmp_priv_protocol'   => '',
		'snmp_context'         => '',
		'snmp_port'            => '161',
		'snmp_timeout'         => '500',
	];
}
