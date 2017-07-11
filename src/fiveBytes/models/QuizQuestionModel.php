<?php

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
 * PHP version 5
 * @copyright  fiveBytes 2014
 * @author     Stefen Baetge <fivebytes.de>
 * @package    Quiz
 * @license    GPL
 * @filesource
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace fiveBytes;


/**
 * Reads and writes Questions
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
	 * @param mixed $varId      The numeric ID or alias name
	 * @param array $arrPids    An array of parent IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return \Model|null The QuestionModel or null if there is no Question
	 */
	public static function findPublishedByParentAndId($varId, $arrPids, array $arrOptions=array())
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
	 * @param array $arrIds     An array of IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return \Model\Collection|null A collection of models or null if there are no Questions
	 */
	public static function findPublishedByIds($arrIds, array $arrOptions=array())
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
	 * @param int   $intPid     The parent ID
	 * @param array $arrOptions An optional options array
	 *
	 * @return \Model\Collection|null A collection of models or null if there are no Questions
	 */
	public static function findPublishedByPid($intPid, array $arrOptions=array())
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
	 * @param array $arrPids    An array of Quiz category IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return \Model\Collection|null A collection of models or null if there are no Questions
	 */
	public static function findPublishedByPids($arrPids, array $arrOptions=array())
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
	 * Find all published Questions by their parent IDs and rating
	 *
	 * @param array $arrPids    An array of Quiz category IDs
	 * @param array $arrRatings    A comma-separated list of ratings
	 * @param array $arrOptions An optional options array
	 *
	 * @return \Model\Collection|null A collection of models or null if there are no Questions
	 */
	public static function findPublishedByPidsAndRating($arrPids, $arrRatings, array $arrOptions=array())
	{
		if (!is_array($arrPids) || empty($arrPids) || !$arrRatings)
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");
		$arrColumns[] = "$t.rating IN(" . $arrRatings . ")";

		if (!BE_USER_LOGGED_IN)
		{
			$arrColumns[] = "$t.published=1";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, null, $arrOptions);
	}
	
	/**
	 * Find all published Questions by their parent IDs and rating, without specific IDs
	 *
	 * @param array $arrids    An array of Quiz question IDs
	 * @param array $arrPids    An array of Quiz category IDs
	 * @param array $arrRatings    A comma-separated list of ratings
	 * @param array $arrOptions An optional options array
	 *
	 * @return \Model\Collection|null A collection of models or null if there are no Questions
	 */
	public static function findPublishedByPidsAndRatingWithoutIds($ids, $arrPids, $arrRatings, array $arrOptions=array())
	{
		if (!is_array($arrPids) || empty($arrPids) || !$arrRatings)
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")");
		$arrColumns[] = "$t.rating IN(" . $arrRatings . ")";
		$arrColumns[] = "$t.id NOT IN(" . implode(',', array_map('intval', $ids)) . ")";

		if (!BE_USER_LOGGED_IN)
		{
			$arrColumns[] = "$t.published=1";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, null, $arrOptions);
	}
	
	/**
	 * Count published questions
	 * 
	 * @param array $arrPids    An array of Quiz category IDs
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
}
