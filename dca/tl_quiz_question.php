<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */
 
/**
 * Load tl_content language file
 */
System::loadLanguageFile('tl_content');
 
/**
 * Table tl_quiz_question
 */
$GLOBALS['TL_DCA']['tl_quiz_question'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_quiz_category',
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_quiz_question', 'checkPermission')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'filter;sort,search,limit',
			'headerFields'            => array('title', 'headline'),
			'child_record_callback'   => array('tl_quiz_question', 'listQuestions')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_quiz_question']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_quiz_question']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.gif'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_quiz_question']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_quiz_question']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_quiz_question']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('tl_quiz_question', 'toggleIcon')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_quiz_question']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__' 				  => array('addImage'),
		'default'                     => '{title_legend},question,author;{settings_legend},rating,answerlink;{image_legend},addImage;{answers_legend},answers,answersSort;{publish_legend},published'
	),
	
	// Subpalettes
	'subpalettes' => array
	(
		'addImage'                    => 'singleSRC,alt,size,imagemargin,imageUrl,fullsize,caption,floating'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_quiz_category.title',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'sorting' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['sorting'],
			'sorting'                 => true,
			'flag'                    => 2,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'question' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_quiz_question']['question'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
        'rating' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_quiz_question']['rating'],
            'exclude'                 => true,
            'search'                  => true,
            'sorting'                 => true,
            'default'                 => 1,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'rgxp'=> 'natural', 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default '1'"
        ),
		'author' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_quiz_question']['author'],
			'default'                 => BackendUser::getInstance()->id,
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('doNotCopy'=>true, 'chosen'=>true, 'mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'belongsTo', 'load'=>'eager')
		),
		'addImage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_quiz_question']['addImage'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'extensions'=>$GLOBALS['TL_CONFIG']['validImageTypes'], 'fieldType'=>'radio', 'mandatory'=>true),
			'sql'                     => "binary(16) NULL"
		),
		'alt' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['size'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'options'                 => $GLOBALS['TL_CROP'],
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'imagemargin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imagemargin'],
			'exclude'                 => true,
			'inputType'               => 'trbl',
			'options'                 => array('px', '%', 'em', 'rem', 'ex', 'pt', 'pc', 'in', 'cm', 'mm'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'imageUrl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50 wizard'),
			'wizard' => array
			(
				array('tl_quiz_question', 'pagePicker')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'fullsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'caption' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'floating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['floating'],
			'default'                 => 'above',
			'exclude'                 => true,
			'inputType'               => 'radioTable',
			'options'                 => array('above', 'left', 'right', 'below'),
			'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(12) NOT NULL default ''"
		),

		'answerlink' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_quiz_question']['answerlink'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'answers' => array
		(
		  	'label'     => &$GLOBALS['TL_LANG']['tl_quiz_question']['answers'],
		    'exclude'   => true,
			'search'    => true,
			'inputType' => 'multiColumnWizard',
		    'save_callback' => array(array('tl_quiz_question','saveCallbackAnswer')),
		    'eval'      => array
				(
		        'style'=>'width:100%;',
		        'columnFields' => array
		          (
		            'answer' => array
		            (
		              'label' 		=> &$GLOBALS['TL_LANG']['tl_quiz_question']['answer'],
					  'exclude'   	=> true,
					  'inputType'   => 'text',
		              'eval'        => array('mandatory'=>true,'style'=>'width:430px;')
		            ),
					'singleSRC' => array
					(
						'label'     => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
						'exclude'   => true,
						'inputType' => 'fileTree',
						'eval'      => array('filesOnly'=>true, 'extensions'=>$GLOBALS['TL_CONFIG']['validImageTypes'], 'fieldType'=>'radio'),
						'sql'       => "binary(16) NULL"
					),
		            'answerTrue' => array
		            (
		              'label' 		=> &$GLOBALS['TL_LANG']['tl_quiz_question']['answerTrue'],
					  'exclude'   	=> true,
					  'inputType'   => 'checkbox',
					  'eval'        => array('style'=>'margin-left:7px;')
		            )
		       	  )
		   		),
			'sql'	 => "blob NULL"
		),
		'answersSort' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_quiz_question']['answersSort'],
			'exclude'               => true,
            'inputType'             => 'checkbox',
			'eval'                  => array('tl_class'=>'w50'),
            'sql'                   => "char(1) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_quiz_question']['published'],
			'exclude'               => true,
			'filter'                => true,
			'flag'                  => 2,
			'inputType'             => 'checkbox',
			'eval'                  => array('doNotCopy'=>true),
			'sql'                   => "char(1) NOT NULL default ''"
		)
	)
);

/**
 * Class tl_quiz_category
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  fiveBytes 2014
 * @author     Stefen Baetge <fivebytes.de>
 * @package    Quiz
 */
class tl_quiz_question extends \Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}


	/**
	 * Check permissions to edit table tl_quiz_question
	 */
	public function checkPermission()
	{
	}

	public function saveCallbackAnswer($value)
    {
        $arrAnswers = deserialize($value,true);
        $count=0;
        $trueAnswers=0;
        foreach($arrAnswers as $arrAnswer)
        {
            $count++;
            if($arrAnswer['answerTrue'] == '1')
            {
                $trueAnswers++;
            }
        }
        if($count > 0 && $trueAnswers < 1)
        {
            throw new Exception('At least 1 answer has to be marked as "true"!');
        }
        if($count > 0 && $trueAnswers > 1)
        {
            throw new Exception('Only 1 answer can be marked as "true"!');
        }
        return $value;
    }

	/**
	 * Add the type of input field
	 * @param array
	 * @return string
	 */
	public function listQuestions($arrRow)
	{
		$key = $arrRow['published'] ? 'published' : 'unpublished';
		$date = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $arrRow['tstamp']);
		
		$tmpAnswerCode = '';
		$tmpAnswers = deserialize($arrRow['answers']);
		if ( $tmpAnswers )
		{
			foreach($tmpAnswers as $key=>$answer)
			{
				$tmpAnswerCode .= $answer['answer'] . ' (' . (($answer['answerTrue']) ? $answer['answerTrue'] : '0') . ')<br />';
			}
		}
		
		$tmpAnswerSiteTitle = "-";
		$objAnswerSite = \PageModel::findByPk($arrRow['answerlink']);
		if ($objAnswerSite !== null)
		{
			$tmpAnswerSite = $objAnswerSite->row();
			$tmpAnswerSiteTitle = $tmpAnswerSite['title'];
		}

		return '
<div class="cte_type ' . $key . '"><strong>' . $arrRow['question'] . '</strong> - ' . $date . '</div>
<div class="limit_height' . (!$GLOBALS['TL_CONFIG']['doNotCollapse'] ? ' h52' : '') . '">
<p>' . $GLOBALS['TL_LANG']['tl_quiz_question']['rating'][0] . ': '.$arrRow['rating'] . ' 
| ' . $GLOBALS['TL_LANG']['tl_quiz_question']['answerlink'][0] . ': ' . $tmpAnswerSiteTitle . '</p>' . $tmpAnswerCode.'
</div>' . "\n";
	}


	/**
	 * Return the link picker wizard
	 * @param \DataContainer
	 * @return string
	 */
	public function pagePicker(DataContainer $dc)
	{
		return ' <a href="contao/page.php?do='.Input::get('do').'&amp;table='.$dc->table.'&amp;field='.$dc->field.'&amp;value='.str_replace(array('{{link_url::', '}}'), '', $dc->value).'" onclick="Backend.getScrollOffset();Backend.openModalSelector({\'width\':765,\'title\':\''.specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MOD']['page'][0])).'\',\'url\':this.href,\'id\':\''.$dc->field.'\',\'tag\':\'ctrl_'.$dc->field . ((Input::get('act') == 'editAll') ? '_' . $dc->id : '').'\',\'self\':this});return false">' . Image::getHtml('pickpage.gif', $GLOBALS['TL_LANG']['MSC']['pagepicker'], 'style="vertical-align:top;cursor:pointer"') . '</a>';
	}


	/**
	 * Return the "toggle visibility" button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if (strlen(Input::get('tid')))
		{
			$this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1));
			$this->redirect($this->getReferer());
		}

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_quiz_question::published', 'alexf'))
		{
			return '';
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

		if (!$row['published'])
		{
			$icon = 'invisible.gif';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
	}


	/**
	 * Disable/enable a user group
	 * @param integer
	 * @param boolean
	 */
	public function toggleVisibility($intId, $blnVisible)
	{
		// Check permissions to publish
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_quiz_question::published', 'alexf'))
		{
			$this->log('Not enough permissions to publish/unpublish Quiz-Question ID "'.$intId.'"', __METHOD__, TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		$objVersions = new Versions('tl_quiz_question', $intId);
		$objVersions->initialize();

		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_quiz_question']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_quiz_question']['fields']['published']['save_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
				}
				elseif (is_callable($callback))
				{
					$blnVisible = $callback($blnVisible, $this);
				}
			}
		}

		// Update the database
		$this->Database->prepare("UPDATE tl_quiz_question SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
					   ->execute($intId);

		$objVersions->create();
		$this->log('A new version of record "tl_quiz_question.id='.$intId.'" has been created'.$this->getParentEntries('tl_quiz_question', $intId), __METHOD__, TL_GENERAL);
	}
}
