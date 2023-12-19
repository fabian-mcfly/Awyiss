<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


/**
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class FormHelper extends \Cake\View\Helper\FormHelper {
	/**
	 * @inheritDoc
	 */
	public function __construct (\Cake\View\View $view, array $config = []) {
		parent::__construct($view, $config + ['templateClass' => \Awyiss\View\StringTemplate::class,]);
	}


	/**
	 * @inheritDoc
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
				$fieldElements = explode('.', $ls_text);
				$ls_text = array_pop($fieldElements);
			}
			if (str_ends_with($ls_text, '_id')) {
				$ls_text = substr($ls_text, 0, -3);
			}
			$ls_text = __('::' . $ls_text);
		}

		return parent::label($as_fieldName, $ls_text, $aa_options);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function error (string $as_field, $as_text = NULL, array $aa_options = []): string {
		return parent::error($as_field, $as_text, $aa_options + ['escape' => FALSE]);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _inputContainerTemplate (array $aa_options): string {
		$ls_inputContainerTemplate = $aa_options['options']['type'] . 'Container' . $aa_options['errorSuffix'];
		if ( ! $this->templater()->get($ls_inputContainerTemplate)) {
			$ls_inputContainerTemplate = 'inputContainer' . $aa_options['errorSuffix'];
		}

		return $this->formatTemplate($ls_inputContainerTemplate, [
			'content' => $aa_options['content'],
			'error' => $aa_options['error'],
			'required' => $aa_options['options']['required'] ? ' Required' : '',
			'type' => ucfirst($aa_options['options']['type']),
			'templateVars' => $aa_options['options']['templateVars'] ?? [],
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
		$ls_domId = \Cake\Utility\Inflector::camelize($as_value);

		if ($this->_idPrefix) {
			$ls_domId = \Cake\Utility\Inflector::camelize($this->_idPrefix) . '-' . $ls_domId;
		}

		return $ls_domId;
	}
}