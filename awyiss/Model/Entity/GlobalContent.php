<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Trait\CustomerGroupAccessTrait;
use Awyiss\Model\Trait\ForcedTitleTrait;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;


/**
 * GlobalContent Entity
 *
 * @property int $id
 * @property string|null $identifier
 * @property int|null $globalContentTemplateId
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
 * @property \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate
 * @property \Awyiss\Model\Entity\GlobalContent $parentGlobalContent
 * @property \Awyiss\Model\Entity\GlobalContent[] $childGlobalContents
 * @property \Awyiss\Model\Entity\Form|null $form
 * @property \Awyiss\Model\Entity\Survey|null $survey
 * @property array{width: \Awyiss\Utility\Content\ColumnInterface, indent: ?\Awyiss\Utility\Content\ColumnInterface} $column
 * @property array|null $parentGlobalContents
 * @property float|null $realColumnWidth
 * @property int $realSystemOrder
 * @property \Awyiss\Model\Entity\CustomerGroupAccessSetting $customerGroupAccessSettings
 * @property \Awyiss\Model\Entity\CustomerGroupAssignment[] $customerGroupAssignments
 */
class GlobalContent extends Entity {
	use CustomerGroupAccessTrait;
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
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'identifier' => true,
		'parentId' => true,
		'title' => true,
		'titleTag' => true,
		'subtitle' => true,
		'subtitleTag' => true,
		'text' => true,
		'link' => true,
		'globalContentTemplateId' => true,
		'columnWidth' => true,
		'columnIndent' => true,
		'columnLast' => true,
		'columnRtl' => true,
		'cssClass' => true,
		'css' => true,
		'data' => true,
		'formId' => true,
		'surveyId' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritdoc
	 */
	protected array $_virtual = ['column', 'label']; // phpcs:ignore


	/**
	 * Get all direct children of the current entity
	 *
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());


		return $table->getChildren($this, $options);
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
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());


		return $table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent entity of the current entity
	 *
	 * @param array $options
	 * @return \Awyiss\Model\Entity\GlobalContent|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());


		return $table->getParent($this, $options);
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
		/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());


		return $table->getParents($this, $options, $currentLevel);
	}


	/**
	 * @return array<string, ?\Awyiss\Utility\Content\ColumnInterface>
	 */
	protected function _getColumn(): array {
		if (!isset(static::$columnWidths)) {
			/** @var \Awyiss\Model\Table\GlobalContentsTable $table */
			$table = FactoryLocator::get('Table')->get($this->getSource());

			static::$columnWidths = $table->getColumnWidths();
			static::$columnIndents = $table->getColumnIndents();
		}

		return [
			'width' => static::$columnWidths[ $this->columnWidth ] ?? reset(static::$columnWidths),
			'indent' => static::$columnIndents[ $this->columnIndent ] ?? null,
		];
	}


	/**
	 * @see \Awyiss\Model\Trait\ForcedTitleTrait::getForcedTitle()
	 */
	protected function _getLabel(): string {
		return $this->getForcedTitle(false);
	}


	/**
	 * Force a bool value when null was provided
	 *
	 * @param bool|null $last
	 * @return bool
	 */
	protected function _setColumnLast(?bool $last = null): bool {
		return (bool)$last;
	}


	/**
	 * Force a bool value when null was provided
	 *
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
