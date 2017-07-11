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
 * Class ModuleQuiz
 *
 * @copyright  fiveBytes 2014
 * @author     Stefen Baetge <fivebytes.de>
 * @package    Quiz
 */
class ModuleQuiz extends \Module
{
	
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_quiz_question';
	
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_quiz';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['quiz']) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}
		
		$this->quiz_categories = deserialize($this->quiz_categories);

		// Return if there are no categories
		if (!is_array($this->quiz_categories) || empty($this->quiz_categories))
		{
			return '';
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$t = static::$strTable;
		
		// Set the template
		if ($this->quizTpl != '')
		{
			$this->Template = new \FrontendTemplate($this->quizTpl);
			$this->Template->setData($this->arrData);
		}
		
		// Check quiz config
		if ($this->addLevel && $this->quiz_level) $this->quiz_level = deserialize($this->quiz_level);
		if ($this->question_count) $this->question_count = explode(",",$this->question_count);
		
		// Bypass quiz actions
		if (!(\Input::post('quiz_action') == 'results') && !(\Input::post('quiz_action') == 'start') && ($this->quiz_teaser || count($this->question_count)>1 || $this->addLevel))
		{
			// Show the intropage with teaser and startform
			// Clean RTE output
			if ($objPage->outputFormat == 'xhtml')
			{
				$this->quiz_teaser = \StringUtil::toXhtml($this->quiz_teaser);
			}
			else
			{
				$this->quiz_teaser = \StringUtil::toHtml5($this->quiz_teaser);
			}
			
			$this->Template->quiz_teaser = \StringUtil::encodeEmail($this->quiz_teaser);
			$this->Template->show_intro = true;
			$this->Template->start_form = $this->getStartForm($this->id, $this->question_count, $this->addLevel, $this->quiz_level, $this->quizformTpl);
		}
		else
		{
			$tmpQuestionCount = 1;
			if (\Input::post('q_question_count'))
			{
				$tmpQuestionCount = \Input::post('q_question_count');
			}
			else if (is_array($this->question_count))
			{
				$tmpQuestionCount = $this->question_count[0];
			}
			
			if (strlen(\Input::post('q_level')) > 0)
			{
				$aryLevelKey = \Input::post('q_level')-1;
				$aryLevel = $this->quiz_level[$aryLevelKey];
				$this->Template->quizLevel = $aryLevel['level_title'];
			}
			
			$arrOptions = array();
			$tmpSort = ($this->question_sort != 'random' && $this->question_sort != '') ? "$t.".$this->question_sort : 'RAND()';
			$arrOptions['order'] = "$t.pid, " . $tmpSort;
			$arrOptions['limit'] = $tmpQuestionCount;
			
			$tmpResults = false;
			if (\Input::post('quiz_action') == 'results')
			{
				// Show the resultpage, with questions, answers (users, correct ones), comment lines and reslut analysis
				// Edit the page title
				global $objPage;
				$objPage->pageTitle .= ' - ' . $GLOBALS['TL_LANG']['MSC']['results_subline'];
				
				// Get the object with questions
				$arrOptions['order'] = 'tl_quiz_question.id=' . str_replace(","," DESC,tl_quiz_question.id=",\Input::post('question_ids')) . ' DESC';
				$this->QuizObject = \QuizQuestionModel::findPublishedByIds(explode(",",\Input::post('question_ids')), $arrOptions);
				
				// Get the quiz questions, user answers and the result
				$this->getQuizResults($this->QuizObject, $this->quiz_categories, $aryLevelKey);
				
				// Save result
				if ($this->save_results) {
					$modResult = new \QuizResultModel();
					$modResult->pid = $this->id;
					$modResult->tstamp = time();
					$modResult->level_id = $aryLevelKey;
					$modResult->level_title = $aryLevel['level_title'];
					$modResult->question_count = $tmpQuestionCount;
					$modResult->quiztime = (time() - $_SESSION['quiz_start']);
					$modResult->user_rating = $this->Template->user_ratings;
					$modResult->max_rating = $this->Template->max_ratings;
					$modResult->rating_percent = $this->Template->result_percent;
					$objUser = \FrontendUser::getInstance();
	    			if ($objUser->id)
	    			{
						$modResult->member_id = $objUser->id;
						$modResult->member_username = $objUser->username;
					}
					$modResult->ip = $this->anonymizeIp(\Environment::get('ip'));
					$modResult->save();
				}
			}
			else
			{
				// Show the quizpage, a form with question and answers
				// Check if percentage distribution of questions per category ist set 
				if ($this->question_cat_count)
				{
					$tmpSumme = 0;
					$this->question_cat_count = explode(",",$this->question_cat_count);
					foreach($this->quiz_categories as $key=>$tmpCat)
					{
						// Get question IDs
						$arrOptions['limit'] = intval(($tmpQuestionCount/100)*$this->question_cat_count[$key]);
						$result = \QuizQuestionModel::findPublishedByPidsAndRating(array($tmpCat), $aryLevel['level_ratings'], $arrOptions);
						if ($result)
						{
							while($result->next())
							{
								$tmpIDs[] = $result->id;
								$tmpSumme ++;
							}
						}
					}
					
					// Check if enough questions IDs returned and if not get missing question IDs
					if ($tmpSumme<$tmpQuestionCount) 
					{
						$arrOptions['limit'] = $tmpQuestionCount-$tmpSumme;
						$result = \QuizQuestionModel::findPublishedByPidsAndRatingWithoutIds($tmpIDs, $this->quiz_categories, $aryLevel['level_ratings'], $arrOptions);
						if ($result)
						{
							while($result->next())
							{
								$tmpIDs[] = $result->id;
							}
						}
					}
				}
				
				// Get object with questions
				$arrOptions['limit'] = $tmpQuestionCount;
				if ($tmpIDs)
				{
					$this->QuizObject = \QuizQuestionModel::findPublishedByIds($tmpIDs, $arrOptions);
				}
				else if (is_array($aryLevel))
				{
					
					$this->QuizObject = \QuizQuestionModel::findPublishedByPidsAndRating($this->quiz_categories, $aryLevel['level_ratings'], $arrOptions);
				}
				else
				{
					$this->QuizObject = \QuizQuestionModel::findPublishedByPids($this->quiz_categories, $arrOptions);
				}
				
				// Get the quiz questions
				$this->getQuizQuestions($this->QuizObject, $this->quiz_categories, $this->answers_sort);
				
				// Set starting time
				$_SESSION['quiz_start'] = time();
			}
		}
		
		$this->Template->action = \Environment::get('indexFreeRequest');
		$this->Template->question_count = $tmpQuestionCount;
	}
	
	/**
	 * Get the questions
	 *
	 * @param array $objQuiz    An array of Quiz questions
	 * @param array $categories An array of Quiz categories
	 * @param int $answerSort 	An integer 1 for random answers
	 *
	 * Gives HTML-Code for the questions and variables to the template
	 */
	protected function getQuizQuestions($objQuiz, $categories, $answersSort)
	{
        unset($_SESSION['mod_quiz']);
	    if ($objQuiz === null)
		{
			return;
		}
		
		$arrQuiz = array_fill_keys($categories, array());
	
		$checkCatID = 0;
		$tmpUserRatings = 0;
		
		// Create HTML-Code for the Questions and answers
		while ($objQuiz->next())
		{
			$objTemp = (object) $objQuiz->row();

			// Radio or checkbox?
			$inputType = $this->getQuizInputType($objTemp);

			$tmpQuestionIDs[] = $objTemp->id;
			
			$tmpAnswerCode = '';
			$tmpAnswers = deserialize($objTemp->answers);

			// Sort answers by random
			if ($objTemp->answers_sort == 1 || ($answersSort && $objTemp->answers_sort < 2))
			{
			    $tmpAnswers = $this->shuffle_assoc($tmpAnswers);
            }

			if ( $tmpAnswers )
			{
				$tmpAnswer = true;
				$tmpAnswerKeys = array();
				foreach($tmpAnswers as $key=>$answer)
				{
					$tmpAnswerPic = '';
					// Add an image
					if ($answer['singleSRC'] != '')
					{
						$objModel = \FilesModel::findByUuid($answer['singleSRC']);
		
						if ($objModel === null)
						{
							if (!\Validator::isUuid($answer['singleSRC']))
							{
								$tmpAnswerPic = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
							}
						}
						elseif (is_file(TL_ROOT . '/' . $objModel->path))
						{
							$tmpAnswerPic = '<figure class="image_container">{{image::' .  $objModel->path . '?width=100&height=100&rel=lightbox&alt=' . $answer['answer'] . '}}</figure>';
						}
					}
					
					$tmpAnswerKeys[] = $key;
					$tmpAnswerCode .= '<div id="answer_' . $objTemp->id . '_' . $key . '" class="answer">';
					if ($tmpAnswerPic != '') $tmpAnswerCode .= $tmpAnswerPic; 
					$tmpAnswerCode .= '<input class="'. $inputType .'" type="'. $inputType .'" id="check_answer_' . $objTemp->id . '_' . $key . '" name="check_answer_' . $objTemp->id . '[]" value="' . $key . '">';
					$tmpAnswerCode .= '<label for="check_answer_' . $objTemp->id . '_' . $key . '">' . $answer['answer'] . '</label></div>';
				}
				$tmpAnswerCode .= '<input type="hidden" id="array_answer_' . $objTemp->id . '" name="array_answer_' . $objTemp->id . '" value="' . implode(',', array_map('intval', $tmpAnswerKeys)) . '">';
				$_SESSION['mod_quiz']['array_answer_' . $objTemp->id] = implode(',', array_map('intval', $tmpAnswerKeys));
			}
			
			// Clean RTE output
			if ($objPage->outputFormat == 'xhtml')
			{
				$objTemp->answers = \StringUtil::toXhtml($tmpAnswerCode);
				$arrQuiz[$objQuiz->pid]['teaser'] = \StringUtil::toXhtml($objQuiz->getRelated('pid')->teaser);
			}
			else
			{
				$objTemp->answers = \StringUtil::toHtml5($tmpAnswerCode);
				$arrQuiz[$objQuiz->pid]['teaser'] = \StringUtil::toHtml5($objQuiz->getRelated('pid')->teaser);
			}
			
			$objTemp->addImage = false;

			// Add an image
			if ($objQuiz->addImage && $objQuiz->singleSRC != '')
			{
				$objModel = \FilesModel::findByUuid($objQuiz->singleSRC);

				if ($objModel === null)
				{
					if (!\Validator::isUuid($objQuiz->singleSRC))
					{
						$objTemp->answers = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
					}
				}
				elseif (is_file(TL_ROOT . '/' . $objModel->path))
				{
					// Do not override the field now that we have a model registry (see #6303)
					$arrQuizTmp = $objQuiz->row();
					$arrQuizTmp['singleSRC'] = $objModel->path;
					$strLightboxId = 'lightbox[' . substr(md5('mod_quiz_' . $objQuiz->id), 0, 6) . ']'; // see #5810

					$this->addImageToTemplate($objTemp, $arrQuizTmp, null, $strLightboxId);
				}
			}

			// Order by PID
			$arrQuiz[$objQuiz->pid]['id'] = $objQuiz->getRelated('pid')->id;
			$arrQuiz[$objQuiz->pid]['headline'] = $objQuiz->getRelated('pid')->headline;
			$arrQuiz[$objQuiz->pid]['items'][] = $objTemp;
		}

		$arrQuiz = $this->getClasses($arrQuiz);
		
		if (\Input::post('user_email')) $this->Template->sendResultMail = true;
		$this->Template->quiz_action = 'results';
		$this->Template->submit = $GLOBALS['TL_LANG']['MSC']['quiz_submit'];
		$this->Template->question_ids = implode(',', array_map('intval', $tmpQuestionIDs));
		$this->Template->quiz = $arrQuiz;
	}
	
	/**
	 * Get the results
	 *
	 * @param array $objQuiz    An array of Quiz questions
	 * @param array $categories An array of Quiz categories
	 * @param integer $levelkey An integer of the quiz level key if set
	 *
	 * Gives HTML-Code for the questions and variables to the template
	 */
	protected function getQuizResults($objQuiz, $categories, $levelkey = 0)
	{
		if ($objQuiz === null)
		{
			return;
		}
		
		$arrQuiz = array_fill_keys($categories, array());
	
		$checkCatID = 0;
		$tmpUserRatings = 0;

		//die(print_r($_POST,true));
		
		// Get ratings and create HTML-Code for the questions, answers (users, correct ones) and comment line
		while ($objQuiz->next())
		{
			if(!isset($_SESSION['mod_quiz']['array_answer_' . $objQuiz->id]))
            {
                $this->throwErrorMessage('sessionTimeout');
            }
            if($_SESSION['mod_quiz']['array_answer_' . $objQuiz->id] != \Input::post('array_answer_' . $objQuiz->id))
            {
                $this->throwErrorMessage('intrusionDetection');
            }

            unset($_SESSION['mod_quiz']['array_answer_' . $objQuiz->id]);

		    // If user has not checked any answer
		    if(!isset($_POST['check_answer_' . $objQuiz->id]))
            {
                $_POST['check_answer_' . $objQuiz->id] = array();
            }

		    $objTemp = (object) $objQuiz->row();

			$inputType = $this->getQuizInputType($objTemp);
			
			// Check and set category ratings
			if ($checkCatID != $objTemp->pid)
			{
				$tmpCatRatings = 0;
				$tmpUserCatRatings = 0;
			}
			
			// Count the quiz ratings
			if (!$objTemp->rating) $objTemp->rating = 1;
			$tmpMaxRatings += $objTemp->rating;
			$tmpCatRatings += $objTemp->rating;
			$checkCatID = $objTemp->pid;
			
			$tmpAnswerCode = '';
			$tmpAnswers = deserialize($objTemp->answers);
			if ( $tmpAnswers )
			{
				$tmpAnswer = true;
				$ArrayAnswerKeys = explode(",", \Input::post('array_answer_' . $objTemp->id));
				foreach($ArrayAnswerKeys as $key)
				{
					$tmpAnswerPic = '';
					// Add an image
					if ($answer['singleSRC'] != '')
					{
						$objModel = \FilesModel::findByUuid($answer['singleSRC']);
		
						if ($objModel === null)
						{
							if (!\Validator::isUuid($answer['singleSRC']))
							{
								$tmpAnswerPic = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
							}
						}
						elseif (is_file(TL_ROOT . '/' . $objModel->path))
						{
							$tmpAnswerPic = '<figure class="image_container">{{image::' .  $objModel->path . '?width=100&height=100&rel=lightbox&alt=' . $answer['answer'] . '}}</figure>';
						}
					}
					
					$answer = $tmpAnswers[$key];
					$tmpLabelClass = "";
					$tmpAnswerCode .= '<div id="answer_' . $objTemp->id . '_' . $key . '" class="answer">';

					// Ass picture to source code
					if ($tmpAnswerPic != '')
					{
                        $tmpAnswerCode .= $tmpAnswerPic;
                    }

                    // Validate answers
					if ((!$answer['answer_true'] && in_array($key, \Input::post('check_answer_' . $objTemp->id))) || ($answer['answer_true'] && !in_array($key, \Input::post('check_answer_' . $objTemp->id))))
					{
						$tmpAnswer = false;
						$tmpLabelClass = ' class="incorrect"';
					}
					else if ($answer['answer_true'])
					{
						$tmpLabelClass = ' class="correct"';
					}
					
					$tmpAnswerCode .= '<span class="control-box"><input class="' . $inputType . '" type="' . $inputType . '"';
					$tmpAnswerCode .= ($answer['answer_true']) ? ' checked' : '';
					$tmpAnswerCode .= ' onClick="return false;"></span><span class="users-choice"><input class="' . $inputType . '" type="' . $inputType . '"';
					$tmpAnswerCode .= (in_array($key, \Input::post('check_answer_' . $objTemp->id))) ? ' checked' : '';
					$tmpAnswerCode .= ' onClick="return false;"></span>';
					
					$tmpAnswerCode .= '<label' . $tmpLabelClass . ' for="check_answer_' . $objTemp->id . '_' . $key . '">' . $answer['answer'] . '</label></div>';
				}
				
				if (!$tmpAnswer)
				{
					$tmpAnswerCode .= '<div class="resultcomment incorrect">' . $GLOBALS['TL_LANG']['MSC']['incorrect_answer'] . '</div>';
					
					// Create linklist with answer pages and categories with wrong answers
					$tmpLinklist[] = $objTemp->answerlink;
					$tmpErrorCat[] = $objQuiz->getRelated('pid')->title;
				}
				else
				{
					$tmpAnswerCode .= '<div class="resultcomment correct">' . $GLOBALS['TL_LANG']['MSC']['correct_answer'] . '</div>';
					
					// Count the category ratings
					$tmpUserRatings += $objTemp->rating;
					$tmpUserCatRatings += $objTemp->rating;
				}
			}
			
			// Clean RTE output
			if ($objPage->outputFormat == 'xhtml')
			{
				$objTemp->answers = \StringUtil::toXhtml($tmpAnswerCode);
				$arrQuiz[$objQuiz->pid]['teaser'] = ($objQuiz->getRelated('pid')->teaser_result) ? '' : \StringUtil::toXhtml($objQuiz->getRelated('pid')->teaser);
			}
			else
			{
				$objTemp->answers = \StringUtil::toHtml5($tmpAnswerCode);
				$arrQuiz[$objQuiz->pid]['teaser'] = ($objQuiz->getRelated('pid')->teaser_result) ? '' : \StringUtil::toHtml5($objQuiz->getRelated('pid')->teaser);
			}
			
			$objTemp->addImage = false;

			// Add an image
			if ($objQuiz->addImage && $objQuiz->singleSRC != '')
			{
				$objModel = \FilesModel::findByUuid($objQuiz->singleSRC);

				if ($objModel === null)
				{
					if (!\Validator::isUuid($objQuiz->singleSRC))
					{
						$objTemp->answers = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
					}
				}
				elseif (is_file(TL_ROOT . '/' . $objModel->path))
				{
					// Do not override the field now that we have a model registry (see #6303)
					$arrQuizTmp = $objQuiz->row();
					$arrQuizTmp['singleSRC'] = $objModel->path;
					$strLightboxId = 'lightbox[' . substr(md5('mod_quiz_' . $objQuiz->id), 0, 6) . ']'; // see #5810

					$this->addImageToTemplate($objTemp, $arrQuizTmp, null, $strLightboxId);
				}
			}

			// Order by PID
			$arrQuiz[$objQuiz->pid]['ratings'] = $tmpCatRatings;
			$arrQuiz[$objQuiz->pid]['user_ratings'] = $tmpUserCatRatings;
			$arrQuiz[$objQuiz->pid]['user_ratings_percent'] = number_format((100/$tmpCatRatings)*$tmpUserCatRatings,0);
			$arrQuiz[$objQuiz->pid]['title'] = $objQuiz->getRelated('pid')->title;
			$arrQuiz[$objQuiz->pid]['headline'] = $objQuiz->getRelated('pid')->headline;
			$arrQuiz[$objQuiz->pid]['id'] = $objQuiz->getRelated('pid')->id;
			$arrQuiz[$objQuiz->pid]['items'][] = $objTemp;
		}

		$arrQuiz = $this->getClasses($arrQuiz);
		
		// Check the ratings and get the reslut analysis and the plain text for the email
		$tmpResultPercent = number_format((100/$tmpMaxRatings)*$tmpUserRatings,0);
		
		switch ($tmpResultPercent) {
		    case 0:
				$tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][0][$levelkey];
		        break;
			case ($tmpResultPercent < 20):
		        $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][20][$levelkey];
		        break;
		    case ($tmpResultPercent < 40):
		        $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][40][$levelkey];
		        break;
		    case ($tmpResultPercent < 60):
		        $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][60][$levelkey];
		        break;
			case ($tmpResultPercent < 80):
		        $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][80][$levelkey];
		        break;
			case ($tmpResultPercent < 100):
		        $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][99][$levelkey];
		        break;
			case 100:
		        $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][100][$levelkey];
		        break;
		}
		
		// Check the Categories with wrong answers and add it to the result text
		if ($tmpErrorCat)
		{
			$tmpErrorCat = array_unique($tmpErrorCat);
			$tmpErrorCatTxt = (count($tmpErrorCat)==1) ? $GLOBALS['TL_LANG']['MSC']['results_analysis_errorcat'] : $GLOBALS['TL_LANG']['MSC']['results_analysis_errorcats'];
			$tmpErrorCatTxt .= " " . implode(', ', array_map(null, $tmpErrorCat));
			$tmpErrorCatTxt = (strrpos($tmpErrorCatTxt, ',')) ? substr_replace($tmpErrorCatTxt = $tmpErrorCatTxt, ' und', strrpos($tmpErrorCatTxt, ','), 1) : $tmpErrorCatTxt;
			$tmpResultText = sprintf($tmpResultText, $tmpErrorCatTxt);
		}
		
		$tmpMailTxt = $tmpResultText . "\n\n";
		
		$tmpMailTxt .= sprintf($GLOBALS['TL_LANG']['MSC']['results_ratings'], $tmpUserRatings, $tmpMaxRatings) . " (" . $tmpResultPercent . " %)\n";
		if ($arrQuiz)
		{
			foreach($arrQuiz as $category)
			{
				$tmpMailTxt .= $category['title'] . ": " . $category['user_ratings'] . "/" . $category['ratings'] . " (" . $category['user_ratings_percent'] . " %)\n";
			}
		}
		
		// Check the linklist and create the plain text for the email
		if ($tmpLinklist)
		{
			$tmpLinklist = array_unique($tmpLinklist);
			
			$tmpMailTxt .= "\n";
			$tmpMailTxt .= $GLOBALS['TL_LANG']['MSC']['results_linklist_headline'];
			foreach($tmpLinklist as $linkID)
			{
				$objTarget = \PageModel::findByPk($linkID);
				if ($objTarget !== null)
				{
					$tmpObj = $objTarget->row();
				}
				$tmpMailTxt .= "\n - " . $tmpObj['title'] . " (" . \Environment::get('base') . $tmpObj['alias'] . ".html)";
			}
			
			$this->Template->linklist = $tmpLinklist;
		}
		
		// Send an email with results to user
		if (\Input::post('user_email')) {
			$this->sendResultMail(\Input::post('user_email'), $tmpMailTxt);
			$this->Template->sendResultMail = true;
		}
		
		$this->Template->show_results = true;
		$this->Template->quiz_action = 'start';
		$this->Template->submit = $GLOBALS['TL_LANG']['MSC']['quiz_start'];
		if (count($this->question_count)>1 || $this->addLevel) $this->Template->start_form = $this->getStartForm($this->id, $this->question_count, $this->addLevel, $this->quiz_level, $this->quizformTpl);
		$this->Template->user_ratings = $tmpUserRatings;
		$this->Template->max_ratings = $tmpMaxRatings;
		$this->Template->result_text = $tmpResultText;
		$this->Template->result_percent = $tmpResultPercent;
		$this->Template->quiz = $arrQuiz;
	}
	
	/**
	 * Get the classes for the questions
	 *
	 * @param array $arrQuiz    An array of quiz questions
	 *
	 * @return array of quiz questions
	 */
	public static function getClasses($arrQuiz)
	{
		if ($arrQuiz === null)
		{
			return;
		}
		
		$arrQuiz = array_values(array_filter($arrQuiz));
		$limit_i = count($arrQuiz) - 1;

		// Add classes first, last, even and odd
		for ($i=0; $i<=$limit_i; $i++)
		{
			$class = (($i == 0) ? 'first ' : '') . (($i == $limit_i) ? 'last ' : '') . (($i%2 == 0) ? 'even' : 'odd');
			$arrQuiz[$i]['class'] = trim($class);
			$limit_j = count($arrQuiz[$i]['items']) - 1;

			for ($j=0; $j<=$limit_j; $j++)
			{
				$class = (($j == 0) ? 'first ' : '') . (($j == $limit_j) ? 'last ' : '') . (($j%2 == 0) ? 'even' : 'odd');
				$arrQuiz[$i]['items'][$j]->class = trim($class);
			}
		}
		
		return $arrQuiz;
	}
	
	/**
	 * Shuffle the answers by shuffling the keys 
	 *
	 * @param array $array	An array of answers
	 *
	 * @return Shuffled array of answers with same keys
	 */
	public static function shuffle_assoc($array)
	{
		// Initialize
	    $shuffled_array = array();
	
	    // Get array's keys and shuffle them.
	    $shuffled_keys = array_keys($array);
	    shuffle($shuffled_keys);
	
	    // Create same array, but in shuffled order.
	    foreach ($shuffled_keys as $shuffled_key)
		{
	    	$shuffled_array[$shuffled_key] = $array[$shuffled_key];	
        } // foreach
		
	    // Return
	    return $shuffled_array;
	}
	
	/**
	 * Get the starting form for the quiz
	 *
	 * @param int $id    The quiz id
	 * @param array $question_counts    An array of question counts
	 * @param array $quiz_levels    	An array of quiz levels
	 * @param string $quizformTpl    	A string with the templatename
	 *
	 * @return starting form for the quiz
	 */
	public static function getStartForm($id, $question_counts, $addLevels, $quiz_levels, $quizformTpl)
	{
		// Construct starting form
		if (($quiz_levels && $quiz_levels[0][0]) || ($question_counts && count($question_counts) > 1))
		{
			// Set-up starting form widgets
			$arrFields = array();
			if ($question_counts && count($question_counts) > 1)
			{
				$arrFields['q_question_count'] = array
				(
						'name'		=> 'q_question_count',
						'label' 	=> $GLOBALS['TL_LANG']['MSC']['question_count'],
						'inputType' => 'select',
						'options' 	=> $question_counts
				);
			}
			
			if ($addLevels && $quiz_levels && $quiz_levels[0]['level_title'])
			{
				//$tmpDefault = '';
				foreach($quiz_levels as $key => $tmpLevel)
				{
					$tmpAryLevel[$key+1] = $tmpLevel['level_title'];
					if ($tmpLevel['level_standard_rating']) $tmpAryLevel = array_reverse($tmpAryLevel, $preserve_keys = true);
					//if ($tmpLevel['level_standard_rating']) $tmpDefault = $tmpLevel['title'];
				}
				
				$arrFields['q_level'] = array
				(
						'name'		=> 'q_level',
						'label' 	=> $GLOBALS['TL_LANG']['MSC']['quiz_level'],
						//'default'   => $tmpDefault,
						//'value'   	=> $tmpDefault,
						//'selected'  => $tmpDefault,
						'inputType' => 'select',
						'options' 	=> $tmpAryLevel,
						//'eval'      => array('selected'=>$tmpDefault)
				);
			}
			
			$objUser = \FrontendUser::getInstance();
			if ($objUser->id)
	    	{
				$tmpMail = $objUser->email;
			}
			
			$arrFields['user_email'] = array
			(
					'name'		=> 'user_email',
					'label' 	=> $GLOBALS['TL_LANG']['MSC']['quiz_email'],
					'value'    	=> $tmpMail,
					'inputType' => 'text',
					'eval'      => array('rgxp'=>'email', 'maxlength'=>128, 'decodeEntities'=>true)
			);
	
			$arrWidgets = array();
			
			// Initialize widgets
			foreach ($arrFields as $arrField)
			{
				$strClass = $GLOBALS['TL_FFL'][$arrField['inputType']];

				// Continue if the class is not defined
				if (!class_exists($strClass))
				{
					continue;
				}
				
				$objWidget = new $strClass($strClass::getAttributesFromDca($arrField, $arrField['name'], $arrField['value']));
				
				// Validate widget
				if (\Input::post('FORM_SUBMIT') == 'tl_quiz_'.$id)
				{
					$objWidget->validate();
	
					if ($objWidget->hasErrors())
					{
						$doNotSubmit = true;
					}
				}
	
				$arrWidgets[] = $objWidget;
			}
			
			// Set the template
			$tmpTpl = ($quizformTpl) ? $quizformTpl : 'form_quiz_start';
			
			// Finalize Template variables
			$objTemplate = new \FrontendTemplate($tmpTpl);
			$objTemplate->class = 'form_quiz_start';
			$objTemplate->action = ampersand(\Environment::get('request'));
			$objTemplate->formId = 'tl_quiz_' . $id;
			$objTemplate->hasError = $doNotSubmit;
			$objTemplate->fields = $arrWidgets;
			$objTemplate->submit = $GLOBALS['TL_LANG']['MSC']['quiz_start'];
		}
		
		if ($objTemplate) return $objTemplate->parse();
	}
	
	/**
	 * Send an email with results to user
	 *
	 * @param text $email    A string with the email
	 * @param text $mailtext A string with the mailtext
	 *
	 */
	public static function sendResultMail($email, $mailtext)
	{	
		$objEmail = new \Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['results_mail_subject'], \Idna::decode(\Environment::get('host')));
		$objEmail->text = $mailtext;
		$objEmail->sendTo($email);
	}

    /**
     * @param $objQuiz
     * @return string
     */
	protected function getQuizInputType($objQuiz)
    {
        $trueAnswers = 0;
        $answers = deserialize($objQuiz->answers, true);
        foreach($answers as $answer)
        {
            if($answer['answer_true'] > 0)
            {
                $trueAnswers++;
            }
        }
        //return 'checkbox';
        return ($trueAnswers == 1) ? 'radio' : 'checkbox';
    }

    /**
     * @param string $strError
     */
    protected function throwErrorMessage($strError='')
    {
        switch($strError){
            case 'sessionTimeout':
                unset($_POST);
                unset($_SESSION['mod_quiz']);
                die('Die Session ist abgelaufen.');
                break;
            case 'intrusionDetection':
                unset($_POST);
                unset($_SESSION['mod_quiz']);
                die('Der Quellcode wurde manipuliert.');
                break;
            default:
                unset($_POST);
                unset($_SESSION['mod_quiz']);
                die('Es ist ein unerwarteter Fehler aufgetreten.');
                break;
        }

    }
}