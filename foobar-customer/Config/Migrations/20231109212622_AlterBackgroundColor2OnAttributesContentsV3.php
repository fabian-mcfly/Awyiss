<?php declare(strict_types=1);


use Migrations\AbstractMigration;


class AlterBackgroundColor2OnAttributesContentsV3 extends AbstractMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('attributes_contents');
		$table->renameColumn('background_color2', 'background_color');
		$table->changeColumn('background_color', 'string', [
            'default' => '',
            'limit' => 50,
            'null' => true,
        ]);
		$table->update();
	}
}
