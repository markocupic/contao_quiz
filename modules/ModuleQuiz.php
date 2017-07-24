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

namespace Markocupic\ContaoQuiz;

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
     * @referenced/assigned event
     * Event model
     */
    protected $refEvent;


    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_quiz_question';

    /**
     * Template
     * @var string
     */
    protected $strTemplate = '';

    /**
     * Set the session key
     * $_SESSION['mod_quiz']
     */
    protected $sessionKey = 'mod_quiz';

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

        // Set session Id first
        if (\Input::get('step') == '' || \Input::get('step') < 2)
        {
            unset($_SESSION[$this->sessionKey]);
        }


        // Handle Ajax requests
        if (\Environment::get('isAjaxRequest'))
        {
            // Bypass search index
            global $objPage;
            $objPage->noSearch = true;

            // Outsource Ajax-handling
            $this->generateAjax();

            exit;

        }

        // Get the referenced event from url token
        // The token is saved in the database tl_calendar_events.eventToken
        if (\Input::get('eventToken'))
        {
            $objEvent = \CalendarEventsModel::findByEventToken(\Input::get('eventToken'));
            if ($objEvent !== null)
            {
                $this->refEvent = $objEvent;
            }
        }

        $this->quizCategories = deserialize($this->quizCategories, true);

        // Return if there are no categories
        if (!is_array($this->quizCategories) || empty($this->quizCategories))
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
            $this->strTemplate = ($this->{'quizTplStep' . $this->step} != '') ? $this->{'quizTplStep' . $this->step} : 'mod_quiz_step_' . $this->step;
        }

        // Store post data in the session
        if (\Input::post('FORM_SUBMIT') == 'tl_quiz')
        {
            $arrData = $this->getFromSession('form_data');
            $arrData = ($arrData === null) ? array() : $arrData;
            foreach ($_POST as $k => $v)
            {
                $arrData[$k] = \Input::post($k);
            }
            $this->addToSession('form_data', $arrData);
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


        // Add number of question to the template
        $this->Template->questionCount = $this->questionCount;

        // Add step index to template
        $this->Template->step = $this->step;

        // Add the referenced Event to the template
        $this->Template->refEvent = $this->refEvent;


        switch ($this->step)
        {
            // Show intro text
            case '1':
                $_SESSION[$this->sessionKey] = array();
                $_SESSION[$this->sessionKey]['stepsTraversed']['step_1'] = true;

                // Set starting time
                $this->addToSession('quiz_start', time());

                // Generate questions and store question data to the session (See system\modules\contao_quiz\modules\session_array.txt)
                $this->generateQuestions();

                $this->Template->quizTeaser = $this->quizTeaser;
                break;
            // Show quiz slider
            case '2':
                if (\Input::post('FORM_SUBMIT') == '' || isset($_SESSION[$this->sessionKey]['stepsTraversed']['step_2']))
                {
                    $this->redirectToStep(1);
                }
                if (!isset($_SESSION[$this->sessionKey]))
                {
                    $this->throwErrorMessage('sessionExpired');
                }
                $arrSteps = $this->getFromSession('stepsTraversed');
                $arrSteps['step_2'] = true;
                $this->addToSession('stepsTraversed', $arrSteps);

                // Add quiz questions to template
                $this->addQuizQuestionsToTemplate();

                break;
            // Show Results
            case '3':
                if (\Input::post('FORM_SUBMIT') == '' || !isset($_SESSION[$this->sessionKey]) || isset($_SESSION[$this->sessionKey]['stepsTraversed']['step_3']))
                {
                    $this->redirectToStep(1);
                }

                $arrSteps = $this->getFromSession('stepsTraversed');
                $arrSteps['step_3'] = true;
                $this->addToSession('stepsTraversed', $arrSteps);

                // Get the quiz questions, user answers and the result
                $this->addQuizResultsToTemplate();


                // Save result
                $this->removeFromSession('rating');
                $this->Template->registerUser = false;

                if ($this->saveResults && $this->minimumPercentScore <= $this->arrResult['rating_percent'])
                {

                    $userScore = array(
                        'pid' => $this->id,
                        'tstamp' => time(),
                        'questionCount' => $this->questionCount,
                        'quiztime' => time() - $this->getFromSession('quiz_start'),
                        'userRating' => $this->arrResult['userRatings'],
                        'maxRating' => $this->arrResult['maxRating'],
                        'rating_percent' => $this->arrResult['rating_percent'],
                        'ip' => $this->anonymizeIp(\Environment::get('ip')),
                    );

                    $this->addToSession('rating', $userScore);

                    $this->Template->registerUser = true;
                }

                break;
            case '4':

                if (\Input::post('FORM_SUBMIT') == '' || !isset($_SESSION[$this->sessionKey]) || isset($_SESSION[$this->sessionKey]['stepsTraversed']['step_4']))
                {
                    $this->redirectToStep(1);
                }
                $arrSteps = $this->getFromSession('stepsTraversed');
                $arrSteps['step_4'] = true;
                $this->addToSession('stepsTraversed', $arrSteps);


                // Redirect to the register page if user has reached 100%
                if (isset($_POST['registerUser']) && $this->saveResults && isset($_SESSION[$this->sessionKey]['rating']))
                {
                    $this->redirectToStep(5);
                }
                $this->Template->linkStep1 = $this->getLinkToStep(1);


                break;
            case '5':
                if (!isset($_SESSION[$this->sessionKey]) || isset($_SESSION[$this->sessionKey]['stepsTraversed']['step_5']) || !isset($_SESSION[$this->sessionKey]['rating']))
                {
                    $this->redirectToStep(1);
                }


                // Add registration form to template
                $objForm = new \Haste\Form\Form('tl_quiz', 'POST', function ($objHaste)
                {
                    return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
                });
                $objForm->preserveGetParameters();
                // Add the form field
                $formData = $this->getFromSession('form_data') ? $this->getFromSession('form_data') : array();
                $objForm->addFormField('user_email', array(
                    'label' => 'E-Mail-Adresse',
                    'inputType' => 'text',
                    'default' => $formData['user_email'],
                    'eval' => array('mandatory' => true, 'rgxp' => 'email')
                ));
                $objForm->addFormField('user_phone', array(
                    'label' => 'Ihre Telefonnummer',
                    'inputType' => 'text',
                    'default' => $formData['user_phone'],
                    'eval' => array('mandatory' => true, 'rgxp' => 'phone')
                ));


                // Add Captcha
                $objForm->addCaptchaFormField('captcha');

                // Add submit button
                $objForm->addSubmitFormField('submit', 'Registrierung abschliessen');

                if ($objForm->validate())
                {
                    // Get the submitted and parsed data of a field (only works with POST):
                    $objResult = new \QuizResultModel();
                    $arrSessionData = $this->getFromSession('rating');
                    $arrSessionFormData = $this->getFromSession('form_data');
                    $objResult->pid = $arrSessionData['pid'];
                    $objResult->tstamp = $arrSessionData['tstamp'];
                    $objResult->questionCount = $arrSessionData['questionCount'];
                    $objResult->quiztime = $arrSessionData['quiztime'];
                    $objResult->userRating = $arrSessionData['userRating'];
                    $objResult->maxRating = $arrSessionData['maxRating'];
                    $objResult->rating_percent = $arrSessionData['rating_percent'];
                    $objResult->ip = $arrSessionData['ip'];
                    $objResult->user_email = $arrSessionFormData['user_email'];
                    $objResult->user_phone = $arrSessionFormData['user_phone'];
                    if ($this->refEvent !== null)
                    {
                        $objResult->refEventId = $this->refEvent->id;
                    }
                    // Save to db
                    $objResult->save();

                    // Notify User
                    $this->notifyUser($objResult);

                    $arrSteps = $this->getFromSession('stepsTraversed');
                    $arrSteps['step_5'] = true;
                    $this->addToSession('stepsTraversed', $arrSteps);

                    // Add User Data to Session
                    $this->addToSession('quizUserSaveToDatabase', 'true');
                    $this->addToSession('quizUserData', $objResult->row());

                    $this->redirectToStep(6);
                }

                $this->Template->form = $objForm->generate();

                break;
            case '6':
                if (!isset($_SESSION[$this->sessionKey]) || isset($_SESSION[$this->sessionKey]['stepsTraversed']['step_6']))
                {
                    $this->redirectToStep(1);
                }
                $arrSteps = $this->getFromSession('stepsTraversed');
                $arrSteps['step_6'] = true;
                $this->addToSession('stepsTraversed', $arrSteps);

                $this->Template->linkStep1 = $this->getLinkToStep(1);
                break;
        }

        // Add Form Action to template
        $this->addFormActionToTemplate();


    }


    /**
     * Gives HTML-Code for the questions and variables to the template
     * @throws \Exception
     */
    protected function addQuizQuestionsToTemplate()
    {
        global $objPage;
        // Get questions from session
        $arrQuestions = $this->getFromSession('questions');
        if (!is_array($arrQuestions))
        {
            return;
        }

        if (count($arrQuestions) < 1)
        {
            return;
        }

        $arrQuizItems = array();
        $i = 0;
        // Create HTML-Code for the Questions and answers
        // Traverse each question
        foreach ($arrQuestions as $arrQuestion)
        {
            $htmlCode = '';

            $objQuizItem = \QuizQuestionModel::findByPk($arrQuestion['questionId']);
            if ($objQuizItem === null)
            {
                throw new \Exception('Quiz question with ID ' . $arrQuestion['questionId'] . ' not found.');
            }

            // Traverse each answer
            foreach ($arrQuestion['arrAnswersOrder'] as $answerKey)
            {
                $arrAnswer = $arrQuestion['arrAnswers'][$answerKey];
                $tmpAnswerPic = '';
                // Add an image to the answer button
                if ($arrAnswer['singleSRC'] != '')
                {
                    $objModel = \FilesModel::findByUuid($arrAnswer['singleSRC']);
                    if ($objModel === null)
                    {
                        if (!\Validator::isUuid($arrAnswer['singleSRC']))
                        {
                            $tmpAnswerPic = '<p class="error">' . $GLOBALS['TL_LANG']['ERR']['version2format'] . '</p>';
                        }
                    }
                    elseif (is_file(TL_ROOT . '/' . $objModel->path))
                    {
                        $tmpAnswerPic = '<figure class="image_container">{{image::' . $objModel->path . '?width=100&height=100&rel=lightbox&alt=' . $arrAnswer['answer'] . '}}</figure>';
                    }
                }

                $htmlCode .= '<div id="answer_' . $arrQuestion['questionId'] . '_' . $answerKey . '" class="answer">';
                if ($tmpAnswerPic != '')
                {
                    $htmlCode .= $tmpAnswerPic;
                }
                // Add the button
                $htmlCode .= '<button type="button" aria-pressed="false" id="button_answer_' . $arrQuestion['questionId'] . '_' . $answerKey . '" data-answer="' . $objQuizItem->id . '_' . $answerKey . '" class="btn btn-info btn-lg button-answer">' . $arrAnswer['answer'] . '</button>';
                $htmlCode .= '</div>';

            }


            // Clean RTE output
            if ($objPage->outputFormat == 'xhtml')
            {
                $objQuizItem->answers = \StringUtil::toXhtml($htmlCode);
                $objQuizItem->parentTeaser = \StringUtil::toXhtml($objQuizItem->getRelated('pid')->teaser);
            }
            else
            {
                $objQuizItem->answers = \StringUtil::toHtml5($htmlCode);
                $objQuizItem->parentTeaser = \StringUtil::toHtml5($objQuizItem->getRelated('pid')->teaser);
            }

            $addImage = false;
            // Add an image
            if ($objQuizItem->addImage && $objQuizItem->singleSRC != '')
            {
                $objModel = \FilesModel::findByUuid($objQuizItem->singleSRC);

                if ($objModel === null)
                {
                    if (!\Validator::isUuid($objQuizItem->singleSRC))
                    {
                        $objQuizItem->answers = '<p class="error">' . $GLOBALS['TL_LANG']['ERR']['version2format'] . '</p>';
                    }
                }
                elseif (is_file(TL_ROOT . '/' . $objModel->path))
                {
                    // Do not override the field now that we have a model registry (see #6303)
                    $arrQuizTmp = $objQuizItem->row();
                    $arrQuizTmp['singleSRC'] = $objModel->path;
                    $strLightboxId = 'lightbox[' . substr(md5('mod_quiz_' . $objQuizItem->id), 0, 6) . ']'; // see #5810
                    $this->addImageToTemplate($objQuizItem, $arrQuizTmp, null, $strLightboxId);
                    $addImage = true;
                }
            }
            $objQuizItem->addImage = $addImage;

            $objQuizItem->pid = $objQuizItem->getRelated('pid')->id;
            $objQuizItem->parentTitle = $objQuizItem->getRelated('pid')->title;
            $objQuizItem->parentHeadline = $objQuizItem->getRelated('pid')->headline;
            $i++;
            $objQuizItem->questionIndex = $i;
            $arrQuizItems[] = $objQuizItem;
        }

        $arrQuizItems = $this->getClasses($arrQuizItems);


        $this->Template->submit = $GLOBALS['TL_LANG']['MSC']['quiz_submit'];
        $this->Template->quizItems = $arrQuizItems;
    }

    /**
     * Add the quiz results to the template
     * @throws \Exception
     */
    protected function addQuizResultsToTemplate()
    {
        global $objPage;
        // Get questions from session
        $arrQuestions = $this->getFromSession('questions');
        if (!is_array($arrQuestions))
        {
            return;
        }

        if (count($arrQuestions) < 1)
        {
            return;
        }

        $arrQuizItems = array();
        $tmpMaxRatings = 0;
        $i = 0;

        // Create HTML-Code for the Questions and answers
        // Traverse each question
        foreach ($arrQuestions as $arrQuestion)
        {

            $objQuizItem = \QuizQuestionModel::findByPk($arrQuestion['questionId']);
            if ($objQuizItem === null)
            {
                throw new \Exception('Quiz question with ID ' . $arrQuestion['questionId'] . ' not found.');
            }


            // Count the quiz ratings
            if (!$objQuizItem->rating)
            {
                $objQuizItem->rating = 1;
            }
            $tmpUserRatings = 0;
            $tmpMaxRatings += $objQuizItem->rating;
            $blnAnswerIsCorrect = true;
            $strHtmlQuizItem = '';

            foreach ($arrQuestion['arrAnswersOrder'] as $answerKey)
            {
                $arrAnswer = $arrQuestion['arrAnswers'][$answerKey];

                // Generate the partial template for each answer (button)
                $objPartial = new \FrontendTemplate('evaluation_partial');
                $objPartial->id = $objQuizItem->id . '_' . $answerKey;
                $objPartial->quizId = $objQuizItem->id;
                $objPartial->answerKey = $answerKey;
                $objPartial->checked = $arrAnswer['checkedByUser'] ? ' checked' : '';
                $objPartial->labelText = $arrAnswer['answer'];


                // Add an image to the answer button
                if ($arrAnswer['singleSRC'] != '')
                {
                    $objModel = \FilesModel::findByUuid($arrAnswer['singleSRC']);
                    if ($objModel !== null)
                    {
                        if (\Validator::isUuid($arrAnswer['singleSRC']))
                        {
                            $objPartial->singleSRC = $objModel->path;
                            $objPartial->alt = $arrAnswer['answer'];
                        }
                    }
                }


                // Validate answers
                if ((!$arrAnswer['answerTrue'] && $answerKey == $arrQuestion['userAnswer']) || ($arrAnswer['answerTrue'] && $answerKey != $arrQuestion['userAnswer']))
                {
                    $blnAnswerIsCorrect = false;
                }

                if ($arrAnswer['answerTrue'])
                {
                    $objPartial->class = 'correct-answer';
                }

                if (!$arrAnswer['answerTrue'] && $arrAnswer['checkedByUser'])
                {
                    $objPartial->class = 'incorrect-answer';
                }

                if ($arrAnswer['answerTrue'])
                {
                    $objPartial->resultComment = $GLOBALS['TL_LANG']['MSC']['correct_answer'];
                }

                if (!$arrAnswer['answerTrue'] && $arrAnswer['checkedByUser'])
                {
                    $objPartial->resultComment = $GLOBALS['TL_LANG']['MSC']['incorrect_answer'];
                }

                $strHtmlQuizItem .= $objPartial->parse();
            }

            $objQuizItem->class = $blnAnswerIsCorrect ? ' answered-correct' : ' answered-false';
            if ($blnAnswerIsCorrect)
            {
                $tmpUserRatings += $objQuizItem->rating;
            }

            // Clean RTE output
            if ($objPage->outputFormat == 'xhtml')
            {
                $objQuizItem->answers = \StringUtil::toXhtml($strHtmlQuizItem);
                $objQuizItem->parentTeaser = \StringUtil::toXhtml($objQuizItem->getRelated('pid')->teaser);
            }
            else
            {
                $objQuizItem->answers = \StringUtil::toHtml5($strHtmlQuizItem);
                $objQuizItem->parentTeaser = \StringUtil::toHtml5($objQuizItem->getRelated('pid')->teaser);
            }


            // Add an image
            $addImage = false;
            if ($objQuizItem->addImage && $objQuizItem->singleSRC != '')
            {
                $objModel = \FilesModel::findByUuid($objQuizItem->singleSRC);

                if ($objModel === null)
                {
                    if (!\Validator::isUuid($objQuizItem->singleSRC))
                    {
                        $objQuizItem->answers = '<p class="error">' . $GLOBALS['TL_LANG']['ERR']['version2format'] . '</p>';
                    }
                }
                elseif (is_file(TL_ROOT . '/' . $objModel->path))
                {
                    // Do not override the field now that we have a model registry (see #6303)
                    $arrQuizTmp = $objQuizItem->row();
                    $arrQuizTmp['singleSRC'] = $objModel->path;
                    $strLightboxId = 'lightbox[' . substr(md5('mod_quiz_' . $objQuizItem->id), 0, 6) . ']'; // see #5810

                    $this->addImageToTemplate($objQuizItem, $arrQuizTmp, null, $strLightboxId);
                    $addImage = true;
                }
            }
            $objQuizItem->addImage = $addImage;

            $objQuizItem->pid = $objQuizItem->getRelated('pid')->id;
            $objQuizItem->parentTitle = $objQuizItem->getRelated('pid')->title;
            $objQuizItem->parentHeadline = $objQuizItem->getRelated('pid')->headline;
            $i++;
            $objQuizItem->questionIndex = $i;
            $arrQuizItems[] = $objQuizItem;

        }

        $arrQuizItems = $this->getClasses($arrQuizItems);
        $this->Template->quizItems = $arrQuizItems;

        // Check the ratings and get the result analysis and the plain text for the email
        $tmpResultPercent = number_format((100 / $tmpMaxRatings) * $tmpUserRatings, 0);
        $this->arrResult['userRatings'] = $tmpUserRatings;
        $this->arrResult['maxRating'] = $tmpMaxRatings;
        $this->arrResult['rating_percent'] = $tmpResultPercent;
        $this->addToSession('arr_result', $this->arrResult);

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

        $this->Template->userRatings = $tmpUserRatings;
        $this->Template->maxRatings = $tmpMaxRatings;
        $this->Template->resultText = $tmpResultText;
        $this->Template->resultPercent = $tmpResultPercent;
    }

    /**
     * Generate the questions and store the dat in the session
     */
    protected function generateQuestions()
    {

        $arrOptions = array();
        $t = static::$strTable;
        $tmpSort = ($this->questionSort != 'random' && $this->questionSort != '') ? "$t." . $this->questionSort : 'RAND()';
        // Order by category
        // $arrOptions['order'] = "$t.pid, " . $tmpSort;

        // No order
        $arrOptions['order'] = $tmpSort;

        if ($this->questionCount > 0)
        {
            $arrOptions['limit'] = $this->questionCount;
        }


        // Get object with questions
        $objQuiz = \QuizQuestionModel::findPublishedByPids($this->quizCategories, $arrOptions);
        // Check if there are not enough questions
        if ($this->questionCount === null)
        {
            $this->throwErrorMessage('No quiz questions found.');
        }

        // Check if there are not enough questions
        if ($this->questionCount > 0)
        {
            if ($this->questionCount > $objQuiz->count())
            {
                $this->throwErrorMessage('notEnoughQuestions');
            }
        }
        $arrQuestionIds = array();
        $arrQuestions = array();
        while ($objQuiz->next())
        {
            $arrQuestionIds[] = $objQuiz->id;

            // Add question array to session
            $arrAnswers = \QuizQuestionModel::getAnswers($objQuiz->id);
            $arrAnswersOrder = array_keys($arrAnswers);
            if ($this->answersSort)
            {
                shuffle($arrAnswersOrder);
            }
            $arrQuestions[$objQuiz->id] = array(
                'answered' => false,
                'userAnswer' => '',
                'answer' => '',
                'eval' => '',
                'questionId' => $objQuiz->id,
                'questionText' => $objQuiz->question,
                'arrAnswers' => $arrAnswers,
                'arrAnswersOrder' => $arrAnswersOrder
            );
        }

        $this->addToSession('question_ids', $arrQuestionIds);
        $this->addToSession('questions', $arrQuestions);
        $this->addToSession('sessionId', md5(microtime()));


    }

    /**
     * @param $k
     * @param $v
     */
    protected function addToSession($k, $v)
    {
        if (!isset($_SESSION[$this->sessionKey]))
        {
            $this->throwErrorMessage('sessionExpired');
            exit;
        }
        $_SESSION[$this->sessionKey][$k] = $v;
    }

    /**
     * @param $k
     * @return null
     */
    protected function getFromSession($k)
    {
        if (!isset($_SESSION[$this->sessionKey]))
        {
            $this->throwErrorMessage('sessionExpired');
            exit;
        }
        if (!isset($_SESSION[$this->sessionKey][$k]) || empty($_SESSION[$this->sessionKey][$k]))
        {
            return null;
        }
        return $_SESSION[$this->sessionKey][$k];

    }

    /**
     * @param $k
     */
    protected function removeFromSession($k)
    {
        if (!isset($_SESSION[$this->sessionKey]))
        {
            $this->throwErrorMessage('sessionExpired');
            exit;
        }
        if (isset($_SESSION[$this->sessionKey][$k]))
        {
            unset($_SESSION[$this->sessionKey][$k]);
        }
    }

    /**
     * @return bool
     */
    protected function validateSessionId()
    {
        if (strlen(\Input::get('sessionId')) > 0 && \Input::get('sessionId') == $this->getFromSession('sessionId'))
        {
            return true;
        }
        return false;
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
        return $arrQuiz;

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
        $objEmail->sendTo($objResult->user_email);
    }


    /**
     * @param string $strError
     */
    protected function throwErrorMessage($strError = '')
    {
        switch ($strError)
        {
            case 'notEnoughQuestions':
                unset($_POST);
                unset($_SESSION[$this->sessionKey]);
                die('Es sind nicht genug Fragen vorhanden. Reduzieren Sie die Anzahl Fragen in den Moduleinstellungen.');
                break;
            case 'sessionExpired':
                unset($_POST);
                unset($_SESSION[$this->sessionKey]);
                // $this->reload();
                die('Die Session ist abgelaufen.');
                break;

            default:
                unset($_POST);
                unset($_SESSION[$this->sessionKey]);
                die('Es ist ein unerwarteter Fehler aufgetreten.');
                break;
        }

    }

    /**
     * @param int|null $step
     */
    protected function addFormActionToTemplate($step = null)
    {
        if ($step === null)
        {
            $step = intval($this->step) + 1;
        }

        $strUrl = \Haste\Util\Url::removeQueryString(array('step', 'sessionId'));

        $strAction = \Haste\Util\Url::addQueryString(sprintf('step=%s', $step), $strUrl);
        $sessionId = $this->getFromSession('sessionId');
        if ($sessionId)
        {
            $strAction = \Haste\Util\Url::addQueryString(sprintf('sessionId=%s', $sessionId), $strAction);
        }
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
    protected function redirectToStep($step)
    {
        $strUrl = \Haste\Util\Url::removeQueryString(array('step'));
        if ($step < 2)
        {
            $strUrl = \Haste\Util\Url::removeQueryString(array('sessionId'), $strUrl);
        }
        $strRedirect = \Haste\Util\Url::addQueryString(sprintf('step=%s', $step), $strUrl);
        $this->redirect($strRedirect);

    }


    /**
     *
     */
    protected function generateAjax()
    {
        // Validate answer, set save data to the $_SESSION and send response to the browser
        if (\Input::get('send_answer') == 'true' && \Input::post('data_answer') != '' && $this->validateSessionId())
        {
            $arrAnswer = explode('_', \Input::post('data_answer'));
            $questionId = $arrAnswer[0];
            $answerKey = $arrAnswer[1];
            $arrSession = $this->getFromSession('questions');
            $json = array();
            // Hacking prevention: There can be only one request per question
            if ($arrSession[$questionId]['answered'] != true)
            {
                $eval = \QuizQuestionModel::evalAnswer($questionId, $answerKey) ? 'true' : 'false';
                $arrSession[$questionId]['answered'] = 'true';
                $arrSession[$questionId]['eval'] = $eval;
                $arrSession[$questionId]['userAnswer'] = $answerKey;
                $arrSession[$questionId]['rightAnswer'] = \QuizQuestionModel::getAnswer($questionId);
                $arrSession[$questionId]['arrAnswers'][$answerKey]['checkedByUser'] = 'true';

                $this->addToSession('questions', $arrSession);

                $json['eval'] = $eval;
                $json['userAnswer'] = $answerKey;
                $json['rightAnswer'] = \QuizQuestionModel::getAnswer($questionId);
                \QuizAnswerStatsModel::addClick($questionId, $answerKey);
                $json['equalClicks'] = \QuizAnswerStatsModel::getClicks($questionId, $answerKey);
                $json['clickPrctDistribution'] = \QuizAnswerStatsModel::getClickDistributionInPercentage($questionId);

            }
            else
            {
                // Hacking attempt
                $json['eval'] = 'error';
            }
            die(json_encode($json));
        }
    }
}