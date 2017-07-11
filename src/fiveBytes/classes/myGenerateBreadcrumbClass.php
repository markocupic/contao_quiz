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
 * Class myGenerateBreadcrumbClass
 *
 * @copyright  fiveBytes 2014
 * @author     Stefen Baetge <fivebytes.de>
 * @package    Quiz
 */
class myGenerateBreadcrumbClass
{
	/**
	 * Insert the resultpage in breadcrumb
	 *
	 * @param array $arrItems    An array with the breadcrumb items
	 * @param object $objModule  An object with emodule
	 *
	 * @return an array with the breadcrumb items
	 */
	public function myGenerateBreadcrumb($arrItems, \Module $objModule)
	{
		if (\Input::post('quiz_action') == 'results')
		{
			$tmpLastItem = count($arrItems)-1;
			$arrItems[$tmpLastItem]['isActive'] = false;
			
			$arrItems[] = array
			(
				'isRoot'   => false,
				'isActive' => true,
				'href'     => '',
				'title'    => '',
				'link'     => '',
				'data'     => array('title' => $GLOBALS['TL_LANG']['MSC']['results_subline']),
				'class'    => ''
			);
		}
		
	    return $arrItems;
	}

}