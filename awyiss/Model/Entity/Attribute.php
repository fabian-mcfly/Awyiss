<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * Attribute Entity
 *
 * @property int $id
 * @property string $scope
 * @property string $title
 * @property string $identifier
 * @property string|null $defaultValue
 * @property string|null $fieldset
 * @property string $inputType
 * @property string $type
 * @property bool $hasIndex
 * @property bool $required
 * @property bool $translatable
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class Attribute extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'scope' => true,
		'title' => true,
		'identifier' => true,
		'defaultValue' => true,
		'fieldset' => true,
		'inputType' => true,
		'type' => true,
		'hasIndex' => true,
		'required' => true,
		'translatable' => true,
		'systemOrder' => true,
		'active' => true,
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
		/** @var \Awyiss\Model\Table\AttributesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $this->defaultValues + ['fieldset' => $lo_table->getAvailableFieldsets()[0]];
	}


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\Attribute::$identifier
	 */
	protected function _setIdentifier(string $as_identifier): string {
		return mb_strtolower(Text::slug($as_identifier, ['replacement' => '_']));
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\Attribute::$scope
	 */
	protected function _setScope(string $as_scope): string {
		return mb_strtolower(Text::slug($as_scope, ['replacement' => '_']));
	}
}
