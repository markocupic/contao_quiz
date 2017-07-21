<?php

/**
 * Created by PhpStorm.
 * User: Marko Cupic m.cupic@gmx.ch
 * Date: 16.03.2017
 * Time: 12:55
 */

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['eventToken'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar_events']['eventToken'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => array('mandatory'=>false, 'maxlength'=>255, 'tl_class'=>'w50'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);


$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['organizerEmail'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar_events']['organizerEmail'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => array('mandatory'=>true, 'rgxp' => 'email', 'maxlength'=>255, 'tl_class'=>'w50'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['refPromoArticle'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_calendar_events']['refPromoArticle'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback' => array('tl_calendar_events_quiz', 'getAssignedPromoArticles'),
    'eval'                    => array('mandatory'=>true, 'includeBlankOption' => true, 'maxlength'=>255, 'tl_class'=>'w50'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);







class tl_calendar_events_quiz extends Backend
{
    public function getAssignedPromoArticles()
    {
        $opt = array();
        $objArticle = $this->Database->prepare('SELECT * FROM tl_article WHERE isQuizPromoArticle=? AND published=?')->execute('1','1');
        while($objArticle->next())
        {
            $opt[$objArticle->id] = $objArticle->title;
        }
        return $opt;
    }
}
