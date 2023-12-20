<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Table\BackendMenuEntriesTable;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\FrozenTime;


/**
 * BackendMenuEntry Entity
 *
 * @property int $id
 * @property string $parentId
 * @property string $insertAfterId
 * @property string $title
 * @property string $link
 * @property array $access
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
class BackendMenuEntry extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'parentId' => TRUE,
		'insertAfterId' => TRUE,
		'title' => TRUE,
		'link' => TRUE,
		'access' => TRUE,
		'external' => TRUE,
		'systemOrder' => TRUE,
		'active' => TRUE,
		'childBackendMenuEntries' => TRUE,
	];
	protected array $defaults = [
		'parentId' => NULL,
		'insertAfterId' => NULL,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'parent_id' => 'parentId',
		'insert_after_id' => 'insertAfterId',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'child_backend_menu_entries' => 'childBackendMenuEntries',
		'parent_backend_menu_entries' => 'parentBackendMenuEntries',
	];


	public function _setParentId(mixed $ax_value) {
		if (empty($ax_value)) {
			return NULL;
		}


		return $ax_value;
	}


	public function _setInsertAfterId(mixed $ax_value) {
		if (empty($ax_value)) {
			return NULL;
		}


		return $ax_value;
	}


	public function _setAccess(mixed $ax_value) {
		if (empty($ax_value)) {
			return NULL;
		}


		return is_string($ax_value) ? json_decode($ax_value) : $ax_value;
	}


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(): ?CollectionInterface {
		/** @var BackendMenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var BackendMenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(): ?self {
		/** @var BackendMenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var BackendMenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}
}
