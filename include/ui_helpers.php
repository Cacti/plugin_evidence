<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2021-2024 Petr Macek                                      |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
 */

if (!function_exists('evidence_render_tab_page')) {
	function evidence_render_tab_page(callable $content_renderer) {
		general_header();
		evidence_display_form();
		$content_renderer();
		bottom_footer();
	}
}
