<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * I18n seed - Consolidated translations from all seed files.
 */
class I18nSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			// page_templates translations
			[
				'locale' => 'de',
				'model' => 'PageTemplates',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'PageTemplates',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'de',
				'model' => 'PageTemplates',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Mit Seitenteaser',
			],
			[
				'locale' => 'en',
				'model' => 'PageTemplates',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'With Page Teaser',
			],

			// content_templates translations
			[
				'locale' => 'de',
				'model' => 'ContentTemplates',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'ContentTemplates',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'de',
				'model' => 'ContentTemplates',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Inhaltsblock',
			],
			[
				'locale' => 'en',
				'model' => 'ContentTemplates',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Section',
			],

			// global_content_templates translations
			[
				'locale' => 'de',
				'model' => 'GlobalContentTemplates',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'GlobalContentTemplates',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],

			// languages translations
			[
				'locale' => 'de',
				'model' => 'Languages',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Deutsch',
			],
			[
				'locale' => 'en',
				'model' => 'Languages',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'German',
			],
			[
				'locale' => 'de',
				'model' => 'Languages',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Deutsch',
			],
			[
				'locale' => 'en',
				'model' => 'Languages',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'German',
			],
			[
				'locale' => 'de',
				'model' => 'Languages',
				'foreignKey' => 3,
				'field' => 'title',
				'content' => 'Englisch',
			],
			[
				'locale' => 'en',
				'model' => 'Languages',
				'foreignKey' => 3,
				'field' => 'title',
				'content' => 'English',
			],

			// dashboard_elements translations
			[
				'locale' => 'de',
				'model' => 'DashboardElements',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Neue Formulareinträge',
			],
			[
				'locale' => 'en',
				'model' => 'DashboardElements',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'New Form Entries',
			],
			[
				'locale' => 'de',
				'model' => 'DashboardElements',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Neue 404 Fehler',
			],
			[
				'locale' => 'en',
				'model' => 'DashboardElements',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'New 404 Errors',
			],

			// media_elements translations
			[
				'locale' => 'de',
				'model' => 'MediaElements',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'MediaElements',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'de',
				'model' => 'MediaElements',
				'foreignKey' => 3,
				'field' => 'title',
				'content' => 'Titel- & Teaserbild',
			],
			[
				'locale' => 'en',
				'model' => 'MediaElements',
				'foreignKey' => 3,
				'field' => 'title',
				'content' => 'Title- & Teaser image',
			],
			[
				'locale' => 'de',
				'model' => 'MediaElements',
				'foreignKey' => 4,
				'field' => 'title',
				'content' => 'Galerie',
			],
			[
				'locale' => 'en',
				'model' => 'MediaElements',
				'foreignKey' => 4,
				'field' => 'title',
				'content' => 'Gallery',
			],

			// media_selectors translations
			[
				'locale' => 'de',
				'model' => 'MediaSelectors',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Einzeldatei',
			],
			[
				'locale' => 'en',
				'model' => 'MediaSelectors',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Single file',
			],
			[
				'locale' => 'de',
				'model' => 'MediaSelectors',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Mehrfachauswahl',
			],
			[
				'locale' => 'en',
				'model' => 'MediaSelectors',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Multi file',
			],
			[
				'locale' => 'de',
				'model' => 'MediaSelectors',
				'foreignKey' => 3,
				'field' => 'title',
				'content' => 'Ordnerauswahl',
			],
			[
				'locale' => 'en',
				'model' => 'MediaSelectors',
				'foreignKey' => 3,
				'field' => 'title',
				'content' => 'Folder selection',
			],

			// media_element_selectors translations
			[
				'locale' => 'de',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Titelbild',
			],
			[
				'locale' => 'en',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 2,
				'field' => 'title',
				'content' => 'Title image',
			],
			[
				'locale' => 'de',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 3,
				'field' => 'title',
				'content' => 'Alternatives Teaserbild',
			],
			[
				'locale' => 'en',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 3,
				'field' => 'title',
				'content' => 'Alternative Teaser image',
			],
			[
				'locale' => 'de',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 4,
				'field' => 'title',
				'content' => 'Datei',
			],
			[
				'locale' => 'en',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 4,
				'field' => 'title',
				'content' => 'File',
			],
			[
				'locale' => 'de',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 5,
				'field' => 'title',
				'content' => 'Lightbox-Datei',
			],
			[
				'locale' => 'en',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 5,
				'field' => 'title',
				'content' => 'Lightbox file',
			],
			[
				'locale' => 'de',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 6,
				'field' => 'title',
				'content' => 'Galerie',
			],
			[
				'locale' => 'en',
				'model' => 'MediaElementSelectors',
				'foreignKey' => 6,
				'field' => 'title',
				'content' => 'Gallery',
			],

			// page_roles translations
			[
				'locale' => 'de',
				'model' => 'PageRoles',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Seite',
			],
			[
				'locale' => 'en',
				'model' => 'PageRoles',
				'foreignKey' => 1,
				'field' => 'title',
				'content' => 'Page',
			],
		];

		$table = $this->table('i18n');
		$table->insert($data)->save();
	}
}

