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
		$elements = [];
		$i18n = count($languages) > 1;
		$mainLocale = current($languages)->locale;

		static::buildTranslations($languages);

		foreach ($fields as $field) {
			$methodName = 'add' . Inflector::camelize($field);

			$default = [
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
				'systemOrder' => count($elements) + 1,
			];

			$elements[] = static::$methodName($mainLocale, $i18n, $languages) + $default;
		}

		return $elements;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addTitle(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'type' => 'select',
			'identifier' => 'title',
			'title' => static::$translations[ $mainLocale ]['title'],
			'options' => static::getOptions('title', ['', 'ms', 'mr', 'diverse'], $languages),
			'columnWidth' => '1/2',
			'columnLast' => true,
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('title', $languages);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addFirstname(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'identifier' => 'firstname',
			'title' => static::$translations[ $mainLocale ]['firstname'],
			'columnWidth' => '1/2',
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('firstname', $languages);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addLastname(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'identifier' => 'lastname',
			'title' => static::$translations[ $mainLocale ]['lastname'],
			'columnWidth' => '1/2',
			'required' => true,
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('lastname', $languages);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addName(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'identifier' => 'name',
			'title' => static::$translations[ $mainLocale ]['name'],
			'required' => true,
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('name', $languages);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addEmail(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'identifier' => 'email',
			'required' => true,
			'title' => static::$translations[ $mainLocale ]['email'],
			'type' => 'email',
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('email', $languages);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addPhone(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'type' => 'tel',
			'identifier' => 'phone',
			'title' => static::$translations[ $mainLocale ]['phone'],
			'placeholder' => static::$translations[ $mainLocale ]['optionalPlaceholder'],
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('phone', $languages, true);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 */
	protected static function addDatetime(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'type' => 'datetime',
			'identifier' => 'datetime',
			'title' => static::$translations[ $mainLocale ]['datetime'],
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('datetime', $languages);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addMessage(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'type' => 'textarea',
			'identifier' => 'message',
			'title' => static::$translations[ $mainLocale ]['message'],
			'required' => true,
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('message', $languages);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addPrivacyAccepted(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'type' => 'checkbox',
			'identifier' => 'privacyAccepted',
			'title' => static::$translations[ $mainLocale ]['privacyAccepted'],
			'options' => static::getOptions('privacyAccepted', ['yes' => 'text'], $languages),
			'required' => true,
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('privacyAccepted', $languages);
		}

		return $settings;
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addSubmit(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'type' => 'submit',
			'title' => static::$translations[ $mainLocale ]['submit'],
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('submit', $languages);
		}

		return $settings;
	}


	/**
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return void
	 */
	protected static function buildTranslations(array $languages): void {
		$currentLocale = I18n::getLocale();

		foreach ($languages as $language) {
			I18n::setLocale($language->locale);
			foreach (array_merge(static::$strings, ['optionalPlaceholder']) as $string) {
				$translation = __d('Forms', 'form_template_' . Inflector::underscore($string));

				if (
					str_contains($translation, '::')
					&& isset(static::$staticTranslations[ $language->shortcode ][ $string ])
				) {
					$translation = static::$staticTranslations[ $language->shortcode ][ $string ];
				}

				if (str_contains($translation, '::')) {
					$translation = null;
				}

				static::$translations[ $language->locale ][ $string ] = $translation;
			}
		}

		// Set the locale back to the original
		I18n::setLocale($currentLocale);
	}


	/**
	 * @param string $field
	 * @param array<string> $options
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 */
	protected static function getOptions(string $field, array $options, array $languages): array {
		$i18n = count($languages) > 1;
		$mainLocale = current($languages)->locale;
		$isList = array_is_list($options);

		$builtOptions = [];

		foreach ($options as $key => $value) {
			$translation = $value ? static::$translations[ $mainLocale ][ $field . Inflector::camelize($value) ] : '';
			if ($translation === null) {
				$translation = static::$translations['en_GB'][ $field . Inflector::camelize($value) ];
			}

			$namedKey = $isList ? null : $key;
			if ($key && is_string($key)) {
				$namedKey = static::$translations[ $mainLocale ][ $field . Inflector::camelize($key) ];
				if ($namedKey === null) {
					$namedKey = static::$translations['en_GB'][ $field . Inflector::camelize($key) ];
				}
			}

			$option = [
				'key' => $namedKey,
				'value' => $translation,
			];

			if ($i18n) {
				$option['_translations'] = [];
				foreach ($languages as $language) {
					$translation = $value ? static::$translations[ $language->locale ][ $field . Inflector::camelize($value) ] : '';
					if ($translation === null) {
						$translation = static::$translations['en_GB'][ $field . Inflector::camelize($value) ];
					}

					$namedKey = $isList ? null : $key;
					if ($key && is_string($key)) {
						$namedKey = static::$translations[ $language->locale ][ $field . Inflector::camelize($key) ];
						if ($namedKey === null) {
							$namedKey = static::$translations['en_GB'][ $field . Inflector::camelize($key) ];
						}
					}

					$option['_translations'][ $language->shortcode ] = [
						'key' => $namedKey,
						'value' => $translation,
					];
				}
			}

			$builtOptions[] = $option;
		}

		return $builtOptions;
	}


	/**
	 * @param string $field
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @param bool $setOptional
	 * @param string $property
	 * @return array
	 */
	protected static function getTranslations(
		string $field,
		array $languages,
		bool $setOptional = false,
		string $property = 'title'
	): array {
		$translations = [];

		foreach ($languages as $language) {
			$translation = static::$translations[ $language->locale ][ $field ];
			if ($translation === null) {
				$translation = static::$translations['en_GB'][ $field ];
			}

			$translations[ $language->shortcode ][ $property ] = $translation;

			if ($setOptional) {
				$translation = static::$translations[ $language->locale ]['optionalPlaceholder'];
				if ($translation === null) {
					$translation = static::$translations['en_GB']['optionalPlaceholder'];
				}

				$translations[ $language->shortcode ]['optionalPlaceholder'] = $translation;
			}
		}

		return $translations;
	}
}
