<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Attribute\AttributeOptionsInterface;
use Awyiss\Attribute\AttributeOptionsProvider;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Entity\Language;
use Cake\Collection\Collection;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use RuntimeException;


/**
 * Helper class that provides methods related to the Authorization-logic in the views
 *
 * @property FormHelper $Form
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
	 * Generate a set of controls for `$aa_fields`.
	 *
	 * You can customize individual controls through `$aa_fields`.
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
	 * @param string $as_fieldset The fieldset
	 * @param array $aa_fields An array of customizations for the fields that will be generated.
	 *  This array allows you to set custom types, labels, or other options.
	 * @param array<string, mixed> $aa_options A list of options. Valid keys are:
	 *
	 * - `fieldset` Set to `false` to disable the fieldset. You can also pass an
	 *     array of params to be applied as HTML attributes to the fieldset tag.
	 *     If you pass an empty array, the fieldset will be enabled.
	 * - `legend` Set to `false` to disable the legend for the generated input set.
	 *     Or supply a string to customize the legend text.
	 * - `onlyProvided` Set to true to only output fields that are present in the `$aa_fields`-paramter.
	 *     Otherwise, fields will get merged.
	 * @return string Completed form controls.
	 * @throws \ReflectionException
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function allControls(string $as_fieldset, array $aa_fields = [], array $aa_options = []): string {
		/** @var \Cake\View\Form\EntityContext $lo_context */
		$lo_context = $this->Form->context();
		$ls_source = $aa_options['source'] ?? $lo_context->entity()?->getSource();

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

		if (empty(static::$attributesByFieldset[ $as_fieldset ])) {
			return $ls_emptyField;
		}

		$la_attributeFields = static::$attributesByFieldset[ $as_fieldset ];

		$la_fields = array_merge(Hash::normalize(array_keys($la_attributeFields)), Hash::normalize($aa_fields));
		$la_fields = array_filter($la_fields, function ($ax_value) {
			return $ax_value !== false;
		});

		if (!empty($aa_options['onlyProvided'])) {
			$la_fields = array_intersect_key($la_fields, Hash::normalize($aa_fields));
		}

		if (empty($la_fields)) {
			return $ls_emptyField;
		}

		$la_fields = $this->prepareFields(
			$la_fields,
			static::$attributesByFieldset[ $as_fieldset ],
			static::$attributeOptions[ $ls_source ]
		);


		return $ls_emptyField . $this->Form->controls($la_fields, $aa_options + ['fieldset' => false]);
	}


	/**
	 * @param string $as_fieldName
	 * @param array $aa_options
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function control(string $as_fieldName, array $aa_options = []): string {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$ls_source = $aa_options['source'] ?? $this->Form->context()->entity()->getSource();

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

		if (empty(static::$attributes[ $as_fieldName ])) {
			return $ls_emptyField;
		}

		[$ls_fieldName, $la_options] = $this->prepareField(
			$as_fieldName,
			$aa_options,
			static::$attributes,
			static::$attributeOptions[ $ls_source ]
		);

		$la_options['templateVars']['identifier'] = 'Attributes' . Inflector::camelize($as_fieldName);


		return $ls_emptyField . $this->Form->control($ls_fieldName, $la_options);
	}


	/**
	 * @param string $as_fieldName
	 * @param array $aa_options
	 * @param array $aa_attributeFields
	 * @param AttributeOptionsInterface|null $ao_attributeOptions
	 * @return array
	 * @throws \Exception
	 */
	protected function prepareField(string $as_fieldName, array $aa_options, array $aa_attributeFields, ?AttributeOptionsInterface $ao_attributeOptions): array {
		if (!array_key_exists($as_fieldName, $aa_attributeFields)) {
			return [];
		}

		$la_options = $this->buildOptions($aa_options, $aa_attributeFields, $as_fieldName);

		/**
		 * @var \Awyiss\Model\Entity $lo_entity
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_entity = $this->Form->context()->entity();
		if (!isset($la_options['error']) && $lo_entity->hasErrors() && ($lo_entity->getError('attributes')[ $as_fieldName ] ?? false)) {
			$la_options['error'] = $lo_entity->getError('attributes')[ $as_fieldName ];
		}

		$this->prepareValue($as_fieldName, $la_options);

		if ($ao_attributeOptions) {
			$la_options = $ao_attributeOptions->getAttributeOptions($as_fieldName, $la_options, $this->Form->context());
		}

		$ls_field = $as_fieldName;
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
	 * @param array $aa_fields
	 * @param array $aa_attributeFields
	 * @param AttributeOptionsInterface|null $ao_attributeOptions
	 * @return array
	 * @throws \Exception
	 */
	protected function prepareFields(array $aa_fields, array $aa_attributeFields, ?AttributeOptionsInterface $ao_attributeOptions): array {
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
		foreach ($aa_fields as $ls_fieldName => $la_options) {
			if (Inflector::underscore($ls_fieldName) === $ls_categoryFieldName) {
				continue;
			}

			[$ls_fieldName, $la_options] = $this->prepareField($ls_fieldName, $la_options ?? [], $aa_attributeFields, $ao_attributeOptions);
			$la_fields[ $ls_fieldName ] = $la_options;
		}
		unset($la_options);


		return $la_fields;
	}


	/**
	 * @param string $as_field
	 * @param mixed $aa_options
	 * @return void
	 */
	protected function prepareValue(string $as_field, mixed &$aa_options): void {
		$la_valOptions = [
			'default' => $lx_options['default'] ?? null,
			'schemaDefault' => $lx_options['schemaDefault'] ?? true,
		];

		if (!array_key_exists('val', $aa_options)) {
			$aa_options['val'] = $this->Form->getSourceValue($as_field, $la_valOptions);
		}

		if (!empty($aa_options['val'])) {
			$this->harmonizeValue($aa_options['val'], $aa_options['type']);
		}
	}


	/**
	 * @param mixed $ax_value
	 * @param string $as_type
	 * @return void
	 */
	protected function harmonizeValue(mixed &$ax_value, string $as_type): void {
		if (is_array($ax_value)) {
			if (!in_array($as_type, ['multicheckbox', 'select_multiple', 'custom_select_multiple'])) {
				$ax_value = json_encode($ax_value);
			}
		}
	}


	/**
	 * @param string $as_source
	 * @return array
	 */
	protected function buildAttributes(string $as_source): array {
		/** @var \Cake\View\Form\EntityContext $lo_context */
		$lo_context = $this->Form->context();
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $lo_context->fetchTable($as_source);

		static::$attributes = array_filter($lo_table->getAttributes(), function (Attribute $ao_attribute) {
			return $ao_attribute->active;
		});

		return static::$attributes;
	}


	/**
	 * @param string $as_source
	 * @return void
	 */
	protected function buildAttributesGroupedByFieldset(string $as_source): void {
		$la_attributes = $this->buildAttributes($as_source);

		if (!$la_attributes) {
			static::$attributesByFieldset = [];


			return;
		}

		$la_groupedByFieldset = (new Collection($la_attributes))->combine(
			'identifier',
			function (Attribute $ao_entity) {
				return $ao_entity;
			},
			function (Attribute $ao_entity) {
				return $ao_entity->fieldset;
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

		$la_translatableAttributes = array_map(function ($as_field) {
			return 'attributes.' . $as_field;
		}, $la_translatableAttributes);

		$this->Form->setTranslatableField($la_translatableAttributes);
	}


	/**
	 * @param string $as_fieldName
	 * @param array $aa_options
	 * @return array
	 */
	protected function prepareTranslationField(string $as_fieldName, array &$aa_options): string {
		$ls_field = $as_fieldName;

		if (($aa_options['isTranslation'] ?? false) === true) {
			$lo_language = $aa_options['language'] ?? null;
			if (empty($lo_language) || !($lo_language instanceof Language)) {
				throw new RuntimeException(sprintf('Missing language for translation of `%s`', $ls_field));
			}

			if (in_array($aa_options['type'], ['datetime'])) {
				$aa_options['timezone'] = $lo_language->timezone;
			}

			$ls_field = '_translations.' . $lo_language->shortcode . '.' . $ls_field;
		}
		unset($aa_options['isTranslation'], $aa_options['language']);


		return $ls_field;
	}


	/**
	 * @param array $aa_options
	 * @param array $aa_attributeFields
	 * @param string $as_fieldName
	 * @return array
	 * @throws \Exception
	 */
	protected function buildOptions(array $aa_options, array $aa_attributeFields, string $as_fieldName): array {
		$la_options = $aa_options;
		$lo_language = $la_options['language'] ?? LocaleMiddleware::getLanguage(null);

		if (!isset($la_options['type'])) {
			$la_options['type'] = $aa_attributeFields[ $as_fieldName ]->inputType;
		}

		switch ($aa_attributeFields[ $as_fieldName ]->inputType) {
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

		if (!isset($la_options['label']) && isset($aa_attributeFields[ $as_fieldName ])) {
			$la_options['label'] = $aa_attributeFields[ $as_fieldName ]->label;
		}

		if (!isset($la_options['timezone']) && in_array($la_options['type'], ['datetime'])) {
			$la_options['timezone'] = $lo_language->timezone;
		}

		if (!isset($la_options['required']) || $la_options['required'] !== false) {
			if ($aa_attributeFields[ $as_fieldName ]->required) {
				$la_options['required'] = true;
			}
		}

		/** @var \Awyiss\Utility\Content\AbstractColumn $lo_columnSpan */
		$lo_columnSpan = $aa_attributeFields[ $as_fieldName ]->column['span'];
		if ($lo_columnSpan->getNumerator() !== 12) {
			$la_options['columnSpan'] = $lo_columnSpan->getNumerator();
		}

		return $la_options;
	}
}
