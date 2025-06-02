<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Trait\ForcedTitleTrait;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;


/**
 * Widget Entity
 *
 * @property int $id
 * @property string|null $identifier
 * @property int|null $widgetTemplateId
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
 * @property \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
 * @property \Awyiss\Model\Entity\Widget $parentWidget
 * @property \Awyiss\Model\Entity\Widget[] $childWidgets
 * @property \Awyiss\Model\Entity\Form|null $form
 * @property \Awyiss\Model\Entity\Survey|null $survey
 * @property array{width: \Awyiss\Utility\Content\ColumnInterface, indent: ?\Awyiss\Utility\Content\ColumnInterface} $column
 * @property array|null $parentWidgets
 * @property float|null $realColumnWidth
 */
class Widget extends Entity {
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
		'parent_id' => 'parentId',
		'widget_template_id' => 'widgetTemplateId',
		'title_tag' => 'titleTag',
		'subtitle_tag' => 'subtitleTag',
		'css_class' => 'cssClass',
		'column_width' => 'columnWidth',
		'column_indent' => 'columnIndent',
		'column_last' => 'columnLast',
		'column_rtl' => 'columnRtl',
		'form_id' => 'formId',
		'survey_id' => 'surveyId',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'widget_template' => 'widgetTemplate',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'identifier' => true,
		'parentId' => true,
		'title' => true,
		'titleTag' => true,
		'subtitle' => true,
		'subtitleTag' => true,
		'text' => true,
		'link' => true,
		'widgetTemplateId' => true,
		'columnWidth' => true,
		'columnIndent' => true,
		'columnLast' => true,
		'columnRtl' => true,
		'cssClass' => true,
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
	 * @noinspection PhpUnused
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\WidgetsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this, $options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\WidgetsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent widget of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\WidgetsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this, $options);
	}


	/**
	 * Get all the parent widget and all of its parents widgets of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\WidgetsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $options, $currentLevel);
	}


	/**
	 * @return array<string, ?\Awyiss\Utility\Content\ColumnInterface>
	 */
	protected function _getColumn(): array {
		if (!isset(static::$columnWidths)) {
			/** @var \Awyiss\Model\Table\WidgetsTable $lo_table */
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
}
