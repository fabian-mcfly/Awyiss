<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Class Initial
 * This class extends the AbstractMigration class and is used to manage the migrations for the application.
 * It contains methods to apply and revert migrations, as well as a method to initialize the migrations.
 */
class Initial extends BaseMigration {
	/**
	 * @var bool $autoId A flag to indicate whether to automatically generate an ID for the migration.
	 */
	public bool $autoId = false;
	/**
	 * @var array $migrations An array to store the instances of the migration classes.
	 */
	protected array $migrations = [];


	/**
	 * Apply the migrations.
	 * This method initializes the migrations and then applies each one in the order they were added.
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->initInitialMigrations();

		foreach ($this->migrations as $migration) {
			$migration->up();
		}
	}


	/**
	 * Revert the migrations.
	 * This method initializes the migrations and then reverts each one in the reverse order they were added.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->initInitialMigrations();

		foreach (array_reverse($this->migrations) as $migration) {
			$migration->down();
		}
	}


	/**
	 * Initialize the initial migration classes.
	 * This method loads the migration classes from the Initial directory and stores an instance of each class in the $migrations array.
	 * If a class has already been loaded, a new instance is not created.
	 *
	 * @return void
	 */
	protected function initInitialMigrations(): void {
		$files = glob(__DIR__ . '/Initial/*.php');
		foreach ($files as $file) {
			require_once $file;

			$className = basename($file, '.php');

			if (!isset($this->migrations[ $className ]) && class_exists($className)) {
				$classInstance = new $className($this);

				$this->migrations[ $className ] = $classInstance;
			}
		}
	}
}