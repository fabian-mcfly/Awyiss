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
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'third_party_consents';


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
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
			'notBoolean' => ['rule' => 'notBoolean'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_exact_length', 36),
				'rule' => function (string $consentId): bool {
					return strlen($consentId) == 36;
				},
			],
		]);


		$validator->add('acceptType', [
			'isScalar' => ['rule' => 'isScalar'],
			'inList' => [
				'rule' => [
					'inList',
					['all', 'custom', 'necessary'],
				],
			],
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

		$schema->setColumnType('acceptedCategories', 'json');
		$schema->setColumnType('rejectedCategories', 'json');
	}
}
