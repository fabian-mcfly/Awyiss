<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Cake\I18n\I18n;
use Cake\Utility\Inflector;


if (!function_exists('__')) {
	/**
	 * Returns a translated string if one is found; Otherwise, the submitted message.
	 *
	 * @param string $string
	 * @param mixed ...$args
	 * @return string The translated text.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __(string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}

		$ls_controller = Router::getRequest()?->getParam('controller');
		if ($ls_controller) {
			$ls_controller = Inflector::underscore(Router::getRequest()->getParam('controller'));

			$ls_return = __d($ls_controller, $string, $la_args);
		}
		else {
			$ls_return = I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, $la_args);
		}


		return $ls_return;
	}
}


if (!function_exists('__d')) {
	/**
	 * Allows you to override the current domain for a single message lookup.
	 *
	 * @param string $domain Domain.
	 * @param string $string String to translate.
	 * @param mixed ...$args
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__d
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __d(string $domain, string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}

		$ls_domain = $domain;
		if (!str_contains($domain, '/')) {
			$ls_domain = Awyiss::getRealm() . '/' . $domain;
		}
		$ls_return = I18n::getTranslator($ls_domain)->translate($string, $la_args);

		if ($ls_return === $string || empty($ls_return)) {
			$ls_return = $domain . '::' . $string;

			// Fallback to system domain
			if ($domain !== 'system') {
				$ls_fallback = I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, $la_args);

				if ($ls_fallback !== $string && !empty($ls_fallback)) {
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
	 * @param string $domain
	 * @param string $fallbackDomain
	 * @param string $string
	 * @param mixed ...$args
	 * @return string The translated text.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __df(string $domain, string $fallbackDomain, string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}

		$ls_domain = $domain;
		if (!str_contains($domain, '/')) {
			$ls_domain = Awyiss::getRealm() . '/' . $domain;
		}
		$ls_return = I18n::getTranslator($ls_domain)->translate($string, $la_args);

		if ($ls_return === $string || empty($ls_return)) {
			$ls_fallbackDomain = $fallbackDomain;
			if (!str_contains($fallbackDomain, '/')) {
				$ls_fallbackDomain = Awyiss::getRealm() . '/' . $fallbackDomain;
			}
			$ls_return = I18n::getTranslator($ls_fallbackDomain)->translate($string, $la_args);
		}

		if ($ls_return === $string || empty($ls_return)) {
			$ls_return = $domain . '::' . $string;
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
	 * @param string $domain
	 * @param string $context
	 * @param string $string
	 * @param mixed ...$args
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__dx
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __dx(string $domain, string $context, string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}


		return __d($domain, $string, ['_context' => $context] + $la_args);
	}
}


if (!function_exists('__dfx')) {
	/**
	 * Allows you to override the current domain for a single message lookup.
	 * If no translation for the given domain can be found, a fallbackdomain will be used
	 * The context is a unique identifier for the translations string that makes it unique
	 * within the same domain.
	 *
	 * @param string $domain
	 * @param string $fallbackDomain
	 * @param string $context
	 * @param string $string
	 * @param mixed ...$args
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__dx
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __dfx(string $domain, string $fallbackDomain, string $context, string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}


		return __df($domain, $fallbackDomain, $string, ['_context' => $context] + $la_args);
	}
}


if (!function_exists('__x')) {
	/**
	 * Returns a translated string if one is found; Otherwise, the submitted message.
	 * The context is a unique identifier for the translations string that makes it unique
	 * within the same domain.
	 *
	 * @param string $context Context of the text.
	 * @param string $string Text to translate.
	 * @param mixed ...$args Array with arguments or multiple arguments in function.
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__x
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __x(string $context, string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}

		$ls_controller = Router::getRequest()?->getParam('controller');
		if ($ls_controller) {
			$ls_controller = Inflector::underscore(Router::getRequest()->getParam('controller'));

			$ls_return = __d($ls_controller, $string, ['_context' => $context] + $la_args);
		}
		else {
			$ls_return = I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, ['_context' => $context] + $la_args);
		}


		return $ls_return;
	}
}
