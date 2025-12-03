<?php declare(strict_types=1);


namespace Awyiss\Utility\Form\Templates;


/**
 * AppointmentFormTemplate
 */
class AppointmentFormTemplate extends AbstractFormTemplate {
	/**
	 * @inheritDoc
	 */
	protected static array $staticTranslations = [
		'es' => [
			'title' => 'Tratamiento',
			'title_ms' => 'Señorita',
			'title_mr' => 'Señor',
			'title_diverse' => 'Diverso',
			'name' => 'Nombre',
			'email' => 'Correo electrónico',
			'phone' => 'Teléfono',
			'datetime' => 'Fecha y hora',
			'message' => 'Mensaje',
			'privacy_accepted' => 'Acepto la política de privacidad',
			'privacy_accepted_yes' => 'Sí',
			'privacy_accepted_text' => 'He leído y acepto la política de privacidad.',
			'submit' => 'Enviar',
		],
		'fr' => [
			'title' => 'Civilité',
			'title_ms' => 'Mademoiselle',
			'title_mr' => 'Monsieur',
			'title_diverse' => 'Divers',
			'name' => 'Nom',
			'email' => 'E-mail',
			'phone' => 'Téléphone',
			'datetime' => 'Date et heure',
			'message' => 'Message',
			'privacy_accepted' => 'J\'accepte la politique de confidentialité',
			'privacy_accepted_yes' => 'Oui',
			'privacy_accepted_text' => 'J\'ai lu et j\'accepte la politique de confidentialité.',
			'submit' => 'Soumettre',
		],
		'it' => [
			'title' => 'Titolo',
			'title_ms' => 'Signorina',
			'title_mr' => 'Signore',
			'title_diverse' => 'Diverso',
			'firstname' => 'Nome',
			'email' => 'E-mail',
			'phone' => 'Telefono',
			'datetime' => 'Data e ora',
			'message' => 'Messaggio',
			'privacy_accepted' => 'Accetto la politica sulla privacy',
			'privacy_accepted_yes' => 'Sì',
			'privacy_accepted_text' => 'Ho letto e accetto la politica sulla privacy.',
			'submit' => 'Invia',
		],
	];
	/**
	 * @inheritDoc
	 */
	protected static array $strings = [
		'title', 'title_mr', 'title_ms', 'title_diverse',
		'name', 'email', 'phone', 'datetime', 'message',
		'privacy_accepted', 'privacy_accepted_yes', 'privacy_accepted_text',
		'submit',
	];
	/**
	 * @inheritDoc
	 */
	protected static array $translations = [];


	/**
	 * @inheritDoc
	 */
	public static function getTitle(): string {
		return __d('forms', 'form_template_appointment_form');
	}


	/**
	 * @inheritDoc
	 */
	public static function getElements(array $languages): array {
		$fields = ['title', 'name', 'phone', 'email', 'datetime', 'message', 'privacy_accepted', 'submit'];

		return static::buildElements($fields, $languages);
	}


	/**
	 * @inheritDoc
	 */
	protected static function addDatetime(string $mainLocale, bool $i18n, array $languages): array {
		$settings = parent::addDatetime($mainLocale, $i18n, $languages);
		$settings['required'] = true;

		return $settings;
	}
}
