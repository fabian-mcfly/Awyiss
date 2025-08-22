<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * MediaElement Entity
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $identifier
 * @property string $columnSpan
 * @property bool $internal
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
 * @property \Awyiss\Model\Entity\MediaAssignment[] $mediaAssignments
 * @property \Awyiss\Model\Entity\MediaElementAssignment[] $mediaElementAssignments
 * @property \Awyiss\Model\Entity\MediaElementSelector[] $mediaElementSelectors
 */
class MediaElement extends Entity {
	/**
	 * @var array The column spans
	 */
	protected static array $columnSpans;
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'identifier' => 'identifier',
		'column_span' => 'columnSpan',
		'system_order' => 'systemOrder',
		'media_element_assignments' => 'mediaElementAssignments',
		'media_element_selectors' => 'mediaElementSelectors',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => true,
		'identifier' => true,
		'columnSpan' => true,
		'internal' => true,
		'systemOrder' => true,
		'active' => true,
		'mediaElementAssignments' => true,
		'mediaElementSelectors' => true,
	];
	/**
	 * @inheritdoc
	 */
	protected array $_virtual = ['column', 'label'];


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Menu::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		$ls_identifier = Text::slug($identifier, ['replacement' => '_']);


		return mb_strtolower($ls_identifier);
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
