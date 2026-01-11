<?php declare(strict_types=1);


use Migrations\BaseMigration;


class AddSubjectFieldsToAudit extends BaseMigration {
	/**
	 * Change Method.
	 * More information on this method is available here:
	 * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
	 *
	 * @return void
	 */
	public function change(): void {
		$this->table('audit')->addColumn('subject_left_table', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => true,
			'after' => 'foreign_key',
		])->addColumn('subject_left_foreign_key', 'integer', [
			'default' => null,
			'null' => true,
			'after' => 'subject_left_table',
		])->addColumn('subject_right_table', 'string', [
			'default' => null,
			'limit' => 50,
			'null' => true,
			'after' => 'subject_left_foreign_key',
		])->addColumn('subject_right_foreign_key', 'integer', [
			'default' => null,
			'null' => true,
			'after' => 'subject_right_table',
		])->changeColumn('type', 'enum', [
			'values' => ['c', 'd', 'u'],
			'null' => false,
		])->addIndex(
			['subject_left_table', 'subject_left_foreign_key'], ['name' => 'AUDIT_SUBJECT_LEFT']
		)->addIndex(
			['subject_right_table', 'subject_right_foreign_key'], ['name' => 'AUDIT_SUBJECT_RIGHT']
		)->update();
	}
}
