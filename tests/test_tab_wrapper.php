<?php

require_once __DIR__ . '/../include/ui_helpers.php';

$events = [];

function general_header() {
	global $events;
	$events[] = 'header';
}

function evidence_display_form() {
	global $events;
	$events[] = 'form';
}

function bottom_footer() {
	global $events;
	$events[] = 'footer';
}

function evidence_tab_test_content() {
	global $events;
	$events[] = 'content';
}

function assert_same($expected, $actual, $message) {
	if ($expected !== $actual) {
		fwrite(STDERR, $message . PHP_EOL);
		fwrite(STDERR, 'Expected: ' . json_encode($expected) . PHP_EOL);
		fwrite(STDERR, 'Actual:   ' . json_encode($actual) . PHP_EOL);
		exit(1);
	}
}

function assert_regex($pattern, $subject, $message) {
	if (!preg_match($pattern, $subject)) {
		fwrite(STDERR, $message . PHP_EOL);
		exit(1);
	}
}

evidence_render_tab_page('evidence_tab_test_content');
assert_same(['header', 'form', 'content', 'footer'], $events, 'Tab helper should render wrapper in correct order.');

$source = file_get_contents(__DIR__ . '/../evidence_tab.php');
if ($source === false) {
	fwrite(STDERR, "Unable to read evidence_tab.php\n");
	exit(1);
}

assert_regex(
	"/evidence_render_tab_page\\(\\s*'evidence_find'\\s*\\)\\s*;/",
	$source,
	"Expected find action to use evidence_render_tab_page()."
);

assert_regex(
	"/evidence_render_tab_page\\(\\s*'evidence_stats'\\s*\\)\\s*;/",
	$source,
	"Expected default action to use evidence_render_tab_page()."
);

echo "OK\n";
