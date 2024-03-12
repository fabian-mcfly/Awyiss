<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\UserConfiguration;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;


/**
 * User-specific Configuration Model
 *
 * @method \Awyiss\Model\Entity\UserConfiguration newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
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
	 * @return array
	 * @throws \ReflectionException
	 */
	public function buildCategories(): array {
		$la_configScopes = [];

		foreach ($this->getScopes() as $ls_identifier => $ls_className) {
			$ls_identifier = Inflector::underscore($ls_identifier);
			$la_configScopes[ $ls_identifier ] = __d($ls_identifier, 'title_menu');
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
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);

		$ao_validator->requirePresence([
			'userId',
			'scope',
			'identifier',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('userId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('value', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add(
			function (UserConfiguration $ao_entity/*, array $aa_options*/) use ($ao_rules): bool|string {
				if (
					$ao_entity->hasOriginal('userId') &&
					$ao_entity->get('userId') !== $ao_entity->getOriginal('userId')
				) {
					return __d($this->getI18nDomain(), 'error_user_id_unchanged');
				}


				return true;
			},
			'userIdUnchanged',
			[
				'errorField' => '_general',
			]
		);


		$ao_rules->add(
			$ao_rules->isUnique(
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
				'message' => __dfx($this->getI18nDomain(), 'validation', 'user_configuration', 'error_identifier_unique_for_scope'),
			]
		);


		$ao_rules->add(function (UserConfiguration $ao_entity/*, array $aa_options*/) use ($ao_rules): bool {
			$lo_configuration = ConfigOptionsProvider::loadConfigOptions($ao_entity->scope);
			$lo_configOption = $lo_configuration?->getConfigOption(Awyiss::REALM_BACKEND, $ao_entity->identifier);


			return $lo_configOption && $lo_configOption->isPersonalizable();
		}, 'configOptionIsPersonalizable', [
			'errorField' => '_general',
			'message' => __d($this->getI18nDomain(), 'error_config_option_is_personalizable'),
		]);


		//Validate the provided value for the scope, identifier and language.
		$ao_rules->add(function (UserConfiguration $ao_entity/*, array $aa_options*/): bool|string {
			$lb_valid = ConfigOptionsProvider::validateConfigValue(
				$ao_entity->scope,
				Awyiss::REALM_BACKEND,
				$ao_entity->identifier,
				$ao_entity->value
			);

			if (!$lb_valid) {
				$lx_value = ConfigOptionsProvider::typecastConfigValue(
					$ao_entity->scope,
					Awyiss::REALM_BACKEND,
					$ao_entity->identifier,
					$ao_entity->value
				);

				//A typecast to null cannot be valid if the initial value wasn't null.
				//This happens when one tries to json_decode a string, for example.
				if ($ao_entity->value !== null && $lx_value !== null) {
					$lb_valid = ConfigOptionsProvider::validateConfigValue(
						$ao_entity->scope,
						Awyiss::REALM_BACKEND,
						$ao_entity->identifier,
						$lx_value
					);
				}
			}

			return $lb_valid;
		},
		'validValue',
		[
			'errorField' => 'value',
			'message' => __d($this->getI18nDomain(), 'error_valid_value'),
		]);


		$ao_rules->addDelete(
			function (UserConfiguration $ao_entity/*, array $aa_options*/): bool {
				return $ao_entity->userId === $this->getIdentity()->getIdentifier();
			},
			'configOwnedByUser',
			[
				'errorField' => '_general',
				'message' => __d($this->getI18nDomain(), 'error_config_owned_by_user'),
			]
		);


		return $ao_rules;
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
			if ($lo_identity?->scopeIsAccessible($ls_scope, [], ['read', 'create', 'update'])) {
				$this->configScopes[ $ls_scope ] = $ls_className;
			}
		}

		ksort($this->configScopes);


		return $this->configScopes;
	}
}
