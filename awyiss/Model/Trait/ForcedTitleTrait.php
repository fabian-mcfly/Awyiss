<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\GlobalContentTemplate;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaAssignment;
use Awyiss\Model\Entity\Survey;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;


/**
 * Trait ForcedTitleTrait
 * This trait provides functionality to generate a forced title for entities such as Content and Global Content
 * so that a title is always available, even if the title field is empty.
 */
trait ForcedTitleTrait {
	/**
	 * @var array $contentTemplates All content templates
	 */
	protected static array $contentTemplates;
	/**
	 * @var array $globalContentTemplates All global content templates
	 */
	protected static array $globalContentTemplates;


	/**
	 * @param bool $includeHtml
	 * @return string
	 * @noinspection DuplicatedCode
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\GlobalContent $this
	 */
	public function getForcedTitle(bool $includeHtml = true): string {
		$fields = ['duplicateOf', 'title', 'subtitle', 'text', 'subtitle', 'text', 'mediaAssignments', 'formId', 'surveyId', 'cssClass'];

		if ($this->_registryAlias === 'GlobalContents') {
			$fields[] = 'globalContentTemplateId';
			$defaultTitle = 'GlobalContent';
		}
		elseif ($this->_registryAlias === 'FormElements') {
			$defaultTitle = 'FormElement';
		}
		else {
			$fields[] = 'contentTemplateId';
			$defaultTitle = 'Content';
		}

		foreach ($fields as $column) {
			$title = $this->processField($column, $includeHtml);
			if ($title !== null) {
				break;
			}
		}

		$inactive = '';
		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$inactive = __d($this->_registryAlias !== 'GlobalContents' ? 'contents' : 'global_contents', 'inactive') . ' ';
		}

		return $inactive . ($title ?: $defaultTitle);
	}


	/**
	 * @param string $column
	 * @param bool $includeHtml
	 * @return string|null
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\GlobalContent $this
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
			'globalContentTemplateId' => $this->processGlobalContentTemplateId($includeHtml),
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
			!in_array($column, ['duplicateOf', 'mediaAssignments', 'cssClass', 'contentTemplateId', 'globalContentTemplateId']) &&
			strlen(trim(strip_tags(str_replace('&nbsp;', '', (string)$this->$column)))) === 0
		);
	}


	/**
	 * @param bool $includeHtml
	 * @return string|null
	 */
	protected function processFormId(bool $includeHtml): ?string {
		$form = $this->form ?? $this->getForm();

		return $form ? __d('contents', 'form_id') . ': ' . ($includeHtml ? '<em>' . $form->label . '</em>' : $form->label) : null;
	}


	/**
	 * @param bool $includeHtml
	 * @return string|null
	 */
	protected function processSurveyId(bool $includeHtml): ?string {
		$survey = $this->survey ?? $this->getSurvey();

		return $survey ? __d('contents', 'survey_id') . ': ' . ($includeHtml ? '<em>' . $survey->label . '</em>' : $survey->label) : null;
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
		$content = $this->loadDuplicatedContent();

		return $content ? __d('contents', 'duplicate_of') . ': ' . $content->label . ' (ID: ' . $content->id . ')' : null;
	}


	/**
	 * @param bool $includeHtml
	 * @return string|null
	 */
	protected function processContentTemplateId(bool $includeHtml): ?string {
		$template = $this->contentTemplate ?? $this->loadContentTemplate();

		return $template ? 'Template: ' . ($includeHtml ? '<em>' . $template->label . '</em>' : $template->label) : null;
	}


	/**
	 * @param bool $includeHtml
	 * @return string|null
	 */
	protected function processGlobalContentTemplateId(bool $includeHtml): ?string {
		$template = $this->globalContentTemplate ?? $this->loadGlobalContentTemplate();

		return $template ? 'Template: ' . ($includeHtml ? '<em>' . $template->label . '</em>' : $template->label) : null;
	}


	/**
	 * @param string $column
	 * @return string|null
	 */
	protected function processDefaultField(string $column): ?string {
		$title = $this->cleanTitle((string)$this->$column);

		if (empty($title)) {
			return null;
		}

		if ($column === 'title' && $this->titleTag) {
			$title = '(' . $this->titleTag . ') ' . $title;
		}
		elseif ($column === 'subtitle' && $this->subtitleTag) {
			$title = '(' . $this->subtitleTag . ') ' . $title;
		}


		return $title;
	}


	/**
	 * @param string $title
	 * @return string
	 * @noinspection DuplicatedCode
	 */
	protected function cleanTitle(string $title): string {
		// If there's a <awyiss-responsive-image> tag in the title
		if (str_contains($title, '<awyiss-responsive-image')) {
			$testTitle = trim(strip_tags(preg_replace('/<awyiss-responsive-image>.*?<\/awyiss-responsive-image>/', '', $title)));
			if (empty($testTitle)) {
				// If the title is empty after removing the <awyiss-responsive-image> tag, set the title to the image's alt attribute
				preg_match('/<awyiss-responsive-image>(.*?)<\/awyiss-responsive-image>/', $title, $matches);
				$attributes = json_decode($matches[1], true) ?: [];
				$media = $this->mediaAssignments['inlineImgTag'][ $attributes['mediaId'] ]?->media ?? null;
				$title = $media?->name ?? $matches[1];
			}
			else {
				$title = preg_replace('/<awyiss-responsive-image>.*?<\/awyiss-responsive-image>/', '', $title);
			}
		}

		// If there is a <widget> tag in the title, replace it with the widget identifier (data-identifier attribute)
		if (str_contains($title, '<widget')) {
			$title = preg_replace('/<widget[^>]*data-identifier="([^"]*)"[^>]*>.*?<\/widget>/', 'Widget: <em>$1</em>', $title);
		}

		$title = trim(strip_tags(html_entity_decode(str_replace(['&nbsp;', '<br>'], ' ', (string)$title))));

		// Multiline titles should only show the first line
		if (str_contains($title, PHP_EOL)) {
			$title = substr($title, 0, strpos($title, PHP_EOL));
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$title = mb_strlen($title) > 100 ? mb_substr($title, 0, 100) . '...' : $title;

		return $title;
	}


	/**
	 * @return \Awyiss\Model\Entity\ContentTemplate|null
	 */
	protected function loadContentTemplate(): ?ContentTemplate {
		if (!isset(static::$contentTemplates)) {
			$table = FactoryLocator::get('Table')->get('ContentTemplates');
			static::$contentTemplates = $table->find()->all()->indexBy('id')->toArray();
		}

		return $this->contentTemplate = static::$contentTemplates[ $this->contentTemplateId ] ?? null;
	}


	/**
	 * @return \Awyiss\Model\Entity\GlobalContentTemplate|null
	 */
	protected function loadGlobalContentTemplate(): ?GlobalContentTemplate {
		if (!isset(static::$globalContentTemplates)) {
			$table = FactoryLocator::get('Table')->get('GlobalContentTemplates');
			static::$globalContentTemplates = $table->find()->all()->indexBy('id')->toArray();
		}

		return $this->globalContentTemplate = static::$globalContentTemplates[ $this->globalContentTemplateId ] ?? null;
	}


	/**
	 * @return \Awyiss\Model\Entity\Content|null
	 */
	protected function loadDuplicatedContent(): ?Content {
		$entity = $this->duplicateOfContent;

		if (!$entity) {
			$table = FactoryLocator::get('Table')->get('Contents');
			$table->loadInto($this, ['DuplicateOfContents']);
			/** @noinspection PhpConditionAlreadyCheckedInspection */
			$entity = $this->duplicateOfContent;
		}

		return $entity;
	}


	/**
	 * @return string|null
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\GlobalContent $this
	 */
	protected function getFirstMediaElementTitle(): ?string {
		// Get the first media element
		$medias = current($this->mediaAssignments);
		// Get the first assigned media
		$medias = is_array($medias) ? $medias : $medias->toArray();
		$media = current($medias);

		// If the media is an array, get the first element
		if (is_array($media)) {
			$media = current($media);
		}

		if ($media instanceof MediaAssignment) {
			$media = $media->media ?? [];
		}

		/** @var \Awyiss\Model\Entity\Media $media */
		return $media instanceof Media ? $media->name : json_encode($media);
	}


	/**
	 * @return \Awyiss\Model\Entity\Form|null
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\GlobalContent $this
	 */
	protected function getForm(): ?Form {
		if (!$this->formId) {
			return null;
		}

		try {
			/** @var \Awyiss\Model\Entity\Form $form */
			$form = FactoryLocator::get('Table')->get('Forms')->get($this->formId);
		}
		catch (RecordNotFoundException) {
			return null;
		}

		return $form;
	}


	/**
	 * @return \Awyiss\Model\Entity\Survey|null
	 * @var \Awyiss\Model\Entity\Content|\Awyiss\Model\Entity\GlobalContent $this
	 */
	protected function getSurvey(): ?Survey {
		if (!$this->surveyId) {
			return null;
		}

		try {
			/** @var \Awyiss\Model\Entity\Survey $survey */
			$survey = FactoryLocator::get('Table')->get('Surveys')->get($this->surveyId);
		}
		catch (RecordNotFoundException) {
			return null;
		}

		return $survey;
	}
}
