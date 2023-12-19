<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Cake\View\Helper;


/**
 * @property \Awyiss\View\Helper\FormHelper $Form
 */
class LocaleHelper extends Helper {
	public $helpers = ['Form'];


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

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->_View->getRequest()->getAttribute('locale');

		$la_languages = $aa_languagesByShortcode ?: $lo_locale->getLanguagesByShortcode();
		if ( ! isset($la_languages [ $as_shortcode ])) {
			return NULL;
		}

		return ($la_languages [ $as_shortcode ]['frontend'] ?? $la_languages [ $as_shortcode ]['backend'])?->title ?? NULL;
	}


	/**
	 * Returns an input (default: select) with all languages.
	 *
	 * Each option has the shortcode as value and the title as text.
	 *
	 * ### Options
	 *
	 * - `languageType` If the type is set and found as a valid language type (e.g. 'frontend' or 'backend'),
	 * the options consist only of languages of this type. Otherwise, it will use all types.
	 *
	 * @param null|string $as_fieldName
	 * @param array $aa_attributes
	 *
	 * @return string
	 *
	 * @see \Awyiss\View\Helper\FormHelper::control()
	 */
	public function control (?string $as_fieldName = NULL, array $aa_attributes = []): string {
		$la_attributes = $aa_attributes + ['type' => 'select'];

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->_View->getRequest()->getAttribute('locale');
		$la_languages = [];
		foreach ($lo_locale->getLanguagesByShortcode() as $ls_shortcode => $la_languageByShortcode) {
			if (in_array($la_attributes['languageType'] ?? NULL, ['frontend', 'backend'])) {
				$lo_language = $la_languageByShortcode[ $la_attributes['languageType'] ] ?? NULL;
			}
			else {
				$lo_language = $la_languageByShortcode['frontend'] ?? $la_languageByShortcode['backend'] ?? NULL;
			}

			if ( ! $lo_language) {
				continue;
			}

			$la_languages[ $ls_shortcode ] = $lo_language->title;
		}
		$la_attributes['options'] = $la_languages;

		unset($la_attributes['languageType']);

		return $this->Form->control($as_fieldName, $la_attributes);
	}
}