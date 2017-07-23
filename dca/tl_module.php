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
$GLOBALS['TL_DCA']['tl_module']['palettes']['quiz'] = '{title_legend},name,headline,type;{config_legend},quizCategories,quizTeaser,questionCount,questionSort,answersSort;{results_legend:hide},saveResults;{template_legend},quizTplStep1,quizTplStep2,quizTplStep3,quizTplStep4,quizTplStep5,quizTplStep6;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['quizEventDashboard'] = '{title_legend},name,headline,type;{quiz_pages_legend},quizPages;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'saveResults';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['saveResults'] = 'minimumPercentScore';




/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['quizCategories'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['quizCategories'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'foreignKey' => 'tl_quiz_category.title',
    'eval' => array('multiple' => true, 'mandatory' => true),
    'sql' => "blob NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['quizPages'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['quizPages'],
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => array('fieldType' => 'checkbox', 'multiple' => true, 'tl_class' => 'clr'),
    'sql' => "blob NULL"
    //'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);
$GLOBALS['TL_DCA']['tl_module']['fields']['quizTeaser'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['quizTeaser'],
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => array('tl_class' => 'clr'),
    'sql' => "text NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['questionCount'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['questionCount'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => array('rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'),
    'sql' => "varchar(64) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['questionSort'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['questionSort'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => array('sorting', 'random'),
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['questionSortSelect'],
    'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
    'sql' => "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['answersSort'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['answersSort'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => array('doNotCopy' => true, 'tl_class' => 'w50'),
    'sql' => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['saveResults'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['saveResults'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => array('submitOnChange'=>true, 'doNotCopy' => true, 'tl_class' => 'clr'),
    'sql' => "char(1) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['minimumPercentScore'] = array
(
    'label' => &$GLOBALS['TL_LANG']['tl_module']['minimumPercentScore'],
    'exclude' => true,
    'inputType' => 'text',
    'default' => 100,
    'eval' => array('submitOnChange'=>true, 'tl_class' => 'w50', 'maxlength' => 3),
    'sql' => "varchar(3) NOT NULL default '100'"
);
for($i=1;$i<7;$i++)
{
    $GLOBALS['TL_DCA']['tl_module']['fields']['quizTplStep' . $i] = array
    (
        'label'             => &$GLOBALS['TL_LANG']['tl_module']['quizTplStep' . $i],
        'exclude'           => true,
        'inputType'         => 'select',
        'options_callback'	=> array('tl_module_quiz', 'getQuizTemplatesStep' . $i),
        'eval'              => array('tl_class'=>'w50'),
        'sql'               => "varchar(64) NOT NULL default ''"
    );
}






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
    public function getQuizTemplatesStep1()
    {
        return $this->getTemplateGroup('mod_quiz_step_1');
    }

    /**
     * Return all quiz templates as array
     * @return array
     */
    public function getQuizTemplatesStep2()
    {
        return $this->getTemplateGroup('mod_quiz_step_2');
    }
    /**
     * Return all quiz templates as array
     * @return array
     */
    public function getQuizTemplatesStep3()
    {
        return $this->getTemplateGroup('mod_quiz_step_3');
    }

    /**
     * Return all quiz templates as array
     * @return array
     */
    public function getQuizTemplatesStep4()
    {
        return $this->getTemplateGroup('mod_quiz_step_4');
    }

    /**
     * Return all quiz templates as array
     * @return array
     */
    public function getQuizTemplatesStep5()
    {
        return $this->getTemplateGroup('mod_quiz_step_5');
    }

    /**
     * Return all quiz templates as array
     * @return array
     */
    public function getQuizTemplatesStep6()
    {
        return $this->getTemplateGroup('mod_quiz_step_6');
    }


}