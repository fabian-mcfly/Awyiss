<?php declare(strict_types=1);


namespace Awyiss\Utility\Form\Templates;


/**
 * CallbackFormTemplate
 * Provides a simple callback form template with the following fields:
 * - name
 * - phone
 * - privacy_accepted
 * - submit
 */
class CallbackFormTemplate extends AbstractFormTemplate {
	/**
	 * @inheritDoc
	 */
	protected static array $staticTranslations = [
		'es' => [
			'name' => 'Nombre',
			'phone' => 'Teléfono',
			'privacy_accepted' => 'Acepto la política de privacidad',
			'privacy_accepted_yes' => 'Sí',
			'privacy_accepted_text' => 'He leído y acepto la política de privacidad.',
			'submit' => 'Enviar',
		],
		'fr' => [
			'name' => 'Nom',
			'phone' => 'Téléphone',
			'privacy_accepted' => 'J\'accepte la politique de confidentialité',
			'privacy_accepted_yes' => 'Oui',
			'privacy_accepted_text' => 'J\'ai lu et j\'accepte la politique de confidentialité.',
			'submit' => 'Soumettre',
		],
		'it' => [
			'name' => 'Nome',
			'phone' => 'Telefono',
			'privacy_accepted' => 'Accetto la politica sulla privacy',
			'privacy_accepted_yes' => 'Sì',
			'privacy_accepted_text' => 'Ho letto e accetto la politica sulla privacy.',
			'submit' => 'Invia',
		],
	];
	/**
	 * @inheritDoc
	 */
	protected static array $strings = ['name', 'phone', 'privacy_accepted', 'privacy_accepted_yes', 'privacy_accepted_text', 'submit'];
	/**
	 * @inheritDoc
	 */
	protected static array $translations = [];


	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		return __d('forms', 'form_template_callback_form');
	}


	/**
	 * @inheritDoc
	 */
	public static function getElements(array $languages): array {
		$la_fields = ['name', 'phone', 'privacy_accepted', 'submit'];

		return static::buildElements($la_fields, $languages);
	}


	/**
	 * @inheritDoc
	 */
	protected static function addPhone(string $mainLocale, bool $i18n, array $languages): array {
		$la_settings = [
			'type' => 'tel',
			'identifier' => 'phone',
			'title' => static::$translations[ $mainLocale ]['phone'],
			'required' => true,
		];

		if ($i18n) {
			$la_settings['_translations'] = static::getTranslations('phone', $languages);
		}

		return $la_settings;
	}
}
