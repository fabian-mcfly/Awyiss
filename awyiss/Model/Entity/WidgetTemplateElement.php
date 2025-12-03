<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;


/**
 * WidgetTemplateElement Entity
 *
 * @property int $id
 * @property int|null $widgetTemplateId
 * @property string|null $identifier
 * @property string|null $title
 * @property string|null $fieldset
 * @property string|null $columnSpan
 * @property bool $required
 * @property int $systemOrder
 * @property \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate
 * @property array{span: ?\Awyiss\Utility\Content\ColumnInterface} $column
 */
class WidgetTemplateElement extends Entity {
	/**
	 * @var array The column spans
	 */
	protected static array $columnSpans;
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'column_span' => 'columnSpan',
		'widget_template_id' => 'widgetTemplateId',
		'system_order' => 'systemOrder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'widgetTemplateId' => true,
		'identifier' => true,
		'title' => true,
		'fieldset' => true,
		'columnSpan' => true,
		'required' => true,
		'systemOrder' => true,
	];
	/**
	 * @inheritdoc
	 */
	protected array $_virtual = ['column', 'label'];


	/**
	 * In the database, the identifier exists as an underscored string
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\WidgetTemplateElement::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		return Inflector::underscore($identifier);
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
}
