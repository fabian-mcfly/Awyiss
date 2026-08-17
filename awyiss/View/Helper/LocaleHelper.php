<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Language;
use Cake\View\Helper;


/**
 * @property \Awyiss\View\Helper\FormHelper $Form
 */
class LocaleHelper extends Helper {
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Form'];


	/**
	 * Returns the value of the `title`-property of a language with the given shortcode.
	 *
	 * It first looks for a frontend-language, then a backend-language and falls back to null.
	 *
	 * @param string|null $shortcode
	 * @param array|null $languagesByShortcode
	 * @return string|null
	 */
	public function languageTitle(?string $shortcode = null, ?array $languagesByShortcode = null): ?string {
		if (!$shortcode) {
			return null;
		}

		$languages = $languagesByShortcode ?: LocaleMiddleware::getLanguagesByShortcode();
		if (!isset($languages [ $shortcode ])) {
			return null;
		}


		return ($languages [ $shortcode ][ Awyiss::REALM_FRONTEND ] ?? $languages [ $shortcode ][ Awyiss::REALM_BACKEND ] ?? null)?->title
			?? null;
	}


	/**
	 * Returns an input (default: select) with all languages.
	 *
	 * Each option has the shortcode as value and the title as text.
	 *
	 * ### Options
	 *
	 * - `languageRealm` If the realm is set and found as a valid language realm (e.g. 'Frontend' or 'Backend'),
	 * the options consist only of languages of this realm. Otherwise, it will use all realms.
	 *
	 * @param string|null $fieldName
	 * @param array $attributes
	 * @return string
	 * @throws \Exception
	 * @see FormHelper::control
	 */
	public function control(?string $fieldName = null, array $attributes = []): string {
		$attributes += ['type' => 'select'];

		if (empty($attributes['languageRealm'])) {
			$languages = $this->allLanguages(true);
		}
		else {
			$languages = $this->languagesForRealm($attributes['languageRealm'], true);
		}

		$languages = array_filter($languages, fn($language) => $language->active);
		$languages = array_map(fn($language) => $language->title, $languages);

		$attributes['options'] = $languages;

		unset($attributes['languageRealm']);


		return $this->Form->control($fieldName, $attributes);
	}


	/**
	 * @param bool $raw
	 * @return array
	 */
	public function allLanguages(bool $raw = false): array {
		$languages = [];

		foreach (LocaleMiddleware::getLanguagesByShortcode() as $shortcode => $languagesByRealm) {
			$language = $languagesByRealm[ Awyiss::REALM_FRONTEND ] ?? $languagesByRealm[ Awyiss::REALM_BACKEND ] ?? null;

			if (!$language) {
				continue;
			}

			$languages[ $shortcode ] = $raw ? $language : $language->title;
		}


		return $languages;
	}


	/**
	 * @param string $realm
	 * @param bool $raw
	 * @return array
	 */
	public function languagesForRealm(string $realm, bool $raw = false): array {
		return array_map(function (Language $language) use ($raw) {
			return $raw ? $language : $language->title;
		}, LocaleMiddleware::getLanguages($realm));
	}
}
