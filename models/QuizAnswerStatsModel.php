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
 * Class QuizCategoryModel
 *
 * @copyright  Marko Cupic 2017 forked from fiveBytes 2014
 * @author     Marko Cupic <m.cupic@gmx.ch> & Stefen Baetge <fivebytes.de>
 * @package    Contao Quiz
 */
class QuizAnswerStatsModel extends \Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_quiz_answer_stats';

    /**
     * @param $pid
     * @param $key
     */
    public static function addClick($pid, $key)
    {
        $arrColumns = array("pid=? AND answerKey=?");
        $objAnswer = static::findOneBy($arrColumns, array($pid, $key));
        if ($objAnswer !== null)
        {
            $objAnswer->clicks = $objAnswer->clicks + 1;
            $objAnswer->save();
        }
        else
        {
            $objAnswer = new static();
            $objAnswer->pid = $pid;
            $objAnswer->answerKey = $key;
            $objAnswer->clicks = 1;
            $objAnswer->save();
        }
    }

    /**
     * @param $pid
     * @param $key
     */
    public static function getClicks($pid, $key)
    {
        $arrColumns = array("pid=? AND answerKey=?");
        $objAnswer = static::findOneBy($arrColumns, array($pid, $key));
        if ($objAnswer !== null)
        {
            return $objAnswer->clicks;
        }
        else
        {
            return '0';
        }
    }

    public static function getClickDistributionInPercentage($pid)
    {
        $arrDistribution = array();
        $objDb = \Database::getInstance()->prepare('SELECT SUM(clicks) AS clicksTotal FROM tl_quiz_answer_stats  WHERE pid=?')->execute($pid);
        $clicksTotal = $objDb->clicksTotal;

        $objAnswer = \QuizQuestionModel::findByPk($pid);
        if ($objAnswer === null)
        {
            return null;
        }
        $keys = array_keys(deserialize($objAnswer->answers, true));
        foreach ($keys as $key)
        {
            $clicks = static::getClicks($pid, $key);
            $percentage = round($clicks / $clicksTotal * 100);
            $arrDistribution[$pid . '_' . $key] = $percentage;
        }
        return $arrDistribution;

    }

}