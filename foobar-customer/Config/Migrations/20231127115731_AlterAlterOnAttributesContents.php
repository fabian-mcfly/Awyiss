<?php declare(strict_types=1);


use Migrations\AbstractMigration;


class AlterAlterOnAttributesContents extends AbstractMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('attributes_contents');
		$table->renameColumn('alter', 'alter2');
		$table->changeColumn('alter2', 'string', [
            'default' => '',
            'limit' => 255,
            'null' => true,
        ]);
		$table->update();
	}
}
