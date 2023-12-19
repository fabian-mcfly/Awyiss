<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Table\MenuEntriesTable;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\FrozenTime;


/**
 * MenuEntry Entity
 *
 * @property int $id
 * @property int $menuId
 * @property string languageShortcode
 * @property int $parentId
 * @property string $title
 * @property string $link
 * @property bool $external
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property FrozenTime|null $createdOn
 * @property int|null $changedBy
 * @property FrozenTime|null $changedOn
 * @property int|null $deletedBy
 * @property FrozenTime|null $deletedOn
 */
class MenuEntry extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
        'menuId' => TRUE,
        'languageShortcode' => TRUE,
        'parentId' => TRUE,
        'title' => TRUE,
        'link' => TRUE,
        'external' => TRUE,
        'systemOrder' => TRUE,
        'active' => TRUE,
    ];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'menu_id' => 'menuId',
		'language_shortcode' => 'languageShortcode',
		'parent_id' => 'parentId',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren (): ?CollectionInterface {
		/** @var MenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getChildren($this);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren (array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var MenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getNestedChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent (): ?self {
		/** @var MenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getParent($this);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents (array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var MenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}
}
