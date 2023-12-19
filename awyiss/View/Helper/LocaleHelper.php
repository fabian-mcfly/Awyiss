<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


/**
 * @property \Awyiss\View\Helper\FormHelper $Form
 */
class LocaleHelper extends \Cake\View\Helper {
	public $helpers = ['Form'];


	/**
	 * @noinspection PhpUnused
	 */
	public function languageTitle (?string $as_shortcode = NULL, ?array $aa_languagesByShortcode = NULL): ?string {
		if (!$as_shortcode) return NULL;

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->_View->getRequest()->getAttribute('locale');

		$la_languages = $aa_languagesByShortcode ?: $lo_locale->getLanguagesByShortcode();
		if (!isset($la_languages [ $as_shortcode ])) {
			return NULL;
		}

		return ($la_languages [ $as_shortcode ]['frontend'] ?? $la_languages [ $as_shortcode ]['backend'])?->title ?? NULL;
	}



	public function control (?string $as_fieldName = NULL, array $aa_attributes = []): string {
		$la_attributes = $aa_attributes;

		/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
		$lo_locale = $this->_View->getRequest()->getAttribute('locale');
		$la_languages = [];
		foreach ($lo_locale->getLanguagesByShortcode() AS $ls_shortcode => $la_languageByShortcode) {
			if (in_array($la_attributes['languageType'] ?? NULL, ['frontend', 'backend'])) {
				$lo_language = $la_languageByShortcode[ $la_attributes['languageType'] ] ?? NULL;
			}
			else {
				$lo_language = $la_languageByShortcode['frontend'] ?? $la_languageByShortcode['backend'] ?? NULL;
			}

			if (!$lo_language) continue;

			$la_languages[ $ls_shortcode ] = $lo_language->title;
		}
		$la_attributes['options'] = $la_languages;

		if ( ! array_key_exists('type', $la_attributes)) {
			$la_attributes['type'] = 'select';
		}

		unset($la_attributes['languageType']);

		return $this->Form->control($as_fieldName, $la_attributes);
	}
}