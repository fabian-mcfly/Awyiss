<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Trait\CustomerGroupAccessTrait;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;


/**
 * MenuEntry Entity
 *
 * @property int $id
 * @property int|null $menuId
 * @property string|null $languageShortcode
 * @property int|null $parentId
 * @property string|null $title
 * @property string|null $link
 * @property bool $external
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Menu $menu
 * @property \Awyiss\Model\Entity\MenuEntry $parentMenuEntry
 * @property \Awyiss\Model\Entity\MenuEntry[] $childMenuEntries
 * @property \Awyiss\Model\Entity\MenuEntry[] $children
 * @property \Awyiss\Model\Entity\Language $language
 * @property \Awyiss\Model\Entity\CustomerGroupAccessSetting $customerGroupAccessSettings
 * @property \Awyiss\Model\Entity\CustomerGroupAssignment[] $customerGroupAssignments
 */
class MenuEntry extends Entity {
	use CustomerGroupAccessTrait;

	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'menu_id' => 'menuId',
		'language_shortcode' => 'languageShortcode',
		'parent_id' => 'parentId',
		'system_order' => 'systemOrder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'menuId' => true,
		'languageShortcode' => true,
		'parentId' => true,
		'title' => true,
		'link' => true,
		'external' => true,
		'systemOrder' => true,
		'active' => true,
	];


	/**
	 * Get all direct children of the current entity
	 *
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
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
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
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
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
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
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());


		return $table->getParents($this, $options, $currentLevel);
	}
}
