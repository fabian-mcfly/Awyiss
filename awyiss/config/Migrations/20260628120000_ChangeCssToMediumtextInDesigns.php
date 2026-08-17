<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;


/**
 * Change css column in designs table to mediumtext to avoid truncation of large CSS content.
 */
class ChangeCssToMediumtextInDesigns extends BaseMigration {
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-change-method
	 *
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('designs');
		$table->changeColumn('css', 'text', [
			'limit' => MysqlAdapter::TEXT_MEDIUM,
			'default' => null,
			'null' => true,
		]);
		$table->update();
	}
}

