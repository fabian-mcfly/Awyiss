<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Table\AttributesTable;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;


/**
 * Attribute Entity
 *
 * @property int $id
 * @property string $scope
 * @property string $title
 * @property string $identifier
 * @property string|NULL $defaultValue
 * @property string|NULL $fieldset
 * @property string $inputType
 * @property string $type
 * @property bool $hasIndex
 * @property bool $required
 * @property bool $translatable
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $createdBy
 * @property FrozenTime|NULL $createdOn
 * @property int|NULL $changedBy
 * @property FrozenTime|NULL $changedOn
 * @property int|NULL $deletedBy
 * @property FrozenTime|NULL $deletedOn
 */
class Attribute extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => TRUE,
		'title' => TRUE,
		'identifier' => TRUE,
		'defaultValue' => TRUE,
		'fieldset' => TRUE,
		'inputType' => TRUE,
		'type' => TRUE,
		'hasIndex' => TRUE,
		'required' => TRUE,
		'translatable' => TRUE,
		'systemOrder' => TRUE,
		'active' => TRUE,
	];
	protected static array $fieldMap = [
		'has_index' => 'hasIndex',
		'input_type' => 'inputType',
		'default_value' => 'defaultValue',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * @inheritDoc
	 */
	public function defaultValues(): array {
		/** @var AttributesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $this->defaults + ['fieldset' => $lo_table->getAvailableFieldsets()[0]];
	}


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setIdentifier(string $as_identifier): string {
		return mb_strtolower(Text::slug($as_identifier, ['replacement' => '_']));
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setScope(string $as_scope): string {
		return mb_strtolower(Text::slug($as_scope, ['replacement' => '_']));
	}
}
