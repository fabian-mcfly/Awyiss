<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Configuration Model
 *
 * @property \Awyiss\Model\Table\LanguagesTable&\Awyiss\ORM\Association\BelongsTo $Languages
 * @method \Awyiss\Model\Entity\Configuration newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ConfigurationTable extends Table {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'configuration';


	/**
	 * @var array
	 */
	protected array $configScopes;
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'enabled' => true,
		'identifier' => 'scope',
		'useDatasource' => false,
	];


	/**
	 * @return array
	 * @throws \ReflectionException
	 * @noinspection DuplicatedCode
	 */
	public function buildCategories(): array {
		/** @var \Awyiss\Model\Table\DatatablesTable $datatablesTable */
		$datatablesTable = FactoryLocator::get('Table')->get('Datatables');
		$datatables = $datatablesTable
			->findAllAndCache()
			->indexBy('identifier')
			->toArray()
		;

		/** @var \Awyiss\Model\Table\PageRolesTable $pageRolesTable */
		$pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		$pageRoles = $pageRolesTable
			->findAllAndCache()
			->indexBy(function (PageRole $pageRole) {
				return Inflector::camelize(Inflector::pluralize($pageRole->identifier));
			})
			->toArray()
		;

		$configScopes = [];
		foreach ($this->getScopes() as $identifier => $className) {
			$identifier = Inflector::camelize($identifier);

			if (isset($pageRoles[ $identifier ]) && $identifier !== 'Pages') {
				$configScopes[ $identifier ] = $pageRoles[ $identifier ]->label;

				continue;
			}

			if (isset($datatables[ $identifier ])) {
				$configScopes[ $identifier ] = $datatables[ $identifier ]->label;

				continue;
			}

			$configScopes[ $identifier ] = __d($identifier, 'menu_title');
		}

		Arrays::naturalSort($configScopes);

		return $configScopes;
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Languages', [
			'bindingKey' => [
				'realm',
				'shortcode',
			],
			'foreignKey' => [
				'realm',
				'languageShortcode',
			],
			'joinType' => 'LEFT',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'realm',
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('realm');
		$validator->add('realm', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'inList' => ['rule' => ['inList', Awyiss::getRealms()]],
			'maxLength' => ['rule' => ['maxLength', 20]],
		]);


		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('value', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
		]);


		$validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 2]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('description', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
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
			$rules->isUnique(
				[
					'realm',
					'scope',
					'identifier',
					'languageShortcode',
				],
				[
					'allowMultipleNulls' => false,
				]
			),
			'identifierUniqueForScope',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_identifier_unique_for_scope'),
			]
		);


		$rules->add(function (Configuration $entity): bool {
			return in_array($entity->realm, Awyiss::getRealms(), true);
		}, 'validRealm', [
			'errorField' => 'realm',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_realm'),
		]);


		//Validate the provided value for the scope, identifier and language.
		$rules->add(function (Configuration $entity/*, array $options*/): bool|string {
			if ($entity->getError('scope') || $entity->getError('realm')) {
				return false;
			}

			$valid = ConfigOptionsProvider::validateConfigValue(
				$entity->scope,
				$entity->realm,
				$entity->identifier,
				$entity->value,
				$entity->languageShortcode
			);

			if (!$valid) {
				$value = ConfigOptionsProvider::typecastConfigValue(
					$entity->scope,
					$entity->realm,
					$entity->identifier,
					$entity->value,
					$entity->languageShortcode
				);

				//A typecast to null cannot be valid if the initial value wasn't null.
				//This happens when one tries to json_decode a string, for example.
				if ($entity->value !== null && $value !== null) {
					$valid = ConfigOptionsProvider::validateConfigValue(
						$entity->scope,
						$entity->realm,
						$entity->identifier,
						$value,
						$entity->languageShortcode
					);
				}
			}

			return $valid;
		}, 'validValue', [
			'errorField' => 'value',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_valid_value'),
		]);


		$rules->add(function (Configuration $entity, array $options) use ($rules): bool {
			if (!$entity->get('languageShortcode')) {
				return true;
			}

			$existsIn = $rules->existsIn([
				'realm',
				'languageShortcode',
			], 'Languages', [
				'errorField' => 'languageShortcode',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_language_exists'),
			]);


			return $existsIn($entity, $options);
		}, 'languageExists');


		return $rules;
	}


	/**
	 * Returns all configurable and accessible scopes
	 *
	 * @throws \ReflectionException
	 */
	public function getScopes(): array {
		if (isset($this->configScopes)) {
			return $this->configScopes;
		}

		$this->configScopes = [];

		$identity = $this->getIdentity();

		foreach (ConfigOptionsProvider::getConfigOptionsFiles() as $scope => $className) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			if ($identity?->scopeIsAccessible($this->getTable(), ['scope' => $scope], 'read')) {
				$this->configScopes[ $scope ] = $className;
			}
		}

		ksort($this->configScopes);


		return $this->configScopes;
	}
}
