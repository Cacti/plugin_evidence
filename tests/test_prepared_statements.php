<?php

function assert_contains($haystack, $needle, $message) {
	if (strpos($haystack, $needle) === false) {
		fwrite(STDERR, $message . PHP_EOL);
		exit(1);
	}
}

function assert_not_contains($haystack, $needle, $message) {
	if (strpos($haystack, $needle) !== false) {
		fwrite(STDERR, $message . PHP_EOL);
		exit(1);
	}
}

function assert_not_regex($pattern, $subject, $message) {
	if (preg_match($pattern, $subject)) {
		fwrite(STDERR, $message . PHP_EOL);
		exit(1);
	}
}

$setup = file_get_contents(__DIR__ . '/../setup.php');
if ($setup === false) {
	fwrite(STDERR, "Unable to read setup.php\n");
	exit(1);
}

assert_contains(
	$setup,
	"db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_specific_query`', []);",
	'Expected specific_query drop to use db_execute_prepared().'
);

assert_contains(
	$setup,
	"db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_organization`', []);",
	'Expected organization drop to use db_execute_prepared().'
);

assert_contains(
	$setup,
	"db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_entity`', []);",
	'Expected entity drop to use db_execute_prepared().'
);

assert_contains(
	$setup,
	"db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_mac`', []);",
	'Expected mac drop to use db_execute_prepared().'
);

assert_contains(
	$setup,
	"db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_ip`', []);",
	'Expected ip drop to use db_execute_prepared().'
);

assert_contains(
	$setup,
	"db_execute_prepared('DROP TABLE IF EXISTS `plugin_evidence_vendor_specific`', []);",
	'Expected vendor_specific drop to use db_execute_prepared().'
);

assert_not_regex(
	'/SHOW TABLES LIKE\\s+[\'"]plugin_evidence_/i',
	$setup,
	'Uninstall path should not rely on SHOW TABLES LIKE pre-checks.'
);

assert_not_regex(
	'/db_execute\\s*\\(\\s*["\\\']DROP TABLE\\s+`plugin_evidence_/i',
	$setup,
	'Raw db_execute drop statements should not remain in setup.php.'
);

echo "OK\n";
