<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the plugin lifecycle contract functions in setup.php:
 * plugin_evidence_uninstall(), plugin_evidence_has_data(),
 * plugin_evidence_remove_data(), and plugin_evidence_check_config()
 * (which drives include/database.php's plugin_evidence_upgrade_database()).
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	evidence_test_reset_db_mocks();
	$GLOBALS['__test_db_calls'] = array();
});

it('reports that it always has data', function () {
	expect(plugin_evidence_has_data())->toBeTrue();
});

it('performs no work on uninstall and reports success', function () {
	expect(plugin_evidence_uninstall())->toBeTrue();
});

it('drops every table it owns on remove_data', function () {
	plugin_evidence_remove_data();

	$drops = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute_prepared' && stripos($call['sql'], 'DROP TABLE') !== false;
	});

	expect($drops)->toHaveCount(6);
});

it('does nothing when the stored version already matches the plugin version', function () {
	$info = plugin_evidence_version();

	evidence_test_mock_db('db_fetch_cell', 'plugin_config', $info['version']);

	expect(plugin_evidence_check_config())->toBeTrue();
	expect($GLOBALS['__test_db_calls'])->toBeEmpty();
});

it('updates the stored plugin_config version when it drifts', function () {
	evidence_test_mock_db('db_fetch_cell', 'plugin_config', '0.0.0');

	expect(plugin_evidence_check_config())->toBeTrue();

	$updates = array_values(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute_prepared' && stripos($call['sql'], 'UPDATE plugin_config') !== false;
	}));

	expect($updates)->toHaveCount(1);
});
