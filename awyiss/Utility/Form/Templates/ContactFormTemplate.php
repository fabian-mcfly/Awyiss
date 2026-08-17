<?php declare(strict_types=1);


namespace Awyiss\Utility\Form\Templates;


/**
 * ContactFormTemplate
 * Provides a contact form template with the following fields:
 * - title
 * - firstname
 * - lastname
 * - email
 * - phone
 * - message
 * - privacy_accepted
 * - submit
 */
class ContactFormTemplate extends AbstractFormTemplate {
	/**
	 * @inheritDoc
	 */
	protected static array $staticTranslations = [
		'es' => [
			'title' => 'Tratamiento',
			'titleMs' => 'Señorita',
			'titleMr' => 'Señor',
			'titleDiverse' => 'Diverso',
			'firstname' => 'Nombre',
			'lastname' => 'Apellido',
			'email' => 'Correo electrónico',
			'phone' => 'Teléfono',
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
			'firstname' => 'Prénom',
			'lastname' => 'Nom de famille',
			'email' => 'E-mail',
			'phone' => 'Téléphone',
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
			'lastname' => 'Cognome',
			'email' => 'E-mail',
			'phone' => 'Telefono',
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
		'firstname',
		'lastname',
		'email',
		'phone',
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
		return __d('Forms', 'form_template_contact_form');
	}


	/**
	 * @inheritDoc
	 */
	public static function getElements(array $languages): array {
		$fields = ['title', 'firstname', 'lastname', 'phone', 'email', 'message', 'privacyAccepted', 'submit'];

		return static::buildElements($fields, $languages);
	}
}
