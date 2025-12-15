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
 * @property \Awyiss\Model\Entity\BackendMenuEntry[] $children
 */
class BackendMenuEntry extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'parent_id' => 'parentId',
		'insert_after_id' => 'insertAfterId',
		'system_order' => 'systemOrder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
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
	 * @param string|null $value
	 * @return string
	 */
	protected function _getTitle(?string $value = null): string {
		if (empty($value)) {
			return '';
		}

		if (str_contains($value, '::')) {
			$parts = explode('::', $value);

			return __d($parts[0], $parts[1]);
		}

		return $value;
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function _setAccess(mixed $value): mixed {
		if (empty($value)) {
			return null;
		}


		return is_string($value) ? json_decode($value) : $value;
	}


	/**
	 * Get all direct children of the current entity
	 *
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
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
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
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
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
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
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());


		return $table->getParents($this, $options, $currentLevel);
	}
}
