<?php
/* vim: ts=4
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group, Inc.                           |
 | Copyright (C) 2004-2025 Petr Macek                                      |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | https://github.com/xmacan/                                              |
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function plugin_evidence_config_settings() {
	global $tabs, $settings, $config;

	$tabs['evidence'] = 'Evidence';

	$settings['evidence'] = array(
		'evidence_frequency' => array(
			'friendly_name' => __('How often gather data', 'evidence'),
			'description'   => __('If enabled, Evidence will gather data periodically. If disabled, you can only view data for specific host', 'evidence'),
			'method'        => 'drop_array',
			'array'         => array(
				'0'     => __('Disabled', 'evidence'),
				'6'     => __('Every %d hours', 6, 'evidence'),
				'24'    => __('Every day', 'evidence'),
				'168'   => __('Every week', 'evidence'),
			),
			'default'       => '24',
		),
		'evidence_base_time' => array(
			'friendly_name' => __('When evidence will be started', 'evidence'),
			'description'   => __('The Base Time for gather data to occur.  For example, if you use \'12:00am\' and you choose once per day, the action would begin at approximately midnight every day.', 'evidence'),
			'method'        => 'textbox',
			'max_length'    => '10',
			'default'       => '01:30am',
		),
		'evidence_records' => array(
		'friendly_name'       => __('How many changes store in database', 'evidence'),
			'description' => __('If data gathering is enabled,  you can specify how many history (changed) records keep for each device', 'evidence'),
			'method'      => 'drop_array',
			'array'       => array(
				'0'   => __('Without history', 'evidence'),
				'2'   => __('%d record', 2, 'evidence'),
				'10'  => __('%d records', 10, 'evidence'),
				'30'  => __('%d records', 30, 'evidence'),
			),
			'default'     => '10',
		),
		'evidence_show_host_data' => array(
			'friendly_name' => __('Display information on device edit page', 'evidence'),
			'description'   => __('If enabled, flowview will display evidence data on device edit page', 'evidence'),
			'method'        => 'checkbox',
			'default'       => 'off',
		),
		'evidence_email_notify' => array(
			'friendly_name' => __('Send email on evidence information change', 'evidence'),
			'description'   => __('If evidence find change, send email', 'evidence'),
			'method'        => 'checkbox',
			'default'       => 'off',
		),
		'evidence_email_notify_exclude_hosts' => array(
			'friendly_name' => __('Excluded notification Host IDs', 'evidence'),
			'description'   => __('Some devices report hw changes too often. You can exclude these host from email notification. Insert Host IDs, comma separator', 'evidence'),
			'method'        => 'textbox',
			'max_length'    => '500',
			'default'       => '',
		),
		'evidence_email_notify_exclude_templates' => array(
			'friendly_name' => __('Excluded notification device templates', 'evidence'),
			'description'   => __('Some devices types report hw changes too often. You can exclude these templates from email notification. Insert device templates IDs, comma separator', 'evidence'),
			'method'        => 'textbox',
			'max_length'    => '500',
			'default'       => '',
		),
	);
}