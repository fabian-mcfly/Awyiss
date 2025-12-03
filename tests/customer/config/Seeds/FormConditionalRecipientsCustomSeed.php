<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * Form Conditional Recipients seed.
 */
class FormConditionalRecipientsCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$data = [
			[
				'id' => 1,
				'form_id' => 1,
				'type' => 'element_identifier',
				'field' => 'vorname',
				'operator' => '=',
				'value' => 'John',
				'recipient' => 'johnsdummy1@domain.com',
				'system_order' => 1,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 2,
				'form_id' => 1,
				'type' => 'element_identifier',
				'field' => 'nachname',
				'operator' => '=',
				'value' => 'Doe',
				'recipient' => 'johnsdummy2@domain.com',
				'system_order' => 2,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 3,
				'form_id' => 1,
				'type' => 'element_identifier',
				'field' => 'email',
				'operator' => 'contains',
				'value' => 'dummy',
				'recipient' => 'johnsdummy3@domain.com',
				'system_order' => 3,
				'created_by' => 1,
				'created_on' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changed_by' => null,
				'changed_on' => null,
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$table = $this->table('form_conditional_recipients');
		$table->insert($data)->save();
	}
}
