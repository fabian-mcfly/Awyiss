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
			'title_ms' => 'Señorita',
			'title_mr' => 'Señor',
			'title_diverse' => 'Diverso',
			'firstname' => 'Nombre',
			'lastname' => 'Apellido',
			'email' => 'Correo electrónico',
			'phone' => 'Teléfono',
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
			'firstname' => 'Prénom',
			'lastname' => 'Nom de famille',
			'email' => 'E-mail',
			'phone' => 'Téléphone',
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
			'lastname' => 'Cognome',
			'email' => 'E-mail',
			'phone' => 'Telefono',
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
		'firstname', 'lastname', 'email', 'phone', 'message',
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
		return __d('forms', 'form_template_contact_form');
	}


	/**
	 * @inheritDoc
	 */
	public static function getElements(array $languages): array {
		$fields = ['title', 'firstname', 'lastname', 'phone', 'email', 'message', 'privacy_accepted', 'submit'];

		return static::buildElements($fields, $languages);
	}
}
