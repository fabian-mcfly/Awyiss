<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;


/**
 * ContentTemplateElement Entity
 *
 * @property int $id
 * @property int|null $contentTemplateId
 * @property string|null $identifier
 * @property string|null $title
 * @property string|null $fieldset
 * @property string|null $columnSpan
 * @property bool $required
 * @property int $systemOrder
 * @property \Awyiss\Model\Entity\ContentTemplate $contentTemplate
 * @property array{span: ?\Awyiss\Utility\Content\ColumnInterface} $column
 */
class ContentTemplateElement extends Entity {
	/**
	 * @var array The column spans
	 */
	protected static array $columnSpans;


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'contentTemplateId' => true,
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
	protected array $_virtual = ['column', 'label']; // phpcs:ignore


	/**
	 * In the database, the identifier exists as an underscored string
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		return Inflector::variable($identifier);
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
