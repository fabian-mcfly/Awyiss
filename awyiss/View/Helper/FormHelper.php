<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


/**
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class FormHelper extends \Cake\View\Helper\FormHelper {
	/**
	 * {@inheritDoc}
	 */
	public function error (string $as_field, $as_text = NULL, array $aa_options = []): string {
		return parent::error($as_field, $as_text, $aa_options + ['escape' => FALSE]);
	}


	/**
	 * {@inheritDoc}
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

}