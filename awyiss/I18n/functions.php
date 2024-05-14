<?php declare(strict_types=1);


use Awyiss\Routing\Router;
use Cake\I18n\I18n;
use Cake\Utility\Inflector;


if (!function_exists('__')) {
	/**
	 * Returns a translated string if one is found; Otherwise, the submitted message.
	 *
	 * @param string $as_string
	 * @param mixed ...$aa_args
	 * @return string The translated text.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __(string $as_string, mixed ...$aa_args): string {
		if (!$as_string) {
			return '';
		}

		$la_args = $aa_args;
		if (isset($aa_args[0]) && is_array($aa_args[0])) {
			$la_args = $aa_args[0];
		}

		$ls_controller = Router::getRequest()?->getParam('controller');
		if ($ls_controller) {
			$ls_controller = Inflector::underscore(Router::getRequest()->getParam('controller'));

			$ls_return = __d($ls_controller, $as_string, $la_args);
		}
		else {
			$ls_return = I18n::getTranslator('system')->translate($as_string, $la_args);
		}


		return $ls_return;
	}
}


if (!function_exists('__d')) {
	/**
	 * Allows you to override the current domain for a single message lookup.
	 *
	 * @param string $as_domain Domain.
	 * @param string $as_string String to translate.
	 * @param mixed ...$aa_args
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__d
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __d(string $as_domain, string $as_string, mixed ...$aa_args): string {
		if (!$as_string) {
			return '';
		}

		$la_args = $aa_args;
		if (isset($aa_args[0]) && is_array($aa_args[0])) {
			$la_args = $aa_args[0];
		}

		$ls_return = I18n::getTranslator($as_domain)->translate($as_string, $la_args);

		if ($ls_return === $as_string || empty($ls_return)) {
			$ls_return = $as_domain . '::' . $as_string;

			// Fallback to system domain
			if ($as_domain !== 'system') {
				$ls_fallback = I18n::getTranslator('system')->translate($as_string, $la_args);

				if ($ls_fallback !== $as_string && !empty($ls_fallback)) {
					$ls_return = $ls_fallback;
				}
			}
		}


		return $ls_return;
	}
}


if (!function_exists('__df')) {
	/**
	 * Allows you to override the current domain for a single message lookup.
	 * If no translation for the given domain can be found, a fallback domain will be used
	 *
	 * @param string $as_domain
	 * @param string $as_fallbackDomain
	 * @param string $as_string
	 * @param mixed ...$aa_args
	 * @return string The translated text.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __df(string $as_domain, string $as_fallbackDomain, string $as_string, mixed ...$aa_args): string {
		if (!$as_string) {
			return '';
		}

		$la_args = $aa_args;
		if (isset($aa_args[0]) && is_array($aa_args[0])) {
			$la_args = $aa_args[0];
		}

		$ls_return = I18n::getTranslator($as_domain)->translate($as_string, $la_args);

		if ($ls_return === $as_string || empty($ls_return)) {
			$ls_return = I18n::getTranslator($as_fallbackDomain)->translate($as_string, $la_args);
		}

		if ($ls_return === $as_string || empty($ls_return)) {
			$ls_return = $as_domain . '::' . $as_string;
		}


		return $ls_return;
	}
}


if (!function_exists('__dx')) {
	/**
	 * Allows you to override the current domain for a single message lookup.
	 * The context is a unique identifier for the translations string that makes it unique
	 * within the same domain.
	 *
	 * @param string $as_domain
	 * @param string $as_context
	 * @param string $as_string
	 * @param mixed ...$aa_args
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__dx
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __dx(string $as_domain, string $as_context, string $as_string, mixed ...$aa_args): string {
		if (!$as_string) {
			return '';
		}

		$la_args = $aa_args;
		if (isset($aa_args[0]) && is_array($aa_args[0])) {
			$la_args = $aa_args[0];
		}


		return __d($as_domain, $as_string, ['_context' => $as_context] + $la_args);
	}
}


if (!function_exists('__dfx')) {
	/**
	 * Allows you to override the current domain for a single message lookup.
	 * If no translation for the given domain can be found, a fallbackdomain will be used
	 * The context is a unique identifier for the translations string that makes it unique
	 * within the same domain.
	 *
	 * @param string $as_domain
	 * @param string $as_fallbackDomain
	 * @param string $as_context
	 * @param string $as_string
	 * @param mixed ...$aa_args
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__dx
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __dfx(string $as_domain, string $as_fallbackDomain, string $as_context, string $as_string, mixed ...$aa_args): string {
		if (!$as_string) {
			return '';
		}

		$la_args = $aa_args;
		if (isset($aa_args[0]) && is_array($aa_args[0])) {
			$la_args = $aa_args[0];
		}


		return __df($as_domain, $as_fallbackDomain, $as_string, ['_context' => $as_context] + $la_args);
	}
}


if (!function_exists('__x')) {
	/**
	 * Returns a translated string if one is found; Otherwise, the submitted message.
	 * The context is a unique identifier for the translations string that makes it unique
	 * within the same domain.
	 *
	 * @param string $as_context Context of the text.
	 * @param string $as_string Text to translate.
	 * @param mixed ...$aa_args Array with arguments or multiple arguments in function.
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__x
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __x(string $as_context, string $as_string, mixed ...$aa_args): string {
		if (!$as_string) {
			return '';
		}

		$la_args = $aa_args;
		if (isset($aa_args[0]) && is_array($aa_args[0])) {
			$la_args = $aa_args[0];
		}

		$ls_controller = Router::getRequest()?->getParam('controller');
		if ($ls_controller) {
			$ls_controller = Inflector::underscore(Router::getRequest()->getParam('controller'));

			$ls_return = __d($ls_controller, $as_string, ['_context' => $as_context] + $la_args);
		}
		else {
			$ls_return = I18n::getTranslator('system')->translate($as_string, ['_context' => $as_context] + $la_args);
		}


		return $ls_return;
	}
}
