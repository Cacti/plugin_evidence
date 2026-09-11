<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('evidence setup.php structure', function () {
	$source = file_get_contents(realpath(__DIR__ . '/../../setup.php'));
	$infoFile = parse_ini_file(__DIR__ . '/../../INFO', true);
	$info = $infoFile['info'];

	it('defines plugin_evidence_install function', function () use ($source) {
		expect($source)->toContain('function plugin_evidence_install');
	});

	it('defines plugin_evidence_version function', function () use ($source) {
		expect($source)->toContain('function plugin_evidence_version');
	});

	it('defines plugin_evidence_uninstall function', function () use ($source) {
		expect($source)->toContain('function plugin_evidence_uninstall');
	});

	it('declares a plugin name in INFO', function () use ($info) {
		expect($info)->toHaveKey('name');
	});

	it('declares a plugin version in INFO', function () use ($info) {
		expect($info)->toHaveKey('version');
	});

	it('registers hooks in install function', function () use ($source) {
		expect($source)->toContain('api_plugin_register_hook');
	});
});
