<?php declare(strict_types=1);


use Migrations\AbstractMigration;


class CreateAttributesPages extends AbstractMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('attributes_pages');
		$table->addColumn('page_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
		$table->addColumn('testdate', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
		$table->create();
	}
}
