<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Validation\Validator;


/**
 * Audit Model
 *
 * @method \Awyiss\Model\Entity\Audit newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
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
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Users', [
			'foreignKey' => 'created_by',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'scope',
			'parentId',
			'transactionId',
			'type',
			'createdOn',
			'createdBy',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('scope');
		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$validator->notEmptyString('transactionId');
		$validator->add('transactionId', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 36]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('type');
		$validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'inList' => ['rule' => ['inList', ['u', 'd']]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('dataOld', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($value) {
					return strlen(json_encode($value)) <= 16777215;
				},
			],
		]);


		$validator->add('dataNew', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($value) {
					return strlen(json_encode($value)) <= 16777215;
				},
			],
		]);


		$validator->add('diff', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($value) {
					return strlen(json_encode($value)) <= 16777215;
				},
			],
		]);


		$validator->notEmptyDateTime('createdOn');
		$validator->add('createdOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		$validator->notEmptyString('createdBy');
		$validator->add('createdBy', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('data_old', 'json');
		$schema->setColumnType('data_new', 'json');
		$schema->setColumnType('diff', 'json');
	}
}
