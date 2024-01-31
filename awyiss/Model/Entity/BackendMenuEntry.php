<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;


/**
 * BackendMenuEntry Entity
 *
 * @property int $id
 * @property string|null $parentId
 * @property string|null $insertAfterId
 * @property string|null $title
 * @property string|null $link
 * @property array|null $access
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
 * @property \Awyiss\Model\Entity\BackendMenuEntry $parentBackendMenuEntry
 * @property \Awyiss\Model\Entity\BackendMenuEntry[] $childBackendMenuEntries
 */
class BackendMenuEntry extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'parentId' => true,
		'insertAfterId' => true,
		'title' => true,
		'link' => true,
		'access' => true,
		'external' => true,
		'systemOrder' => true,
		'active' => true,
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
	];


	/**
	 * @param mixed $ax_value
	 * @return mixed
	 * @noinspection PhpUnused
	 */
	public function _setAccess(mixed $ax_value): mixed {
		if (empty($ax_value)) {
			return null;
		}


		return is_string($ax_value) ? json_decode($ax_value) : $ax_value;
	}


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(array $aa_options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this, $aa_options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @param array $aa_options
	 * @param int $ai_currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getNestedChildren(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @param array $aa_options
	 * @return \Awyiss\Model\Entity\BackendMenuEntry|null
	 */
	public function getParent(array $aa_options = []): ?self {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this, $aa_options);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @param array $aa_options
	 * @param int $ai_currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getParents(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}
}
