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
 * Class ModuleQuizEventDashboard
 *
 * @copyright  Marko Cupic 2017
 * @author     Marko Cupic <m.cupic@gmx.ch>
 * @package    Contao Quiz
 */
class ModuleQuizEventDashboard extends \Module
{


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_quiz_event_dashboard';

    /**
     * Event model
     * @var
     */
    protected $objEvent;

    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['quizEventDashboard']) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && \Config::get('useAutoItem') && isset($_GET['auto_item']))
        {
            \Input::setGet('items', \Input::get('auto_item'));
        }

        if (\Input::get('items') == '')
        {
            return '';
        }
        $this->objEvent = \CalendarEventsModel::findByIdOrAlias(\Input::get('items'));
        if ($this->objEvent === null)
        {
            return '';
        }

        // CSV export/download
        if (\Input::get('downloadCsv') == 'true')
        {
            $this->csvExport();
        }


        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {


        // Form handling
        $this->handleForm();

        // Add quiz select buttons to template
        $arrPages = deserialize($this->quizPages, true);
        $arrQuizPages = array();
        foreach ($arrPages as $pageUuid)
        {
            $objQuizPage = \PageModel::findPublishedById($pageUuid);
            if ($objQuizPage !== null)
            {
                $url = \Controller::generateFrontendUrl($objQuizPage->row(), '?eventToken=' . $this->objEvent->eventToken, $objPage->language);
                $arrQuizPages[] = array(
                    'id' => $objQuizPage->id,
                    'title' => $objQuizPage->title,
                    'href' => $url
                );

            }
        }
        $this->Template->arrQuizPages = $arrQuizPages;

        // Add download link for quiz participant csv
        $this->Template->csvDownloadHref = \Haste\Util\Url::addQueryString('downloadCsv=true');


    }

    /**
     * @throws \Exception
     */
    protected function handleForm()
    {
        $objForm = new \Haste\Form\Form('tl_calendar_event_quiz_dashboard', 'POST', function ($objHaste)
        {
            return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });
        $objForm->preserveGetParameters();

        // you can exclude or modify certain fields by passing a callable as second
        // parameter
        $objForm->addFieldsFromDca('tl_calendar_events', function (&$strField, &$arrDca)
        {
            // make sure to skip elements without inputType or you will get an exception
            if (!isset($arrDca['inputType']))
            {
                return false;
            }

            if ($strField != 'refPromoArticle' && $strField != 'organizerEmail')
            {
                return false;
            }

            $objEventsModel = \CalendarEventsModel::findByIdOrAlias(\Input::get('items'));
            if ($objEventsModel === null)
            {
                throw new \Exception("EventModel is null. Maybe there is no valid event-alias in the url query string.");
            }

            // add anything you like
            if ($strField == 'organizerEmail')
            {
                $arrDca['default'] = $objEventsModel->organizerEmail;
            }
            // add anything you like
            if ($strField == 'refPromoArticle')
            {
                $arrDca['default'] = $objEventsModel->refPromoArticle;
            }

            // you must return true otherwise the field will be skipped
            return true;
        });

        // Add submit button
        $objForm->addSubmitFormField('submit', 'Daten aktualisieren');

        if ($objForm->validate())
        {
            $objEventsModel = \CalendarEventsModel::findByIdOrAlias(\Input::get('items'));
            if ($objEventsModel !== null)
            {
                $objEventsModel->organizerEmail = \Input::post('organizerEmail');
                $objEventsModel->refPromoArticle = \Input::post('refPromoArticle');
                $objEventsModel->tstamp = time();
                $objEventsModel->save();
            }
            else
            {
                throw new \Exception("Form values could not be saved to the database. Maybe there is no valid event-alias in the url query.");
            }

        }

        $this->Template->form = $objForm->generate();
    }

    /**
     *
     */
    protected function csvExport()
    {

        if ($this->objEvent !== null)
        {
            $filter = array();
            $filter[] = array('refEventId=?', $this->objEvent->id);

            $arrOptions = array(
                'arrFilter' => $filter
            );
            \Markocupic\ExportTable\ExportTable::exportTable('tl_quiz_results', $arrOptions);
            exit();
        }

    }


}