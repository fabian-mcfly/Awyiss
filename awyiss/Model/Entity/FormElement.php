<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
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
	protected static array $fieldMap = [
		'parent_id' => 'parentId',
		'form_id' => 'formId',
		'title_email' => 'titleEmail',
		'column_width' => 'columnWidth',
		'column_indent' => 'columnIndent',
		'column_last' => 'columnLast',
		'column_rtl' => 'columnRtl',
		'css_class' => 'cssClass',
		'system_order' => 'systemOrder',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
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
	protected array $_virtual = ['column', 'label'];


	/**
	 * Get all direct children of the current entity
	 *
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\FormElementsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('FormElements');


		return $lo_table->getChildren($this, $options);
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
		/** @var \Awyiss\Model\Table\FormElementsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('FormElements');


		return $lo_table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent entity of the current entity
	 *
	 * @param array $options
	 * @return \Awyiss\Model\Entity\Widget|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\FormElementsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('FormElements');


		return $lo_table->getParent($this, $options);
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
		/** @var \Awyiss\Model\Table\FormElementsTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('FormElements');


		return $lo_table->getParents($this, $options, $currentLevel);
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

		$la_options = [];
		foreach ($options as $li_key => $la_option) {
			if ($languageShortcode && isset($la_option['_translations'][ $languageShortcode ])) {
				$ls_value = $la_option['_translations'][ $languageShortcode ]['value'];
				$ls_key = $la_option['_translations'][ $languageShortcode ]['key'];
			}
			else {
				$ls_key = $la_option['key'];
				$ls_value = $la_option['value'];
			}

			if (empty($ls_key)) {
				$ls_key = $ls_value;
			}
			elseif (empty($ls_value)) {
				$ls_value = $ls_key;
			}

			// If both, key and value are empty, skip this option if the element is not the first one
			if (($li_key !== 0 || in_array($type, ['checkbox', 'radio'])) && empty($ls_key) && empty($ls_value)) {
				continue;
			}

			$la_options[ $ls_key ] = $ls_value;
		}

		return $la_options;
	}


	/**
	 * @return array<string, ?\Awyiss\Utility\Content\ColumnInterface>
	 */
	protected function _getColumn(): array {
		if (!isset(static::$columnWidths)) {
			/** @var \Awyiss\Model\Table\FormElementsTable $lo_table */
			$lo_table = FactoryLocator::get('Table')->get('FormElements');
			static::$columnWidths = $lo_table->getColumnWidths();
			static::$columnIndents = $lo_table->getColumnIndents();
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
		if ($this->type === 'free_text') {
			return $this->cleanTitle($this->text ?? '');
		}

		return parent::_getLabel();
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

		$ls_identifier = Text::slug($identifier, ['replacement' => '_']);

		return mb_strtolower($ls_identifier);
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


	/**
	 * @param string $title
	 * @return string
	 * @noinspection DuplicatedCode
	 */
	protected function cleanTitle(string $title): string {
		$ls_title = $title;

		// If there is a <module> tag in the title, replace it with the module identifier (data-identifier attribute)
		if (str_contains($ls_title, '<module')) {
			$ls_title = preg_replace('/<module[^>]*data-identifier="([^"]*)"[^>]*>.*?<\/module>/', 'Module: <em>$1</em>', $ls_title);
		}

		$ls_title = trim(strip_tags(html_entity_decode(str_replace(['&nbsp;', '<br>'], ' ', (string)$ls_title))));

		// Multiline titles should only show the first line
		if (str_contains($ls_title, PHP_EOL)) {
			$ls_title = substr($ls_title, 0, strpos($ls_title, PHP_EOL));
		}

		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$ls_title = mb_strlen($ls_title) > 100 ? mb_substr($ls_title, 0, 100) . '...' : $ls_title;

		return $ls_title;
	}
}
