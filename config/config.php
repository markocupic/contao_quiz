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
 * PHP version 5
 * @copyright  fiveBytes 2014
 * @author     Stefen Baetge <fivebytes.de>
 * @package    Quiz
 * @license    GPL
 * @filesource
 */

/** Back end modules */

$GLOBALS['BE_MOD']['content']['quiz'] = array
(
	'tables' => array('tl_quiz_category', 'tl_quiz_question'),
	'icon'   => 'system/modules/contao_quiz/assets/icon.gif'
);

/** Front end modules */

array_insert($GLOBALS['FE_MOD']['application'], 3, array
	(
		'quiz' => 'fiveBytes\ModuleQuiz'
	)
);

/** Hooks */

$GLOBALS['TL_HOOKS']['generateBreadcrumb'][] = array('fiveBytes\myGenerateBreadcrumbClass', 'myGenerateBreadcrumb');

/** Models */

$GLOBALS['TL_MODELS']['tl_quiz_category'] = 'fiveBytes\QuizCategoryModel';
$GLOBALS['TL_MODELS']['tl_quiz_question'] = 'fiveBytes\QuizQuestionModel';
$GLOBALS['TL_MODELS']['tl_quiz_results'] = 'fiveBytes\QuizResultModel';

/**
 * Add permissions
 */
 
$GLOBALS['TL_PERMISSIONS'][] = 'quizs';
$GLOBALS['TL_PERMISSIONS'][] = 'quizp';

// Version der eigenen Extension registrieren

define('CONTAO_QUIZ','1.3.0');