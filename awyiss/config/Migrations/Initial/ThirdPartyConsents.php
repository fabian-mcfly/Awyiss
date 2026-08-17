<?php declare(strict_types=1);

/**
 * Class ThirdPartyConsent
 */
class ThirdPartyConsents {
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
		if ($this->migration->hasTable('third_party_consents')) {
			$this->migration
				->table('third_party_consents')
				->drop()
				->save()
			;
		}

		$this->migration
			->table('third_party_consents')
			->addColumn('id', 'integer', [
				'autoIncrement' => true,
				'default' => null,
				'limit' => null,
				'null' => false,
				'signed' => true,
			])
			->addPrimaryKey(['id'])
			->addColumn('consent_id', 'char', [
				'default' => null,
				'limit' => 36,
				'null' => true,
			])
			->addColumn('accept_type', 'string', [
				'default' => null,
				'limit' => 50,
				'null' => false,
			])
			->addColumn('accepted_categories', 'text', [
				'default' => null,
				'limit' => 4294967295,
				'null' => true,
			])
			->addColumn('rejected_categories', 'text', [
				'default' => null,
				'limit' => 4294967295,
				'null' => true,
			])
			->addColumn('created_on', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addIndex(
				[
					'consent_id',
				], [
					'name' => 'THIRD_PARTY_CONSENT_CONSENT_ID',
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
			->table('third_party_consents')
			->drop()
			->save()
		;
	}
}
