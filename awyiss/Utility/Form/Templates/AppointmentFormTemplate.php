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
			'titleMs' => 'Señorita',
			'titleMr' => 'Señor',
			'titleDiverse' => 'Diverso',
			'name' => 'Nombre',
			'email' => 'Correo electrónico',
			'phone' => 'Teléfono',
			'datetime' => 'Fecha y hora',
			'message' => 'Mensaje',
			'privacyAccepted' => 'Acepto la política de privacidad',
			'privacyAcceptedYes' => 'Sí',
			'privacyAcceptedText' => 'He leído y acepto la política de privacidad.',
			'submit' => 'Enviar',
		],
		'fr' => [
			'title' => 'Civilité',
			'titleMs' => 'Mademoiselle',
			'titleMr' => 'Monsieur',
			'titleDiverse' => 'Divers',
			'name' => 'Nom',
			'email' => 'E-mail',
			'phone' => 'Téléphone',
			'datetime' => 'Date et heure',
			'message' => 'Message',
			'privacyAccepted' => 'J\'accepte la politique de confidentialité',
			'privacyAcceptedYes' => 'Oui',
			'privacyAcceptedText' => 'J\'ai lu et j\'accepte la politique de confidentialité.',
			'submit' => 'Soumettre',
		],
		'it' => [
			'title' => 'Titolo',
			'titleMs' => 'Signorina',
			'titleMr' => 'Signore',
			'titleDiverse' => 'Diverso',
			'firstname' => 'Nome',
			'email' => 'E-mail',
			'phone' => 'Telefono',
			'datetime' => 'Data e ora',
			'message' => 'Messaggio',
			'privacyAccepted' => 'Accetto la politica sulla privacy',
			'privacyAcceptedYes' => 'Sì',
			'privacyAcceptedText' => 'Ho letto e accetto la politica sulla privacy.',
			'submit' => 'Invia',
		],
	];
	/**
	 * @inheritDoc
	 */
	protected static array $strings = [
		'title',
		'titleMr',
		'titleMs',
		'titleDiverse',
		'name',
		'email',
		'phone',
		'datetime',
		'message',
		'privacyAccepted',
		'privacyAcceptedYes',
		'privacyAcceptedText',
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
		return __d('Forms', 'form_template_appointment_form');
	}


	/**
	 * @inheritDoc
	 */
	public static function getElements(array $languages): array {
		$fields = ['title', 'name', 'phone', 'email', 'datetime', 'message', 'privacyAccepted', 'submit'];

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
