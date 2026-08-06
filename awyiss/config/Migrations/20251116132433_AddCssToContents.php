<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Add css column to contents table
 */
class AddCssToContents extends BaseMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-change-method
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('contents');
		$table->addColumn('css', 'text', [
            'default' => null,
            'null' => true,
			'after' => 'css_class',
        ]);
		if ($table->hasIndexByName('BY_CSS')) {
			$table->removeIndexByName('BY_CSS');
		}
		$table->update();
	}
}
