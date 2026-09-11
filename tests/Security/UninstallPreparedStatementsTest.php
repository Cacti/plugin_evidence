<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('evidence uninstall drop statements', function () {
	$setup = file_get_contents(realpath(__DIR__ . '/../../setup.php'));

	it('drops plugin_evidence_specific_query with a prepared statement', function () use ($setup) {
		expect($setup)->toContain("db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_specific_query`', []);");
	});

	it('drops plugin_evidence_organization with a prepared statement', function () use ($setup) {
		expect($setup)->toContain("db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_organization`', []);");
	});

	it('drops plugin_evidence_entity with a prepared statement', function () use ($setup) {
		expect($setup)->toContain("db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_entity`', []);");
	});

	it('drops plugin_evidence_mac with a prepared statement', function () use ($setup) {
		expect($setup)->toContain("db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_mac`', []);");
	});

	it('drops plugin_evidence_ip with a prepared statement', function () use ($setup) {
		expect($setup)->toContain("db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_ip`', []);");
	});

	it('drops plugin_evidence_vendor_specific with a prepared statement', function () use ($setup) {
		expect($setup)->toContain("db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_vendor_specific`', []);");
	});

	it('does not rely on SHOW TABLES LIKE pre-checks', function () use ($setup) {
		expect($setup)->not->toMatch('/SHOW TABLES LIKE\s+[\'"]plugin_evidence_/i');
	});

	it('does not leave raw db_execute drop statements', function () use ($setup) {
		expect($setup)->not->toMatch('/db_execute\s*\(\s*["\']DROP TABLE\s+`plugin_evidence_/i');
	});
});
