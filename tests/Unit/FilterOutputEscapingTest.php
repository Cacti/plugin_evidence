<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('evidence unit escaping helpers', function () {
	it('encodes HTML in filter values', function () {
		$payload = '\'"<script>alert(1)</script>';

		$escaped = html_escape($payload);

		expect($escaped)->not->toContain('<script>');
		expect($escaped)->toContain('&quot;');
		expect($escaped)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
	});
});
