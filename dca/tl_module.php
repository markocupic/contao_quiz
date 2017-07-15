<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['quiz'] = '{title_legend},name,headline,type;{config_legend},quiz_categories,quiz_teaser,question_count,question_sort,answers_sort;{results_legend:hide},save_results;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['quizEventDashboard'] = '{title_legend},name,headline,type;{quiz_pages_legend},quiz_pages;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['quiz_categories'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['quiz_categories'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'foreignKey' => 'tl_quiz_category.title',
    'eval' => array('multiple' => true, 'mandatory' => true),
    'sql' => "blob NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['quiz_pages'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['quiz_pages'],
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => array('fieldType' => 'checkbox', 'multiple' => true, 'tl_class' => 'clr'),
    'sql' => "blob NULL"
    //'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);
$GLOBALS['TL_DCA']['tl_module']['fields']['quiz_teaser'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['quiz_teaser'],
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => array('tl_class' => 'clr'),
    'sql' => "text NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['question_count'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['question_count'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => array('rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'),
    'sql' => "varchar(64) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['question_sort'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['question_sort'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => array('sorting', 'rating', 'random'),
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['question_sort_select'],
    'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
    'sql' => "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['answers_sort'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['answers_sort'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => array('doNotCopy' => true, 'tl_class' => 'w50'),
    'sql' => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['save_results'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['save_results'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => array('doNotCopy' => true, 'tl_class' => 'w50'),
    'sql' => "char(1) NOT NULL default ''"
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


}