<?php declare(strict_types=1);


use Migrations\BaseMigration;


class AlterBackgroundColorOnDummyMigration extends BaseMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('dummy_migration');
		$table->renameColumn('background_color', 'background_color_renamed');
		$table->changeColumn('background_color_renamed', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => true,
        ]);
		$table->update();
	}
}
