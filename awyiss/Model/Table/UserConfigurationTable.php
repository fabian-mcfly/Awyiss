<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Entity\UserConfiguration;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * User-specific Configuration Model
 *
 * @method \Awyiss\Model\Entity\UserConfiguration newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class UserConfigurationTable extends Table {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'user_configuration';


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
	 * @inheritDoc
	 */
	protected array $search = [
		'blocklistedColumns' => ['user_id'],
	];


	/**
	 * @return array
	 * @throws \ReflectionException
	 */
	public function buildCategories(): array {
		/** @var \Awyiss\Model\Table\DatatablesTable $datatablesTable */
		$datatablesTable = FactoryLocator::get('Table')->get('Datatables');
		$datatables = $datatablesTable->findAllAndCache()->indexBy('identifier')->toArray();

		/** @var \Awyiss\Model\Table\PageRolesTable $pageRolesTable */
		$pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		$pageRoles = $pageRolesTable->findAllAndCache()->indexBy(function (PageRole $pageRole) {
			return Inflector::pluralize($pageRole->identifier);
		})->toArray();

		$configScopes = [];
		foreach ($this->getScopes() as $identifier => $className) {
			$identifier = Inflector::underscore($identifier);

			if (isset($pageRoles[ $identifier ])) {
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
		$this->belongsTo('Users', [
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
			'userId',
			'scope',
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('userId', [
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


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('value', [
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
			function (UserConfiguration $entity/*, array $options*/): bool|string {
				if (
					$entity->hasOriginal('userId') &&
					$entity->get('userId') !== $entity->getOriginal('userId')
				) {
					return __df($this->getI18nDomain(), 'validation', 'error_user_id_unchanged');
				}


				return true;
			},
			'userIdUnchanged',
			[
				'errorField' => '_general',
			]
		);


		$rules->add(
			$rules->isUnique(
				[
					'scope',
					'identifier',
					'user_id',
				],
				[
					'allowMultipleNulls' => false,
				]
			),
			'identifierUniqueForScope',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique_for_scope'),
			]
		);


		$rules->add(function (UserConfiguration $entity/*, array $options*/): bool {
			$configOptions = ConfigOptionsProvider::loadConfigOptions($entity->scope);
			$configOption = $configOptions?->getConfigOption(Awyiss::REALM_BACKEND, $entity->identifier);


			return $configOption && $configOption->isPersonalizable();
		}, 'configOptionIsPersonalizable', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_config_option_is_personalizable'),
		]);


		//Validate the provided value for the scope, identifier and language.
		$rules->add(function (UserConfiguration $entity/*, array $options*/): bool|string {
			$valid = ConfigOptionsProvider::validateConfigValue(
				$entity->scope,
				Awyiss::REALM_BACKEND,
				$entity->identifier,
				$entity->value
			);

			if (!$valid) {
				$value = ConfigOptionsProvider::typecastConfigValue(
					$entity->scope,
					Awyiss::REALM_BACKEND,
					$entity->identifier,
					$entity->value
				);

				//A typecast to null cannot be valid if the initial value wasn't null.
				//This happens when one tries to json_decode a string, for example.
				if ($entity->value !== null && $value !== null) {
					$valid = ConfigOptionsProvider::validateConfigValue(
						$entity->scope,
						Awyiss::REALM_BACKEND,
						$entity->identifier,
						$value
					);
				}
			}

			return $valid;
		},
		'validValue',
		[
			'errorField' => 'value',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_value'),
		]);


		$rules->addDelete(
			function (UserConfiguration $entity/*, array $options*/): bool {
				return $entity->userId === $this->getIdentity()->getIdentifier();
			},
			'configOwnedByUser',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_config_owned_by_user'),
			]
		);


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
			if (
				// For now, contents are always accessible since accessing them depends on page roles
				in_array($scope, ['Contents', 'System'], true) ||
				// Form elements are accessible if the user has access to the Forms scope
				(
					$scope === 'FormElements' &&
					$identity?->scopeIsAccessible('Forms', [], ['read', 'create', 'update', 'configure'])
				) ||
				// Menu entries are accessible if the user has access to the Menus scope
				(
					$scope === 'MenuEntries' &&
					$identity?->scopeIsAccessible('Menus', [], ['read', 'create', 'update', 'configure'])
				) ||
				// The user has access to the scope if any access is granted
				$identity?->scopeIsAccessible($scope, [], ['read', 'create', 'update', 'configure'])
			) {
				$this->configScopes[ $scope ] = $className;
			}
		}

		ksort($this->configScopes);

		return $this->configScopes;
	}
}
