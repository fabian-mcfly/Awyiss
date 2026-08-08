<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Form;
use Awyiss\Utility\Inflector;
use Awyiss\View\StringTemplate;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\View\Form\ContextInterface;
use Cake\View\Form\EntityContext;
use Cake\View\Form\NullContext;
use Cake\View\Helper\FormHelper as BaseFormHelper;
use Cake\View\View;
use RuntimeException;


/**
 * @inheritDoc
 * @property \Awyiss\View\Helper\AttributesHelper $Attributes
 * @property \Awyiss\View\Helper\HtmlHelper $Html
 * @property \Awyiss\View\Helper\MediaHelper $Media
 * @property LocaleHelper $Locale
 * @property UrlHelper $Url
 */
class FormHelper extends BaseFormHelper {
	/**
	 * Other helpers used by FormHelper
	 *
	 * @var array
	 */
	protected array $helpers = ['Attributes', 'Html', 'Locale', 'Media', 'Url'];
	/**
	 * @var array<string, \Awyiss\Model\Entity\Language> List of languages by realm
	 */
	protected array $languages = [];
	/**
	 * @var array<string> List of fields that are translatable
	 */
	protected array $translatableFields = [];
	/**
	 * @var mixed|string The realm for the translations
	 */
	protected string $languageRealm;


	/**
	 * Use the StringTemplate class from the Awyiss namespace
	 * and add the 'translatableText' widget.
	 *
	 * @inheritDoc
	 */
	public function __construct(View $view, array $config = []) {
		parent::__construct(
			$view,
			$config + [
				'templateClass' => StringTemplate::class,
				'widgets' => [
					'inputList' => ['InputList'],
					'inputKeyValueList' => ['InputKeyValueList'],
					'linkSelect' => ['LinkSelect'],
					'translatableText' => ['TranslatableText'],
				],
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function create(mixed $context = null, array $options = []): string {
		// Check if the form is locked
		$lockData = $options['lock'] ?? $this->getView()->get('_lock');

		unset($options['lock']);
		if ($lockData) {
			$options['data-locked-until'] = $lockData['lockedUntil']->format('c');
			$options['data-locked'] = $lockData['isOwnLock'] ? 'false' : 'true';
		}

		$form = parent::create($context, $options);


		// Check if the form is locked
		if ($lockData && !$lockData['isOwnLock']) {
			$form = str_replace(' action="', ' data-action="', $form);
		}

		$context = $this->context();

		if (!is_a($context, EntityContext::class)) {
			return $form;
		}

		$sourceTable = $context->fetchTable($context->entity()->getSource());

		$translateBehavior = $sourceTable->hasBehavior('Translate') ? $sourceTable->getBehavior('Translate') : null;
		$this->translatableFields = $translateBehavior?->getConfig('fields') ?? [];

		$this->languageRealm = $options['languageRealm'] ?? $translateBehavior?->getConfig('realm') ?? Awyiss::REALM_BACKEND;

		foreach (LocaleMiddleware::getLanguages($this->languageRealm) as $language) {
			if (!$language->active) {
				continue;
			}

			$this->languages[ $language->shortcode ] = $language;
		}

		return $form;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extended version that uses a different default value for the label text, if none was provided.
	 */
	public function label(string $fieldName, ?string $text = null, array $options = []): string {
		$text ??= $this->labelTextFromFieldname($fieldName);

		if (!empty($options['class'])) {
			$options['templateVars']['labelClass'] = ' ' . trim($options['class']);
		}
		unset($options['class']);

		return parent::label($fieldName, $text, $options);
	}


	/**
	 * Generates a translated label text based on the field name
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function labelTextFromFieldname(string $fieldName): string {
		$text = $fieldName;

		if (str_ends_with($text, '._ids')) {
			$text = substr($text, 0, -5);
		}

		if (str_contains($text, '.')) {
			$fieldElements = explode('.', $text);
			$text = array_pop($fieldElements);
		}

		$translation = __(Inflector::underscore($text));

		if (!str_contains($translation, '::')) {
			return $translation;
		}

		$context = $this->_getContext();
		if (!$context instanceof EntityContext || !$context->entity()->has('attributes')) {
			return $translation;
		}

		/** @var \Awyiss\Model\Table $table */
		$table = $context->fetchTable($context->entity()->getSource());
		if (!$table->fieldIsAttribute($text)) {
			return $translation;
		}

		return $table->getAttributes()[ $text ]?->title ?? $translation;
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function control(string $fieldName, array $options = []): string {
		unset($options['isCategory']);

		if (!empty($options['columnSpan'])) {
			$options['templateVars']['columnSpan'] = ' ColumnSpan-' . $options['columnSpan'];
		}
		unset($options['columnSpan']);

		if (in_array($fieldName, $this->translatableFields) && count($this->languages) > 1) {
			$association = '';
			$associationFieldName = $fieldName;
			if (str_contains($associationFieldName, '.') && !str_starts_with($associationFieldName, '_translations.')) {
				[$association, $associationFieldName] = explode('.', $associationFieldName);
				$association .= '.';
			}

			$options['realType'] = $options['type'] ?? null;
			$options['type'] = 'translatableText';
			$options['val'] = $this->getSourceValue($association . '_translations.' . array_key_first($this->languages) . '.' . $associationFieldName);

			//If there's no translation for the main language, reset the val.
			//We might need to use the untranslated table value.
			if (empty($options['val'])) {
				$options['val'] = null;
			}
		}

		$options = $this->setTimezoneOptions($fieldName, $options);

		$options['templateVars'] ??= [];
		$options['templateVars']['containerAttrs'] ??= [];
		if (in_array(($options['type'] ?? null), ['inputList', 'inputKeyValueList'])) {
			$options['templateVars']['containerAttrs']['data-list-item-add'] = __('list_item_add');
			$options['templateVars']['containerAttrs']['data-list-item-remove'] = __('list_item_remove');
		}

		if (is_array($options['templateVars']['containerAttrs'] ?? null)) {
			$options['templateVars']['containerAttrs'] = $this->templater()->formatAttributes($options['templateVars']['containerAttrs']);
		}

		return parent::control($fieldName, $options);
	}


	/**
	/**
	 * This negates CakePHP's decision to remove the empty option
	 * if a select is required.
	 * Usability-wise it's not good to show any fields prepopulated as they
	 * get overlooked easier.
	 *
	 * @inheritDoc
	 */
	public function select(string $fieldName, iterable $options = [], array $attributes = []): string {
		return parent::select($fieldName, $options, $attributes + [
			'empty' => !($attributes['multiple'] ?? false),
			'data-filter-placeholder' => __d('System', 'select_filter_placeholder'),
			'data-empty-label' => __d('System', 'select_empty_label'),
		]);
	}


	/**
	 * Creates a link select element using the `linkSelect`-template
	 * with the provided array of links as options.
	 *
	 * ### Options:
	 * - `escape` Boolean value whether to escape html entities.
	 * - `id` The id attribute for the link select element.
	 * - `label` The label to display in the filter.
	 * - `options` An array of links.
	 * - `templateVars` Additional template variables.
	 *
	 * @param string $label
	 * @param array $options An array of links (key = title, value = URL)
	 * @param array $attributes Additional attributes
	 * @return string
	 */
	public function linkSelect(string $label, array $options = [], array $attributes = []): string {
		$attributes += [
			'disabled' => false,
			'escape' => false,
			'id' => true,
			'identifier' => $label,
			'label' => $label,
			'templateVars' => [],
			'val' => null,
		];

		if (isset($attributes['id']) && $attributes['id'] === true) {
			$attributes['id'] = 'LinkSelect-' . Inflector::camelize($this->_domId($label), '-');
		}

		$formattedOptions = [];
		foreach ($options as $title => $link) {
			$formattedOptions[ (string)$title ] = [
				'title'	=> (string)$title,
				'link' => (string)$link,
			];
		}

		$attributes['options'] = $formattedOptions;

		return $this->widget('linkSelect', $attributes);
	}


	/**
	 * Replace the default textarea method to rebuild simple image tags in the field
	 * if the field is an entity context and the data-editor option is set to true.
	 *
	 * @param string $fieldName
	 * @param array $options
	 * @return string
	 */
	public function textarea(string $fieldName, array $options = []): string {
		if (
			($options['data-editor'] ?? null) === true &&
			$this->_getContext() instanceof EntityContext
		) {
			$options['val'] ??= $this->getSourceValue($fieldName);
			if (is_string($options['val'])) {
				/**
				 * @noinspection PhpPossiblePolymorphicInvocationInspection
				 */
				$options['val'] = $this->Media->rebuildSimpleImageTagsInField($this->_getContext()->entity(), $fieldName, $options['val']);
			}
		}

		return parent::textarea($fieldName, $options);
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function translatableText(string $fieldName, array $options = []): string {
		if (!isset($this->languageRealm)) {
			throw new RuntimeException('The language realm is not set. Make sure to call FormHelper::create() before using translatable fields.');
		}

		if ((!in_array($fieldName, $this->translatableFields) || count($this->languages) < 2) && !str_contains($fieldName, '.')) {
			return $this->control($fieldName, $options);
		}

		$widgetOtions = $options;

		$realType = $widgetOtions['realType'] ?? $widgetOtions['type'] ?? null;
		unset($widgetOtions['realType']);
		if ($realType === 'translatableText') {
			unset($realType);
		}

		$widgetOtions['type'] = $realType ?? $this->_inputType($fieldName, $widgetOtions);
		if (!isset($realType)) {
			$realType = $widgetOtions['type'];
		}


		$values = $widgetOtions['values'] ?? null;
		unset($widgetOtions['values']);

		$noValue = false;
		if ($values && !($widgetOtions['val'] ?? null)) {
			$noValue = true;
		}

		$widgetOtions = $this->_initInputField($fieldName, $widgetOtions) + ['controls' => []];
		if ($noValue) {
			unset($widgetOtions['val']);
		}

		if ($this->error($fieldName)) {
			$widgetOtions = $this->removeClass($widgetOtions, $this->templater()->getConfig('errorClass'));
		}

		if (!empty($widgetOtions['controls'])) {
			$widgetOtions['aria-required'] = $widgetOtions['required'] = false;
			$widgetOtions['input'] = $this->widget($realType, $widgetOtions + ['readonly' => true]);

			return $this->widget('translatableText', $widgetOtions);
		}

		$widgetOtions = $this->processMultiLanguageControls($fieldName, $widgetOtions, $options, $realType, $values);

		$widgetOtions['aria-required'] = $widgetOtions['required'] = false;
		$widgetOtions['input'] = $this->widget($realType, $widgetOtions + ['readonly' => true]);

		return $this->widget('translatableText', $widgetOtions);
	}


	/**
	 * Removes a given class string from the given attribute name.
	 *
	 * @param array $options
	 * @param string $class
	 * @param string $attributeName
	 * @return array
	 */
	public function removeClass(array $options, string $class, string $attributeName = 'class'): array {
		if (isset($options[ $attributeName ]) && is_array($options[ $attributeName ])) {
			$key = array_search($class, $options[ $attributeName ]);
			if ($key !== false) {
				unset($options[ $attributeName ][ $key ]);
			}

			$options[ $attributeName ] = array_values($options[ $attributeName ]);
		}
		elseif (isset($options[ $attributeName ]) && trim($options[ $attributeName ])) {
			$parts = explode(' ', $options[ $attributeName ]);
			$key = array_search($class, $parts);
			if ($key !== false) {
				unset($parts[ $key ]);
			}

			$options[ $attributeName ] = implode(' ', $parts);
		}

		if (empty($options[ $attributeName ])) {
			unset($options[ $attributeName ]);
		}


		return $options;
	}


	/**
	 * @param array|string $fields
	 * @param bool $merge
	 * @return $this
	 */
	public function setTranslatableField(string|array $fields, bool $merge = true): static {
		if (!$merge) {
			$this->translatableFields = (array)$fields;

			return $this;
		}

		foreach ((array)$fields as $field) {
			if (!in_array($field, $this->translatableFields)) {
				$this->translatableFields[] = $field;
			}
		}

		return $this;
	}


	/**
	 * Re-implemented 1:1 but
	 * - uses 'Required' instead of 'required' as a class name for required elements. We like our classes uppercase.
	 * - uses `ucparts` for the `type`-option.
	 * - uses the `id`-option as the identifier for the input container.
	 *
	 * @inheritDoc
	 */
	protected function _inputContainerTemplate(array $options): string {
		$inputContainerTemplate = $options['options']['type'] . 'Container' . $options['errorSuffix'];
		if (!$this->templater()->get($inputContainerTemplate)) {
			$inputContainerTemplate = 'inputContainer' . $options['errorSuffix'];
		}

		$name = $options['options']['id'];
		// When the id starts with the entity context's table, we want to remove the prefix (association name) from the id.
		if ($this->context() instanceof EntityContext) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$entity = $this->context()->entity();
			$source = $entity->getSource();

			if (str_starts_with($name, $source . '-')) {
				$name = substr($name, strlen($source) + 1);
			}
			else {
				$source = Inflector::singularize($entity->getSource());

				if (str_starts_with($name, $source . '-')) {
					$name = substr($name, strlen($source) + 1);
				}
			}
		}

		return $this->formatTemplate($inputContainerTemplate, [
			'content' => $options['content'],
			'error' => $options['error'],
			'inputId' => $options['inputId'] ?? '',
			'label' => $options['label'] ?? '',
			'required' => $options['options']['required'] ? ' Required' : '',
			'type' => Inflector::ucparts(Inflector::underscore($options['options']['type']), false),
			'templateVars' => ($options['options']['templateVars'] ?? []) + ['identifier' => $name],
		]);
	}


	/**
	 * Overrides the original method to check if the field
	 * is one of an association and if the associated entity
	 * has an error for the field.
	 *
	 * @inheritDoc
	 */
	public function isFieldError(string $field): bool {
		if (!str_contains($field, '.')) {
			return $this->_getContext()->hasError($field);
		}

		$parts = explode('.', $field);
		$field = array_pop($parts);

		if ($this->_getContext() instanceof NullContext) {
			return false;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$entity = $this->_getContext()->entity();
		$associatedEntity = $entity->get($parts[0]);

		if (!$associatedEntity instanceof EntityInterface) {
			return false;
		}


		return (bool)$associatedEntity->getError($field);
	}


	/**
	 * Overrides the original method to check if the field
	 * is one of an association and if the associated entity
	 * has an error for the field.
	 *
	 * @param string $field
	 * @param array|string|null $text
	 * @param array $options
	 * @return string
	 */
	public function error(string $field, array|string|null $text = null, array $options = []): string {
		if (str_ends_with($field, '._ids')) {
			$field = substr($field, 0, -5);
		}

		$options = $options + ['escape' => true];

		$context = $this->_getContext();
		if (!$context->hasError($field) && !str_contains($field, '.')) {
			return '';
		}

		if (!str_contains($field, '.') || $context->error($field)) {
			$errors = $context->error($field);
		}
		else {
			if ($context instanceof NullContext) {
				return '';
			}

			$parts = explode('.', $field);
			$field = array_pop($parts);
			/**
			 * @var \Awyiss\Model\Entity $entity
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$entity = $context->entity();
			$associatedEntity = $entity->get(implode('.', $parts));

			if (!$associatedEntity instanceof EntityInterface) {
				return '';
			}

			$errors = $associatedEntity->getError($field);
		}

		if (!$errors) {
			return '';
		}

		if (is_array($text)) {
			$tmp = [];
			foreach ($errors as $errorKey => $error) {
				if (isset($text[ $errorKey ])) {
					$tmp[] = $text[ $errorKey ];
				}
				elseif (isset($text[ $error ])) {
					$tmp[] = $text[ $error ];
				}
				else {
					$tmp[] = $error;
				}
			}
			$text = $tmp;
		}

		if ($text !== null) {
			$errors = $text;
		}

		if ($options['escape']) {
			$errors = h($errors);
			unset($options['escape']);
		}

		if (is_array($errors)) {
			if (count($errors) > 1) {
				$errorTexts = [];
				foreach ($errors as $error) {
					$errorTexts[] = $this->formatTemplate('errorItem', ['text' => $error]);
				}
				$errorMessage = $this->formatTemplate('errorList', [
					'content' => implode('', $errorTexts),
				]);
			}
			else {
				$errorMessage = array_pop($errors);
			}
		}

		return $this->formatTemplate('error', [
			'content' => $errorMessage ?? '',
			'id' => $this->_domId($field) . '-error',
			'inputId' => $this->_domId($field),
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param string $position
	 * @return string|null
	 */
	public function renderFormProtection(Form $form, string $position): ?string {
		$return = '';

		foreach ($form->getProtectionMethods() as $protectionMethod) {
			$return .= $protectionMethod->getHtml($position) . PHP_EOL;
		}

		return $return;
	}


	/**
	 * Generate an ID suitable for use in an ID attribute.
	 *
	 * @param string $value The value to convert into an ID.
	 * @return string The generated id.
	 */
	protected function _domId(string $value): string {
		if (str_contains($value, '.')) {
			$parts = explode('.', $value);
			array_walk($parts, function (&$part): void {
				$part = Inflector::camelize($part);
			});
			$domId = implode('-', $parts);
		}
		else {
			$domId = Inflector::camelize($value);
		}

		if ($this->_idPrefix) {
			$domId = Inflector::camelize($this->_idPrefix) . '-' . $domId;
		}


		return $domId;
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return array
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	protected function setTimezoneOptions(string $fieldName, array $options): array {
		if (
			($options['type'] ?? null) !== 'datetime' &&
			$this->_inputType($fieldName, $options) !== 'datetime'
		) {
			return $options;
		}

		$timezone = ($options['timezone'] ?? null) ?: Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone') ?: date_default_timezone_get(); // phpcs:ignore

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
	 * Processes input parameters to generate options for translatable form controls
	 * for various languages, including handling associations and specific field names.
	 *
	 * @param string $fieldName The name of the field being processed.
	 * @param array $options Additional options for configuring the controls.
	 * @param array $baseOptions Basic options to be merged into each language-specific configuration.
	 * @param string $realType The type of the field (e.g., text, number).
	 * @param array|null $values An array of values that may include language-specific translations.
	 * @return array Modified options array, including controls for multiple languages.
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function processMultiLanguageControls(string $fieldName, array $options, array $baseOptions, string $realType, ?array $values = null): array {
		$options += [
			'placeholder' => null,
			'required' => null,
		];

		$association = '';

		// Strip off the association name from the field name if it exists.
		$plainFieldName = $fieldName;
		if (str_contains($plainFieldName, '.')) {
			$parts = explode('.', $plainFieldName);
			$plainFieldName = array_pop($parts);

			$association = implode('.', $parts);
			$association .= '.';
		}

		$userLanguage = LocaleMiddleware::getLanguage($this->languageRealm);

		foreach ($this->languages as $shortcode => $language) {
			$value = false;
			if (isset($values)) {
				$value = $values[ $shortcode ] ?? null;
				if ($value instanceof EntityInterface) {
					$value = $value->get($plainFieldName);
				}
			}

			$translatableOptions = [
				'id' => $this->_domId($fieldName . '-Translations[' . $shortcode . ']'),
				'label' => $language->label,
				'placeholder' => $options['placeholder'] ?? $options['val'] ?? null,
				'required' => $options['required'] && !count($options['controls']),
				'type' => $realType,
				'val' => ($value !== false ? $value : $this->getSourceValue($association . '_translations.' . $shortcode . '.' . $plainFieldName)) ?? '',
			];
			$translatableOptions += $baseOptions;
			unset($translatableOptions['values']);

			if ($userLanguage->shortcode === $shortcode) {
				// If the user's language is the same as the current language, add a class to highlight it.
				$translatableOptions['templateVars']['containerClass'] = ' IsCurrentLanguage';
			}

			if ($association === 'attributes.') {
				$translatableOptions['isTranslation'] = true;
				$translatableOptions['language'] = $language;
				$options['controls'][] = $this->Attributes->control($plainFieldName, $translatableOptions);
			}
			else {
				$options['controls'][] = $this->control($association . '_translations.' . $shortcode . '.' . $plainFieldName, $translatableOptions);
			}
		}

		return $options;
	}


	/**
	 * @param \Cake\View\Form\ContextInterface|\Cake\Datasource\EntityInterface|null $context
	 * @return \Cake\View\Form\ContextInterface
	 */
	public function context(ContextInterface|EntityInterface|null $context = null): ContextInterface {
		if ($context instanceof EntityInterface) {
			$context = $this->_getContext(['entity' => $context]);
		}

		return parent::context($context);
	}
}
