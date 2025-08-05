<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Design;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Designs Model
 *
 * @method \Awyiss\Model\Entity\Design newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class DesignsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'designs';


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'description',
		],
	];


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'identifier',
		], 'create');

		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);

		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 12]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		$validator->add('description', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);

		$validator->add('settings', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);

		$validator->add('css', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);

		$validator->add('inUse', [
			'boolean' => ['rule' => 'boolean'],
		]);

		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->isUnique(['identifier']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique'),
		]);

		$rules->addDelete(
			function (Design $entity/*, array $options*/): bool {
				return $entity->inUse === false;
			},
			'notInUse',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_not_in_use'),
			]
		);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('settings', 'json');
	}
}
