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

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}

		$controller = Router::getRequest()?->getParam('controller');
		if ($controller) {
			return __d($controller, $string, $args);
		}

		if (
			!in_array($string, [
				'meta_title_overview',
				'menu_title',
				'headline_overview',
			])
		) {
			return I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, $args);
		}

		return $string;
	}
}


if (!function_exists('__f')) {
	/**
	 * Returns a translated string if one is found; Otherwise, the provided fallback
	 *
	 * @param string $string
	 * @param string $fallback
	 * @param mixed ...$args
	 * @return string The translated text.
	 * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#__
	 */
	function __f(string $string, string $fallback, mixed ...$args): string {
		$translation = __($string, ...$args);

		if ($translation === $string || str_contains($translation, '::')) {
			$translation = $fallback;
		}

		if (str_contains($translation, '::')) {
			$translation = $string;
		}

		return $translation;
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

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}

		$realmDomain = __buildDomain($domain);
		$return = I18n::getTranslator($realmDomain)->translate($string, $args);

		if (
			(
				!empty($return) &&
				$return !== $string
			) ||
			$domain === 'cake'
		) {
			return $return;
		}

		$return = $domain . '::' . $string;

		// Fallback to system domain
		if (
			strtolower($domain) !== 'system' &&
			!in_array($string, [
				'meta_title_overview',
				'menu_title',
				'headline_overview',
			])
		) {
			$fallback = I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, $args);

			if ($fallback !== $string && !empty($fallback)) {
				return $fallback;
			}
		}

		return $return;
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

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}

		$realmDomain = __buildDomain($domain);
		$return = I18n::getTranslator($realmDomain)->translate($string, $args);

		if ($return === $string || empty($return)) {
			$return = I18n::getTranslator(__buildDomain($fallbackDomain))->translate($string, $args);
		}

		if ($return === $string || empty($return)) {
			$return = $domain . '::' . $string;

			// Fallback to system domain
			if (
				Inflector::underscore($fallbackDomain) === 'generic_pages' &&
				!in_array($string, [
					'meta_title_overview',
					'menu_title',
					'headline_overview',
				])
			) {
				$fallback = I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, $args);

				if ($fallback !== $string && !empty($fallback)) {
					$return = $fallback;
				}
			}
		}


		return $return;
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

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}


		return __d($domain, $string, ['_context' => $context] + $args);
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

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}


		return __df($domain, $fallbackDomain, $string, ['_context' => $context] + $args);
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

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}

		$controller = Router::getRequest()?->getParam('controller');
		if ($controller) {
			$controller = Inflector::underscore(Router::getRequest()->getParam('controller'));

			return __d($controller, $string, ['_context' => $context] + $args);
		}

		return I18n::getTranslator(Awyiss::getRealm() . '/system')->translate($string, ['_context' => $context] + $args);
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

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}

		$controller = Router::getRequest()?->getParam('controller');
		if ($controller) {
			$controller = Inflector::underscore(Router::getRequest()->getParam('controller'));

			return __d($controller, $string, $args);
		}

		if (
			!in_array($string, [
				'meta_title_overview',
				'menu_title',
				'headline_overview',
			])
		) {
			return I18n::getTranslator(Awyiss::getRealm() . '/system', $locale)->translate($string, $args);
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

		if (isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}

		$realmDomain = __buildDomain($domain);
		$return = I18n::getTranslator($realmDomain, $locale)->translate($string, $args);

		if (
			(
				!empty($return) &&
				$return !== $string
			) ||
			$domain === 'cake'
		) {
			return $return;
		}

		$return = $domain . '::' . $string;

		// Fallback to system domain
		if (
			strtolower($domain) !== 'system' &&
			!in_array($string, [
				'meta_title_overview',
				'menu_title',
				'headline_overview',
			])
		) {
			$fallback = I18n::getTranslator(Awyiss::getRealm() . '/system', $locale)->translate($string, $args);

			if ($fallback !== $string && !empty($fallback)) {
				return $fallback;
			}
		}

		return $return;
	}
}


/**
 * @param string $domain
 * @return string
 */
function __buildDomain(string $domain): string {
	if ($domain === 'cake') {
		return $domain;
	}

	if (!str_contains($domain, '/')) {
		return Awyiss::getRealm() . '/' . Inflector::underscore($domain);
	}

	$parts = explode('/', $domain);
	array_walk($parts, function (string &$value, int $key): void {
		if ($key === 0) {
			return;
		}

		$value = Inflector::underscore($value);
	});

	return count($parts) > 1 ? implode('/', $parts) : Inflector::underscore($domain);
}
