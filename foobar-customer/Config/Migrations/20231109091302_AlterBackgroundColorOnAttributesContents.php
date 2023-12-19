<?php declare(strict_types=1);


use Migrations\AbstractMigration;


class AlterBackgroundColorOnAttributesContents extends AbstractMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('attributes_contents');
		$table->renameColumn('background_color', 'background_color2');
		$table->changeColumn('background_color2', 'string', [
            'default' => '',
            'limit' => 50,
            'null' => true,
        ]);
		$table->update();
	}
}
