<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * Attribute Entity
 *
 * @property int $id
 * @property string|null $scope
 * @property string|null $title
 * @property string|null $identifier
 * @property string|null $defaultValue
 * @property string|null $fieldset
 * @property string|null $inputType
 * @property string|null $type
 * @property bool $hasIndex
 * @property bool $required
 * @property bool $translatable
 * @property string $columnSpan
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property array{span: ?\Awyiss\Utility\Content\ColumnInterface} $column
 */
class Attribute extends Entity {
	/**
	 * @var array The column spans
	 */
	protected static array $columnSpans;
	/**
	 * @inheritdoc
	 */
	protected static array $fieldMap = [
		'has_index' => 'hasIndex',
		'input_type' => 'inputType',
		'default_value' => 'defaultValue',
		'column_span' => 'columnSpan',
		'system_order' => 'systemOrder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
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
		'columnSpan' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritdoc
	 */
	protected array $_virtual = ['column', 'label']; // phpcs:ignore


	/**
	 * @inheritDoc
	 */
	public function defaultValues(): array {
		/** @var \Awyiss\Model\Table\AttributesTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());


		return $this->defaultValues + ['fieldset' => $table->getAvailableFieldsets()[0]];
	}


	/**
	 * @return array<string, ?\Awyiss\Utility\Content\ColumnInterface>
	 */
	protected function _getColumn(): array {
		if (!isset(static::$columnSpans)) {
			/** @var \Awyiss\Model\Table\AttributesTable $table */
			$table = FactoryLocator::get('Table')->get('Attributes');
			static::$columnSpans = $table->getColumnSpans();
		}

		return [
			'span' => static::$columnSpans[ $this->columnSpan ] ?? reset(static::$columnSpans),
		];
	}


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Attribute::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		return mb_strtolower(Text::slug($identifier, ['replacement' => '_']));
	}


	/**
	 * Make sure the scope is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $scope
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Attribute::$scope
	 */
	protected function _setScope(?string $scope): ?string {
		if ($scope === null) {
			return null;
		}

		return mb_strtolower(Text::slug($scope, ['replacement' => '_']));
	}
}
