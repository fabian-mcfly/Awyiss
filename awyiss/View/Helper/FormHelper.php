<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\View\StringTemplate;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Inflector;
use Cake\View\Form\EntityContext;
use Cake\View\Helper\FormHelper as BaseFormHelper;
use Cake\View\View;


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
	protected array $languages = [];
	protected array $translatableFields = [];


	/**
	 * @inheritDoc
	 */
	public function __construct(View $ao_view, array $aa_config = []) {
		foreach (LocaleMiddleware::getLanguages() as $la_languages) {
			foreach ($la_languages as $lo_language) {
				//If a language already exist, and it's active, do not use another one with the same shortcode.
				if (isset($this->languages[ $lo_language->shortcode ]) && $this->languages[ $lo_language->shortcode ]->active) {
					continue;
				}

				$this->languages[ $lo_language->shortcode ] = $lo_language;
			}
		}

		parent::__construct(
			$ao_view,
			$aa_config + [
				'templateClass' => StringTemplate::class,
				'widgets' => [
					'translatableText' => ['TranslatableText'],
				],
			]
		);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function create(mixed $ax_context = null, array $aa_options = []): string {
		$ls_form = parent::create($ax_context, $aa_options);

		$lo_context = $this->context();
		if (!is_a($lo_context, EntityContext::class)) {
			return $ls_form;
		}

		$lo_sourceTable = $lo_context->fetchTable($lo_context->entity()->getSource());
		if (!$lo_sourceTable->hasBehavior('Translate')) {
			return $ls_form;
		}

		$this->translatableFields = $lo_sourceTable->getBehavior('Translate')->getConfig('fields');


		return $ls_form;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extended version that uses a different default value for the label text, if none was provided.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function label(string $as_fieldName, ?string $as_text = null, array $aa_options = []): string {
		$ls_text = $as_text;
		if ($ls_text === null) {
			$ls_text = $this->labelTextFromFieldname($as_fieldName);
		}


		return parent::label($as_fieldName, $ls_text, $aa_options);
	}


	/**
	 * Generates a translated label text based on the fieldname
	 *
	 * @param string $as_fieldName
	 * @return string
	 */
	public function labelTextFromFieldname(string $as_fieldName): string {
		$ls_text = $as_fieldName;
		if (str_ends_with($ls_text, '._ids')) {
			$ls_text = substr($ls_text, 0, -5);
		}
		if (str_contains($ls_text, '.')) {
			$ls_fieldElements = explode('.', $ls_text);
			$ls_text = array_pop($ls_fieldElements);
		}
		if (str_ends_with($ls_text, '_id')) {
			$ls_text = substr($ls_text, 0, -3);
		}


		return __($ls_text);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function control(string $as_fieldName, array $aa_options = []): string {
		$la_options = $aa_options;

		unset($la_options['isCategory']);

		if (isset($la_options['columnSpan'])) {
			$la_options['templateVars']['columnSpan'] = ' ColumnSpan-' . $la_options['columnSpan'];
			unset($la_options['columnSpan']);
		}

		if (in_array($as_fieldName, $this->translatableFields) && count($this->languages) > 1) {
			$ls_association = '';
			$ls_fieldName = $as_fieldName;
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


		return parent::control($as_fieldName, $la_options);
	}


	/**
	 * @inheritDoc
	 *
	 * Use "empty => true" as default value for selects. This negates CakePHP's questionable decision to remove
	 * the empty option if a select is required. Usability-wise it's not very clever to show required fields prepopulated.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function select(string $as_fieldName, iterable $ax_options = [], array $aa_attributes = []): string {
		return parent::select($as_fieldName, $ax_options, $aa_attributes + ['empty' => true]);
	}


	/**
	 * @param string $as_fieldName
	 * @param array $aa_options
	 * @return string
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public function translatableText(string $as_fieldName, array $aa_options = []): string {
		$la_options = $aa_options;

		$ls_realType = $la_options['realType'] ?? $la_options['type'];
		unset($la_options['realType']);
		if ($ls_realType === 'translatableText') {
			unset($ls_realType);
		}

		$la_options['type'] = $ls_realType ?? $this->_inputType($as_fieldName, $la_options);
		if (!isset($ls_realType)) {
			$ls_realType = $la_options['type'];
		}

		$la_options = $this->_initInputField($as_fieldName, $la_options) + ['controls' => []];

		if ($this->error($as_fieldName)) {
			$la_options = $this->removeClass($la_options, $this->_config['errorClass']);
		}

		if (!empty($la_options['controls'])) {
			$la_options['aria-required'] = $la_options['required'] = false;
			$la_options['input'] = $this->widget($ls_realType, $la_options + ['readonly' => true]);


			return $this->widget('translatableText', $la_options);
		}

		$ls_association = '';
		$ls_fieldName = $as_fieldName;
		if (str_contains($ls_fieldName, '.')) {
			[$ls_association, $ls_fieldName] = explode('.', $ls_fieldName);
			$ls_association .= '.';
		}

		$lo_userLanguage = LocaleMiddleware::getLanguage(null);

		foreach ($this->languages as $ls_shortcode => $lo_language) {
			$la_translatableOptions = [
				'aria-required' => $la_options['aria-required'] && !count($la_options['controls']),
				'id' => $this->_domId($as_fieldName . '-Translations[' . $ls_shortcode . ']'),
				'label' => $lo_language->label,
				'placeholder' => $la_options['placeholder'] ?? $la_options['val'] ?? null,
				'required' => $la_options['required'] && !count($la_options['controls']),
				'type' => $ls_realType,
				'val' => $this->getSourceValue($ls_association . '_translations.' . $ls_shortcode . '.' . $ls_fieldName),
			];
			$la_translatableOptions += $aa_options;

			if ($lo_userLanguage->shortcode === $ls_shortcode) {
				// If the user's language is the same as the current language, add a class to highlight it.
				$la_translatableOptions['templateVars']['containerClass'] = ' IsCurrentLanguage';
			}

			/*if (!count($la_options['controls']) && $lo_context->hasError($as_fieldName)) {
				$la_translatableOptions = $this->addClass($la_translatableOptions, $this->_config['errorClass']);
				//$la_translatableOptions['error'] = $this->error($as_fieldName);
			}*/

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
	 * @param array $aa_options
	 * @param string $as_class
	 * @param string $as_key
	 * @return array
	 */
	public function removeClass(array $aa_options, string $as_class, string $as_key = 'class'): array {
		$la_options = $aa_options;
		if (isset($la_options[ $as_key ]) && is_array($la_options[ $as_key ])) {
			$ls_key = array_search($as_class, $la_options[ $as_key ]);
			if ($ls_key !== false) {
				unset($la_options[ $as_key ][ $ls_key ]);
			}
		}
		elseif (isset($la_options[ $as_key ]) && trim($la_options[ $as_key ])) {
			$la_parts = explode(' ', $la_options[ $as_key ]);
			$ls_key = array_search($as_class, $la_parts);
			if ($ls_key !== false) {
				unset($la_parts[ $ls_key ]);
			}

			$la_options[ $as_key ] = implode(' ', $la_parts);
		}

		if (empty($la_options[ $as_key ])) {
			unset($la_options[ $as_key ]);
		}


		return $la_options;
	}


	/**
	 * @param array|string $ax_fields
	 * @param bool $ab_merge
	 * @return $this
	 */
	public function setTranslatableField(string|array $ax_fields, bool $ab_merge = true): static {
		if (!$ab_merge) {
			$this->translatableFields = (array)$ax_fields;


			return $this;
		}


		foreach ((array)$ax_fields as $ls_field) {
			if (!in_array($ls_field, $this->translatableFields)) {
				$this->translatableFields[] = $ls_field;
			}
		}


		return $this;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented 1:1 but
	 * - uses 'Required' instead of 'required' as a class name for required elements. We like our classes uppercase.
	 * - uses `ucfirst` for the `type`-option
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _inputContainerTemplate(array $aa_options): string {
		$ls_inputContainerTemplate = $aa_options['options']['type'] . 'Container' . $aa_options['errorSuffix'];
		if (!$this->templater()->get($ls_inputContainerTemplate)) {
			$ls_inputContainerTemplate = 'inputContainer' . $aa_options['errorSuffix'];
		}

		$ls_name = $aa_options['options']['id'];
		$li_dashPos = strpos($ls_name, '-');
		if ($li_dashPos !== false) {
			$ls_name = substr($ls_name, $li_dashPos + 1);
		}


		return $this->formatTemplate($ls_inputContainerTemplate, [
			'content' => $aa_options['content'],
			'error' => $aa_options['error'],
			'required' => $aa_options['options']['required'] ? ' Required' : '',
			'type' => ucfirst($aa_options['options']['type']),
			'templateVars' => ($aa_options['options']['templateVars'] ?? []) + ['identifier' => $ls_name],
		]);
	}


	/**
	 * @param string $field
	 * @return bool
	 */
	public function isFieldError(string $field): bool {
		if (!str_contains($field, '.')) {
			return $this->_getContext()->hasError($field);
		}

		$la_parts = explode('.', $field);
		$ls_field = array_pop($la_parts);

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_entity = $this->_getContext()->entity();
		$lo_associatedEntity = $lo_entity->get($la_parts[0]);

		if (!$lo_associatedEntity instanceof EntityInterface) {
			return false;
		}


		return (bool)$lo_associatedEntity->getError($ls_field);
	}


	/**
	 * @param string $as_field
	 * @param array|string|null $ax_text
	 * @param array $aa_options
	 * @return string
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function error(string $as_field, array|string|null $ax_text = null, array $aa_options = []): string {
		$ls_field = $as_field;
		if (str_ends_with($ls_field, '._ids')) {
			$ls_field = substr($ls_field, 0, -5);
		}

		$la_options = $aa_options + ['escape' => true];

		$lo_context = $this->_getContext();
		if (!$lo_context->hasError($ls_field) && !str_contains($ls_field, '.')) {
			return '';
		}

		if (!str_contains($ls_field, '.')) {
			$la_error = $lo_context->error($ls_field);
		}
		else {
			$la_parts = explode('.', $ls_field);
			$ls_field = array_pop($la_parts);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_entity = $lo_context->entity();
			$lo_associatedEntity = $lo_entity->get($la_parts[0]);

			if (!$lo_associatedEntity instanceof EntityInterface) {
				return '';
			}

			$la_error = $lo_associatedEntity->getError($ls_field);
		}

		if (!$la_error) {
			return '';
		}

		$lx_text = $ax_text;
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
	 * Generate an ID suitable for use in an ID attribute.
	 *
	 * @param string $as_value The value to convert into an ID.
	 * @return string The generated id.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _domId(string $as_value): string {
		if (str_contains($as_value, '.')) {
			$la_parts = explode('.', $as_value);
			array_walk($la_parts, function (&$as_part): void {
				$as_part = Inflector::camelize($as_part);
			});
			$ls_domId = implode('-', $la_parts);
		}
		else {
			$ls_domId = Inflector::camelize($as_value);
		}


		if ($this->_idPrefix) {
			$ls_domId = Inflector::camelize($this->_idPrefix) . '-' . $ls_domId;
		}


		return $ls_domId;
	}
}
