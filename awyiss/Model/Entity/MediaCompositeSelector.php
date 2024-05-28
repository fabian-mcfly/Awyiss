<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;


/**
 * MediaCompositeSelector Entity
 *
 * @property int $id
 * @property int|null $mediaCompositeId
 * @property int|null $mediaSelectorId
 * @property string|null $title
 * @property string|null $identifier
 * @property string|null $columnSpan
 * @property bool $required
 * @property \Awyiss\Model\Entity\MediaAssignment[] $mediaAssignments
 * @property \Awyiss\Model\Entity\MediaComposite $mediaComposite
 * @property \Awyiss\Model\Entity\MediaSelector $mediaSelector
 * @property array{span: ?\Awyiss\Utility\Content\ColumnInterface} $column
 */
class MediaCompositeSelector extends Entity {
	/**
	 * @var array The column spans
	 */
	protected static array $columnSpans;
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_composite_id' => 'mediaCompositeId',
		'media_selector_id' => 'mediaSelectorId',
		'column_span' => 'columnSpan',
		'media_selector' => 'mediaSelector',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaCompositeId' => true,
		'mediaSelectorId' => true,
		'title' => true,
		'identifier' => true,
		'columnSpan' => true,
		'required' => true,
	];


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
