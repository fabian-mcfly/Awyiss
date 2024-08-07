<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;


/**
 * Content Entity
 *
 * @property int $id
 * @property int|null $pageId
 * @property int|null $contentAreaId
 * @property int|null $contentTemplateId
 * @property int|null $parentId
 * @property string|null $title
 * @property string|null $titleTag
 * @property string|null $subtitle
 * @property string|null $subtitleTag
 * @property string|null $text
 * @property string|null $link
 * @property string $columnWidth
 * @property string $columnIndent
 * @property bool $columnLast
 * @property bool $columnRtl
 * @property string|null $cssClass
 * @property int|null $duplicateOf
 * @property string|null $data
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Page $page
 * @property \Awyiss\Model\Entity\ContentArea $contentArea
 * @property \Awyiss\Model\Entity\ContentTemplate $contentTemplate
 * @property \Awyiss\Model\Entity\Content[] $duplicatingContents
 * @property \Awyiss\Model\Entity\Content $duplicateOfContent
 * @property \Awyiss\Model\Entity\Content $parentContent
 * @property \Awyiss\Model\Entity\Content[] $childContents
 * @property array{width: \Awyiss\Utility\Content\ColumnInterface, indent: ?\Awyiss\Utility\Content\ColumnInterface} $column
 * @property array|null $parentContents
 * @property float|null $realColumnWidth
 */
class Content extends Entity {
	/**
	 * @var array $contentTemplates All content templates
	 */
	protected static array $contentTemplates;
	/**
	 * @var array The column widths
	 */
	protected static array $columnWidths;
	/**
	 * @inheritdoc
	 */
	protected static array $fieldMap = [
		'page_id' => 'pageId',
		'parent_id' => 'parentId',
		'content_area_id' => 'contentAreaId',
		'content_template_id' => 'contentTemplateId',
		'title_tag' => 'titleTag',
		'subtitle_tag' => 'subtitleTag',
		'css_class' => 'cssClass',
		'column_width' => 'columnWidth',
		'column_indent' => 'columnIndent',
		'column_last' => 'columnLast',
		'column_rtl' => 'columnRtl',
		'duplicate_of' => 'duplicateOf',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'content_area' => 'contentArea',
		'content_template' => 'contentTemplate',
		'duplicating_contents' => 'duplicatingContents',
		'duplicate_of_content' => 'duplicateOfContent',
	];
	/**
	 * @var array The column indents
	 */
	protected static array $columnIndents;


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'pageId' => true,
		'parentId' => true,
		'title' => true,
		'titleTag' => true,
		'subtitle' => true,
		'subtitleTag' => true,
		'text' => true,
		'link' => true,
		'contentAreaId' => true,
		'contentTemplateId' => true,
		'columnWidth' => true,
		'columnIndent' => true,
		'columnLast' => true,
		'columnRtl' => true,
		'cssClass' => true,
		'duplicateOf' => true,
		'data' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritdoc
	 */
	protected array $_virtual = ['column', 'label'];


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Contents');


		return $lo_table->getChildren($this, $options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Contents');


		return $lo_table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent content of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Contents');


		return $lo_table->getParent($this, $options);
	}


	/**
	 * Get all the parent content and all of its parents contents of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Contents');


		return $lo_table->getParents($this, $options, $currentLevel);
	}


	/**
	 * @param bool $includeHtml
	 * @return string
	 * @noinspection DuplicatedCode
	 */
	public function getForcedTitle(bool $includeHtml = true): string {
		$la_fields = ['duplicateOf', 'title', 'subtitle', 'text', 'subtitle', 'text', 'mediaAssignments', 'cssClass', 'contentTemplateId'];

		$ls_title = 'Content';

		foreach ($la_fields as $ls_column) {
			if (
				empty($this->$ls_column) ||
				(!in_array($ls_column, ['duplicateOf', 'mediaAssignments', 'cssClass', 'contentTemplateId']) && strlen(trim(strip_tags(str_replace('&nbsp;', '', (string)$this->$ls_column)))) === 0)
			) {
				continue;
			}

			$ls_title = $this->$ls_column;

			if ($ls_column === 'mediaAssignments') {
				if ($this->mediaAssignments) {
					$ls_title = $this->getFirstMediaElementTitle();

					break;
				}

				continue;
			}

			if ($ls_column === 'duplicateOf') {
				$lo_content = $this->loadDuplicatedContent();

				if ($lo_content) {
					$ls_title = __d('contents', 'duplicate_of') . ': ' . $lo_content->label . ' (ID: ' . $lo_content->id . ')';
					break;
				}
			}

			if ($ls_column === 'contentTemplateId') {
				$lo_template = $this->contentTemplate ?? $this->loadContentTemplate();

				if ($lo_template) {
					$ls_title = 'Template: ' . ($includeHtml ? '<em>' . $lo_template->label . '</em>' : $lo_template->label);
					break;
				}
			}

			if ($ls_column === 'cssClass' && $includeHtml) {
				$ls_title = '<em>' . $ls_title . '</em>';
				break;
			}

			$ls_title = $this->cleanTitle((string)$ls_title);

			if (!empty($ls_title)) {
				if ($ls_column === 'title' && $this->titleTag) {
					$ls_title = '(' . $this->titleTag . ') ' . $ls_title;
				}
				elseif ($ls_column === 'subtitle' && $this->subtitleTag) {
					$ls_title = '(' . $this->subtitleTag . ') ' . $ls_title;
				}

				break;
			}
		}

		$ls_inactive = '';
		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$ls_inactive = __d('contents', 'inactive') . ' ';
		}

		return $ls_inactive . $ls_title;
	}


	/**
	 * @return array<string, ?\Awyiss\Utility\Content\ColumnInterface>
	 */
	protected function _getColumn(): array {
		if (!isset(static::$columnWidths)) {
			/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
			$lo_table = FactoryLocator::get('Table')->get('Contents');
			static::$columnWidths = $lo_table->getColumnWidths();
			static::$columnIndents = $lo_table->getColumnIndents();
		}

		return [
			'width' => static::$columnWidths[ $this->columnWidth ] ?? reset(static::$columnWidths),
			'indent' => static::$columnIndents[ $this->columnIndent ] ?? null,
		];
	}



	/**
	 * Creates and returns a specific text, used for list items and so on
	 * It uses the first of following db colums identifier, filename, title if present and
	 * prepends a translatable text in case the entity is inactive (active = 0)
	 * The label can be translated as well
	 *
	 * @noinspection PhpUnused
	 */
	protected function _getLabel(): string {
		return $this->getForcedTitle(false);
	}


	/**
	 * @param bool|null $last
	 * @return bool
	 */
	protected function _setColumnLast(?bool $last = null): bool {
		return (bool)$last;
	}


	/**
	 * @param bool|null $rtl
	 * @return bool
	 */
	protected function _setColumnRtl(?bool $rtl = null): bool {
		return (bool)$rtl;
	}


	/**
	 * Make sure no empty array finds its way into the db
	 *
	 * @param array|null $data
	 * @return array|null
	 * @noinspection PhpUnused
	 */
	protected function _setData(?array $data): ?array {
		if (empty($data)) {
			return null;
		}


		return $data;
	}


	/**
	 * @param string $title
	 * @return string
	 * @noinspection DuplicatedCode
	 */
	protected function cleanTitle(string $title): string {
		$ls_title = $title;

		// Multiline titles should only show the first line
		if (str_contains($ls_title, PHP_EOL)) {
			$ls_title = substr($ls_title, 0, strpos($ls_title, PHP_EOL));
		}

		// If there is a <module> tag in the title, replace it with the module identifier (data-identifier attribute)
		if (str_contains($ls_title, '<module')) {
			$ls_title = preg_replace('/<module[^>]*data-identifier="([^"]*)"[^>]*>.*?<\/module>/', 'Module: <em>$1</em>', $ls_title);
		}

		$ls_title = trim(strip_tags(html_entity_decode(str_replace(['&nbsp;', '<br>'], ' ', (string)$ls_title))));
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
	 * @return \Awyiss\Model\Entity\Content|null
	 */
	protected function loadDuplicatedContent(): ?Content {
		$lo_content = $this->duplicateOfContent;

		if (!$lo_content) {
			$lo_table = FactoryLocator::get('Table')->get('Contents');
			$lo_table->loadInto($this, ['DuplicateOfContents']);
			/** @noinspection PhpConditionAlreadyCheckedInspection */
			$lo_content = $this->duplicateOfContent;
		}

		return $lo_content;
	}


	/**
	 * @return string|null
	 */
	protected function getFirstMediaElementTitle(): ?string {
		// Get the first media element
		$la_medias = current($this->mediaAssignments);
		// Get the first assigned media
		$lx_media = current($la_medias);

		// If the media is an array, get the first element
		if (is_array($lx_media)) {
			$lo_media = current($lx_media);
		}
		else {
			$lo_media = $lx_media;
		}

		/** @var \Awyiss\Model\Entity\Media $lo_media */
		return $lo_media->name;
	}
}
