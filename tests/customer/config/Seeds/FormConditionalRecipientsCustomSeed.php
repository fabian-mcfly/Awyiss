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
				'formId' => 1,
				'type' => 'elementIdentifier',
				'field' => 'vorname',
				'operator' => '=',
				'value' => 'John',
				'recipient' => 'johnsdummy1@domain.com',
				'systemOrder' => 1,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 2,
				'formId' => 1,
				'type' => 'elementIdentifier',
				'field' => 'nachname',
				'operator' => '=',
				'value' => 'Doe',
				'recipient' => 'johnsdummy2@domain.com',
				'systemOrder' => 2,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 3,
				'formId' => 1,
				'type' => 'elementIdentifier',
				'field' => 'email',
				'operator' => 'contains',
				'value' => 'dummy',
				'recipient' => 'johnsdummy3@domain.com',
				'systemOrder' => 3,
				'createdBy' => 1,
				'createdOn' => (new \Cake\I18n\DateTime('now'))->format('Y-m-d H:i:s'),
				'changedBy' => null,
				'changedOn' => null,
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('form_conditional_recipients');
		$table->insert($data)->save();
	}
}
