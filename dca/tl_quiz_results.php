<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
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
		'questionCount' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'quiztime' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'userRating' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'maxRating' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'rating_percent' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'user_email' => array
		(
			'sql'                     => "varchar(64) COLLATE utf8_bin NOT NULL default ''"
		),
        'user_phone' => array
        (
            'sql'                     => "varchar(64) COLLATE utf8_bin NOT NULL default ''"
        ),
		'ip' => array
		(
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
        'refEventId' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        )
	)
);