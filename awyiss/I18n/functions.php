<?php declare(strict_types=1);


use Cake\I18n\I18n;


if ( ! function_exists('__')) {
	/**
	 * Returns a translated string if one is found; Otherwise, the submitted message.
	 *
	 * @param string $as_singular Text to translate.
	 * @param mixed ...$args Array with arguments or multiple arguments in function.
	 *
	 * @return string The translated text.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__
	 */
	function __ (string $as_string, ...$aa_args): string {
		if ( ! $as_string) {
			return '';
		}
		if (isset($aa_args[0]) && is_array($aa_args[0])) {
			$aa_args = $aa_args[0];
		}

		$ls_domain = 'default';
		if (strpos($as_string, '::') !== FALSE) {
			$la_parts = explode('::', $as_string);
			$ls_string = array_pop($la_parts);

			if (empty($la_parts[0])) {
				$la_parts[0] = \Cake\Utility\Inflector::underscore(\Cake\Routing\Router::getRequest()->getParam('controller'));
			}

			$ls_domain = implode('/', $la_parts);
		}
		else {
			$ls_string = $as_string;
		}

		$lx_return = I18n::getTranslator($ls_domain)->translate($ls_string, $aa_args);

		if ($lx_return == $ls_string) {
			return $as_string;
		}

		return $lx_return;
	}
}