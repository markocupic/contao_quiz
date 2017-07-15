<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 14.07.2017
 * Time: 23:21
 */

namespace Markocupic\ContaoQuiz;

/**
 * Class Hooks
 * @package Markocupic\ContaoQuiz
 */
class Hooks
{
    /**
     * generatePage-Hooks
     */
    public function generateQuizToken()
    {
        if (TL_MODE != 'FE')
        {
            return;
        }

        $objDatabase = \Database::getInstance();
        $objEvents = $objDatabase->prepare('SELECT * FROM tl_calendar_events WHERE eventToken=?')->execute('');
        while ($objEvents->next())
        {
            $token = sha1(microtime()) . $objEvents->id;
            $set = array('eventToken' => $token);
            $objDatabase->prepare('UPDATE tl_calendar_events %s WHERE id=?')->set($set)->execute($objEvents->id);
        }
    }
}