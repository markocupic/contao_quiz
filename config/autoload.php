<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'fiveBytes',
	'Markocupic',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Src
	'fiveBytes\myGenerateBreadcrumbClass'            => 'system/modules/contao_quiz/src/fiveBytes/classes/myGenerateBreadcrumbClass.php',

	// Modules
	'Markocupic\ContaoQuiz\ModuleQuizEventDashboard' => 'system/modules/contao_quiz/modules/ModuleQuizEventDashboard.php',
	'Markocupic\ContaoQuiz\ModuleQuiz'               => 'system/modules/contao_quiz/modules/ModuleQuiz.php',

	// Classes
	'Markocupic\ContaoQuiz\Hooks'                    => 'system/modules/contao_quiz/classes/Hooks.php',

	// Models
	'Contao\QuizCategoryModel'                       => 'system/modules/contao_quiz/models/QuizCategoryModel.php',
	'Contao\QuizAnswerStatsModel'                    => 'system/modules/contao_quiz/models/QuizAnswerStatsModel.php',
	'Contao\QuizResultModel'                         => 'system/modules/contao_quiz/models/QuizResultModel.php',
	'Contao\QuizQuestionModel'                       => 'system/modules/contao_quiz/models/QuizQuestionModel.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'notifyQuizUserByEmail'    => 'system/modules/contao_quiz/templates/email',
	'evaluation_partial'       => 'system/modules/contao_quiz/templates/partials',
	'mod_quiz_step_2'          => 'system/modules/contao_quiz/templates/modules/steps',
	'mod_quiz_step_6'          => 'system/modules/contao_quiz/templates/modules/steps',
	'mod_quiz_step_3'          => 'system/modules/contao_quiz/templates/modules/steps',
	'mod_quiz_step_5'          => 'system/modules/contao_quiz/templates/modules/steps',
	'mod_quiz_step_4'          => 'system/modules/contao_quiz/templates/modules/steps',
	'mod_quiz_step_1'          => 'system/modules/contao_quiz/templates/modules/steps',
	'mod_quiz_event_dashboard' => 'system/modules/contao_quiz/templates/modules',
));
