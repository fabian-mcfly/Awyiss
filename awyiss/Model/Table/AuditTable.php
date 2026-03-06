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
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'audit';


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
			'foreignKey' => 'createdBy',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'scope',
			'foreignKey',
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
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('foreignKey', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$validator->notEmptyString('subjectLeftTable');
		$validator->add('subjectLeftTable', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('subjectLeftForeignKey', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$validator->notEmptyString('subjectRightTable');
		$validator->add('subjectRightTable', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('subjectRightForeignKey', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$validator->notEmptyString('transactionId');
		$validator->add('transactionId', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 36]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('type');
		$validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'inList' => ['rule' => ['inList', ['c', 'd', 'u']]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('dataOld', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('dataNew', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
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

		$schema->setColumnType('diff', 'json');
	}
}
