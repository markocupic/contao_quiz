<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Quiz
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
 
ClassLoader::addNamespaces(array('fiveBytes','Markocupic'));


ClassLoader::addClasses(array
(
	// Classes
	'fiveBytes\myGenerateBreadcrumbClass'	=> 'system/modules/contao_quiz/src/fiveBytes/classes/myGenerateBreadcrumbClass.php',
    'Markocupic\ContaoQuiz\Hooks'	=> 'system/modules/contao_quiz/classes/Hooks.php',

    // Modules
	'Markocupic\ContaoQuiz\ModuleQuiz'					=> 'system/modules/contao_quiz/modules/ModuleQuiz.php',
    'Markocupic\ContaoQuiz\ModuleQuizEventDashboard'		=> 'system/modules/contao_quiz/modules/ModuleQuizEventDashboard.php',

	// Models
	'Contao\QuizCategoryModel'			=> 'system/modules/contao_quiz/models/QuizCategoryModel.php',
	'Contao\QuizQuestionModel'			=> 'system/modules/contao_quiz/models/QuizQuestionModel.php',
    'Contao\QuizResultModel'			=> 'system/modules/contao_quiz/models/QuizResultModel.php',
    'Contao\QuizAnswerStatsModel'			=> 'system/modules/contao_quiz/models/QuizAnswerStatsModel.php',

));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_quiz_step_1'       => 'system/modules/contao_quiz/templates/modules/steps',
    'mod_quiz_step_2'       => 'system/modules/contao_quiz/templates/modules/steps',
    'mod_quiz_step_3'       => 'system/modules/contao_quiz/templates/modules/steps',
    'mod_quiz_step_4'       => 'system/modules/contao_quiz/templates/modules/steps',
    'mod_quiz_step_5'       => 'system/modules/contao_quiz/templates/modules/steps',
    'mod_quiz_step_6'       => 'system/modules/contao_quiz/templates/modules/steps',
    'notifyQuizUserByEmail' => 'system/modules/contao_quiz/templates/email',
    'mod_quiz_event_dashboard' => 'system/modules/contao_quiz/templates/modules',

));
