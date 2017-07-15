<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 15.07.2017
 * Time: 00:04
 */

$GLOBALS['TL_DCA']['tl_article']['palettes'] = str_replace('{layout_legend', '{quiz_legend},isQuizPromoArticle;{layout_legend', $GLOBALS['TL_DCA']['tl_article']['palettes']);

$GLOBALS['TL_DCA']['tl_article']['fields']['isQuizPromoArticle'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar_events']['isQuizPromoArticle'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);