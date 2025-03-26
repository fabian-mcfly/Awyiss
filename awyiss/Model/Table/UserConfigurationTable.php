<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Entity\UserConfiguration;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
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
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'user_configuration';


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
		/** @var \Awyiss\Model\Table\DatatablesTable $lo_datatablesTable */
		$lo_datatablesTable = FactoryLocator::get('Table')->get('Datatables');
		$la_datatables = $lo_datatablesTable->findAllAndCache()->indexBy('identifier')->toArray();

		/** @var \Awyiss\Model\Table\PageRolesTable $lo_pageRolesTable */
		$lo_pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		$la_pageRoles = $lo_pageRolesTable->findAllAndCache()->indexBy(function (PageRole $pageRole) {
			return Inflector::pluralize($pageRole->identifier);
		})->toArray();

		$la_configScopes = [];
		foreach ($this->getScopes() as $ls_identifier => $ls_className) {
			$ls_identifier = Inflector::underscore($ls_identifier);

			if (isset($la_pageRoles[ $ls_identifier ])) {
				$la_configScopes[ $ls_identifier ] = $la_pageRoles[ $ls_identifier ]->label;

				continue;
			}

			if (isset($la_datatables[ $ls_identifier ])) {
				$la_configScopes[ $ls_identifier ] = $la_datatables[ $ls_identifier ]->label;

				continue;
			}

			$la_configScopes[ $ls_identifier ] = __d($ls_identifier, 'menu_title');
		}

		uasort($la_configScopes, function ($a, $b) {
			return strnatcasecmp($a, $b);
		});

		return $la_configScopes;
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
	 * Returns the default validator object.
	 *
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
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


		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('value', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
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
			$lo_configuration = ConfigOptionsProvider::loadConfigOptions($entity->scope);
			$lo_configOption = $lo_configuration?->getConfigOption(Awyiss::REALM_BACKEND, $entity->identifier);


			return $lo_configOption && $lo_configOption->isPersonalizable();
		}, 'configOptionIsPersonalizable', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_config_option_is_personalizable'),
		]);


		//Validate the provided value for the scope, identifier and language.
		$rules->add(function (UserConfiguration $entity/*, array $options*/): bool|string {
			$lb_valid = ConfigOptionsProvider::validateConfigValue(
				$entity->scope,
				Awyiss::REALM_BACKEND,
				$entity->identifier,
				$entity->value
			);

			if (!$lb_valid) {
				$lx_value = ConfigOptionsProvider::typecastConfigValue(
					$entity->scope,
					Awyiss::REALM_BACKEND,
					$entity->identifier,
					$entity->value
				);

				//A typecast to null cannot be valid if the initial value wasn't null.
				//This happens when one tries to json_decode a string, for example.
				if ($entity->value !== null && $lx_value !== null) {
					$lb_valid = ConfigOptionsProvider::validateConfigValue(
						$entity->scope,
						Awyiss::REALM_BACKEND,
						$entity->identifier,
						$lx_value
					);
				}
			}

			return $lb_valid;
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

		$lo_identity = $this->getIdentity();

		foreach (ConfigOptionsProvider::getConfigOptionsFiles() as $ls_scope => $ls_className) {
			if (in_array($ls_scope, ['Contents', 'System'], true)) {
				// For now, contents are always accessible since accessing them depends on page roles
				$this->configScopes[ $ls_scope ] = $ls_className;
			}
			elseif ($ls_scope === 'FormElements' && $lo_identity?->scopeIsAccessible('Forms', [], ['read', 'create', 'update', 'configure'])) {
				$this->configScopes[ $ls_scope ] = $ls_className;
			}
			elseif ($lo_identity?->scopeIsAccessible($ls_scope, [], ['read', 'create', 'update', 'configure'])) {
				$this->configScopes[ $ls_scope ] = $ls_className;
			}
		}

		ksort($this->configScopes);

		return $this->configScopes;
	}
}
