<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Trait\ForcedTitleTrait;
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
 * @property string|null $css
 * @property int|null $duplicateOf
 * @property string|null $data
 * @property int|null $formId
 * @property int|null $surveyId
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
 * @property \Awyiss\Model\Entity\Form|null $form
 * @property \Awyiss\Model\Entity\Survey|null $survey
 * @property array{width: \Awyiss\Utility\Content\ColumnInterface, indent: ?\Awyiss\Utility\Content\ColumnInterface} $column
 * @property array|null $parentContents
 * @property float|null $realColumnWidth
 * @property int $realSystemOrder
 */
class Content extends Entity {
	use ForcedTitleTrait;


	/**
	 * @var array The column indents
	 */
	protected static array $columnIndents;
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
		'form_id' => 'formId',
		'survey_id' => 'surveyId',
		'system_order' => 'systemOrder',
		'content_area' => 'contentArea',
		'content_template' => 'contentTemplate',
		'duplicating_contents' => 'duplicatingContents',
		'duplicate_of_content' => 'duplicateOfContent',
	];


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
		'css' => true,
		'duplicateOf' => true,
		'data' => true,
		'formId' => true,
		'surveyId' => true,
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
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Contents');


		return $lo_table->getChildren($this, $options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function getNestedChildren(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Contents');


		return $lo_table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent entity of the current entity
	 *
	 * @param array $options
	 * @return \Awyiss\Model\Entity\Widget|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Contents');


		return $lo_table->getParent($this, $options);
	}


	/**
	 * Get all the parent entities and all of its parent entities of the current entity
	 *
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function getParents(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Contents');


		return $lo_table->getParents($this, $options, $currentLevel);
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
	 * It uses the first of following db columns identifier, filename, title if present and
	 * prepends a translatable text in case the entity is inactive (active = 0)
	 * The label can be translated as well
	 *
	 * @return string
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
	 */
	protected function _setData(?array $data): ?array {
		if (empty($data)) {
			return null;
		}


		return $data;
	}
}
