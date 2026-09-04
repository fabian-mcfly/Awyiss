<?php /** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Adds the data required for optional or mandatory backend two-factor authentication.
 */
class AddTwoFactorToUsers extends BaseMigration {
	/**
	 * Add the two-factor fields to users.
	 *
	 * @return void
	 */
	public function up(): void {
		$this
			->table('users')
			->addColumn('twoFactorEnabled', 'boolean', [
				'default' => false,
				'null' => false,
				'after' => 'failedAttempts',
			])
			->addColumn('twoFactorSecret', 'binary', [
				'default' => null,
				'limit' => 255,
				'null' => true,
				'after' => 'twoFactorEnabled',
			])
			->save()
		;
	}


	/**
	 * Remove the two-factor fields from users.
	 *
	 * @return void
	 */
	public function down(): void {
		$this
			->table('users')
			->removeColumn('twoFactorEnabled')
			->removeColumn('twoFactorSecret')
			->save()
		;
	}
}

