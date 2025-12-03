<?php declare(strict_types=1);


namespace Awyiss\Utility\Form\Templates;


/**
 * JobApplicationFormTemplate
 * Provides an application form template with the following fields:
 */
class JobApplicationFormTemplate extends AbstractFormTemplate {
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
			'job_application_files_text' => '<strong>Documentos de solicitud</strong><br>Espacio para currículum, certificados y una carta de presentación opcional',
			'job_application_file' => 'Archivo',
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
			'job_application_files_text' => '<strong>Documents de candidature</strong><br>Espace pour le CV, les diplômes et une lettre de motivation optionnelle',
			'job_application_file' => 'Fichier',
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
			'job_application_files_text' => '<strong>Documenti di candidatura</strong><br>Spazio per curriculum vitae, certificati e una lettera di presentazione opzionale',
			'job_application_file' => 'File',
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
		'title', 'title_mr', 'title_ms', 'title_diverse', 'firstname', 'lastname', 'email', 'phone',
		'job_application_files_text', 'job_application_file', 'privacy_accepted', 'privacy_accepted_yes', 'privacy_accepted_text',
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
		return __d('forms', 'form_template_job_application_form');
	}


	/**
	 * @inheritDoc
	 */
	public static function getElements(array $languages): array {
		$fields = ['title', 'firstname', 'lastname', 'phone', 'email', 'job_application_files_text', 'privacy_accepted', 'submit'];

		return static::buildElements($fields, $languages);
	}


	/**
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected static function addJobApplicationFilesText(string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'type' => 'free_text',
			'text' => static::$translations[ $mainLocale ]['job_application_files_text'],
			'child_form_elements' => [
				static::addJobApplicationFile('01', $mainLocale, $i18n, $languages),
				static::addJobApplicationFile('02', $mainLocale, $i18n, $languages),
				static::addJobApplicationFile('03', $mainLocale, $i18n, $languages),
				static::addJobApplicationFile('04', $mainLocale, $i18n, $languages),
			],
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('job_application_files_text', $languages, false, 'text');
		}

		return $settings;
	}


	/**
	 * @param string $count
	 * @param string $mainLocale
	 * @param bool $i18n
	 * @param array<string, \Awyiss\Model\Entity\Language> $languages
	 * @return array
	 */
	protected static function addJobApplicationFile(string $count, string $mainLocale, bool $i18n, array $languages): array {
		$settings = [
			'identifier' => 'job_application_file_' . $count,
			'type' => 'file',
			'title' => static::$translations[ $mainLocale ]['job_application_file'],
			'titleEmail' => null,
			'placeholder' => null,
			'text' => null,
			'options' => null,
			'columnWidth' => '1/1',
			'columnIndent' => null,
			'columnLast' => false,
			'columnRtl' => false,
			'cssClass' => null,
			'required' => $count === '01',
			'systemOrder' => (int)$count,
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('job_application_file', $languages);
		}

		return $settings;
	}
}
