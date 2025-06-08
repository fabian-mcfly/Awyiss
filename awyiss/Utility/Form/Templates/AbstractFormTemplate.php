<?php declare(strict_types=1);


namespace Awyiss\Utility\Form\Templates;


use Awyiss\Utility\Inflector;
use Cake\I18n\I18n;


/**
 * AbstractFormTemplate
 */
abstract class AbstractFormTemplate implements FormTemplateInterface {
	/**
	 * An array of static translations for the form template in case
	 * the translation is not available for the respective language.
	 *
	 * @var array
	 */
	protected static array $staticTranslations = [];
	/**
	 * A list of strings used in this form template
	 * that require translation.
	 *
	 * @var array<string>
	 */
	protected static array $strings = [];
	/**
	 * A list of translations for the strings used in this form template
	 *
	 * @var array<string, array<string>>
	 */
	protected static array $translations = [];


	/**
	 * @param array<string> $fields
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 */
	protected static function buildElements(array $fields, array $languages): array {
		$la_elements = [];
		$lb_i18n = count($languages) > 1;
		$ls_mainLocale = current($languages)->locale;

		static::buildTranslations($languages);

		foreach ($fields as $ls_field) {
			$ls_methodName = 'add' . Inflector::camelize($ls_field);

			$la_elements[] = static::$ls_methodName($ls_mainLocale, $lb_i18n, $languages) + [
				'identifier' => null,
				'type' => 'text',
				'titleEmail' => null,
				'placeholder' => null,
				'text' => null,
				'options' => null,
				'columnWidth' => '1/1',
				'columnIndent' => null,
				'columnLast' => false,
				'columnRtl' => false,
				'cssClass' => null,
				'required' => false,
				'systemOrder' => count($la_elements) + 1,
			];
		}

		return $la_elements;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addTitle(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'type' => 'select',
			'identifier' => 'title',
			'title' => static::$translations[ $mainLocale ]['title'],
			'options' => static::getOptions('title', ['', 'ms', 'mr', 'diverse'], $languages),
			'columnWidth' => '1/2',
			'columnLast' => true,
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('title', $languages);
		}

		return $la_settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addFirstname(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'identifier' => 'firstname',
			'title' => static::$translations[ $mainLocale ]['firstname'],
			'columnWidth' => '1/2',
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('firstname', $languages);
		}

		return $la_settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addLastname(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'identifier' => 'lastname',
			'title' => static::$translations[ $mainLocale ]['lastname'],
			'columnWidth' => '1/2',
			'required' => true,
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('lastname', $languages);
		}

		return $la_settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addName(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'identifier' => 'name',
			'title' => static::$translations[ $mainLocale ]['name'],
			'required' => true,
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('name', $languages);
		}

		return $la_settings;
	}

	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addEmail(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'identifier' => 'email',
			'required' => true,
			'title' => static::$translations[ $mainLocale ]['email'],
			'type' => 'email',
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('email', $languages);
		}

		return $la_settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addPhone(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'type' => 'tel',
			'identifier' => 'phone',
			'title' => static::$translations[ $mainLocale ]['phone'],
			'placeholder' => static::$translations[ $mainLocale ]['optional_placeholder'],
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('phone', $languages, true);
		}

		return $la_settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addDatetime(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'type' => 'datetime',
			'identifier' => 'datetime',
			'title' => static::$translations[ $mainLocale ]['datetime'],
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('datetime', $languages);
		}

		return $la_settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addMessage(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'type' => 'textarea',
			'identifier' => 'message',
			'title' => static::$translations[ $mainLocale ]['message'],
			'required' => true,
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('message', $languages);
		}

		return $la_settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addPrivacyAccepted(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'type' => 'checkbox',
			'identifier' => 'privacy_accepted',
			'title' => static::$translations[ $mainLocale ]['privacy_accepted'],
			'options' => static::getOptions('privacy_accepted', ['yes' => 'text'], $languages),
			'required' => true,
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('privacy_accepted', $languages);
		}

		return $la_settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addSubmit(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'type' => 'submit',
			'title' => static::$translations[ $mainLocale ]['submit'],
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('submit', $languages);
		}

		return $la_settings;
	}


	/**
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return void
	 */
	protected static function buildTranslations(array $languages): void {
		$ls_currentLocale = I18n::getLocale();

		foreach ($languages as $lo_language) {
			I18n::setLocale($lo_language->locale);
			foreach (array_merge(static::$strings, ['optional_placeholder']) as $ls_string) {
				$ls_translation = __d('forms', 'form_template_' . $ls_string);

				if (
					str_contains($ls_translation, '::') &&
					isset(static::$staticTranslations[ $lo_language->shortcode ][ $ls_string ])
				) {
					$ls_translation = static::$staticTranslations[ $lo_language->shortcode ][ $ls_string ];
				}

				if (str_contains($ls_translation, '::')) {
					$ls_translation = null;
				}

				static::$translations[ $lo_language->locale ][ $ls_string ] = $ls_translation;
			}
		}

		// Set the locale back to the original
		I18n::setLocale($ls_currentLocale);
	}


	/**
	 * @param string $field
	 * @param array<string> $options
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 */
	protected static function getOptions(string $field, array $options, array $languages): array {
		$lb_i18n = count($languages) > 1;
		$ls_mainLocale = current($languages)->locale;
		$lb_isList = array_is_list($options);

		$la_options = [];

		foreach ($options as $lx_key => $ls_value) {
			$ls_translation = $ls_value ? static::$translations[ $ls_mainLocale ][ $field . '_' . $ls_value ] : '';
			if ($ls_translation === null) {
				$ls_translation = static::$translations['en_GB'][ $field . '_' . $ls_value ];
			}

			$ls_key = $lb_isList ? null : $lx_key;
			if ($lx_key && is_string($lx_key)) {
				$ls_key = static::$translations[ $ls_mainLocale ][ $field . '_' . $lx_key ];
				if ($ls_key === null) {
					$ls_key = static::$translations['en_GB'][ $field . '_' . $lx_key ];
				}
			}

			$la_option = [
				'key' => $ls_key,
				'value' => $ls_translation,
			];

			if ($lb_i18n) {
				$la_option['_translations'] = [];
				foreach ($languages as $lo_language) {
					$ls_translation = $ls_value ? static::$translations[ $lo_language->locale ][ $field . '_' . $ls_value ] : '';
					if ($ls_translation === null) {
						$ls_translation = static::$translations['en_GB'][ $field . '_' . $ls_value ];
					}

					$ls_key = $lb_isList ? null : $lx_key;
					if ($lx_key && is_string($lx_key)) {
						$ls_key = static::$translations[ $lo_language->locale ][ $field . '_' . $lx_key ];
						if ($ls_key === null) {
							$ls_key = static::$translations['en_GB'][ $field . '_' . $lx_key ];
						}
					}

					$la_option['_translations'][ $lo_language->shortcode ] = [
						'key' => $ls_key,
						'value' => $ls_translation,
					];
				}
			}

			$la_options[] = $la_option;
		}

		 return $la_options;
	}


	/**
	 * @param string $field
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 */
	protected static function getTranslations(string $field, array $languages, bool $setOptional = false, string $property = 'title'): array {
		$la_translations = [];

		foreach ($languages as $lo_language) {
			$ls_translation = static::$translations[ $lo_language->locale ][ $field ];
			if ($ls_translation === null) {
				$ls_translation = static::$translations['en_GB'][ $field ];
			}

			$la_translations[ $lo_language->shortcode ][ $property ] = $ls_translation;

			if ($setOptional) {
				$ls_translation = static::$translations[ $lo_language->locale ]['optional_placeholder'];
				if ($ls_translation === null) {
					$ls_translation = static::$translations['en_GB']['optional_placeholder'];
				}

				$la_translations[ $lo_language->shortcode ]['optional_placeholder'] = $ls_translation;
			}
		}

		return $la_translations;
	}
}
