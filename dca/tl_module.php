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
 * This is the data container array for table tl_module.
 *
 * PHP version 5
 * @copyright  fiveBytes 2014
 * @author     Stefen Baetge <fivebytes.de>
 * @package    Quiz
 * @license    GPL
 * @filesource
 */

/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] 	= 'addLevel';
$GLOBALS['TL_DCA']['tl_module']['palettes']['quiz']   			= '{title_legend},name,headline,type;{config_legend},quiz_categories,quiz_teaser,question_count,question_sort,question_cat_count,answers_sort;{level_legend:hide},addLevel;{results_legend:hide},save_results;{template_legend:hide},quizTpl,quizformTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['addLevel']		= 'quiz_level';

/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['quiz_categories'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['quiz_categories'],
	'exclude'                 => true,
	'inputType'               => 'checkboxWizard',
	'foreignKey'              => 'tl_quiz_category.title',
	'eval'                    => array('multiple'=>true, 'mandatory'=>true),
	'sql'                     => "blob NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['quiz_teaser'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['quiz_teaser'],
	'exclude'                 => true,
	'inputType'               => 'textarea',
	'eval'                    => array('rte'=>'tinyMCE', 'helpwizard'=>true),
	'explanation'             => 'insertTags',
	'sql'                     => "text NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['question_count'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['question_count'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['question_sort'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['question_sort'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('sorting','rating','random'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module']['question_sort_select'],
	'eval'                    => array('includeBlankOption' => true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(32) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['question_cat_count'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['question_cat_count'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['answers_sort'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['answers_sort'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['addLevel'] = array
( 
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['addLevel'], 
    'exclude'                  => true, 
    'inputType'               => 'checkbox', 
    'eval'                    => array('submitOnChange'=>true) ,
	'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['quiz_level'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['quiz_level'],
	'exclude'   			  => true,
	'inputType' 			  => 'multiColumnWizard',
    'eval'      			  => array
		(
        'style'=>'width:100%;',
        'columnFields' => array
          (
            'level_title' => array
            (
              'label' 		=> &$GLOBALS['TL_LANG']['tl_module']['level_title'],
			  'exclude'   	=> true,
			  'inputType'   => 'text',
		      'eval'        => array('mandatory'=>true,'style'=>'width:250px;')
            ),
            'level_ratings' => array
            (
              'label' 		=> &$GLOBALS['TL_LANG']['tl_module']['level_ratings'],
			  'exclude'   	=> true,
			  'inputType'   => 'text',
		      'eval'        => array('mandatory'=>true,'style'=>'width:250px;')
            ),
			'level_standard_rating' => array
            (
              'label' 		=> &$GLOBALS['TL_LANG']['tl_module']['level_standard_rating'],
			  'exclude'   	=> true,
			  'inputType'   => 'checkbox',
			  'eval'        => array('style'=>'margin-left:7px;')
            )
       	  )
   		),
	'sql'	 => "blob NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['save_results'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['save_results'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['quizTpl'] = array
(
	'label'             => &$GLOBALS['TL_LANG']['tl_module']['quizTpl'],
	'exclude'           => true,
	'inputType'         => 'select',
	'options_callback'	=> array('tl_module_quiz', 'getQuizTemplates'),
	'eval'              => array('tl_class'=>'w50'),
	'sql'               => "varchar(64) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['quizformTpl'] = array
(
	'label'             => &$GLOBALS['TL_LANG']['tl_module']['quizformTpl'],
	'exclude'           => true,
	'inputType'         => 'select',
	'options_callback'  => array('tl_module_quiz', 'getQuizFormTemplates'),
	'eval'              => array('tl_class'=>'w50'),
	'sql'               => "varchar(64) NOT NULL default ''"
);

/**
 * Class tl_module_quiz
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  fiveBytes 2014
 * @author     Stefen Baetge <fivebytes.de>
 * @package    Quiz
 */
class tl_module_quiz extends \Backend
{
	/**
	 * Return all quiz templates as array
	 * @return array
	 */
	public function getQuizTemplates()
	{
		return $this->getTemplateGroup('mod_quiz');
	}
	
	/**
	 * Return all quiz starting form templates as array
	 * @return array
	 */
	public function getQuizFormTemplates()
	{
		return $this->getTemplateGroup('form_quiz');
	}
}