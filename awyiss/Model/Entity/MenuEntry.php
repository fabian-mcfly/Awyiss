<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
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
 * @property \Awyiss\Model\Entity\Language $language
 */
class MenuEntry extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
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
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'menu_id' => 'menuId',
		'language_shortcode' => 'languageShortcode',
		'parent_id' => 'parentId',
		'system_order' => 'systemOrder',
	];


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this, $options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this, $options);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $options, $currentLevel);
	}
}
