<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * FormEntries Model
 *
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Form
 * @method \Awyiss\Model\Entity\FormEntry newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class FormEntriesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'form_entries';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'associationName' => 'Forms',
		'enabled' => true,
		'identifier' => 'form',
	];
	/**
	 * @inheritDoc
	 */
	protected array $search = [
		'blocklistedColumns' => ['form_id', 'page_id'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Forms');
		$this->belongsTo('Pages', [
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'formId',
			'pageId',
			'ipHash',
			'postHash',
		], 'create');


		$validator->notEmptyString('formId');
		$validator->add('formId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('pageId');
		$validator->add('pageId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('subject');
		$validator->add('subject', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('subjectConfirmation');
		$validator->add('subjectConfirmation', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('emailBody', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('emailConfirmationBody', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('data', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->notEmptyString('ipHash');
		$validator->add('ipHash', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 40]],
		]);


		$validator->notEmptyString('postHash');
		$validator->add('postHash', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 40]],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param BaseRulesChecker $rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->existsIn('formId', 'Forms'),
			'formExists',
			[
				'errorField' => 'formId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_form_exists'),
			]
		);

		$rules->add(
			$rules->existsIn('pageId', 'Pages'),
			'pageExists',
			[
				'errorField' => 'pageId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_page_exists'),
			]
		);

		return $rules;
	}
}
