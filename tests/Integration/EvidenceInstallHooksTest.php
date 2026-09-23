<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Integration coverage for plugin_evidence_install(): verifies every hook
 * and the realm the plugin depends on at runtime are actually registered,
 * together with the tables it needs, in a single end-to-end pass.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_registered_hooks']  = array();
	$GLOBALS['__test_registered_realms'] = array();
	$GLOBALS['__test_db_calls']          = array();
});

it('registers every hook evidence depends on, its realm, and provisions its tables', function () {
	plugin_evidence_install();

	$hooks = array();
	foreach ($GLOBALS['__test_registered_hooks'] as $registered) {
		$hooks[$registered['hook']] = $registered;
	}

	foreach (array('device_edit_top_links', 'top_header_tabs', 'top_graph_header_tabs', 'device_remove', 'config_settings', 'poller_bottom', 'host_edit_bottom') as $expected) {
		expect($hooks)->toHaveKey($expected);
		expect($hooks[$expected]['name'])->toBe('evidence');
	}

	expect($GLOBALS['__test_registered_realms'])->toHaveCount(1);
	expect($GLOBALS['__test_registered_realms'][0]['file'])->toBe('evidence.php,evidence_tab.php,');

	$tables = array_column(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'api_plugin_db_table_create';
	}), 'sql');

	expect($tables)->toContain('plugin_evidence_organization');
});
