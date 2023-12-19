<?php declare(strict_types=1);


use Migrations\AbstractMigration;


class AddAlterToAttributesContents extends AbstractMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('attributes_contents');
		$table->addColumn('alter', 'string', [
            'default' => '',
            'limit' => 255,
            'null' => true,
        ]);
		$table->update();
	}
}
