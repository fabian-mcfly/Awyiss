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
			'privacyAccepted' => 'Acepto la política de privacidad',
			'privacyAcceptedYes' => 'Sí',
			'privacyAcceptedText' => 'He leído y acepto la política de privacidad.',
			'submit' => 'Enviar',
		],
		'fr' => [
			'name' => 'Nom',
			'phone' => 'Téléphone',
			'privacyAccepted' => 'J\'accepte la politique de confidentialité',
			'privacyAcceptedYes' => 'Oui',
			'privacyAcceptedText' => 'J\'ai lu et j\'accepte la politique de confidentialité.',
			'submit' => 'Soumettre',
		],
		'it' => [
			'name' => 'Nome',
			'phone' => 'Telefono',
			'privacyAccepted' => 'Accetto la politica sulla privacy',
			'privacyAcceptedYes' => 'Sì',
			'privacyAcceptedText' => 'Ho letto e accetto la politica sulla privacy.',
			'submit' => 'Invia',
		],
	];
	/**
	 * @inheritDoc
	 */
	protected static array $strings = ['name', 'phone', 'privacyAccepted', 'privacyAcceptedYes', 'privacyAcceptedText', 'submit'];
	/**
	 * @inheritDoc
	 */
	protected static array $translations = [];


	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		return __d('Forms', 'form_template_callback_form');
	}


	/**
	 * @inheritDoc
	 */
	public static function getElements(array $languages): array {
		$fields = ['name', 'phone', 'privacyAccepted', 'submit'];

		return static::buildElements($fields, $languages);
	}


	/**
	 * @inheritDoc
	 */
	protected static function addPhone(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'type' => 'tel',
			'identifier' => 'phone',
			'title' => static::$translations[ $mainLocale ]['phone'],
			'required' => true,
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('phone', $languages);
		}

		return $settings;
	}
}
