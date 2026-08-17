<?php declare(strict_types=1);

/**
 * Class Audit
 */
class Audit {
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
		if ($this->migration->hasTable('audit')) {
			$this->migration
				->table('audit')
				->drop()
				->save()
			;
		}

		$this->migration
			->table('audit')
			->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addPrimaryKey(['id'])
			->addColumn('scope', 'string', [
				'default' => null,
				'limit' => 50,
				'null' => false,
			])
			->addColumn('foreign_key', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addColumn('transaction_id', 'uuid', [
				'default' => null,
				'limit' => null,
				'null' => false,
			])
			->addColumn('type', 'string', [
				'default' => null,
				'limit' => null,
				'null' => false,
			])
			->addColumn('data_old', 'text', [
				'default' => null,
				'limit' => 16777215,
				'null' => true,
			])
			->addColumn('data_new', 'text', [
				'default' => null,
				'limit' => 16777215,
				'null' => true,
			])
			->addColumn('diff', 'text', [
				'default' => null,
				'limit' => 16777215,
				'null' => true,
			])
			->addColumn('created_by', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
				'signed' => true,
			])
			->addColumn('created_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addIndex(
				[
					'scope',
				], [
					'name' => 'AUDIT_SCOPE',
				]
			)
			->addIndex(
				[
					'foreign_key',
				], [
					'name' => 'AUDIT_FOREIGN_KEY',
				]
			)
			->addIndex(
				[
					'scope',
					'foreign_key',
				], [
					'name' => 'AUDIT_SCOPE_KEY',
				]
			)
			->addIndex(
				[
					'transaction_id',
				], [
					'name' => 'AUDIT_TRANSACTION_ID',
				]
			)
			->create()
		;
	}


	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down(): void {
		$this->migration
			->table('audit')
			->drop()
			->save()
		;
	}
}
