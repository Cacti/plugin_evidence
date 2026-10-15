<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for plugin_evidence_setup_database() in setup.php, which
 * drives includes/database.php's plugin_evidence_initialize_database().
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls'] = array();
});

it('creates every table the plugin owns', function () {
	plugin_evidence_setup_database();

	$tables = array_column(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'api_plugin_db_table_create';
	}), 'sql');

	expect($tables)->toEqualCanonicalizing(array(
		'plugin_evidence_organization',
		'plugin_evidence_snmp_info',
		'plugin_evidence_specific_query',
		'plugin_evidence_entity',
		'plugin_evidence_vendor_specific',
		'plugin_evidence_mac',
		'plugin_evidence_ip',
	));
});

it('declares scalar primary keys for api_plugin_db_table_create', function () {
	// api_plugin_db_table_create() interpolates data['primary'] straight into
	// PRIMARY KEY (`...`) down to the 1.2.29 compat floor, so an array would
	// render as PRIMARY KEY (`Array`) and fail the create on a fresh install.
	plugin_evidence_setup_database();

	$withPrimary = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'api_plugin_db_table_create' && isset($call['params']['primary']);
	});

	expect($withPrimary)->not->toBeEmpty();

	foreach ($withPrimary as $call) {
		expect($call['params']['primary'])->toBeString();
	}
});

it('seeds the vendor-specific query catalog', function () {
	plugin_evidence_setup_database();

	$inserts = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute_prepared' && stripos($call['sql'], 'INSERT INTO plugin_evidence_specific_query') !== false;
	});

	expect($inserts)->not->toBeEmpty();
});
