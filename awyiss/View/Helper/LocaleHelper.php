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

		$la_languages = $languagesByShortcode ?: LocaleMiddleware::getLanguagesByShortcode();
		if (!isset($la_languages [ $shortcode ])) {
			return null;
		}


		return ($la_languages [ $shortcode ][ Awyiss::REALM_FRONTEND ] ?? $la_languages [ $shortcode ][ Awyiss::REALM_BACKEND ] ?? null)?->title ?? null;
	}


	/**
	 * Returns an input (default: select) with all languages.
	 *
	 * Each option has the shortcode as value and the title as text.
	 *
	 * ### Options
	 *
	 * - `languageRealm` If the realm is set and found as a valid language realm (e.g. 'frontend' or 'backend'),
	 * the options consist only of languages of this realm. Otherwise, it will use all realms.
	 *
	 * @param string|null $fieldName
	 * @param array $attributes
	 * @return string
	 * @throws \Exception
	 * @see FormHelper::control
	 */
	public function control(?string $fieldName = null, array $attributes = []): string {
		$la_attributes = $attributes + ['type' => 'select'];

		if (empty($attributes['languageRealm'])) {
			$la_languages = $this->allLanguages(true);
		}
		else {
			$la_languages = $this->languagesForRealm($attributes['languageRealm'], true);
		}

		$la_languages = array_filter($la_languages, fn($language) => $language->active);
		$la_languages = array_map(fn($language) => $language->title, $la_languages);

		$la_attributes['options'] = $la_languages;

		unset($la_attributes['languageRealm']);


		return $this->Form->control($fieldName, $la_attributes);
	}


	/**
	 * @param bool $raw
	 * @return array
	 */
	public function allLanguages(bool $raw = false): array {
		$la_languages = [];

		foreach (LocaleMiddleware::getLanguagesByShortcode() as $ls_shortcode => $la_languagesByRealm) {
			$lo_language = $la_languagesByRealm[ Awyiss::REALM_FRONTEND ] ?? $la_languagesByRealm[ Awyiss::REALM_BACKEND ] ?? null;

			if (!$lo_language) {
				continue;
			}

			$la_languages[ $ls_shortcode ] = $raw ? $lo_language : $lo_language->title;
		}


		return $la_languages;
	}


	/**
	 * @param string $realm
	 * @param bool $raw
	 * @return array
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function languagesForRealm(string $realm, bool $raw = false): array {
		return array_map(function (Language $language) use ($raw) {
			return $raw ? $language : $language->title;
		}, LocaleMiddleware::getLanguages($realm));
	}
}
