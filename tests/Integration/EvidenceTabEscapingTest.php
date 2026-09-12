<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('evidence tab integration escaping', function () {
	it('escapes scan date values rendered into select options', function () {
		$contents = file_get_contents(realpath(__DIR__ . '/../../evidence_tab.php'));

		expect($contents)->toContain('html_escape($scan_date)');
	});

	it('escapes entity keys and values rendered into select options', function () {
		$contents = file_get_contents(realpath(__DIR__ . '/../../evidence_tab.php'));

		expect($contents)->toContain('html_escape($key)');
		expect($contents)->toContain('html_escape($value)');
	});
});
