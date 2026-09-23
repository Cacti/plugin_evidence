<?php
/* vim: ts=4
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group, Inc.                           |
 | Copyright (C) 2004-2024 Petr Macek                                      |
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

$entities = array(
	'descr'        => __('Description', 'evidence'),
	'name'         => __('Name', 'evidence'),
	'hardware_rev' => __('Hardware revision', 'evidence'),
	'firmware_rev' => __('Firmware revision', 'evidence'),
	'software_rev' => __('Software revision', 'evidence'),
	'serial_num'   => __('Serial number', 'evidence'),
	'mfg_name'     => __('Manufacturer name', 'evidence'),
	'model_name'   => __('Model name', 'evidence'),
	'alias'        => __('Alias', 'evidence'),
	'asset_id'     => __('Asset ID', 'evidence'),
	'mfg_date'     => __('Manufacturing date', 'evidence'),
	'uuid'         => __('UUID', 'evidence')
);

$datatypes = array(
	'info'   => __('SNMP info', 'evidence'),
	'entity' => __('Entity MIB', 'evidence'),
	'mac'    => __('Mac addresses', 'evidence'),
	'ip'     => __('IP addresses', 'evidence'),
	'spec'   => __('Vendor specific data', 'evidence'),
	'opt'    => __('Vendor optional data', 'evidence')
);