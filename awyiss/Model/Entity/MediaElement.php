<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Utility\Inflector;
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
 * @property array{span: ?\Awyiss\Utility\Content\ColumnSystem\ColumnInterface} $column
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
	protected array $_accessible = [ // phpcs:ignore
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
	protected array $_virtual = ['column', 'label']; // phpcs:ignore


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

		$identifier = Text::slug($identifier, ['replacement' => '_']);


		return Inflector::variable($identifier);
	}


	/**
	 * @return array<string, ?\Awyiss\Utility\Content\ColumnSystem\ColumnInterface>
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
