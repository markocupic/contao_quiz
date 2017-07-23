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
 
$GLOBALS['TL_DCA']['tl_quiz_answer_stats'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		//'enableVersioning'            => true,
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
		'answerKey' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
        'clicks' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        )
	)
);