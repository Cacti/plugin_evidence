<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2021-2024 Petr Macek                                      |
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
			'friendly_name' => 'How often gather data',
			'description'   => 'If enabled, Evidence will gather data periodically. If disabled, you can only view data for specific host',
			'method'        => 'drop_array',
			'array'         => array(
				'0'     => 'Disabled',
				'6'     => 'Every 6 hours',
				'24'    => 'Every day',
				'168'   => 'Every week',
			),
			'default'       => '24',
		),
		'evidence_base_time' => array(
			'friendly_name' => 'When evidence will be started',
			'description'   => 'The Base Time for gather data to occur.  For example, if you use \'12:00am\' and you choose once per day, the action would begin at approximately midnight every day.',
			'method'        => 'textbox',
			'max_length'    => '10',
			'default'       => '01:30am',
		),
		'evidence_records' => array(
		'friendly_name' => 'How many changes store in database',
			'description'   => 'If data gathering is enabled,  you can specify how many history (changed) records keep for each device',
			'method'        => 'drop_array',
			'array'         => array(
				'0'    => 'Without history',
				'2'    => '2 record',
				'10'   => '10 records',
				'30'   => '30 records',
			),
			'default'       => '10',
		),
		'evidence_email_notify' => array(
			'friendly_name' => 'Send email on evidence information change',
			'description'   => 'If evidence find change, send email',
			'method'        => 'checkbox',
			'default'       => 'off',
		),
		'evidence_email_notify_exclude_hosts' => array(
			'friendly_name' => 'Excluded notification Host IDs',
			'description'   => 'Some devices report hw changes too often. You can exclude these host from email notification. Insert Host IDs, comma separator',
			'method'        => 'textbox',
			'max_length'    => '500',
			'default'       => '',
		),
		'evidence_email_notify_exclude_templates' => array(
			'friendly_name' => 'Excluded notification device templates',
			'description'   => 'Some devices types report hw changes too often. You can exclude these templates from email notification. Insert device templates IDs, comma separator',
			'method'        => 'textbox',
			'max_length'    => '500',
			'default'       => '',
		),
	);
}