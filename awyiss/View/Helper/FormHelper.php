<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\View\StringTemplate;
use Cake\Utility\Inflector;
use Cake\View\Form\EntityContext;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;


/**
 * @inheritDoc
 *
 * @property AttributesHelper $Attributes
 * @property HtmlHelper $Html
 * @property LocaleHelper $Locale
 * @property UrlHelper $Url
 */
class FormHelper extends \Cake\View\Helper\FormHelper {
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
	public function __construct (View $ao_view, array $aa_config = []) {
		foreach (LocaleMiddleware::getLanguages() AS $la_languages) {
			foreach ($la_languages AS $lo_language) {
				//If a language already exist, and it's active, do not use another one with the same shortcode.
				if (isset($this->languages[ $lo_language->shortcode ])
					&& $this->languages[ $lo_language->shortcode ]->active
				) {
					continue;
				}

				$this->languages[ $lo_language->shortcode ] = $lo_language;
			}
		}

		parent::__construct($ao_view, $aa_config + [
			'templateClass' => StringTemplate::class,
			'widgets' => [
				'translatableText' => ['TranslatableText'],
			],
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function create ($ax_context = null, array $aa_options = []): string {
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

		/*if ($this->translatableFields) {
			dd($this->translatableFields, $ls_form, __FILE__, __LINE__);
		}*/

		return $ls_form;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Extended version that uses a different default value for the label text, if none was provided.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function label (string $as_fieldName, ?string $as_text = NULL, array $aa_options = []): string {
		$ls_text = $as_text;
		if ($ls_text === NULL) {
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
			$ls_text = __($ls_text);
		}

		return parent::label($as_fieldName, $ls_text, $aa_options);
	}


	/*
	 * {@inheritDoc}
	 *
	 * Change the default behavior to not escape by default
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 *
	public function error (string $as_field, $as_text = NULL, array $aa_options = []): string {
		return parent::error($as_field, $as_text, $aa_options);
		//return parent::error($as_field, $as_text, $aa_options + ['escape' => FALSE]);
	}*/


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function control (string $as_fieldName, array $aa_options = []): string {
		$la_options = $aa_options;
		if (in_array($as_fieldName, $this->translatableFields) && count($this->languages) > 1) {
			/*if ($this->error($as_fieldName)) {
				$la_options['error'] = FALSE;
			}*/

			$ls_association = '';
			$ls_fieldName = $as_fieldName;
			if (str_contains($ls_fieldName, '.') && !str_starts_with($ls_fieldName, '_translations.')) {
				[$ls_association, $ls_fieldName] = explode('.', $ls_fieldName);
				$ls_association .= '.';
			}

			$la_options['realType'] = $la_options['type'] ?? NULL;
			$la_options['type'] = 'translatableText';
			$la_options['val'] = $this->getSourceValue($ls_association . '_translations.' . array_key_first($this->languages) . '.' . $ls_fieldName);

			//If there's no translation for the main language, reset the val.
			//We might need to use the untranslated table value.
			if (empty($la_options['val'])) {
				$la_options['val'] = NULL;
			}
		}

		return parent::control($as_fieldName, $la_options);
	}


	/**
	 * @inheritDoc
	 *
	 * Use "empty => TRUE" as default value for selects. This negates CakePHP's questionable decision to remove
	 * the empty option if a select is required. Usability-wise it's not very clever to show required fields prepopulated.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function select (string $as_fieldName, iterable $ax_options = [], array $aa_attributes = []): string {
		return parent::select($as_fieldName, $ax_options, $aa_attributes + ['empty' => TRUE]);
	}


	/**
	 * @param string $as_fieldName
	 * @param array $aa_options
	 *
	 * @return string
	 *
	 * @noinspection PhpUnused
	 */
	public function translatableText (string $as_fieldName, array $aa_options = []): string {
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
			$la_options['aria-required'] = $la_options['required'] = FALSE;
			$la_options['input'] = $this->widget($ls_realType, $la_options + ['readonly' => TRUE]);

			return $this->widget('translatableText', $la_options);
		}

		$ls_association = '';
		$ls_fieldName = $as_fieldName;
		if (str_contains($ls_fieldName, '.')) {
			[$ls_association, $ls_fieldName] = explode('.', $ls_fieldName);
			$ls_association .= '.';
		}

		//$lo_context = $this->_getContext();
		foreach ($this->languages AS $ls_shortcode => $lo_language) {
			$la_translatableOptions = [
				'aria-required' => $la_options['aria-required'] && !count($la_options['controls']),
				'id' => $this->_domId($as_fieldName . '-Translations[' . $ls_shortcode . ']'),
				'label' => $lo_language->label,
				'required' => $la_options['required'] && !count($la_options['controls']),
				'type' => $ls_realType,
				'val' => $this->getSourceValue($ls_association . '_translations.' . $ls_shortcode . '.' . $ls_fieldName),
			];
			$la_translatableOptions += $aa_options;

			/*if (!count($la_options['controls']) && $lo_context->hasError($as_fieldName)) {
				$la_translatableOptions = $this->addClass($la_translatableOptions, $this->_config['errorClass']);
				//$la_translatableOptions['error'] = $this->error($as_fieldName);
			}*/

			if ($ls_association === 'attributes.') {
				$la_translatableOptions['isTranslation'] = TRUE;
				$la_translatableOptions['language'] = $lo_language;
				$la_options['controls'][] = $this->Attributes->control($ls_fieldName, $la_translatableOptions);
			}
			else {
				$la_options['controls'][] = $this->control($ls_association . '_translations.' . $ls_shortcode . '.' . $ls_fieldName, $la_translatableOptions);
			}
		}


		$la_options['aria-required'] = $la_options['required'] = FALSE;
		$la_options['input'] = $this->widget($ls_realType, $la_options + ['readonly' => TRUE]);

		return $this->widget('translatableText', $la_options);
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $as_fieldName
	 * @param $ax_text
	 * @param array $aa_options
	 *
	 * @return string
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	/*public function error (string $as_fieldName, $ax_text = NULL, array $aa_options = []): string {
		$ls_fieldName = $as_fieldName;

		if (str_contains($ls_fieldName, '_translations.')) {
			$ls_error = parent::error($ls_fieldName, $ax_text, $aa_options);
			if (!empty($ls_error)) {
				return $ls_error;
			}

			$ls_fieldName = preg_replace('/_translations\.[a-z]{2}\./', '', $ls_fieldName);
		}

		return parent::error($ls_fieldName, $ax_text, $aa_options);
	}*/


	/**
	 * Removes a given class string from the given attribute name.
	 *
	 * @param array $aa_options
	 * @param string $as_class
	 * @param string $as_key
	 *
	 * @return array
	 */
	public function removeClass (array $aa_options, string $as_class, string $as_key = 'class'): array {
		$la_options = $aa_options;
		if (isset($la_options[ $as_key ]) && is_array($la_options[ $as_key ])) {
			$ls_key = array_search($as_class, $la_options[ $as_key ]);
			if ($ls_key !== FALSE) {
				unset($la_options[ $as_key ][ $ls_key ]);
			}
		}
		elseif (isset($la_options[ $as_key ]) && trim($la_options[ $as_key ])) {
			$la_parts = explode(' ', $la_options[ $as_key ]);
			$ls_key = array_search($as_class, $la_parts);
			if ($ls_key !== FALSE) {
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
	 * @param string|array $ax_fields
	 * @param $ab_merge
	 *
	 * @return $this
	 */
	public function setTranslatableField (string|array $ax_fields, $ab_merge = TRUE): static {
		if (!$ab_merge) {
			$this->translatableFields = (array)$ax_fields;

			return $this;
		}


		foreach ((array)$ax_fields AS $ls_field) {
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
	protected function _inputContainerTemplate (array $aa_options): string {
		$ls_inputContainerTemplate = $aa_options['options']['type'] . 'Container' . $aa_options['errorSuffix'];
		if ( ! $this->templater()->get($ls_inputContainerTemplate)) {
			$ls_inputContainerTemplate = 'inputContainer' . $aa_options['errorSuffix'];
		}

		$ls_name = substr($aa_options['options']['id'], strpos($aa_options['options']['id'], '-') + 1);

		return $this->formatTemplate($ls_inputContainerTemplate, [
			'content' => $aa_options['content'],
			'error' => $aa_options['error'],
			'required' => $aa_options['options']['required'] ? ' Required' : '',
			'type' => ucfirst($aa_options['options']['type']),
			'templateVars' => ($aa_options['options']['templateVars'] ?? []) + ['identifier' => $ls_name],
		]);
	}


	/**
	 * Generate an ID suitable for use in an ID attribute.
	 *
	 * @param string $as_value The value to convert into an ID.
	 *
	 * @return string The generated id.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _domId (string $as_value): string {
		if (str_contains($as_value, '.')) {
			$la_parts = explode('.', $as_value);
			array_walk($la_parts, function(&$as_part) {
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
