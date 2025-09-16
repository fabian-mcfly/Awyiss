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
	/**
	 * Default config for this helper.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'attributeOptionsProviderClass' => AttributeOptionsProvider::class,
	];
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Form'];
	/**
	 * @var \Cake\View\Form\ContextInterface|null
	 */
	protected ?ContextInterface $context = null;
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
	 * @var array
	 */
	protected static array $attributeOptions = [];


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
		$ls_source = $this->getSource();
		$this->initializeSource($ls_source);

		if (!isset(static::$attributesByFieldset)) {
			$this->buildAttributesGroupedByFieldset($ls_source);
		}

		if (empty(static::$attributesByFieldset[ $fieldset ])) {
			return '';
		}

		$la_attributeFields = static::$attributesByFieldset[ $fieldset ];

		// Merge the provided fields with the fields from the attribute table
		$la_fields = array_merge(Hash::normalize(array_keys($la_attributeFields)), Hash::normalize($fields));
		// Remove fields that are set to false
		$la_fields = array_filter($la_fields, function ($value) {
			return $value !== false;
		});

		// If onlyProvided is set to true, only output fields that are present in the $fields-parameter
		if (!empty($options['onlyProvided'])) {
			$la_fields = array_intersect_key($la_fields, Hash::normalize($fields));
		}

		if (empty($la_fields)) {
			return '';
		}

		$ls_fields = '';
		foreach (
			$this->prepareFields(
				$la_fields,
				static::$attributesByFieldset[ $fieldset ],
				static::$attributeOptions[ $ls_source ]
			) as $ls_field => $la_options
		) {
			$ls_fields .= $this->Form->control($ls_field, $la_options);
		}

		return $ls_fields;
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function control(string $fieldName, array $options = []): string {
		$ls_source = $this->getSource();
		$this->initializeSource($ls_source);

		if (!isset(static::$attributes)) {
			$this->buildAttributes($ls_source);
		}

		if (empty(static::$attributes[ $fieldName ])) {
			return '';
		}

		$la_field = $this->prepareField(
			$fieldName,
			$options,
			static::$attributes,
			static::$attributeOptions[ $ls_source ]
		);

		$ls_fieldName = key($la_field);
		$la_options = current($la_field);

		/** @noinspection PhpAutovivificationOnFalseValuesInspection */
		$la_options['templateVars']['identifier'] = 'Attributes-' . Inflector::camelize($fieldName);

		return $this->Form->control($ls_fieldName, $la_options);
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
	 * @param string $fieldName
	 * @param array $options
	 * @param array $attributeFields
	 * @param \Awyiss\Attribute\AttributeOptionsCollectionInterface|null $attributeOptions
	 * @return array<string, array>
	 * @throws \Exception
	 */
	protected function prepareField(string $fieldName, array $options, array $attributeFields, ?AttributeOptionsCollectionInterface $attributeOptions): array {
		if (!array_key_exists($fieldName, $attributeFields)) {
			return [];
		}

		$la_options = $this->buildOptions($options, $attributeFields, $fieldName);

		/**
		 * @var \Awyiss\Model\Entity $lo_entity
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_entity = $this->getContext()->entity();
		if (!isset($la_options['error']) && $lo_entity->hasErrors() && ($lo_entity->getError('attributes')[ $fieldName ] ?? false)) {
			$la_options['error'] = $lo_entity->getError('attributes')[ $fieldName ];
		}

		$this->prepareValue($fieldName, $la_options);

		if (
			$attributeOptions &&
			empty($la_options['options']) &&
			in_array($la_options['type'], ['checkbox', 'multicheckbox', 'select', 'select_multiple'])
		) {
			$la_options = $attributeOptions->getAttributeOptionsAttributes($fieldName, $la_options, $this->getContext());
		}

		$ls_field = $fieldName;
		$ls_field = $this->prepareTranslationField($ls_field, $la_options);

		if (!str_starts_with($ls_field, 'attributes.')) {
			$ls_field = 'attributes.' . $ls_field;
		}

		unset($la_options['realType']);


		return [$ls_field => $la_options];
	}


	/**
	 * @param array $fields
	 * @param array $attributeFields
	 * @param \Awyiss\Attribute\AttributeOptionsCollectionInterface|null $attributeOptions
	 * @return array<string, array>
	 * @throws \Exception
	 */
	protected function prepareFields(array $fields, array $attributeFields, ?AttributeOptionsCollectionInterface $attributeOptions): array {
		static $ls_categoryFieldName;

		// Get the category field name
		if (!isset($ls_categoryFieldName)) {
			$ls_categoryIdentifier = $this->getView()->get('_categoriesIdentifier');

			$ls_categoryFieldName = null;
			if ($ls_categoryIdentifier) {
				$la_categoryOptions = $this->getView()->get('_categories', [])[ Inflector::variable(Inflector::pluralize($ls_categoryIdentifier)) ]['config'] ?? [];
				$ls_categoryFieldName = Inflector::underscore($la_categoryOptions['field']);
			}
		}

		$la_fields = [];
		foreach ($fields as $ls_fieldName => $la_options) {
			// If the field is the category field, skip it
			if (Inflector::underscore($ls_fieldName) === $ls_categoryFieldName) {
				continue;
			}

			$la_fields += $this->prepareField($ls_fieldName, $la_options ?? [], $attributeFields, $attributeOptions);
		}
		unset($la_options);


		return $la_fields;
	}


	/**
	 * @param string $field
	 * @param mixed $options
	 * @return void
	 */
	protected function prepareValue(string $field, mixed &$options): void {
		$la_valOptions = [
			'default' => $lx_options['default'] ?? null,
			'schemaDefault' => $lx_options['schemaDefault'] ?? true,
		];

		if (!array_key_exists('val', $options)) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$options['val'] = $this->Form->getSourceValue($field, $la_valOptions);
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
		if (!in_array($type, ['multicheckbox', 'select_multiple', 'custom_select_multiple'])) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$value = json_encode($value);
		}
	}


	/**
	 * @param string $source
	 * @return array
	 */
	protected function buildAttributes(string $source): array {
		/**
		 * @var \Awyiss\Model\Table $lo_table
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_table = $this->getContext()->fetchTable($source);

		static::$attributes = array_filter($lo_table->getAttributes(), function (Attribute $attribute) {
			return $attribute->active;
		});

		return static::$attributes;
	}


	/**
	 * @param string $source
	 * @return void
	 */
	protected function buildAttributesGroupedByFieldset(string $source): void {
		$la_attributes = $this->buildAttributes($source);

		if (!$la_attributes) {
			static::$attributesByFieldset = [];


			return;
		}

		$la_groupedByFieldset = new Collection($la_attributes)->combine(
			'identifier',
			function (Attribute $entity) {
				return $entity;
			},
			function (Attribute $entity) {
				return $entity->fieldset;
			}
		)->toArray();

		static::$attributesByFieldset = $la_groupedByFieldset;
	}


	/**
	 * @return void
	 */
	protected function initializeTranslate(): void {
		/**
		 * @var \Awyiss\Model\Table $lo_table
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_table = $this->getContext()->fetchTable($this->getContext()->entity()->getSource());

		$ls_associationAlias = $lo_table->getAttributesTableName(true);
		if (!$lo_table->hasAssociation($ls_associationAlias)) {
			return;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_attributesTable = $this->getContext()->fetchTable($ls_associationAlias);
		if (!$lo_attributesTable->hasBehavior('Translate')) {
			return;
		}

		$la_translatableAttributes = $lo_attributesTable->getBehavior('Translate')->getConfig('fields', []);

		if (!$la_translatableAttributes) {
			return;
		}

		$la_translatableAttributes = array_map(function ($field) {
			return 'attributes.' . $field;
		}, $la_translatableAttributes);

		$this->Form->setTranslatableField($la_translatableAttributes);
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return string
	 */
	protected function prepareTranslationField(string $fieldName, array &$options): string {
		$ls_field = $fieldName;

		if (($options['isTranslation'] ?? false) === true) {
			$lo_language = $options['language'] ?? null;
			if (empty($lo_language) || !($lo_language instanceof Language)) {
				throw new RuntimeException(sprintf('Missing language for translation of `%s`', $ls_field));
			}

			if (in_array($options['type'], ['datetime', 'datetime-local'])) {
				$ls_timezone = ($options['timezone'] ?? null) ?: Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone') ?: date_default_timezone_get(); // phpcs:ignore

				if ($ls_timezone === 'auto') {
					$ls_timezone = $lo_language->timezone;
				}

				/** @noinspection PhpVariableNamingConventionInspection */
				$options['timezone'] = $ls_timezone;

				/** @noinspection PhpVariableNamingConventionInspection */
				$options['templateVars']['additionalContent'] ??= '';
				/** @noinspection PhpVariableNamingConventionInspection */
				$options['templateVars']['additionalContent'] .= '<span class="Timezone">' . $ls_timezone . '</span>';
			}

			$ls_field = '_translations.' . $lo_language->shortcode . '.' . $ls_field;
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		unset($options['isTranslation'], $options['language']);

		return $ls_field;
	}


	/**
	 * @param array $options
	 * @param array $attributeFields
	 * @param string $fieldName
	 * @return array
	 * @throws \Exception
	 */
	protected function buildOptions(array $options, array $attributeFields, string $fieldName): array {
		$la_options = $options;

		if (!isset($la_options['type'])) {
			$la_options['type'] = $attributeFields[ $fieldName ]->inputType;
		}

		$la_options = $this->normalizeOptionsByType($la_options);

		if (!isset($la_options['label']) && isset($attributeFields[ $fieldName ])) {
			$la_options['label'] = $attributeFields[ $fieldName ]->label;
		}

		$la_options = $this->setTimezoneOptions($la_options);

		if (!isset($la_options['required']) || $la_options['required'] !== false) {
			if ($attributeFields[ $fieldName ]->required) {
				$la_options['required'] = true;
			}
		}

		/** @var \Awyiss\Utility\Content\AbstractColumn $lo_columnSpan */
		$lo_columnSpan = $attributeFields[ $fieldName ]->column['span'];
		if ($lo_columnSpan->getNumerator() !== 12) {
			$la_options['columnSpan'] = $lo_columnSpan->getNumerator();
		}

		return $la_options;
	}


	/**
	 * @param array $options
	 * @return array
	 */
	protected function normalizeOptionsByType(array $options): array {
		$la_options = $options;

		switch ($la_options['type']) {
			case 'datetime':
				$la_options['type'] = 'datetime-local';
				break;
			case 'select':
				$la_options['empty'] ??= true;
				break;
			case 'select_multiple':
				$la_options['type'] = 'select';
				$la_options['multiple'] = true;
				break;
			case 'texteditor':
				$la_options['type'] = 'textarea';
				$la_options['data-editor'] = true;
				break;
			case 'password':
				$la_options['placeholder'] = '******';
				$la_options['val'] = '';
				break;
		}

		return $la_options;
	}


	/**
	 * @param array $options
	 * @return array
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	protected function setTimezoneOptions(array $options): array {
		$la_options = $options;

		if ($la_options['type'] !== 'datetime') {
			return $la_options;
		}

		$ls_timezone = ($la_options['timezone'] ?? null) ?: Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone') ?: date_default_timezone_get(); // phpcs:ignore

		if ($ls_timezone === 'auto') {
			$lo_language = $la_options['language'] ?? LocaleMiddleware::getLanguage(null);
			$ls_timezone = $lo_language->timezone;
		}

		$la_options['timezone'] = $ls_timezone;

		$la_options['templateVars']['additionalContent'] ??= '';
		$la_options['templateVars']['additionalContent'] .= '<span class="Timezone">' . $ls_timezone . '</span>';

		return $la_options;
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

		/** @var AttributeOptionsProvider $ls_attributeOptionsProvider */
		$ls_attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
		static::$attributeOptions[ $source ] = $ls_attributeOptionsProvider::getAttributeOptionsFile($source, true);

		$this->initializeTranslate();
	}


	/**
	 * @return string
	 */
	protected function getSource(): string {
		$this->context ??= $this->Form->context();
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ls_source = $this->getContext()?->entity()->getSource();

		if (!$ls_source) {
			throw new RuntimeException('No form context set.');
		}

		return $ls_source;
	}
}
