<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Enum\CustomerGroupAccessType;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * CustomerGroupAccessSettings Model
 *
 * @method \Awyiss\Model\Entity\CustomerGroupAccessSetting newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class CustomerGroupAccessSettingsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'customer_group_access_settings';


	/**
	 * @inheritDoc
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'scope',
			'accessType',
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

		$validator->allowEmptyString('foreignKey');
		$validator->add('foreignKey', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);

		$validator->notEmptyString('accessType');
		$validator->add('accessType', [
			'enum' => ['rule' => ['enum', CustomerGroupAccessType::class]],
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
		$rules->add(
			function ($entity): bool {
				if ($entity->accessType === null) {
					return true;
				}

				if (is_string($entity->accessType)) {
					return CustomerGroupAccessType::tryFrom($entity->accessType) !== null;
				}

				return in_array($entity->accessType, CustomerGroupAccessType::cases());
			},
			'validAccessType',
			[
				'errorField' => 'accessType',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_access_type'),
			]
		);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('accessType', EnumType::from(CustomerGroupAccessType::class));
	}
}
