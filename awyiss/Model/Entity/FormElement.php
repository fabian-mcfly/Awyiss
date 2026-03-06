<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Trait\ForcedTitleTrait;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * FormElement Entity
 *
 * @property int $id
 * @property int $formId
 * @property int $parentId
 * @property string $type
 * @property string|null $identifier
 * @property string|null $title
 * @property string|null $titleEmail
 * @property string|null $placeholder
 * @property string|null $text
 * @property array|null $options
 * @property string $columnWidth
 * @property string|null $columnIndent
 * @property bool $columnLast
 * @property bool $columnRtl
 * @property string|null $cssClass
 * @property bool $required
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Form $form
 * @property \Awyiss\Model\Entity\FormElement $parentFormElement
 * @property \Awyiss\Model\Entity\FormElement[] $childFormElements
 * @property array{width: \Awyiss\Utility\Content\ColumnInterface, indent: ?\Awyiss\Utility\Content\ColumnInterface} $column
 * @property array|null $parentFormElements
 * @property array|bool|null $disabled
 * @property array|bool|null $readonly
 * @property mixed|null $value
 * @property float|null $realColumnWidth
 * @property int $realSystemOrder
 */
class FormElement extends Entity {
	use ForcedTitleTrait;


	/**
	 * @var array The column widths
	 */
	protected static array $columnWidths;
	/**
	 * @var array The column indents
	 */
	protected static array $columnIndents;


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'formId' => true,
		'parentId' => true,
		'type' => true,
		'identifier' => true,
		'title' => true,
		'titleEmail' => true,
		'placeholder' => true,
		'text' => true,
		'options' => true,
		'columnWidth' => true,
		'columnIndent' => true,
		'columnLast' => true,
		'columnRtl' => true,
		'cssClass' => true,
		'required' => true,
		'systemOrder' => true,
		'active' => true,
	];
	/**
	 * @inheritdoc
	 */
	protected array $_virtual = ['column', 'label']; // phpcs:ignore


	/**
	 * Get all direct children of the current entity
	 *
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');


		return $table->getChildren($this, $options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function getNestedChildren(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');


		return $table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent entity of the current entity
	 *
	 * @param array $options
	 * @return \Awyiss\Model\Entity\GlobalContent|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');


		return $table->getParent($this, $options);
	}


	/**
	 * Get all the parent entities and all of its parent entities of the current entity
	 *
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function getParents(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\FormElementsTable $table */
		$table = FactoryLocator::get('Table')->get('FormElements');


		return $table->getParents($this, $options, $currentLevel);
	}


	/**
	 * @param array|null $options
	 * @param string $type
	 * @param string|null $languageShortcode
	 * @return array
	 */
	public function parseOptions(?array $options, string $type, ?string $languageShortcode = null): array {
		if (!$options) {
			return [];
		}

		$parsedOptions = [];
		foreach ($options as $key => $option) {
			if ($languageShortcode && isset($option['_translations'][ $languageShortcode ])) {
				$optionValue = $option['_translations'][ $languageShortcode ]['value'];
				$optionKey = $option['_translations'][ $languageShortcode ]['key'];
			}
			else {
				$optionKey = $option['key'];
				$optionValue = $option['value'];
			}

			if (empty($optionKey)) {
				$optionKey = $optionValue;
			}
			elseif (empty($optionValue)) {
				$optionValue = $optionKey;
			}

			// If both, key and value are empty, skip this option if the element is not the first one
			if (($key !== 0 || in_array($type, ['checkbox', 'radio'])) && empty($optionKey) && empty($optionValue)) {
				continue;
			}

			$parsedOptions[ $optionKey ] = $optionValue;
		}

		return $parsedOptions;
	}


	/**
	 * @return array<string, ?\Awyiss\Utility\Content\ColumnInterface>
	 */
	protected function _getColumn(): array {
		if (!isset(static::$columnWidths)) {
			/** @var \Awyiss\Model\Table\FormElementsTable $table */
			$table = FactoryLocator::get('Table')->get('FormElements');
			static::$columnWidths = $table->getColumnWidths();
			static::$columnIndents = $table->getColumnIndents();
		}

		return [
			'width' => static::$columnWidths[ $this->columnWidth ] ?? reset(static::$columnWidths),
			'indent' => static::$columnIndents[ $this->columnIndent ] ?? null,
		];
	}


	/**
	 * @inheritDoc
	 */
	protected function _getLabel(): string {
		return $this->getForcedTitle(false);
	}


	/**
	 * @param bool|null $last
	 * @return bool
	 */
	protected function _setColumnLast(?bool $last = null): bool {
		return (bool)$last;
	}


	/**
	 * @param bool|null $rtl
	 * @return bool
	 */
	protected function _setColumnRtl(?bool $rtl = null): bool {
		return (bool)$rtl;
	}


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\FormElement::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		$identifier = Text::slug($identifier, ['replacement' => '_']);

		return Inflector::variable($identifier);
	}


	/**
	 * Make sure no empty array finds its way into the db
	 *
	 * @param array|null $options
	 * @return array|null
	 */
	protected function _setOptions(?array $options): ?array {
		if (empty($options)) {
			return null;
		}


		return $options;
	}
}
