<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Validation\Validator;


/**
 * ThirdPartyConsents Model
 *
 * @method \Awyiss\Model\Entity\ThirdPartyConsent newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class ThirdPartyConsentsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'third_party_consents';


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
			'consentId',
			'acceptType',
			'acceptedCategories',
			'rejectedCategories',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('consentId');
		$validator->add('consentId', [
			'isScalar' => ['rule' => 'isScalar'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'validation', 'error_exact_length', 36),
				'rule' => function ($consentId) {
					return strlen($consentId) == 36;
				},
			],
		]);


		$validator->add('acceptType', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('acceptedCategories', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);

		$validator->add('rejectedCategories', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);

		return $validator;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('accepted_categories', 'json');
		$schema->setColumnType('rejected_categories', 'json');
	}
}
