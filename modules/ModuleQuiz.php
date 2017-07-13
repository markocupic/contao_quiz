<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace Markocupic;

/**
 * Class ModuleQuiz
 *
 * @copyright  Marko Cupic 2017 forked from fiveBytes 2014
 * @author     Marko Cupic <m.cupic@gmx.ch> & Stefen Baetge <fivebytes.de>
 * @package    Contao Quiz
 */
class ModuleQuiz extends \Module
{
    /**
     * @var
     */
    protected $step;


    /**
     * @var
     */
    protected $arrResult = array();


    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_quiz_question';

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_quiz_step_1';

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

        // Redirect if there is no "step" parameter in the query string
        if (!\Input::get('step') || !is_numeric(\Input::get('step')))
        {
            $strUrl = \Haste\Util\Url::addQueryString('step=1');
            $this->redirect($strUrl);
        }
        else
        {
            // set the step var
            $this->step = \Input::get('step');

            // Set the template
            $this->strTemplate = 'mod_quiz_step_' . \Input::get('step');
        }

        return parent::generate();
    }

    /**
     * Generate the module (controler)
     */
    protected function compile()
    {
        // Edit the page title
        global $objPage;
        $objPage->pageTitle .= ' - ' . $GLOBALS['TL_LANG']['MSC']['step_' . $this->step . '_subline'];

        // Add Form Action
        $this->addFormActionToTemplate();

        // Add number of question to the template
        $this->Template->question_count = $this->question_count;

        // Add step index to template
        $this->Template->step = $this->step;


        $arrOptions = array();
        $t = static::$strTable;
        $tmpSort = ($this->question_sort != 'random' && $this->question_sort != '') ? "$t." . $this->question_sort : 'RAND()';
        $arrOptions['order'] = "$t.pid, " . $tmpSort;
        if ($this->question_count > 0)
        {
            $arrOptions['limit'] = $this->question_count;
        }


        switch ($this->step)
        {
            // Show intro text
            case '1':
                unset($_SESSION['mod_quiz']);
                $_SESSION['mod_quiz'] = array();
                $_SESSION['mod_quiz']['stepsTraversed']['step_1'] = true;
                // Set starting time
                $_SESSION['mod_quiz']['quiz_start'] = time();

                $this->Template->quizTeaser = $this->quiz_teaser;
                break;
            // Show quiz slider
            case '2':
                if (\Input::post('FORM_SUBMIT') == '' || isset($_SESSION['mod_quiz']['stepsTraversed']['step_2']))
                {
                    $this->redirectToStep(1);
                }
                if (!isset($_SESSION['mod_quiz']))
                {
                    $this->throwErrorMessage('sessionExpired');
                }

                $_SESSION['mod_quiz']['stepsTraversed']['step_2'] = true;


                // Get object with questions
                $this->objQuiz = \QuizQuestionModel::findPublishedByPids($this->quiz_categories, $arrOptions);

                // Check if there are not enough questions
                if ($this->question_count > 0)
                {
                    if ($this->question_count > $this->objQuiz->count())
                    {
                        $this->throwErrorMessage('notEnoughQuestions');
                    }
                }


                // Add quiz questions to template
                $this->addQuizQuestionsToTemplate($this->objQuiz, $this->quiz_categories, $this->answers_sort);


                break;
            // Show Results
            case '3':
                if (\Input::post('FORM_SUBMIT') == '' || !isset($_SESSION['mod_quiz']) || isset($_SESSION['mod_quiz']['stepsTraversed']['step_3']))
                {
                    $this->redirectToStep(1);
                }
                $_SESSION['mod_quiz']['stepsTraversed']['step_3'] = true;


                // Get the object with questions
                $arrOptions['order'] = 'tl_quiz_question.id=' . str_replace(",", " DESC,tl_quiz_question.id=", \Input::post('question_ids')) . ' DESC';
                $this->objQuiz = \QuizQuestionModel::findPublishedByIds(explode(",", \Input::post('question_ids')), $arrOptions);

                // Get the quiz questions, user answers and the result
                $this->addQuizResultsToTemplate($this->objQuiz, $this->quiz_categories);


                // Save result
                unset($_SESSION['mod_quiz']['rating']);
                $this->Template->registerUser = false;
                if ($this->save_results && $this->arrResult['rating_percent'] == 100)
                {
                    $_SESSION['mod_quiz']['rating'] = array(
                        'pid' => $this->id,
                        'tstamp' => time(),
                        'question_count' => $this->question_count,
                        'quiztime' => time() - $_SESSION['mod_quiz']['quiz_start'],
                        'user_rating' => $this->arrResult['user_ratings'],
                        'max_rating' => $this->arrResult['max_rating'],
                        'rating_percent' => $this->arrResult['rating_percent'],
                        'ip' => $this->anonymizeIp(\Environment::get('ip')),
                    );

                    $this->Template->registerUser = true;
                }

                break;
            case '4':

                if (\Input::post('FORM_SUBMIT') == '' || !isset($_SESSION['mod_quiz']) || isset($_SESSION['mod_quiz']['stepsTraversed']['step_4']))
                {
                    $this->redirectToStep(1);
                }
                $_SESSION['mod_quiz']['stepsTraversed']['step_4'] = true;


                // Redirect to the register page if user has reached 100%
                if (isset($_POST['registerUser']) && $this->save_results && isset($_SESSION['mod_quiz']['rating']))
                {
                    $this->redirectToStep(5);
                }
                $this->Template->linkStep1 = $this->getLinkToStep(1);


                break;
            case '5':
                if (!isset($_SESSION['mod_quiz']) || !isset($_SESSION['mod_quiz']['rating']))
                {
                    $this->redirectToStep(1);
                }
                $_SESSION['mod_quiz']['stepsTraversed']['step_5'] = true;


                $objForm = new \Haste\Form\Form('tl_quiz_register_email', 'POST', function ($objHaste)
                {
                    return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
                });
                $objForm->preserveGetParameters();
                // Add the form field
                $objForm->addFormField('email', array(
                    'label' => 'E-Mail-Adresse',
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'rgxp' => 'email')
                ));


                // Add Captcha
                $objForm->addCaptchaFormField('captcha');

                // Add submit button
                $objForm->addSubmitFormField('submit', 'Registrierung abschliessen');

                if ($objForm->validate())
                {
                    // Get the submitted and parsed data of a field (only works with POST):
                    $arrData = $objForm->fetch('email');
                    $objResult = new \QuizResultModel();
                    $objResult->pid = $_SESSION['mod_quiz']['rating']['pid'];
                    $objResult->tstamp = $_SESSION['mod_quiz']['rating']['tstamp'];
                    $objResult->question_count = $_SESSION['mod_quiz']['rating']['question_count'];
                    $objResult->quiztime = $_SESSION['mod_quiz']['rating']['quiztime'];
                    $objResult->user_rating = $_SESSION['mod_quiz']['rating']['user_rating'];
                    $objResult->max_rating = $_SESSION['mod_quiz']['rating']['max_rating'];
                    $objResult->rating_percent = $_SESSION['mod_quiz']['rating']['rating_percent'];
                    $objResult->ip = $_SESSION['mod_quiz']['rating']['ip'];
                    $objResult->email = \Input::post('email');
                    // Save to db
                    $objResult->save();

                    // Notify User
                    $this->notifyUser($objResult);

                    $this->redirectToStep(6);
                }

                $this->Template->form = $objForm->generate();

                break;
            case '6':
                if (!isset($_SESSION['mod_quiz']))
                {
                    $this->redirectToStep(1);
                }
                unset($_SESSION['mod_quiz']);
                $this->Template->linkStep1 = $this->getLinkToStep(1);
                break;
        }
    }

    /**
     * Get the questions
     *
     * @param array $objQuiz An array of Quiz questions
     * @param array $categories An array of Quiz categories
     * @param int $answerSort An integer 1 for random answers
     *
     * Gives HTML-Code for the questions and variables to the template
     */
    protected function addQuizQuestionsToTemplate($objQuiz, $categories, $answersSort)
    {

        if ($objQuiz === null)
        {
            return;
        }

        $arrQuiz = array_fill_keys($categories, array());


        // Create HTML-Code for the Questions and answers
        while ($objQuiz->next())
        {
            $objTemp = (object)$objQuiz->row();

            // Get correct input type (radio or checkbox)
            $inputType = $this->getQuizInputType($objTemp);

            $tmpQuestionIDs[] = $objTemp->id;

            $tmpAnswerCode = '';
            $tmpAnswers = deserialize($objTemp->answers);

            // Sort answers by random
            if ($objTemp->answers_sort == 1 || ($answersSort && $objTemp->answers_sort < 2))
            {
                $tmpAnswers = $this->shuffle_assoc($tmpAnswers);
            }

            if ($tmpAnswers)
            {
                $tmpAnswerKeys = array();
                foreach ($tmpAnswers as $key => $answer)
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
                                $tmpAnswerPic = '<p class="error">' . $GLOBALS['TL_LANG']['ERR']['version2format'] . '</p>';
                            }
                        }
                        elseif (is_file(TL_ROOT . '/' . $objModel->path))
                        {
                            $tmpAnswerPic = '<figure class="image_container">{{image::' . $objModel->path . '?width=100&height=100&rel=lightbox&alt=' . $answer['answer'] . '}}</figure>';
                        }
                    }

                    $tmpAnswerKeys[] = $key;
                    $tmpAnswerCode .= '<div id="answer_' . $objTemp->id . '_' . $key . '" class="answer">';
                    if ($tmpAnswerPic != '')
                    {
                        $tmpAnswerCode .= $tmpAnswerPic;
                    }
                    $tmpAnswerCode .= '<input class="check_answer ' . $inputType . '" type="' . $inputType . '" id="check_answer_' . $objTemp->id . '_' . $key . '" name="check_answer_' . $objTemp->id . '[]" value="' . $key . '">';
                    $tmpAnswerCode .= '<label for="check_answer_' . $objTemp->id . '_' . $key . '">' . $answer['answer'] . '</label></div>';
                    $tmpAnswerCode .= '<button type="button" aria-pressed="false" id="button_answer_' . $objTemp->id . '_' . $key . '" class="btn btn-info btn-lg button_answer" data-radio-id="check_answer_' . $objTemp->id . '_' . $key . '" data-input-type="' . $inputType . '">' . $answer['answer'] . '</button>';



                }
                $tmpAnswerCode .= '<input type="hidden" id="array_answer_' . $objTemp->id . '" name="array_answer_' . $objTemp->id . '" value="' . implode(',', array_map('intval', $tmpAnswerKeys)) . '">';

                // Prevent hacking attempts
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
                        $objTemp->answers = '<p class="error">' . $GLOBALS['TL_LANG']['ERR']['version2format'] . '</p>';
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


        $this->Template->submit = $GLOBALS['TL_LANG']['MSC']['quiz_submit'];
        $this->Template->question_ids = implode(',', array_map('intval', $tmpQuestionIDs));
        $this->Template->quiz = $arrQuiz;

    }

    /**
     * @param $objQuiz
     * @param $categories
     */
    protected function addQuizResultsToTemplate($objQuiz, $categories)
    {
        if (!isset($_SESSION['mod_quiz']))
        {
            $this->throwErrorMessage('sessionExpired');
        }

        if ($objQuiz === null)
        {
            $this->throwErrorMessage('notEnoughQuestions');
        }

        $arrQuiz = array_fill_keys($categories, array());

        $checkCatID = 0;
        $tmpUserRatings = 0;


        // Get ratings and create HTML-Code for the questions, answers (users, correct ones) and comment line
        while ($objQuiz->next())
        {
            if (!isset($_SESSION['mod_quiz']['array_answer_' . $objQuiz->id]))
            {
                $this->throwErrorMessage('sessionExpired');
            }
            if ($_SESSION['mod_quiz']['array_answer_' . $objQuiz->id] != \Input::post('array_answer_' . $objQuiz->id))
            {
                $this->throwErrorMessage('intrusionDetection');
            }

            unset($_SESSION['mod_quiz']['array_answer_' . $objQuiz->id]);

            // If user has not checked any answer in this question
            if (!isset($_POST['check_answer_' . $objQuiz->id]))
            {
                $_POST['check_answer_' . $objQuiz->id] = array();
            }

            $objTemp = (object)$objQuiz->row();

            // Get correct input type (radio or checkbox)
            $inputType = $this->getQuizInputType($objTemp);

            // Check and set category ratings
            if ($checkCatID != $objTemp->pid)
            {
                $tmpCatRatings = 0;
                $tmpUserCatRatings = 0;
            }

            // Count the quiz ratings
            if (!$objTemp->rating)
            {
                $objTemp->rating = 1;
            }
            $tmpMaxRatings += $objTemp->rating;
            $tmpCatRatings += $objTemp->rating;
            $checkCatID = $objTemp->pid;

            $tmpAnswerCode = '';
            $tmpAnswers = deserialize($objTemp->answers);
            if ($tmpAnswers)
            {
                $tmpAnswer = true;
                $ArrayAnswerKeys = explode(",", \Input::post('array_answer_' . $objTemp->id));
                foreach ($ArrayAnswerKeys as $key)
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
                                $tmpAnswerPic = '<p class="error">' . $GLOBALS['TL_LANG']['ERR']['version2format'] . '</p>';
                            }
                        }
                        elseif (is_file(TL_ROOT . '/' . $objModel->path))
                        {
                            $tmpAnswerPic = '<figure class="image_container">{{image::' . $objModel->path . '?width=100&height=100&rel=lightbox&alt=' . $answer['answer'] . '}}</figure>';
                        }
                    }

                    $answer = $tmpAnswers[$key];
                    $tmpLabelClass = "";

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
                        $tmpAnswerCode .= '<div id="answer_' . $objTemp->id . '_' . $key . '" class="incorrect-answer answer">';
                    }
                    else
                    {
                        if ($answer['answer_true'])
                        {
                            $tmpLabelClass = ' class="correct"';
                        }
                        $tmpAnswerCode .= '<div id="answer_' . $objTemp->id . '_' . $key . '" class="correct-answer answer">';

                    }

                    $tmpAnswerCode .= '<span class="control-box"><input class="' . $inputType . '" type="' . $inputType . '"';
                    $tmpAnswerCode .= ($answer['answer_true']) ? ' checked' : '';
                    $checked = in_array($key, \Input::post('check_answer_' . $objTemp->id)) ? ' checked' : '';
                    $tmpAnswerCode .= ' onClick="return false;"></span><span class="users-choice' . $checked . '"><input class="' . $inputType . '" type="' . $inputType . '"';
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
                        $objTemp->answers = '<p class="error">' . $GLOBALS['TL_LANG']['ERR']['version2format'] . '</p>';
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
            $arrQuiz[$objQuiz->pid]['user_ratings_percent'] = number_format((100 / $tmpCatRatings) * $tmpUserCatRatings, 0);
            $arrQuiz[$objQuiz->pid]['title'] = $objQuiz->getRelated('pid')->title;
            $arrQuiz[$objQuiz->pid]['headline'] = $objQuiz->getRelated('pid')->headline;
            $arrQuiz[$objQuiz->pid]['id'] = $objQuiz->getRelated('pid')->id;
            $arrQuiz[$objQuiz->pid]['items'][] = $objTemp;
        }

        $arrQuiz = $this->getClasses($arrQuiz);

        // Check the ratings and get the reslut analysis and the plain text for the email
        $tmpResultPercent = number_format((100 / $tmpMaxRatings) * $tmpUserRatings, 0);

        switch ($tmpResultPercent)
        {
            case 0:
                $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][0];
                break;
            case ($tmpResultPercent < 20):
                $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][20];
                break;
            case ($tmpResultPercent < 40):
                $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][40];
                break;
            case ($tmpResultPercent < 60):
                $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][60];
                break;
            case ($tmpResultPercent < 80):
                $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][80];
                break;
            case ($tmpResultPercent < 100):
                $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][99];
                break;
            case 100:
                $tmpResultText = $GLOBALS['TL_LANG']['MSC']['results_analysis'][100];
                break;
        }

        // Check the Categories with wrong answers and add it to the result text
        if ($tmpErrorCat)
        {
            $tmpErrorCat = array_unique($tmpErrorCat);
            $tmpErrorCatTxt = (count($tmpErrorCat) == 1) ? $GLOBALS['TL_LANG']['MSC']['results_analysis_errorcat'] : $GLOBALS['TL_LANG']['MSC']['results_analysis_errorcats'];
            $tmpErrorCatTxt .= " " . implode(', ', array_map(null, $tmpErrorCat));
            $tmpErrorCatTxt = (strrpos($tmpErrorCatTxt, ',')) ? substr_replace($tmpErrorCatTxt, ' und', strrpos($tmpErrorCatTxt, ','), 1) : $tmpErrorCatTxt;
            $tmpResultText = sprintf($tmpResultText, $tmpErrorCatTxt);
        }

        $tmpMailTxt = $tmpResultText . "\n\n";

        $tmpMailTxt .= sprintf($GLOBALS['TL_LANG']['MSC']['results_ratings'], $tmpUserRatings, $tmpMaxRatings) . " (" . $tmpResultPercent . " %)\n";
        if ($arrQuiz)
        {
            foreach ($arrQuiz as $category)
            {
                $tmpMailTxt .= $category['title'] . ": " . $category['user_ratings'] . "/" . $category['ratings'] . " (" . $category['user_ratings_percent'] . " %)\n";
            }
        }


        $this->arrResult['user_ratings'] = $tmpUserRatings;
        $this->arrResult['max_rating'] = $tmpMaxRatings;
        $this->arrResult['rating_percent'] = $tmpResultPercent;

        $this->Template->user_ratings = $tmpUserRatings;
        $this->Template->max_ratings = $tmpMaxRatings;
        $this->Template->result_text = $tmpResultText;
        $this->Template->result_percent = $tmpResultPercent;
        $this->Template->quiz = $arrQuiz;
    }

    /**
     * Get the classes for the questions
     *
     * @param array $arrQuiz An array of quiz questions
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
        for ($i = 0; $i <= $limit_i; $i++)
        {
            $class = (($i == 0) ? 'first ' : '') . (($i == $limit_i) ? 'last ' : '') . (($i % 2 == 0) ? 'even' : 'odd');
            $arrQuiz[$i]['class'] = trim($class);
            $limit_j = count($arrQuiz[$i]['items']) - 1;

            for ($j = 0; $j <= $limit_j; $j++)
            {
                $class = (($j == 0) ? 'first ' : '') . (($j == $limit_j) ? 'last ' : '') . (($j % 2 == 0) ? 'even' : 'odd');
                $arrQuiz[$i]['items'][$j]->class = trim($class);
            }
        }

        return $arrQuiz;
    }

    /**
     * Shuffle the answers by shuffling the keys
     *
     * @param array $array An array of answers
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
     * @param $objResult
     */
    public static function notifyUser($objResult)
    {
        $objTemplate = new \FrontendTemplate('notifyQuizUserByEmail');
        $objEmail = new \Email();
        $objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
        $objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
        $objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['results_mail_subject'], \Idna::decode(\Environment::get('host')));
        $objEmail->text = $objTemplate->parse();
        $objEmail->sendTo($objResult->email);
    }

    /**
     * @param $objQuiz
     * @return string
     */
    protected function getQuizInputType($objQuiz)
    {
        $trueAnswers = 0;
        $answers = deserialize($objQuiz->answers, true);
        foreach ($answers as $answer)
        {
            if ($answer['answer_true'] > 0)
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
    protected function throwErrorMessage($strError = '')
    {
        switch ($strError)
        {

            case 'intrusionDetection':
                unset($_POST);
                unset($_SESSION['mod_quiz']);
                die('Der Quellcode wurde manipuliert.');
                break;
            case 'notEnoughQuestions':
                unset($_POST);
                unset($_SESSION['mod_quiz']);
                die('Es sind nicht genug Fragen vorhanden. Reduzieren Sie die Anzahl Fragen in den Moduleinstellungen.');
                break;
            case 'sessionExpired':
                unset($_POST);
                unset($_SESSION['mod_quiz']);
                // $this->reload();
                die('Die Session ist abgelaufen.');
                break;

            default:
                unset($_POST);
                unset($_SESSION['mod_quiz']);
                die('Es ist ein unerwarteter Fehler aufgetreten.');
                break;
        }

    }

    /**
     * @param int|null $step
     */
    protected function addFormActionToTemplate(int $step = null)
    {
        if ($step === null)
        {
            $step = intval($this->step) + 1;
        }

        $strUrl = \Haste\Util\Url::removeQueryString(array('step'));

        $strAction = \Haste\Util\Url::addQueryString(sprintf('step=%s', $step), $strUrl);
        $this->Template->action = $strAction;

    }

    /**
     * @param int $step
     * @return string
     */
    protected function getLinkToStep($step = 1)
    {
        $strUrl = \Haste\Util\Url::removeQueryString(array('step'));
        return \Haste\Util\Url::addQueryString(sprintf('step=%s', $step), $strUrl);
    }

    /**
     * @param int|null $step
     */
    protected function redirectToStep(int $step)
    {
        $strUrl = \Haste\Util\Url::removeQueryString(array('step'));
        $strRedirect = \Haste\Util\Url::addQueryString(sprintf('step=%s', $step), $strUrl);
        $this->redirect($strRedirect);
    }
}