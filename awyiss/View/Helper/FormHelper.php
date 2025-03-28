<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Form;
use Awyiss\Utility\Inflector;
use Awyiss\View\StringTemplate;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\View\Form\EntityContext;
use Cake\View\Form\NullContext;
use Cake\View\Helper\FormHelper as BaseFormHelper;
use Cake\View\View;
use RuntimeException;


/**
 * @inheritDoc
 * @property AttributesHelper $Attributes
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property LocaleHelper $Locale
 * @property UrlHelper $Url
 */
class FormHelper extends BaseFormHelper {
	/**
	 * Other helpers used by FormHelper
	 *
	 * @var array
	 */
	protected array $helpers = ['Attributes', 'Html', 'Locale', 'Url'];
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
					'translatableText' => ['TranslatableText'],
				],
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function create(mixed $context = null, array $options = []): string {
		$ls_form = parent::create($context, $options);

		$lo_context = $this->context();
		if (!is_a($lo_context, EntityContext::class)) {
			return $ls_form;
		}

		$lo_sourceTable = $lo_context->fetchTable($lo_context->entity()->getSource());

		$lo_behavior = $lo_sourceTable->hasBehavior('Translate') ? $lo_sourceTable->getBehavior('Translate') : null;
		$this->translatableFields = $lo_behavior?->getConfig('fields') ?? [];
		$this->languageRealm = $options['languageRealm'] ?? $lo_behavior?->getConfig('realm') ?? Awyiss::REALM_BACKEND;

		foreach (LocaleMiddleware::getLanguages($this->languageRealm) as $lo_language) {
			if (!$lo_language->active) {
				continue;
			}

			$this->languages[ $lo_language->shortcode ] = $lo_language;
		}

		return $ls_form;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extended version that uses a different default value for the label text, if none was provided.
	 */
	public function label(string $fieldName, ?string $text = null, array $options = []): string {
		$ls_text = $text;
		if ($ls_text === null) {
			$ls_text = $this->labelTextFromFieldname($fieldName);
		}

		$la_options = $options;
		if (isset($la_options['class'])) {
			$la_options['templateVars']['labelClass'] = ' ' . trim($la_options['class']);
			unset($la_options['class']);
		}

		return parent::label($fieldName, $ls_text, $la_options);
	}


	/**
	 * Generates a translated label text based on the field name
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function labelTextFromFieldname(string $fieldName): string {
		$ls_text = $fieldName;

		if (str_ends_with($ls_text, '._ids')) {
			$ls_text = substr($ls_text, 0, -5);
		}

		if (str_contains($ls_text, '.')) {
			$ls_fieldElements = explode('.', $ls_text);
			$ls_text = array_pop($ls_fieldElements);
		}

		return __($ls_text);
	}


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function control(string $fieldName, array $options = []): string {
		$la_options = $options;

		unset($la_options['isCategory']);

		if (isset($la_options['columnSpan'])) {
			$la_options['templateVars']['columnSpan'] = ' ColumnSpan-' . $la_options['columnSpan'];
			unset($la_options['columnSpan']);
		}

		if (in_array($fieldName, $this->translatableFields) && count($this->languages) > 1) {
			$ls_association = '';
			$ls_fieldName = $fieldName;
			if (str_contains($ls_fieldName, '.') && !str_starts_with($ls_fieldName, '_translations.')) {
				[$ls_association, $ls_fieldName] = explode('.', $ls_fieldName);
				$ls_association .= '.';
			}

			$la_options['realType'] = $la_options['type'] ?? null;
			$la_options['type'] = 'translatableText';
			$la_options['val'] = $this->getSourceValue($ls_association . '_translations.' . array_key_first($this->languages) . '.' . $ls_fieldName);

			//If there's no translation for the main language, reset the val.
			//We might need to use the untranslated table value.
			if (empty($la_options['val'])) {
				$la_options['val'] = null;
			}
		}

		$la_options = $this->setTimezoneOptions($fieldName, $la_options);

		return parent::control($fieldName, $la_options);
	}


	/**
	 * Use "empty => true" as default value for selects (if not multiple).
	 * This negates CakePHP's questionable decision to remove
	 * the empty option if a select is required. Usability-wise it's not very clever to show required fields prepopulated.
	 *
	 * @inheritDoc
	 */
	public function select(string $fieldName, iterable $options = [], array $attributes = []): string {
		return parent::select($fieldName, $options, $attributes + ['empty' => !($attributes['multiple'] ?? false)]);
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function translatableText(string $fieldName, array $options = []): string {
		if (!isset($this->languageRealm)) {
			throw new RuntimeException('The language realm is not set. Make sure to call FormHelper::create() before using translatable fields.');
		}

		if ((!in_array($fieldName, $this->translatableFields) || count($this->languages) < 2) && !str_contains($fieldName, '.')) {
			return $this->control($fieldName, $options);
		}

		$la_options = $options;

		$ls_realType = $la_options['realType'] ?? $la_options['type'];
		unset($la_options['realType']);
		if ($ls_realType === 'translatableText') {
			unset($ls_realType);
		}

		$la_options['type'] = $ls_realType ?? $this->_inputType($fieldName, $la_options);
		if (!isset($ls_realType)) {
			$ls_realType = $la_options['type'];
		}

		$la_options = $this->_initInputField($fieldName, $la_options) + ['controls' => []];

		if ($this->error($fieldName)) {
			$la_options = $this->removeClass($la_options, $this->_config['errorClass']);
		}

		if (!empty($la_options['controls'])) {
			$la_options['aria-required'] = $la_options['required'] = false;
			$la_options['input'] = $this->widget($ls_realType, $la_options + ['readonly' => true]);

			return $this->widget('translatableText', $la_options);
		}

		$ls_association = '';
		$ls_fieldName = $fieldName;
		if (str_contains($ls_fieldName, '.')) {
			$la_parts = explode('.', $ls_fieldName);
			$ls_fieldName = array_pop($la_parts);

			$ls_association = implode('.', $la_parts);
			$ls_association .= '.';
		}

		$lo_userLanguage = LocaleMiddleware::getLanguage($this->languageRealm);

		foreach ($this->languages as $ls_shortcode => $lo_language) {
			$la_translatableOptions = [
				'id' => $this->_domId($fieldName . '-Translations[' . $ls_shortcode . ']'),
				'label' => $lo_language->label,
				'placeholder' => $la_options['placeholder'] ?? $la_options['val'] ?? null,
				'required' => $la_options['required'] && !count($la_options['controls']),
				'type' => $ls_realType,
				'val' => $this->getSourceValue($ls_association . '_translations.' . $ls_shortcode . '.' . $ls_fieldName),
			];
			$la_translatableOptions += $options;

			if ($lo_userLanguage->shortcode === $ls_shortcode) {
				// If the user's language is the same as the current language, add a class to highlight it.
				$la_translatableOptions['templateVars']['containerClass'] = ' IsCurrentLanguage';
			}

			if ($ls_association === 'attributes.') {
				$la_translatableOptions['isTranslation'] = true;
				$la_translatableOptions['language'] = $lo_language;
				$la_options['controls'][] = $this->Attributes->control($ls_fieldName, $la_translatableOptions);
			}
			else {
				$la_options['controls'][] = $this->control($ls_association . '_translations.' . $ls_shortcode . '.' . $ls_fieldName, $la_translatableOptions);
			}
		}


		$la_options['aria-required'] = $la_options['required'] = false;
		$la_options['input'] = $this->widget($ls_realType, $la_options + ['readonly' => true]);


		return $this->widget('translatableText', $la_options);
	}


	/**
	 * Removes a given class string from the given attribute name.
	 *
	 * @param array $options
	 * @param string $class
	 * @param string $key
	 * @return array
	 */
	public function removeClass(array $options, string $class, string $key = 'class'): array {
		$la_options = $options;
		if (isset($la_options[ $key ]) && is_array($la_options[ $key ])) {
			$ls_key = array_search($class, $la_options[ $key ]);
			if ($ls_key !== false) {
				unset($la_options[ $key ][ $ls_key ]);
			}

			$la_options[ $key ] = array_values($la_options[ $key ]);
		}
		elseif (isset($la_options[ $key ]) && trim($la_options[ $key ])) {
			$la_parts = explode(' ', $la_options[ $key ]);
			$ls_key = array_search($class, $la_parts);
			if ($ls_key !== false) {
				unset($la_parts[ $ls_key ]);
			}

			$la_options[ $key ] = implode(' ', $la_parts);
		}

		if (empty($la_options[ $key ])) {
			unset($la_options[ $key ]);
		}


		return $la_options;
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

		foreach ((array)$fields as $ls_field) {
			if (!in_array($ls_field, $this->translatableFields)) {
				$this->translatableFields[] = $ls_field;
			}
		}

		return $this;
	}


	/**
	 * Re-implemented 1:1 but
	 * - uses 'Required' instead of 'required' as a class name for required elements. We like our classes uppercase.
	 * - uses `ucparts` for the `type`-option
	 *
	 * @inheritDoc
	 */
	protected function _inputContainerTemplate(array $options): string {
		$ls_inputContainerTemplate = $options['options']['type'] . 'Container' . $options['errorSuffix'];
		if (!$this->templater()->get($ls_inputContainerTemplate)) {
			$ls_inputContainerTemplate = 'inputContainer' . $options['errorSuffix'];
		}

		$ls_name = $options['options']['id'];
		$li_dashPos = strpos($ls_name, '-');
		// When the id contains a dash, we want to remove the prefix (association name) from the id.
		if ($li_dashPos !== false) {
			$ls_name = substr($ls_name, $li_dashPos + 1);
		}

		return $this->formatTemplate($ls_inputContainerTemplate, [
			'content' => $options['content'],
			'error' => $options['error'],
			'required' => $options['options']['required'] ? ' Required' : '',
			'type' => Inflector::ucparts(Inflector::underscore($options['options']['type']), false),
			'templateVars' => ($options['options']['templateVars'] ?? []) + ['identifier' => $ls_name],
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

		$la_parts = explode('.', $field);
		$ls_field = array_pop($la_parts);

		if ($this->_getContext() instanceof NullContext) {
			return false;
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_entity = $this->_getContext()->entity();
		$lo_associatedEntity = $lo_entity->get($la_parts[0]);

		if (!$lo_associatedEntity instanceof EntityInterface) {
			return false;
		}


		return (bool)$lo_associatedEntity->getError($ls_field);
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
		$ls_field = $field;
		if (str_ends_with($ls_field, '._ids')) {
			$ls_field = substr($ls_field, 0, -5);
		}

		$la_options = $options + ['escape' => true];

		$lo_context = $this->_getContext();
		if (!$lo_context->hasError($ls_field) && !str_contains($ls_field, '.')) {
			return '';
		}

		if (!str_contains($ls_field, '.') || $lo_context->error($ls_field)) {
			$la_error = $lo_context->error($ls_field);
		}
		else {
			if ($lo_context instanceof NullContext) {
				return '';
			}

			$la_parts = explode('.', $ls_field);
			$ls_field = array_pop($la_parts);
			/**
			 * @var \Awyiss\Model\Entity $lo_entity
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$lo_entity = $lo_context->entity();
			$lo_associatedEntity = $lo_entity->get(implode('.', $la_parts));

			if (!$lo_associatedEntity instanceof EntityInterface) {
				return '';
			}

			$la_error = $lo_associatedEntity->getError($ls_field);
		}

		if (!$la_error) {
			return '';
		}

		$lx_text = $text;
		if (is_array($lx_text)) {
			$la_tmp = [];
			foreach ($la_error as $lx_errorKey => $ls_error) {
				if (isset($lx_text[ $lx_errorKey ])) {
					$la_tmp[] = $lx_text[ $lx_errorKey ];
				}
				elseif (isset($lx_text[ $ls_error ])) {
					$la_tmp[] = $lx_text[ $ls_error ];
				}
				else {
					$la_tmp[] = $ls_error;
				}
			}
			$lx_text = $la_tmp;
		}

		if ($lx_text !== null) {
			$la_error = $lx_text;
		}

		if ($la_options['escape']) {
			$la_error = h($la_error);
			unset($la_options['escape']);
		}

		if (is_array($la_error)) {
			if (count($la_error) > 1) {
				$la_errorTexts = [];
				foreach ($la_error as $ls_error) {
					$la_errorTexts[] = $this->formatTemplate('errorItem', ['text' => $ls_error]);
				}
				$ls_error = $this->formatTemplate('errorList', [
					'content' => implode('', $la_errorTexts),
				]);
			}
			else {
				$ls_error = array_pop($la_error);
			}
		}

		return $this->formatTemplate('error', [
			'content' => $ls_error ?? '',
			'id' => $this->_domId($ls_field) . '-error',
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Form $form
	 * @param string $position
	 * @return string|null
	 */
	public function renderFormProtection(Form $form, string $position): ?string {
		$ls_return = '';

		foreach ($form->getProtectionMethods() as $lo_protectionMethod) {
			$ls_return .= $lo_protectionMethod->getHtml($position) . PHP_EOL;
		}

		return $ls_return;
	}


	/**
	 * Generate an ID suitable for use in an ID attribute.
	 *
	 * @param string $value The value to convert into an ID.
	 * @return string The generated id.
	 */
	protected function _domId(string $value): string {
		if (str_contains($value, '.')) {
			$la_parts = explode('.', $value);
			array_walk($la_parts, function (&$part): void {
				/** @noinspection PhpVariableNamingConventionInspection */
				$part = Inflector::camelize($part);
			});
			$ls_domId = implode('-', $la_parts);
		}
		else {
			$ls_domId = Inflector::camelize($value);
		}


		if ($this->_idPrefix) {
			$ls_domId = Inflector::camelize($this->_idPrefix) . '-' . $ls_domId;
		}


		return $ls_domId;
	}


	/**
	 * @param string $fieldName
	 * @param array $options
	 * @return array
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	protected function setTimezoneOptions(string $fieldName, array $options): array {
		$la_options = $options;

		if (
			($la_options['type'] ?? null) !== 'datetime' &&
			$this->_inputType($fieldName, $la_options) !== 'datetime'
		) {
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
}
