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
 * (which drives includes/database.php's plugin_evidence_upgrade_database()).
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

	expect($drops)->toHaveCount(7);
});

it('does nothing when the stored version already matches the plugin version', function () {
	$info = plugin_evidence_version();

	evidence_test_mock_db('db_fetch_cell', 'plugin_config', $info['version']);

	expect(plugin_evidence_check_config())->toBeTrue();
	expect($GLOBALS['__test_db_calls'])->toBeEmpty();
});

it('updates the stored plugin_config version when it drifts', function () {
	// Load the schema library from the real checkout, then sandbox base_path so
	// the upgrade-time prune (plugin_evidence_prune_files) runs against a temp
	// tree, never the real checkout. A minimal INFO drives the version-drift
	// path; no manifest.json there means the prune no-ops.
	require_once $GLOBALS['config']['base_path'] . '/plugins/evidence/includes/database.php';

	evidence_test_mock_db('db_fetch_cell', 'plugin_config', '0.0.0');

	$restore = $GLOBALS['config']['base_path'];
	$base    = sys_get_temp_dir() . '/evidence-upg-' . uniqid();
	mkdir($base . '/plugins/evidence', 0777, true);
	file_put_contents($base . '/plugins/evidence/INFO', "[info]\nversion = 9.9.9\nauthor = x\nhomepage = x\n");
	$GLOBALS['config']['base_path'] = $base;

	try {
		plugin_evidence_upgrade_database();
	} finally {
		$GLOBALS['config']['base_path'] = $restore;
	}

	$updates = array_values(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute_prepared' && stripos($call['sql'], 'UPDATE plugin_config') !== false;
	}));

	expect($updates)->toHaveCount(1);
});
