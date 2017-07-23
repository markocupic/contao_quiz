<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


namespace Contao;

/**
 * Class QuizQuestionModel
 *
 * @copyright  Marko Cupic 2017 forked from fiveBytes 2014
 * @author     Marko Cupic <m.cupic@gmx.ch> & Stefen Baetge <fivebytes.de>
 * @package    Contao Quiz
 */
class QuizQuestionModel extends \Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_quiz_question';

    /**
     * Find a published Question from one or more categories by its ID
     *
     * @param mixed $varId The numeric ID or alias name
     * @param array $arrPids An array of parent IDs
     * @param array $arrOptions An optional options array
     *
     * @return \Model|null The QuestionModel or null if there is no Question
     */
    public static function findPublishedByParentAndId($varId, $arrPids, array $arrOptions = array())
    {
        if (!is_array($arrPids) || empty($arrPids))
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.id=? AND pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

        if (!BE_USER_LOGGED_IN)
        {
            $arrColumns[] = "$t.published=1";
        }

        return static::findOneBy($arrColumns, array((is_numeric($varId) ? $varId : 0), $varId), $arrOptions);
    }

    /**
     * Find all published Questions by their IDs
     *
     * @param array $arrIds An array of IDs
     * @param array $arrOptions An optional options array
     *
     * @return \Model\Collection|null A collection of models or null if there are no Questions
     */
    public static function findPublishedByIds($arrIds, array $arrOptions = array())
    {
        $t = static::$strTable;
        $arrColumns = array("$t.id IN (" . implode(',', array_map('intval', $arrIds)) . ")");

        if (!BE_USER_LOGGED_IN)
        {
            $arrColumns[] = "$t.published=1";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "$t.pid, $t.sorting";
        }

        return static::findBy($arrColumns, $intPid, $arrOptions);
    }

    /**
     * Find all published Questions by their parent ID
     *
     * @param int $intPid The parent ID
     * @param array $arrOptions An optional options array
     *
     * @return \Model\Collection|null A collection of models or null if there are no Questions
     */
    public static function findPublishedByPid($intPid, array $arrOptions = array())
    {
        $t = static::$strTable;
        $arrColumns = array("$t.pid=?");

        if (!BE_USER_LOGGED_IN)
        {
            $arrColumns[] = "$t.published=1";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "$t.sorting";
        }

        return static::findBy($arrColumns, $intPid, $arrOptions);
    }

    /**
     * Find all published Questions by their parent IDs
     *
     * @param array $arrPids An array of Quiz category IDs
     * @param array $arrOptions An optional options array
     *
     * @return \Model\Collection|null A collection of models or null if there are no Questions
     */
    public static function findPublishedByPids($arrPids, array $arrOptions = array())
    {
        if (!is_array($arrPids) || empty($arrPids))
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");

        if (!BE_USER_LOGGED_IN)
        {
            $arrColumns[] = "$t.published=1";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order'] = "$t.pid, $t.sorting";
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }


    /**
     * Count published questions
     *
     * @param array $arrPids An array of Quiz category IDs
     *
     * @return int|0 The number of questions or 0 if there are no questions
     */
    public static function countPublishedByPids($arrPids)
    {
        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");
        $arrColumns[] = "$t.published=1";

        return static::countBy($arrColumns, null);
    }

    /**
 * @param $quizId
 * @param $answerId
 * @return bool
 */
    public static function evalAnswer($quizId, $answerId)
    {
        $objQuiz = static::findByPk($quizId);
        if ($objQuiz !== null)
        {
            $arrAnswers = deserialize($objQuiz->answers, true);
            if ($arrAnswers[$answerId]['answerTrue'] == '1')
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $quizId
     * @return int|string
     */
    public static function getAnswer($quizId)
    {
        $objQuiz = static::findByPk($quizId);
        if ($objQuiz !== null)
        {
            $arrAnswers = deserialize($objQuiz->answers, true);
            foreach($arrAnswers as $k => $arrAnswer)
            {
                if($arrAnswer['answerTrue'] == '1')
                {
                    return $k;
                }
            }
        }
    }

    /**
     * @param $quizId
     * @param string $shuffle
     * @return bool|mixed
     */
    public function getAnswers($quizId)
    {
        $objQuiz = static::findByPk($quizId);
        if ($objQuiz !== null)
        {
            $arrAnswers = deserialize($objQuiz->answers, true);
            $arrAnswers = array_map(function($el){
                if($el['singleSRC'] != '')
                {
                    $el['singleSRC'] = \StringUtil::binToUuid($el['singleSRC']);
                }
                $el['answerTrue']  = $el['answerTrue'] == 1 ? 'true' : null;
                return $el;
            },$arrAnswers);
            return $arrAnswers;
        }
    }
}
