<?php declare(strict_types=1);


use Migrations\BaseMigration;


class AlterBackgroundColorOnDummyMigration extends BaseMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('dummy_migration');
		$table->changeColumn('background_color', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => false,
        ]);
		$table->update();
	}
}
