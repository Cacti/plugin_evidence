<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('evidence filter xss regression wiring', function () {
	it('keeps raw request variables out of the filter value attribute', function () {
		$contents = file_get_contents(realpath(__DIR__ . '/../../evidence_tab.php'));

		expect($contents)->not->toContain('value="' . "' . get_request_var('find_text') . '");
		expect($contents)->toContain('value="' . "' . html_escape(get_request_var('find_text')) . '");
	});
});
