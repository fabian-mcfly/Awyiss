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
 * @property string|null $subtitle
 * @property string|null $text
 * @property string|null $link
 * @property float $columnWidth
 * @property float $columnIndent
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
 * @property array{width: \Awyiss\Utilities\Contents\ColumnInterface, indent: ?\Awyiss\Utilities\Contents\ColumnInterface} $column
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
		'subtitle' => true,
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
	protected static array $fieldMap = [
		'page_id' => 'pageId',
		'parent_id' => 'parentId',
		'content_area_id' => 'contentAreaId',
		'content_template_id' => 'contentTemplateId',
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
	 * @inheritdoc
	 */
	protected array $_virtual = ['column', 'label'];


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(array $aa_options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this, $aa_options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(array $aa_options = []): ?self {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this, $aa_options);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * @param bool $ab_includeHtml
	 * @return string
	 */
	public function getForcedTitle(bool $ab_includeHtml = true): string {
		$la_fields = ['duplicateOf', 'title', 'subtitle', 'text', 'subtitle', 'text', 'cssClass', 'contentTemplateId'];

		$ls_title = 'Content';

		foreach ($la_fields as $ls_column) {
			if (
				empty($this->$ls_column) ||
				(!in_array($ls_column, ['duplicateOf', 'cssClass', 'contentTemplateId']) && strlen(trim(strip_tags(str_replace('&nbsp;', '', (string)$this->$ls_column)))) === 0)
			) {
				continue;
			}

			$ls_title = $this->$ls_column;

			if ($ls_column === 'duplicateOf') {
				$lo_content = $this->duplicateOfContent;
				if (!$lo_content) {
					$lo_table = FactoryLocator::get('Table')->get($this->getSource());
					$lo_table->loadInto($this, ['DuplicateOfContents']);
					/** @noinspection PhpConditionAlreadyCheckedInspection */
					$lo_content = $this->duplicateOfContent;
				}

				if ($lo_content) {
					$ls_title = __('duplicate_of') . ': ' . $lo_content->label . ' (ID: ' . $lo_content->id . ')';
					break;
				}
			}

			if ($ls_column === 'contentTemplateId') {
				$lo_template = $this->contentTemplate;
				if (!$lo_template) {
					if (!isset(static::$contentTemplates)) {
						$lo_table = FactoryLocator::get('Table')->get('ContentTemplates');
						static::$contentTemplates = $lo_table->find()->all()->indexBy('id')->toArray();
					}

					$lo_template = $this->contentTemplate = static::$contentTemplates[ $this->contentTemplateId ] ?? null;
				}

				if ($lo_template) {
					$ls_title = 'Template: ' . ($ab_includeHtml ? '<em>' . $lo_template->label . '</em>' : $lo_template->label);
					break;
				}
			}

			if ($ls_column === 'cssClass' && $ab_includeHtml) {
				$ls_title = '<em>' . $ls_title . '</em>';
				break;
			}

			$ls_title = trim(strip_tags(str_replace('&nbsp;', '', (string)$ls_title)));
			$ls_title = mb_strlen($ls_title) > 100 ? mb_substr($ls_title, 0, 100) . '...' : $ls_title;

			if (!empty($ls_title)) {
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
	 * @return array<string, ?\Awyiss\Utilities\Contents\ColumnInterface>
	 */
	protected function _getColumn(): array {
		if (!isset(static::$columnWidths)) {
			/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
			$lo_table = FactoryLocator::get('Table')->get($this->getSource());
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
	 * @param bool|null $ab_last
	 * @return bool
	 */
	protected function _setColumnLast(?bool $ab_last = null): bool {
		return (bool)$ab_last;
	}


	/**
	 * @param bool|null $ab_rtl
	 * @return bool
	 */
	protected function _setColumnRtl(?bool $ab_rtl = null): bool {
		return (bool)$ab_rtl;
	}


	/**
	 * Make sure no empty array finds its way into the db
	 *
	 * @param array|null $aa_data
	 * @return array|null
	 * @noinspection PhpUnused
	 */
	protected function _setData(?array $aa_data): ?array {
		if (empty($aa_data)) {
			return null;
		}


		return $aa_data;
	}
}
