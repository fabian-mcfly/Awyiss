<?php declare(strict_types=1);


use Migrations\BaseMigration;


class AddLanguageShortcodeToFormEntries extends BaseMigration {
	public function change(): void {
		$table = $this->table('form_entries');
		$table->addColumn('language_shortcode', 'char', [
            'default' => null,
            'limit' => 2,
            'null' => true,
			'after' => 'page_id',
        ])->addIndex(
			[
				'language_shortcode',
			], [
				'name' => 'FORM_ENTRIES_LANGUAGE_SHORTCODE',
			]
		);

		$table->update();
	}
}
