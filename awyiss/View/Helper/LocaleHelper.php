<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Cake\Event\Event;
use Cake\View\Helper;
use Cake\View\View;


/**
 * @property FormHelper $Form
 */
class LocaleHelper extends Helper {
	/**
	 * @inheritDoc
	 */
	protected array $helpers = ['Form'];


	/**
	 * Returns the value of the `title`-property of a language with the given shorrtcode.
	 *
	 * It first looks for a frontend-language, then a backend-language and falls back to NULL.
	 *
	 * @param null|string $as_shortcode
	 * @param null|array $aa_languagesByShortcode
	 *
	 * @return null|string
	 *
	 * @noinspection PhpUnused
	 */
	public function languageTitle (?string $as_shortcode = NULL, ?array $aa_languagesByShortcode = NULL): ?string {
		if ( ! $as_shortcode) {
			return NULL;
		}

		$la_languages = $aa_languagesByShortcode ?: LocaleMiddleware::getLanguagesByShortcode();
		if ( ! isset($la_languages [ $as_shortcode ])) {
			return NULL;
		}

		return ($la_languages [ $as_shortcode ][ Awyiss::REALM_FRONTEND ] ?? $la_languages [ $as_shortcode ][ Awyiss::REALM_BACKEND ])?->title ?? NULL;
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
	 * @param null|string $as_fieldName
	 * @param array $aa_attributes
	 *
	 * @return string
	 *
	 * @see FormHelper::control
	 */
	public function control (?string $as_fieldName = NULL, array $aa_attributes = []): string {
		$la_attributes = $aa_attributes + ['type' => 'select'];

		if (empty($aa_attributes['realm'])) {
			$la_languages = $this->allLanguages();
		}
		else {
			$la_languages = $this->languagesForRealm($aa_attributes['realm']);
		}

		$la_attributes['options'] = $la_languages;

		unset($la_attributes['languageRealm']);

		return $this->Form->control($as_fieldName, $la_attributes);
	}


	/**
	 * @param Event $ao_event
	 *
	 * @return void
	 */
	public function beforeRender (Event $ao_event): void {
		$la_languages = LocaleMiddleware::getLanguages();

		/** @var View $ao_view */
		$ao_view = $ao_event->getSubject();

		$ao_view->set('aa_languages', $la_languages);
		$ao_view->set('aa_languagesFrontend', $la_languages[ Awyiss::REALM_FRONTEND ] ?? []);
		$ao_view->set('aa_languagesBackend', $la_languages[ Awyiss::REALM_BACKEND ] ?? []);
	}


	/**
	 * @return array
	 */
	protected function allLanguages (): array {
		$la_languages = [];

		foreach (LocaleMiddleware::getLanguagesByShortcode() as $ls_shortcode => $la_languagesByRealm) {
			$lo_language = $la_languagesByRealm[ Awyiss::REALM_FRONTEND ] ?? $la_languagesByRealm[ Awyiss::REALM_BACKEND ] ?? NULL;

			if ( ! $lo_language) {
				continue;
			}

			$la_languages[ $ls_shortcode ] = $lo_language->title;
		}

		return $la_languages;
	}


	/**
	 * @param string $as_realm
	 *
	 * @return array
	 */
	protected function languagesForRealm (string $as_realm): array {
		$la_languages = [];

		foreach (LocaleMiddleware::getLanguages($as_realm) AS $ls_shortcode => $lo_language) {
			$la_languages[ $ls_shortcode ] = $lo_language->title;
		}

		return $la_languages;
	}
}
