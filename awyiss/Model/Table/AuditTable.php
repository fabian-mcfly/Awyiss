<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Validation\Validator;


/**
 * Audit Model
 *
 * @method \Awyiss\Model\Entity\Audit newDefaultEntity(array $aa_additionalData = [])
 */
class AuditTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'audit';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'scope',
			'parentId',
			'transactionId',
			'type',
			'createdOn',
			'createdBy',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('scope');
		$ao_validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->notEmptyString('transactionId');
		$ao_validator->add('transactionId', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 36]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('type');
		$ao_validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'inList' => ['rule' => ['inList', ['u', 'd']]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('dataOld', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($ax_value) {
					return strlen(json_encode($ax_value)) <= 16777215;
				},
			],
		]);


		$ao_validator->add('dataNew', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($ax_value) {
					return strlen(json_encode($ax_value)) <= 16777215;
				},
			],
		]);


		$ao_validator->add('diff', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($ax_value) {
					return strlen(json_encode($ax_value)) <= 16777215;
				},
			],
		]);


		$ao_validator->notEmptyDateTime('createdOn');
		$ao_validator->add('createdOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		$ao_validator->notEmptyString('createdBy');
		$ao_validator->add('createdBy', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$ao_schema->setColumnType('data_old', 'json');
		$ao_schema->setColumnType('data_new', 'json');
		$ao_schema->setColumnType('diff', 'json');
	}
}
