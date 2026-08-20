<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Attribute\AttributeOptionsCollectionInterface;
use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\Language;
use Awyiss\Utility\Inflector;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Cake\View\Form\ContextInterface;
use Cake\View\Form\NullContext;
use Cake\View\Helper;
use RuntimeException;


/**
 * Helper class that provides methods related to the Authorization-logic in the views
 *
 * @property \Awyiss\View\Helper\FormHelper $Form
 */
class AttributesHelper extends Helper {
	use LocatorAwareTrait;


	/**
	 * @var array
	 */
	protected static array $attributeOptions = [];
	/**
	 * @var array|null
	 */
	protected static ?array $attributes = null;
	/**
	 * @var ?array
	 */
	protected static ?array $attributesByFieldset = null;
	/**
	 * @var array
	 */
	protected static array $initiatedSources = [];


	/**
	 * Default config for this helper.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'attributeOptionsProviderClass' => AttributeOptionsProvider::class,
	];
	/**
	 * @var \Cake\View\Form\ContextInterface|null
	 */
	protected ?ContextInterface $context = null;
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Form'];


	/**
	 * Generate a set of controls for `$fields`.
	 *
	 * You can customize individual controls through `$fields`.
	 * ```
	 * $this->Attributes->allControls([
	 *   'title' => ['label' => 'custom label']
	 * ]);
	 * ```
	 *
	 * You can exclude fields by specifying them as `false`:
	 *
	 * ```
	 * $this->Attributes->allControls(['title' => false]);
	 * ```
	 *
	 * In the above example, no field would be generated for the title field.
	 *
	 * @param string $fieldset The fieldset
	 * @param array $fields An array of customizations for the fields that will be generated.
	 *  This array allows you to set custom types, labels, or other options.
	 * @param array<string, mixed> $options A list of options. Valid keys are:
	 *
	 * - `fieldset` Set to `false` to disable the fieldset. You can also pass an
	 *     array of params to be applied as HTML attributes to the fieldset tag.
	 *     If you pass an empty array, the fieldset will be enabled.
	 * - `legend` Set to `false` to disable the legend for the generated input set.
	 *     Or supply a string to customize the legend text.
	 * - `onlyProvided` Set to true to only output fields that are present in the `$fields`-parameter.
	 *     Otherwise, fields will get merged.
	 * @return string Completed form controls.
	 * @throws \Exception
	 */
	public function allControls(string $fieldset, array $fields = [], array $options = []): string {
		$source = $this->getSource();
		$this->initializeSource($source);

		if (!isset(static::$attributesByFieldset)) {
			$this->buildAttributesGroupedByFieldset($source);
		}

		if (empty(static::$attributesByFieldset[ $fieldset ])) {
			return '';
		}

		$attributeFields = static::$attributesByFieldset[ $fieldset ];

		// Merge the provided fields with the fields from the attribute table
		$usedFields = array_merge(Hash::normalize(array_keys($attributeFields)), Hash::normalize($fields));
		// Remove fields that are set to false
		$usedFields = array_filter($usedFields, function ($value) {
			return $value !== false;
		});

		// If onlyProvided is set to true, only output fields that are present in the $fields-parameter
		if (!empty($options['onlyProvided'])) {
			$usedFields = array_intersect_key($usedFields, Hash::normalize($fields));
		}

		if (empty($usedFields)) {
			return '';
		}

		$renderedFields = '';
		foreach (
			$this->prepareFields(
				$usedFields,
				static::$attributesByFieldset[ $fieldset ],
				static::$attributeOptions[ $source ]
			) as $field => $fieldOptions
		) {
			$renderedFields .= $this->Form->control($field, $fieldOptions);
		}

		return $renderedFields;
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function control(string $fieldName, array $options = []): string {
		$source = $this->getSource();
		$this->initializeSource($source);

		if (!isset(static::$attributes)) {
			$this->buildAttributes($source);
		}

		if (empty(static::$attributes[ $fieldName ])) {
			return '';
		}

		$prepareField = $this->prepareField(
			$fieldName,
			$options,
			static::$attributes,
			static::$attributeOptions[ $source ]
		);

		$realFieldName = key($prepareField);
		$fieldOptions = current($prepareField);

		/** @noinspection PhpAutovivificationOnFalseValuesInspection */
		$fieldOptions['templateVars']['identifier'] = 'Attributes-' . Inflector::camelize($fieldName);

		return $this->Form->control($realFieldName, $fieldOptions);
	}


	/**
	 * @return \Cake\View\Form\ContextInterface|null
	 */
	public function getContext(): ?ContextInterface {
		if ($this->context instanceof NullContext) {
			return null;
		}

		return $this->context;
	}


	/**
	 * @param \Cake\View\Form\ContextInterface|null $context
	 * @return $this
	 */
	public function setContext(?ContextInterface $context = null): static {
		$this->context = $context;

		return $this;
	}


	/**
	 * Get attribute options for a source.
	 *
	 * @param object|string|null $source Entity-like object with getSource() or a source string.
	 * @return \Awyiss\Attribute\AttributeOptionsCollectionInterface|null
	 * @throws \ReflectionException
	 */
	public function getAttributeOptions(object|string|null $source = null): ?AttributeOptionsCollectionInterface {
		if ($source === null) {
			$source = $this->getSource();
		}
		elseif (method_exists($source, 'getSource')) {
			$source = (string)$source->getSource();
		}
		elseif (!is_string($source)) {
			throw new RuntimeException('Expected source as string or entity-like object with getSource().');
		}

		if ($source === '') {
			throw new RuntimeException('No source provided.');
		}

		$this->initializeSource($source);

		return static::$attributeOptions[ $source ] ?? null;
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @param array $attributeFields
	 * @param \Awyiss\Attribute\AttributeOptionsCollectionInterface|null $attributeOptions
	 * @return array<string, array>
	 * @throws \Exception
	 */
	protected function prepareField(
		string $fieldName,
		array $options,
		array $attributeFields,
		?AttributeOptionsCollectionInterface $attributeOptions
	): array {
		if (!array_key_exists($fieldName, $attributeFields)) {
			return [];
		}

		$originalOptions = $options;
		$options = $this->buildOptions($options, $attributeFields, $fieldName);

		/**
		 * @var \Awyiss\Model\Entity $entity
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$entity = $this->getContext()->entity();
		if (!isset($options['error']) && $entity->hasErrors() && ($entity->getError('attributes')[ $fieldName ] ?? false)) {
			$options['error'] = $entity->getError('attributes')[ $fieldName ];
		}

		$this->prepareValue($fieldName, $options);

		if (
			$attributeOptions
			&& empty($options['options'])
			&& in_array(
				$options['type'],
				['checkbox', 'multicheckbox', 'select', 'selectMultiple']
			)
		) {
			$options = $attributeOptions->getAttributeOptionsAttributes($fieldName, $options, $this->getContext());

			if (!isset($originalOptions['empty']) && array_key_exists('', $options['options'] ?? [])) {
				$options['empty'] = false;
			}
		}

		$fieldName = $this->prepareTranslationField($fieldName, $options);

		if (!str_starts_with($fieldName, 'attributes.')) {
			$fieldName = 'attributes.' . $fieldName;
		}

		unset($options['realType']);


		return [$fieldName => $options];
	}


	/**
	 * @param array $fields
	 * @param array $attributeFields
	 * @param \Awyiss\Attribute\AttributeOptionsCollectionInterface|null $attributeOptions
	 * @return array<string, array>
	 * @throws \Exception
	 */
	protected function prepareFields(array $fields, array $attributeFields, ?AttributeOptionsCollectionInterface $attributeOptions): array {
		static $categoryFieldName;

		// Get the category field name
		if (!isset($categoryFieldName)) {
			$categoryIdentifier = $this->getView()->get('_categoriesIdentifier');

			$categoryFieldName = null;
			if ($categoryIdentifier) {
				$categoryOptions = $this->getView()->get('_categories', [])[ Inflector::variable(
					Inflector::pluralize($categoryIdentifier)
				) ]['config'] ?? [];
				$categoryFieldName = Inflector::underscore($categoryOptions['field']);
			}
		}

		$preparedFields = [];
		foreach ($fields as $fieldName => $fieldOptions) {
			// If the field is the category field, skip it
			if (Inflector::underscore($fieldName) === $categoryFieldName) {
				continue;
			}

			$preparedFields += $this->prepareField($fieldName, $fieldOptions ?? [], $attributeFields, $attributeOptions);
		}
		unset($fieldOptions);


		return $preparedFields;
	}


	/**
	 * @param string $field
	 * @param mixed $options
	 * @return void
	 */
	protected function prepareValue(string $field, mixed &$options): void {
		$valueOptions = [
			'default' => $options['default'] ?? null,
			'schemaDefault' => $options['schemaDefault'] ?? true,
		];

		if (!array_key_exists('val', $options)) {
			$options['val'] = $this->Form->getSourceValue($field, $valueOptions);
		}

		if (!empty($options['val'])) {
			$this->harmonizeValue($options['val'], $options['type']);
		}
	}


	/**
	 * @param mixed $value
	 * @param string $type
	 * @return void
	 */
	protected function harmonizeValue(mixed &$value, string $type): void {
		if (!is_array($value)) {
			return;
		}

		// If the value is an array, but the type is not a multiple-select, convert the value to a JSON-string
		if (!in_array($type, ['multicheckbox', 'selectMultiple', 'customSelectMultiple'])) {
			$value = json_encode($value);
		}
	}


	/**
	 * @param string $source
	 * @return array
	 */
	protected function buildAttributes(string $source): array {
		/**
		 * @var \Awyiss\Model\Table $table
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$table = $this->getContext()->fetchTable($source);

		static::$attributes = array_filter($table->getAttributes(), function (Attribute $attribute) {
			return $attribute->active;
		});

		return static::$attributes;
	}


	/**
	 * @param string $source
	 * @return void
	 */
	protected function buildAttributesGroupedByFieldset(string $source): void {
		$attributes = $this->buildAttributes($source);

		if (!$attributes) {
			static::$attributesByFieldset = [];


			return;
		}

		$groupedByFieldset = new Collection($attributes)
			->combine(
				'identifier',
				fn(Attribute $entity) => $entity,
				fn(Attribute $entity) => $entity->fieldset
			)
			->toArray()
		;

		static::$attributesByFieldset = $groupedByFieldset;
	}


	/**
	 * @param string $source
	 * @return void
	 */
	protected function initializeTranslate(string $source): void {
		/**
		 * @var \Awyiss\Model\Table $table
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$table = $this->fetchTable($source);

		$associationAlias = $table->getAttributesTableName(true);
		if (!$table->hasAssociation($associationAlias)) {
			return;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$attributesTable = $this->fetchTable($associationAlias);
		if (!$attributesTable->hasBehavior('Translate')) {
			return;
		}

		$translatableAttributes = $attributesTable->getBehavior('Translate')->getConfig('fields', []);

		if (!$translatableAttributes) {
			return;
		}

		$translatableAttributes = array_map(function ($field) {
			return 'attributes.' . $field;
		}, $translatableAttributes);

		$this->Form->setTranslatableField($translatableAttributes);
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return string
	 */
	protected function prepareTranslationField(string $fieldName, array &$options): string {
		if (($options['isTranslation'] ?? false) === true) {
			$language = $options['language'] ?? null;
			if (empty($language) || !($language instanceof Language)) {
				throw new RuntimeException(sprintf('Missing language for translation of `%s`', $fieldName));
			}

			if (in_array($options['type'], ['datetime', 'datetime-local'])) {
				$timezone = $options['timezone'] ?? null
					?: Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone')
						?: date_default_timezone_get();

				if ($timezone === 'auto') {
					$timezone = $language->timezone;
				}

				$options['timezone'] = $timezone;

				$options['templateVars']['additionalContent'] ??= '';
				$options['templateVars']['additionalContent'] .= '<span class="Timezone">' . $timezone . '</span>';
			}

			$fieldName = '_translations.' . $language->shortcode . '.' . $fieldName;
		}

		unset($options['isTranslation'], $options['language']);

		return $fieldName;
	}


	/**
	 * @param array $options
	 * @param array $attributeFields
	 * @param string $fieldName
	 * @return array
	 * @throws \Exception
	 */
	protected function buildOptions(array $options, array $attributeFields, string $fieldName): array {
		if (!isset($options['type'])) {
			$options['type'] = $attributeFields[ $fieldName ]->inputType;
		}

		$options = $this->normalizeOptionsByType($options);

		if (!isset($options['label']) && isset($attributeFields[ $fieldName ])) {
			$options['label'] = $attributeFields[ $fieldName ]->label;
		}

		$options = $this->setTimezoneOptions($options);

		if (!isset($options['required']) || $options['required'] !== false) {
			if ($attributeFields[ $fieldName ]->required) {
				$options['required'] = true;
			}
		}

		/** @var \Awyiss\Utility\Content\ColumnSystem\AbstractColumn $columnSpan */
		$columnSpan = $attributeFields[ $fieldName ]->column['span'];
		if ($columnSpan->getNumerator() !== 12) {
			$options['columnSpan'] = $columnSpan->getNumerator();
		}

		return $options;
	}


	/**
	 * @param array $options
	 * @return array
	 */
	protected function normalizeOptionsByType(array $options): array {
		switch ($options['type']) {
			case 'datetime':
				$options['type'] = 'datetime-local';
				break;
			case 'select':
				$options['empty'] ??= true;
				break;
			case 'selectMultiple':
				$options['type'] = 'select';
				$options['multiple'] = true;
				break;
			case 'texteditor':
				$options['type'] = 'textarea';
				$options['data-editor'] = true;
				break;
			case 'password':
				$options['placeholder'] = '******';
				$options['val'] = '';
				break;
		}

		return $options;
	}


	/**
	 * @param array $options
	 * @return array
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	protected function setTimezoneOptions(array $options): array {
		if ($options['type'] !== 'datetime') {
			return $options;
		}

		$timezone = $options['timezone'] ?? null
			?: Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone')
				?: date_default_timezone_get();

		if ($timezone === 'auto') {
			$language = $options['language'] ?? LocaleMiddleware::getLanguage(null);
			$timezone = $language->timezone;
		}

		$options['timezone'] = $timezone;

		$options['templateVars']['additionalContent'] ??= '';
		$options['templateVars']['additionalContent'] .= '<span class="Timezone">' . $timezone . '</span>';

		return $options;
	}


	/**
	 * Initialize the source and return
	 * an empty field to be added to the form,
	 * if the source has not been initialized yet.
	 *
	 * @param string $source
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function initializeSource(string $source): void {
		if (isset(static::$initiatedSources[ $source ])) {
			return;
		}

		static::$initiatedSources[ $source ] = true;

		/** @var AttributeOptionsProvider $attributeOptionsProvider */
		$attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
		static::$attributeOptions[ $source ] = $attributeOptionsProvider::getAttributeOptionsFile($source, true);

		$this->initializeTranslate($source);
	}


	/**
	 * @return string
	 */
	protected function getSource(): string {
		$this->context ??= $this->Form->context();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$source = $this
			->getContext()
			?->entity()
			->getSource()
		;

		if (!$source) {
			throw new RuntimeException('No form context set.');
		}

		return $source;
	}
}
