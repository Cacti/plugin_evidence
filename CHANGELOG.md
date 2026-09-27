# Changelog

--- develop ---
* security: Add a version-safe CSP nonce (`plugin_evidence_csp_nonce()`) to every inline `<script>` tag so pages stay compatible with Cacti's Content-Security-Policy nonce enforcement, while falling back cleanly on older Cacti releases that lack the `CactiSecureHeaders` class
* issue: PHPStan level 8 typing pass - fixed a wrong get_allowed_ajax_hosts() argument (broke the AJAX host filter dropdown), a cross-iteration variable-reuse bug in the vendor-specific data-query 'table' method, an undefined $data_descr fallback in the Entity MIB scan, and several unguarded db_fetch_row_prepared()/parse_ini_file() result reads

--- 0.4 ---
* Customizing data search and display

--- 0.3 ---
* Add generic snmp info

--- 0.2 ---
* Better data display

--- 0.1 ---
* Beginning
