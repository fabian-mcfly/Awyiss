<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Inflector;


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
	protected static array $fieldMap = [
		'column_span' => 'columnSpan',
		'content_template_id' => 'contentTemplateId',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'contentTemplateId' => true,
		'identifier' => true,
		'title' => true,
		'fieldset' => true,
		'columnSpan' => true,
		'required' => true,
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
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\ContentTemplateElement::$identifier
	 */
	public function _setIdentifier(?string $identifier): ?string {
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
			/** @var \Awyiss\Model\Table\AttributesTable $lo_table */
			$lo_table = FactoryLocator::get('Table')->get('Attributes');
			static::$columnSpans = $lo_table->getColumnSpans();
		}

		return [
			'span' => static::$columnSpans[ $this->columnSpan ] ?? reset(static::$columnSpans),
		];
	}
}
