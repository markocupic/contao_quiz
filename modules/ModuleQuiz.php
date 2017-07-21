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

        $this->quizCategories = deserialize($this->quizCategories);

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
            $this->strTemplate = 'mod_quiz_step_' . \Input::get('step');
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

        // Add Form Action to template
        $this->addFormActionToTemplate();


        switch ($this->step)
        {
            // Show intro text
            case '1':
                unset($_SESSION['mod_quiz']);
                $_SESSION['mod_quiz'] = array();
                $_SESSION['mod_quiz']['stepsTraversed']['step_1'] = true;

                // Set starting time
                $this->addToSession('quiz_start', time());

                // Generate questions and store question_ids in the session
                $this->generateQuestions();

                $this->Template->quizTeaser = $this->quizTeaser;
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

                // Add quiz questions to template
                $this->addQuizQuestionsToTemplate();

                break;
            // Show Results
            case '3':
                if (\Input::post('FORM_SUBMIT') == '' || !isset($_SESSION['mod_quiz']) || isset($_SESSION['mod_quiz']['stepsTraversed']['step_3']))
                {
                    $this->redirectToStep(1);
                }
                $_SESSION['mod_quiz']['stepsTraversed']['step_3'] = true;

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

                if (\Input::post('FORM_SUBMIT') == '' || !isset($_SESSION['mod_quiz']) || isset($_SESSION['mod_quiz']['stepsTraversed']['step_4']))
                {
                    $this->redirectToStep(1);
                }
                $_SESSION['mod_quiz']['stepsTraversed']['step_4'] = true;


                // Redirect to the register page if user has reached 100%
                if (isset($_POST['registerUser']) && $this->saveResults && isset($_SESSION['mod_quiz']['rating']))
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
    protected function addQuizQuestionsToTemplate()
    {

        $arrQuestions = $this->getFromSession('question_ids');
        if (!is_array($arrQuestions))
        {
            return;
        }

        if (count($arrQuestions) < 1)
        {
            return;
        }


        $oQuiz = \QuizQuestionModel::findMultipleByIds($arrQuestions);

        $arrQuizItems = array();
        $i = 0;
        // Create HTML-Code for the Questions and answers
        while ($oQuiz->next())
        {

            $objQuizItem = \QuizQuestionModel::findByPk($oQuiz->id);
            if ($objQuizItem === null)
            {
                continue;
            }

            // Get correct input type (radio or checkbox)
            $inputType = $this->getQuizInputType($objQuizItem);

            $tmpAnswerCode = '';
            $tmpAnswers = deserialize($objQuizItem->answers);

            // Sort answers by random

            if ($objQuizItem->answersSort || $this->answersSort)
            {
                $tmpAnswers = $this->shuffleAssoc($tmpAnswers);
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
                    $tmpAnswerCode .= '<div id="answer_' . $objQuizItem->id . '_' . $key . '" class="answer">';
                    if ($tmpAnswerPic != '')
                    {
                        $tmpAnswerCode .= $tmpAnswerPic;
                    }
                    $tmpAnswerCode .= '<input class="check_answer ' . $inputType . '" type="' . $inputType . '" id="check_answer_' . $objQuizItem->id . '_' . $key . '" name="check_answer_' . $objQuizItem->id . '[]" value="' . $key . '">';
                    $tmpAnswerCode .= '<label for="check_answer_' . $objQuizItem->id . '_' . $key . '">' . $answer['answer'] . '</label></div>';
                    $tmpAnswerCode .= '<button type="button" aria-pressed="false" id="button_answer_' . $objQuizItem->id . '_' . $key . '" class="btn btn-info btn-lg button-answer" data-radio-id="check_answer_' . $objQuizItem->id . '_' . $key . '" data-input-type="' . $inputType . '">' . $answer['answer'] . '</button>';


                }
                $tmpAnswerCode .= '<input type="hidden" id="array_answer_' . $objQuizItem->id . '" name="array_answer_' . $objQuizItem->id . '" value="' . implode(',', array_map('intval', $tmpAnswerKeys)) . '">';

                // Prevent hacking attempts
                $_SESSION['mod_quiz']['array_answer_' . $objQuizItem->id] = implode(',', array_map('intval', $tmpAnswerKeys));
            }

            // Clean RTE output
            if ($objPage->outputFormat == 'xhtml')
            {
                $objQuizItem->answers = \StringUtil::toXhtml($tmpAnswerCode);
                $objQuizItem->parentTeaser = \StringUtil::toXhtml($objQuizItem->getRelated('pid')->teaser);
            }
            else
            {
                $objQuizItem->answers = \StringUtil::toHtml5($tmpAnswerCode);
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
     * @param $objQuiz
     * @param $categories
     */
    protected function addQuizResultsToTemplate()
    {
        $arrQuestions = $this->getFromSession('question_ids');
        if (!is_array($arrQuestions))
        {
            return;
        }

        if (count($arrQuestions) < 1)
        {
            return;
        }

        $arrQuiz = array_fill_keys($this->quizCategories, array());

        $tmpUserRatings = 0;
        $arrQuizItems = array();
        $i = 0;

        // Get ratings and create HTML-Code for the questions, answers (users, correct ones) and comment line
        $oQuiz = \QuizQuestionModel::findMultipleByIds($arrQuestions);
        while ($oQuiz->next())
        {
            $objQuizItem = \QuizQuestionModel::findByPk($oQuiz->id);
            if ($objQuizItem === null)
            {
                continue;
            }

            $this->removeFromSession('array_answer_' . $objQuizItem->id);

            // If user has not checked any answer in this question
            $formData = $this->getFromSession('form_data');
            if (!isset($formData['check_answer_' . $objQuizItem->id]))
            {
                $formData['check_answer_' . $objQuizItem->id] = array();
            }


            // Get correct input type (radio or checkbox)
            $inputType = $this->getQuizInputType($objQuizItem);

            // Count the quiz ratings
            if (!$objQuizItem->rating)
            {
                $objQuizItem->rating = 1;
            }

            $tmpMaxRatings += $objQuizItem->rating;

            $tmpAnswerCode = '';
            $tmpAnswers = deserialize($objQuizItem->answers);
            if ($tmpAnswers)
            {
                $answerIsCorrect = true;
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
                            $tmpAnswerPic = sprintf('<figure class="image_container">{{image::%s?width=100&height=100&rel=lightbox&alt=%s}}</figure>', $objModel->path, $answer['answer']);
                        }
                    }

                    $tmpLabelClass = "";

                    // Validate answers
                    if ((!$answer['answerTrue'] && in_array($key, $formData['check_answer_' . $objQuizItem->id])) || ($answer['answerTrue'] && !in_array($key, $formData['check_answer_' . $objQuizItem->id])))
                    {
                        $answerIsCorrect = false;
                        $tmpLabelClass = 'incorrect';
                        $tmpAnswerCode .= '<div id="answer_' . $objQuizItem->id . '_' . $key . '" class="incorrect-answer answer">';
                    }
                    else
                    {
                        if ($answer['answerTrue'])
                        {
                            $tmpLabelClass = 'correct';
                        }
                        $tmpAnswerCode .= '<div id="answer_' . $objQuizItem->id . '_' . $key . '" class="correct-answer answer">';

                    }

                    // Add picture to source code
                    if ($tmpAnswerPic != '')
                    {
                        $tmpAnswerCode .= $tmpAnswerPic;
                    }


                    $checked = in_array($key, $formData['check_answer_' . $objQuizItem->id]) ? ' checked' : '';
                    $tmpAnswerCode .= '<span class="control-box"></span>';
                    $tmpAnswerCode .= '<span class="users-choice' . $checked . '"></span>';
                    $tmpAnswerCode .= sprintf('<label class="%s" for="check_answer_%s_%s">%s</label>', $tmpLabelClass, $objQuizItem->id, $key, $answer['answer']);
                    $tmpAnswerCode .= '</div>';
                }

                if (!$answerIsCorrect)
                {
                    $tmpAnswerCode .= '<div class="resultcomment incorrect">' . $GLOBALS['TL_LANG']['MSC']['incorrect_answer'] . '</div>';

                    // Create linklist with answer pages and categories with wrong answers
                    $tmpLinklist[] = $objQuizItem->answerlink;
                    $tmpErrorCat[] = $objQuizItem->getRelated('pid')->title;
                }
                else
                {
                    $tmpAnswerCode .= '<div class="resultcomment correct">' . $GLOBALS['TL_LANG']['MSC']['correct_answer'] . '</div>';

                    // Count the category ratings
                    $tmpUserRatings += $objQuizItem->rating;
                }
            }

            // Clean RTE output
            if ($objPage->outputFormat == 'xhtml')
            {
                $objQuizItem->answers = \StringUtil::toXhtml($tmpAnswerCode);
                $objQuizItem->parentTeaser = \StringUtil::toXhtml($objQuizItem->getRelated('pid')->teaser);
            }
            else
            {
                $objQuizItem->answers = \StringUtil::toHtml5($tmpAnswerCode);
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
        $this->Template->quiz = $arrQuiz;
    }

    /**
     *
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
        while ($objQuiz->next())
        {
            $arrQuestionIds[] = $objQuiz->id;
        }

        $this->addToSession('question_ids', $arrQuestionIds);
        $this->addToSession('obj_quiz', serialize($objQuiz));

    }

    /**
     * @param $k
     * @param $v
     */
    protected function addToSession($k, $v)
    {
        if (!isset($_SESSION['mod_quiz']))
        {
            $this->throwErrorMessage('sessionExpired');
            exit;
        }
        $_SESSION['mod_quiz'][$k] = $v;
    }

    /**
     * @param $k
     * @return null
     */
    protected function getFromSession($k)
    {
        if (!isset($_SESSION['mod_quiz']))
        {
            $this->throwErrorMessage('sessionExpired');
            exit;
        }
        if (!isset($_SESSION['mod_quiz'][$k]) || empty($_SESSION['mod_quiz'][$k]))
        {
            return null;
        }
        return $_SESSION['mod_quiz'][$k];

    }

    /**
     * @param $k
     */
    protected function removeFromSession($k)
    {
        if (!isset($_SESSION['mod_quiz']))
        {
            $this->throwErrorMessage('sessionExpired');
            exit;
        }
        if (isset($_SESSION['mod_quiz'][$k]))
        {
            unset($_SESSION['mod_quiz'][$k]);
        }
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
     * Shuffle the answers by shuffling the keys
     *
     * @param array $array An array of answers
     *
     * @return Shuffled array of answers with same keys
     */
    public static function shuffleAssoc($array)
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
        $objEmail->sendTo($objResult->user_email);
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
            if ($answer['answerTrue'] > 0)
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
    protected function addFormActionToTemplate($step = null)
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
    protected function redirectToStep($step)
    {
        $strUrl = \Haste\Util\Url::removeQueryString(array('step'));
        $strRedirect = \Haste\Util\Url::addQueryString(sprintf('step=%s', $step), $strUrl);
        $this->redirect($strRedirect);
    }
}