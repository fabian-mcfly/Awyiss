<?php declare(strict_types=1);


/**
 * Class I18n
 */
class I18n {
	/**
	 * @var \Initial $migration The migration that is being migrated
	 */
	protected Initial $migration;


	/**
	 * Constructor
	 *
	 * @param \Initial $migration The migration that is being migrated
	 */
	public function __construct(Initial $migration) {
		$this->migration = $migration;
	}


	/**
	 * Migrate Up.
	 * 
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function up(): void {
		$this->migration->table('i18n')->addColumn('id', 'integer', [
			'autoIncrement' => true,
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addPrimaryKey(['id'])->addColumn('locale', 'string', [
			'default' => null,
			'limit' => 2,
			'null' => false,
		])->addColumn('model', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => false,
		])->addColumn('foreign_key', 'integer', [
			'default' => null,
			'limit' => null,
			'null' => false,
			'signed' => true,
		])->addColumn('field', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => false,
		])->addColumn('content', 'text', [
			'default' => null,
			'limit' => null,
			'null' => true,
		])->addIndex(
			[
				'locale',
				'model',
				'foreign_key',
				'field',
			], [
				'name' => 'I18N_LOCALE_FIELD',
				'unique' => true,
			]
		)->addIndex(
			[
				'model',
				'foreign_key',
				'field',
			], [
				'name' => 'I18N_FIELD',
			]
		)->create();
	}


	/**
	 * Migrate Down.
	 * 
	 * @return void
	 */
	public function down(): void {
		$this->migration->table('i18n')->drop()->save();
	}
}
