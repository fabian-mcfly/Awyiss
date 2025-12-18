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
				'model' => 'page_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'page_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'de',
				'model' => 'page_templates',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Mit Seitenteaser',
			],
			[
				'locale' => 'en',
				'model' => 'page_templates',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'With Page Teaser',
			],

			// content_templates translations
			[
				'locale' => 'de',
				'model' => 'content_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'content_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'de',
				'model' => 'content_templates',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Inhaltsblock',
			],
			[
				'locale' => 'en',
				'model' => 'content_templates',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Section',
			],

			// global_content_templates translations
			[
				'locale' => 'de',
				'model' => 'global_content_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'global_content_templates',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Standard',
			],

			// languages translations
			[
				'locale' => 'de',
				'model' => 'languages',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Deutsch',
			],
			[
				'locale' => 'en',
				'model' => 'languages',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'German',
			],
			[
				'locale' => 'de',
				'model' => 'languages',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Deutsch',
			],
			[
				'locale' => 'en',
				'model' => 'languages',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'German',
			],
			[
				'locale' => 'de',
				'model' => 'languages',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Englisch',
			],
			[
				'locale' => 'en',
				'model' => 'languages',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'English',
			],

			// dashboard_elements translations
			[
				'locale' => 'de',
				'model' => 'dashboard_elements',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Neue Formulareinträge',
			],
			[
				'locale' => 'en',
				'model' => 'dashboard_elements',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'New Form Entries',
			],
			[
				'locale' => 'de',
				'model' => 'dashboard_elements',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Neue 404 Fehler',
			],
			[
				'locale' => 'en',
				'model' => 'dashboard_elements',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'New 404 Errors',
			],

			// media_elements translations
			[
				'locale' => 'de',
				'model' => 'media_elements',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'en',
				'model' => 'media_elements',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Standard',
			],
			[
				'locale' => 'de',
				'model' => 'media_elements',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Titel- & Teaserbild',
			],
			[
				'locale' => 'en',
				'model' => 'media_elements',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Title- & Teaser image',
			],
			[
				'locale' => 'de',
				'model' => 'media_elements',
				'foreign_key' => 4,
				'field' => 'title',
				'content' => 'Galerie',
			],
			[
				'locale' => 'en',
				'model' => 'media_elements',
				'foreign_key' => 4,
				'field' => 'title',
				'content' => 'Gallery',
			],

			// media_selectors translations
			[
				'locale' => 'de',
				'model' => 'media_selectors',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Einzeldatei',
			],
			[
				'locale' => 'en',
				'model' => 'media_selectors',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Single file',
			],
			[
				'locale' => 'de',
				'model' => 'media_selectors',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Mehrfachauswahl',
			],
			[
				'locale' => 'en',
				'model' => 'media_selectors',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Multi file',
			],
			[
				'locale' => 'de',
				'model' => 'media_selectors',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Ordnerauswahl',
			],
			[
				'locale' => 'en',
				'model' => 'media_selectors',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Folder selection',
			],

			// media_element_selectors translations
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Titelbild',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 2,
				'field' => 'title',
				'content' => 'Title image',
			],
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Alternatives Teaserbild',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 3,
				'field' => 'title',
				'content' => 'Alternative Teaser image',
			],
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 4,
				'field' => 'title',
				'content' => 'Datei',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 4,
				'field' => 'title',
				'content' => 'File',
			],
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 5,
				'field' => 'title',
				'content' => 'Lightbox-Datei',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 5,
				'field' => 'title',
				'content' => 'Lightbox file',
			],
			[
				'locale' => 'de',
				'model' => 'media_element_selectors',
				'foreign_key' => 6,
				'field' => 'title',
				'content' => 'Galerie',
			],
			[
				'locale' => 'en',
				'model' => 'media_element_selectors',
				'foreign_key' => 6,
				'field' => 'title',
				'content' => 'Gallery',
			],

			// page_roles translations
			[
				'locale' => 'de',
				'model' => 'page_roles',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Seite',
			],
			[
				'locale' => 'en',
				'model' => 'page_roles',
				'foreign_key' => 1,
				'field' => 'title',
				'content' => 'Page',
			],
		];

		$table = $this->table('i18n');
		$table->insert($data)->save();
	}
}

