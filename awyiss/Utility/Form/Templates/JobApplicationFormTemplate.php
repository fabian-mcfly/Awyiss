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
			'titleMs' => 'Señorita',
			'titleMr' => 'Señor',
			'titleDiverse' => 'Diverso',
			'firstname' => 'Nombre',
			'lastname' => 'Apellido',
			'email' => 'Correo electrónico',
			'phone' => 'Teléfono',
			'jobApplicationFilesText' => '<strong>Documentos de solicitud</strong><br>Espacio para currículum, certificados y una carta de presentación opcional',
			'jobApplicationFile' => 'Archivo',
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
			'jobApplicationFilesText' => '<strong>Documents de candidature</strong><br>Espace pour le CV, les diplômes et une lettre de motivation optionnelle',
			'jobApplicationFile' => 'Fichier',
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
			'jobApplicationFilesText' => '<strong>Documenti di candidatura</strong><br>Spazio per curriculum vitae, certificati e una lettera di presentazione opzionale',
			'jobApplicationFile' => 'File',
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
		'title', 'titleMr', 'titleMs', 'titleDiverse', 'firstname', 'lastname', 'email', 'phone',
		'jobApplicationFilesText', 'jobApplicationFile', 'privacyAccepted', 'privacyAcceptedYes', 'privacyAcceptedText',
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
		return __d('Forms', 'form_template_job_application_form');
	}


	/**
	 * @inheritDoc
	 */
	public static function getElements(array $languages): array {
		$fields = ['title', 'firstname', 'lastname', 'phone', 'email', 'jobApplicationFilesText', 'privacyAccepted', 'submit'];

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
			'type' => 'freeText',
			'text' => static::$translations[ $mainLocale ]['jobApplicationFilesText'],
			'childFormElements' => [
				static::addJobApplicationFile('01', $mainLocale, $i18n, $languages),
				static::addJobApplicationFile('02', $mainLocale, $i18n, $languages),
				static::addJobApplicationFile('03', $mainLocale, $i18n, $languages),
				static::addJobApplicationFile('04', $mainLocale, $i18n, $languages),
			],
		];

		if ($i18n) {
			$settings['_translations'] = static::getTranslations('jobApplicationFilesText', $languages, false, 'text');
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
			'identifier' => 'jobApplicationFile' . $count,
			'type' => 'file',
			'title' => static::$translations[ $mainLocale ]['jobApplicationFile'],
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
			$settings['_translations'] = static::getTranslations('jobApplicationFile', $languages);
		}

		return $settings;
	}
}
