<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;


/**
 * MediaElementSelector Entity
 *
 * @property int $id
 * @property int|null $mediaElementId
 * @property int|null $mediaSelectorId
 * @property string|null $title
 * @property string|null $identifier
 * @property string|null $columnSpan
 * @property bool $required
 * @property int $systemOrder
 * @property \Awyiss\Model\Entity\MediaAssignment[] $mediaAssignments
 * @property \Awyiss\Model\Entity\MediaElement $mediaElement
 * @property \Awyiss\Model\Entity\MediaSelector $mediaSelector
 * @property array{span: ?\Awyiss\Utility\Content\ColumnInterface} $column
 */
class MediaElementSelector extends Entity {
	/**
	 * @var array The column spans
	 */
	protected static array $columnSpans;
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_element_id' => 'mediaElementId',
		'media_selector_id' => 'mediaSelectorId',
		'column_span' => 'columnSpan',
		'media_selector' => 'mediaSelector',
		'system_order' => 'systemOrder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'mediaElementId' => true,
		'mediaSelectorId' => true,
		'title' => true,
		'identifier' => true,
		'columnSpan' => true,
		'required' => true,
		'systemOrder' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $_virtual = [ // phpcs:ignore
		'column',
		'label',
	];


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
