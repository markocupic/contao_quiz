<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * This is the data container array for table tl_quiz_results.
 *
 * PHP version 5
 * @copyright  fiveBytes 2014
 * @author     Stefen Baetge <fivebytes.de>
 * @package    Quiz
 * @license    GPL
 * @filesource
 */

/**
 * Table tl_quiz_category
 */
 
$GLOBALS['TL_DCA']['tl_quiz_results'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'level_id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'level_title' => array
		(
			'sql'                     => "text NULL"
		),
		'question_count' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'quiztime' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'user_rating' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'max_rating' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'rating_percent' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'member_id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'member_username' => array
		(
			'sql'                     => "varchar(64) COLLATE utf8_bin NOT NULL default ''"
		),
		'ip' => array
		(
			'sql'                     => "varchar(64) NOT NULL default ''"
		)
	)
);