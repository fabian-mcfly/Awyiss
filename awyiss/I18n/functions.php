<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\I18n\I18n;


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

			return __d($ls_controller, $string, $la_args);
		}

		if (
			!in_array($string, [
				'meta_title_overview',
				'menu_title',
				'headline_overview',
			])
		) {
			return I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, $la_args);
		}

		return $string;
	}
}


if (!function_exists('__f')) {
	/**
	 * Returns a translated string if one is found; Otherwise, the provided fallback
	 *
	 * @param string $string
	 * @param mixed ...$args
	 * @return string The translated text.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __f(string $string, string $fallback, mixed ...$args): string {
		$ls_string = __($string, ...$args);

		if ($ls_string === $string || str_contains($ls_string, '::')) {
			$ls_string = $fallback;
		}

		if (str_contains($ls_string, '::')) {
			$ls_string = $string;
		}

		return $ls_string;
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
	 * @noinspection DuplicatedCode, PhpFunctionNamingConventionInspection
	 */
	function __d(string $domain, string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}

		$ls_domain = __buildDomain($domain);
		$ls_return = I18n::getTranslator($ls_domain)->translate($string, $la_args);

		if (
			(
				!empty($ls_return) &&
				$ls_return !== $string
			) ||
			$domain === 'cake'
		) {
			return $ls_return;
		}

		$ls_return = Inflector::underscore($domain) . '::' . $string;

		// Fallback to system domain
		if (
			$domain !== 'system' &&
			!in_array($string, [
				'meta_title_overview',
				'menu_title',
				'headline_overview',
			])
		) {
			$ls_fallback = I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, $la_args);

			if ($ls_fallback !== $string && !empty($ls_fallback)) {
				$ls_return = $ls_fallback;
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
	 * @noinspection DuplicatedCode, PhpFunctionNamingConventionInspection
	 */
	function __df(string $domain, string $fallbackDomain, string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}

		$ls_domain = __buildDomain($domain);
		$ls_return = I18n::getTranslator($ls_domain)->translate($string, $la_args);

		if ($ls_return === $string || empty($ls_return)) {
			$ls_fallbackDomain = __buildDomain($fallbackDomain);
			$ls_return = I18n::getTranslator($ls_fallbackDomain)->translate($string, $la_args);
		}

		if ($ls_return === $string || empty($ls_return)) {
			$ls_return = Inflector::underscore($domain) . '::' . $string;

			// Fallback to system domain
			if (
				$fallbackDomain === 'generic_pages' &&
				!in_array($string, [
					'meta_title_overview',
					'menu_title',
					'headline_overview',
				])
			) {
				$ls_fallback = I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, $la_args);

				if ($ls_fallback !== $string && !empty($ls_fallback)) {
					$ls_return = $ls_fallback;
				}
			}
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
	 * If no translation for the given domain can be found, a fallback domain will be used
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


if (!function_exists('__l')) {
	/**
	 * Returns a translated string if one is found; Otherwise, the submitted message.
	 *
	 * @param string $locale Locale.
	 * @param string $string
	 * @param mixed ...$args
	 * @return string The translated text.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__
	 * @noinspection PhpFunctionNamingConventionInspection
	 */
	function __l(string $locale, string $string, mixed ...$args): string {
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

			return __d($ls_controller, $string, $la_args);
		}

		if (
			!in_array($string, [
				'meta_title_overview',
				'menu_title',
				'headline_overview',
			])
		) {
			return I18n::getTranslator(Awyiss::getRealm() . '/system', $locale)->translate($string, $la_args);
		}

		return $string;
	}
}


if (!function_exists('__ld')) {
	/**
	 * Allows you to override the current domain for a single message lookup.
	 *
	 * @param string $locale Locale.
	 * @param string $domain Domain.
	 * @param string $string String to translate.
	 * @param mixed ...$args
	 * @return string Translated string.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__d
	 * @noinspection DuplicatedCode, PhpFunctionNamingConventionInspection
	 */
	function __ld(string $locale, string $domain, string $string, mixed ...$args): string {
		if (!$string) {
			return '';
		}

		$la_args = $args;
		if (isset($args[0]) && is_array($args[0])) {
			$la_args = $args[0];
		}

		$ls_domain = __buildDomain($domain);
		$ls_return = I18n::getTranslator($ls_domain, $locale)->translate($string, $la_args);

		if (
			(!empty($ls_return) && $ls_return !== $string) || $domain === 'cake'
		) {
			return $ls_return;
		}

		$ls_return = Inflector::underscore($domain) . '::' . $string;

		// Fallback to system domain
		if (
			$domain !== 'system' && !in_array($string, [
				'meta_title_overview',
				'menu_title',
				'headline_overview',
			])
		) {
			$ls_fallback = I18n::getTranslator(Awyiss::getRealm() . '/system', $locale)->translate($string, $la_args);

			if ($ls_fallback !== $string && !empty($ls_fallback)) {
				$ls_return = $ls_fallback;
			}
		}

		return $ls_return;
	}
}


/**
 * @param string $domain
 * @return string
 */
function __buildDomain(string $domain): string {
	if (!str_contains($domain, '/')) {
		return Awyiss::getRealm() . '/' . Inflector::underscore($domain);
	}

	$la_parts = explode('/', $domain);
	array_walk($la_parts, function (string &$value, int $key): void {
		if ($key === 0) {
			return;
		}

		/** @noinspection PhpVariableNamingConventionInspection */
		$value = Inflector::underscore($value);
	});

	return count($la_parts) > 1 ? implode('/', $la_parts) : $domain;
}
