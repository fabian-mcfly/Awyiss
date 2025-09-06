<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\Survey;
use Awyiss\Model\Entity\WidgetTemplate;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;


/**
 * Trait ForcedTitleTrait
 * This trait provides functionality to generate a forced title for entities such as Content and Widget
 * so that a title is always available, even if the title field is empty.
 */
trait ForcedTitleTrait {
	/**
	 * @var array $contentTemplates All content templates
	 */
	protected static array $contentTemplates;
	/**
	 * @var array $widgetTemplates All widget templates
	 */
	protected static array $widgetTemplates;


	/**
	 * @param bool $includeHtml
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $this
	 * @return string
	 * @noinspection DuplicatedCode
	 */
	public function getForcedTitle(bool $includeHtml = true): string {
		$la_fields = ['duplicateOf', 'title', 'subtitle', 'text', 'subtitle', 'text', 'mediaAssignments', 'formId', 'surveyId', 'cssClass'];

		if ($this->_registryAlias === 'Widgets') {
			$la_fields[] = 'widgetTemplateId';
			$ls_defaultTitle = 'Widget';
		}
		else {
			$la_fields[] = 'contentTemplateId';
			$ls_defaultTitle = 'Content';
		}

		foreach ($la_fields as $ls_column) {
			$ls_title = $this->processField($ls_column, $includeHtml);
			if ($ls_title !== null) {
				break;
			}
		}

		$ls_inactive = '';
		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$ls_inactive = __d($this->_registryAlias !== 'Widgets' ? 'contents' : 'widgets', 'inactive') . ' ';
		}

		return $ls_inactive . ($ls_title ?: $ls_defaultTitle);
	}


	/**
	 * @param string $column
	 * @param bool $includeHtml
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $this
	 * @return string|null
	 */
	protected function processField(string $column, bool $includeHtml): ?string {
		if ($this->fieldIsEmpty($column)) {
			return null;
		}

		return match ($column) {
			'mediaAssignments' => $this->processMediaAssignments(),
			'formId' => $this->processFormId($includeHtml),
			'surveyId' => $this->processSurveyId($includeHtml),
			'duplicateOf' => $this->processDuplicateOf(),
			'contentTemplateId' => $this->processContentTemplateId($includeHtml),
			'widgetTemplateId' => $this->processWidgetTemplateId($includeHtml),
			'cssClass' => $includeHtml ? '<em>' . $this->$column . '</em>' : $this->$column,
			default => $this->processDefaultField($column),
		};
	}


	/**
	 * @param string $column
	 * @return bool
	 */
	protected function fieldIsEmpty(string $column): bool {
		return empty($this->$column) || (
			!in_array($column, ['duplicateOf', 'mediaAssignments', 'cssClass', 'contentTemplateId', 'widgetTemplateId']) &&
			strlen(trim(strip_tags(str_replace('&nbsp;', '', (string)$this->$column)))) === 0
		);
	}


	/**
	 * @param bool $includeHtml
	 * @return string|null
	 */
	protected function processFormId(bool $includeHtml): ?string {
		$lo_form = $this->form ?? $this->getForm();

		return $lo_form ? __d('contents', 'form_id') . ': ' . ($includeHtml ? '<em>' . $lo_form->label . '</em>' : $lo_form->label) : null;
	}


	/**
	 * @param bool $includeHtml
	 * @return string|null
	 */
	protected function processSurveyId(bool $includeHtml): ?string {
		$lo_survey = $this->survey ?? $this->getSurvey();

		return $lo_survey ? __d('contents', 'survey_id') . ': ' . ($includeHtml ? '<em>' . $lo_survey->label . '</em>' : $lo_survey->label) : null;
	}


	/**
	 * @return string|null
	 */
	protected function processMediaAssignments(): ?string {
		return $this->mediaAssignments ? $this->getFirstMediaElementTitle() : null;
	}


	/**
	 * @return string|null
	 */
	protected function processDuplicateOf(): ?string {
		$lo_content = $this->loadDuplicatedContent();

		return $lo_content ? __d('contents', 'duplicate_of') . ': ' . $lo_content->label . ' (ID: ' . $lo_content->id . ')' : null;
	}


	/**
	 * @param bool $includeHtml
	 * @return string|null
	 */
	protected function processContentTemplateId(bool $includeHtml): ?string {
		$lo_template = $this->contentTemplate ?? $this->loadContentTemplate();

		return $lo_template ? 'Template: ' . ($includeHtml ? '<em>' . $lo_template->label . '</em>' : $lo_template->label) : null;
	}


	/**
	 * @param bool $includeHtml
	 * @return string|null
	 */
	protected function processWidgetTemplateId(bool $includeHtml): ?string {
		$lo_template = $this->widgetTemplate ?? $this->loadWidgetTemplate();

		return $lo_template ? 'Template: ' . ($includeHtml ? '<em>' . $lo_template->label . '</em>' : $lo_template->label) : null;
	}


	/**
	 * @param string $column
	 * @return string|null
	 */
	protected function processDefaultField(string $column): ?string {
		$ls_title = $this->cleanTitle((string)$this->$column);

		if (empty($ls_title)) {
			return null;
		}

		if ($column === 'title' && $this->titleTag) {
			$ls_title = '(' . $this->titleTag . ') ' . $ls_title;
		}
		elseif ($column === 'subtitle' && $this->subtitleTag) {
			$ls_title = '(' . $this->subtitleTag . ') ' . $ls_title;
		}


		return $ls_title;
	}


	/**
	 * @param string $title
	 * @return string
	 * @noinspection DuplicatedCode
	 */
	protected function cleanTitle(string $title): string {
		$ls_title = $title;

		// If there's a <awyiss-responsive-image> tag in the title
		if (str_contains($ls_title, '<awyiss-responsive-image')) {
			$ls_testTitle = trim(strip_tags(preg_replace('/<awyiss-responsive-image>.*?<\/awyiss-responsive-image>/', '', $ls_title)));
			if (empty($ls_testTitle)) {
				// If the title is empty after removing the <awyiss-responsive-image> tag, set the title to the image's alt attribute
				preg_match('/<awyiss-responsive-image>(.*?)<\/awyiss-responsive-image>/', $ls_title, $la_matches);
				$la_attributes = json_decode($la_matches[1], true) ?: [];
				$lo_media = $this->mediaAssignments['inlineImgTag'][ $la_attributes['mediaId'] ]?->media ?? null;
				$ls_title = $lo_media?->name ?? $la_matches[1];
			}
			else {
				$ls_title = preg_replace('/<awyiss-responsive-image>.*?<\/awyiss-responsive-image>/', '', $ls_title);
			}
		}

		// If there is a <module> tag in the title, replace it with the module identifier (data-identifier attribute)
		if (str_contains($ls_title, '<module')) {
			$ls_title = preg_replace('/<module[^>]*data-identifier="([^"]*)"[^>]*>.*?<\/module>/', 'Module: <em>$1</em>', $ls_title);
		}

		$ls_title = trim(strip_tags(html_entity_decode(str_replace(['&nbsp;', '<br>'], ' ', (string)$ls_title))));

		// Multiline titles should only show the first line
		if (str_contains($ls_title, PHP_EOL)) {
			$ls_title = substr($ls_title, 0, strpos($ls_title, PHP_EOL));
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$ls_title = mb_strlen($ls_title) > 100 ? mb_substr($ls_title, 0, 100) . '...' : $ls_title;

		return $ls_title;
	}


	/**
	 * @return \Awyiss\Model\Entity\ContentTemplate|null
	 */
	protected function loadContentTemplate(): ?ContentTemplate {
		if (!isset(static::$contentTemplates)) {
			$lo_table = FactoryLocator::get('Table')->get('ContentTemplates');
			static::$contentTemplates = $lo_table->find()->all()->indexBy('id')->toArray();
		}

		return $this->contentTemplate = static::$contentTemplates[ $this->contentTemplateId ] ?? null;
	}


	/**
	 * @return \Awyiss\Model\Entity\WidgetTemplate|null
	 */
	protected function loadWidgetTemplate(): ?WidgetTemplate {
		if (!isset(static::$widgetTemplates)) {
			$lo_table = FactoryLocator::get('Table')->get('WidgetTemplates');
			static::$widgetTemplates = $lo_table->find()->all()->indexBy('id')->toArray();
		}

		return $this->widgetTemplate = static::$widgetTemplates[ $this->widgetTemplateId ] ?? null;
	}


	/**
	 * @return \Awyiss\Model\Entity\Content|null
	 */
	protected function loadDuplicatedContent(): ?Content {
		$lo_entity = $this->duplicateOfContent;

		if (!$lo_entity) {
			$lo_table = FactoryLocator::get('Table')->get('Contents');
			$lo_table->loadInto($this, ['DuplicateOfContents']);
			/** @noinspection PhpConditionAlreadyCheckedInspection */
			$lo_entity = $this->duplicateOfContent;
		}

		return $lo_entity;
	}


	/**
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $this
	 * @return string|null
	 */
	protected function getFirstMediaElementTitle(): ?string {
		// Get the first media element
		$la_medias = current($this->mediaAssignments);
		// Get the first assigned media
		$la_medias = is_array($la_medias) ? $la_medias : $la_medias->toArray();
		$lx_media = current($la_medias);

		// If the media is an array, get the first element
		if (is_array($lx_media)) {
			$lo_media = current($lx_media);
		}
		else {
			$lo_media = $lx_media;
		}

		/** @var \Awyiss\Model\Entity\Media $lo_media */
		return $lo_media instanceof Media ? $lo_media->name : json_encode($lo_media);
	}


	/**
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $this
	 * @return \Awyiss\Model\Entity\Form|null
	 */
	protected function getForm(): ?Form {
		if (!$this->formId) {
			return null;
		}

		try {
			/** @var \Awyiss\Model\Entity\Form $lo_form */
			$lo_form = FactoryLocator::get('Table')->get('Forms')->get($this->formId);
		}
		catch (RecordNotFoundException) {
			return null;
		}

		return $lo_form;
	}


	/**
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\Widget $this
	 * @return \Awyiss\Model\Entity\Survey|null
	 */
	protected function getSurvey(): ?Survey {
		if (!$this->surveyId) {
			return null;
		}

		try {
			/** @var \Awyiss\Model\Entity\Survey $lo_survey */
			$lo_survey = FactoryLocator::get('Table')->get('Surveys')->get($this->surveyId);
		}
		catch (RecordNotFoundException) {
			return null;
		}

		return $lo_survey;
	}
}
