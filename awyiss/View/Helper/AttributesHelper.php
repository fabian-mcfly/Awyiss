<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Attribute\AttributeOptionsInterface;
use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\Language;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
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
	protected static array $attributes;
	protected static array $attributesByFieldset;
	protected static array $initiatedSources = [];
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
	 * - `onlyProvided` Set to true to only output fields that are present in the `$fields`-paramter.
	 *     Otherwise, fields will get merged.
	 * @return string Completed form controls.
	 * @throws \ReflectionException
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function allControls(string $fieldset, array $fields = [], array $options = []): string {
		/** @var \Cake\View\Form\EntityContext $lo_context */
		$lo_context = $this->Form->context();
		$ls_source = $options['source'] ?? $lo_context->entity()?->getSource();

		if (!isset(static::$attributesByFieldset)) {
			$this->buildAttributesGroupedByFieldset($ls_source);
		}

		$ls_emptyField = '';
		if (!isset(static::$initiatedSources[ $ls_source ])) {
			static::$initiatedSources[ $ls_source ] = true;
			$ls_emptyField = $this->Form->hidden('attributes', [
				'val' => [],
			]);

			/** @var AttributeOptionsProvider $ls_attributeOptionsProvider */
			$ls_attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
			static::$attributeOptions[ $ls_source ] = $ls_attributeOptionsProvider::getAttributeOptionsFile($ls_source, true);

			$this->initializeTranslate();
		}

		if (empty(static::$attributesByFieldset[ $fieldset ])) {
			return $ls_emptyField;
		}

		$la_attributeFields = static::$attributesByFieldset[ $fieldset ];

		$la_fields = array_merge(Hash::normalize(array_keys($la_attributeFields)), Hash::normalize($fields));
		$la_fields = array_filter($la_fields, function ($value) {
			return $value !== false;
		});

		if (!empty($options['onlyProvided'])) {
			$la_fields = array_intersect_key($la_fields, Hash::normalize($fields));
		}

		if (empty($la_fields)) {
			return $ls_emptyField;
		}

		$la_fields = $this->prepareFields(
			$la_fields,
			static::$attributesByFieldset[ $fieldset ],
			static::$attributeOptions[ $ls_source ]
		);


		return $ls_emptyField . $this->Form->controls($la_fields, $options + ['fieldset' => false]);
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function control(string $fieldName, array $options = []): string {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ls_source = $options['source'] ?? $this->Form->context()->entity()->getSource();

		if (!isset(static::$attributes)) {
			$this->buildAttributes($ls_source);
		}

		$ls_emptyField = '';
		if (!isset(static::$initiatedSources[ $ls_source ])) {
			static::$initiatedSources[ $ls_source ] = true;
			$ls_emptyField = $this->Form->hidden('attributes', [
				'val' => [],
			]);

			/** @var AttributeOptionsProvider $ls_attributeOptionsProvider */
			$ls_attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
			static::$attributeOptions[ $ls_source ] = $ls_attributeOptionsProvider::getAttributeOptionsFile($ls_source, true);

			$this->initializeTranslate();
		}

		if (empty(static::$attributes[ $fieldName ])) {
			return $ls_emptyField;
		}

		[$ls_fieldName, $la_options] = $this->prepareField(
			$fieldName,
			$options,
			static::$attributes,
			static::$attributeOptions[ $ls_source ]
		);

		$la_options['templateVars']['identifier'] = 'Attributes' . Inflector::camelize($fieldName);


		return $ls_emptyField . $this->Form->control($ls_fieldName, $la_options);
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @param array $attributeFields
	 * @param AttributeOptionsInterface|null $attributeOptions
	 * @return array
	 * @throws \Exception
	 */
	protected function prepareField(string $fieldName, array $options, array $attributeFields, ?AttributeOptionsInterface $attributeOptions): array {
		if (!array_key_exists($fieldName, $attributeFields)) {
			return [];
		}

		$la_options = $this->buildOptions($options, $attributeFields, $fieldName);

		/**
		 * @var \Awyiss\Model\Entity $lo_entity
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_entity = $this->Form->context()->entity();
		if (!isset($la_options['error']) && $lo_entity->hasErrors() && ($lo_entity->getError('attributes')[ $fieldName ] ?? false)) {
			$la_options['error'] = $lo_entity->getError('attributes')[ $fieldName ];
		}

		$this->prepareValue($fieldName, $la_options);

		if ($attributeOptions) {
			$la_options = $attributeOptions->getAttributeOptionsAttributes($fieldName, $la_options, $this->Form->context());
		}

		$ls_field = $fieldName;
		$ls_field = $this->prepareTranslationField($ls_field, $la_options);

		if (!str_starts_with($ls_field, 'attributes.')) {
			$ls_field = 'attributes.' . $ls_field;
		}

		unset($la_options['realType']);


		return [
			$ls_field,
			$la_options,
		];
	}


	/**
	 * @param array $fields
	 * @param array $attributeFields
	 * @param AttributeOptionsInterface|null $attributeOptions
	 * @return array
	 * @throws \Exception
	 */
	protected function prepareFields(array $fields, array $attributeFields, ?AttributeOptionsInterface $attributeOptions): array {
		static $ls_categoryFieldName;

		if (!isset($ls_categoryFieldName)) {
			$ls_categoryIdentifier = $this->getView()->get('categoriesIdentifier');

			$ls_categoryFieldName = null;
			if ($ls_categoryIdentifier) {
				$la_categoryOptions = $this->getView()->get(Inflector::variable(Inflector::pluralize($ls_categoryIdentifier)))['config'] ?? [];
				$ls_categoryFieldName = Inflector::underscore($la_categoryOptions['field']);
			}
		}

		$la_fields = [];
		foreach ($fields as $ls_fieldName => $la_options) {
			if (Inflector::underscore($ls_fieldName) === $ls_categoryFieldName) {
				continue;
			}

			[$ls_fieldName, $la_options] = $this->prepareField($ls_fieldName, $la_options ?? [], $attributeFields, $attributeOptions);
			$la_fields[ $ls_fieldName ] = $la_options;
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
		if (is_array($value)) {
			if (!in_array($type, ['multicheckbox', 'select_multiple', 'custom_select_multiple'])) {
				/** @noinspection PhpVariableNamingConventionInspection */
				$value = json_encode($value);
			}
		}
	}


	/**
	 * @param string $source
	 * @return array
	 */
	protected function buildAttributes(string $source): array {
		/** @var \Cake\View\Form\EntityContext $lo_context */
		$lo_context = $this->Form->context();
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $lo_context->fetchTable($source);

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

		$la_groupedByFieldset = (new Collection($la_attributes))->combine(
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
		/** @var \Cake\View\Form\EntityContext $lo_context */
		$lo_context = $this->Form->context();
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $lo_context->fetchTable($lo_context->entity()->getSource());

		$ls_associationAlias = $lo_table->getAttributesTableName(true);
		if (!$lo_table->hasAssociation($ls_associationAlias)) {
			return;
		}

		$lo_attributesTable = $lo_context->fetchTable($ls_associationAlias);

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
	 * @return array
	 */
	protected function prepareTranslationField(string $fieldName, array &$options): string {
		$ls_field = $fieldName;

		if (($options['isTranslation'] ?? false) === true) {
			$lo_language = $options['language'] ?? null;
			if (empty($lo_language) || !($lo_language instanceof Language)) {
				throw new RuntimeException(sprintf('Missing language for translation of `%s`', $ls_field));
			}

			if ($options['type'] == 'datetime') {
				$ls_timezone = Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone');
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
		$lo_language = $la_options['language'] ?? LocaleMiddleware::getLanguage(null);

		if (!isset($la_options['type'])) {
			$la_options['type'] = $attributeFields[ $fieldName ]->inputType;
		}

		switch ($attributeFields[ $fieldName ]->inputType) {
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
			/*case 'time':
			case 'datetime':
				break;*/
		}

		if (!isset($la_options['label']) && isset($attributeFields[ $fieldName ])) {
			$la_options['label'] = $attributeFields[ $fieldName ]->label;
		}

		if (!isset($la_options['timezone']) && $la_options['type'] == 'datetime') {
			$ls_timezone = Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone');
			if ($ls_timezone === 'auto') {
				$ls_timezone = $lo_language->timezone;
			}

			$la_options['timezone'] = $ls_timezone;

			$la_options['templateVars']['additionalContent'] ??= '';
			$la_options['templateVars']['additionalContent'] .= '<span class="Timezone">' . $ls_timezone . '</span>';
		}

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
}
