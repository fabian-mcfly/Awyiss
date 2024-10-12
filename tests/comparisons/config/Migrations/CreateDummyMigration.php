<?php declare(strict_types=1);


use Migrations\AbstractMigration;


class CreateDummyMigration extends AbstractMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('dummy_migration');
		$table->addColumn('content_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
		$table->addColumn('testattribute', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);
		$table->create();
	}
}
