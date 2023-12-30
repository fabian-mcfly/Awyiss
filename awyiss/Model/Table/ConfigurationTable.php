<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptions\GenericPagesConfigOptions;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;


/**
 * Configuration Model
 *
 * @method Configuration newDefaultEntity(array $aa_additionalData = [])
 */
class ConfigurationTable extends Table {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'configuration';
	/**
	 * @var array
	 */
	protected array $configScopes;


	/**
	 * @inheritDoc
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		$this->belongsTo('Languages', [
			'bindingKey' => [
				'realm',
				'shortcode',
			],
			'foreignKey' => [
				'realm',
				'language_shortcode',
			],
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
			'realm',
			'identifier',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('realm');
		$ao_validator->add('realm', [
			'isScalar' => ['rule' => 'isScalar'],
			'inList' => ['rule' => ['inList', Awyiss::getRealms()]],
			'maxLength' => ['rule' => ['maxLength', 20]],
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


		$ao_validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 2]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		//$ao_validator->integer('id')->allowEmptyString('id', null, 'create');
		//$ao_validator->scalar('realm')->maxLength('realm', 20)->requirePresence('realm')->notEmptyString('realm');
		//$ao_validator->scalar('scope')->maxLength('scope', 50)->requirePresence('scope')->notEmptyString('scope');
		//$ao_validator->scalar('identifier')->maxLength('identifier', 255)->requirePresence('identifier', 'create')->notEmptyString('identifier');
		//$ao_validator->scalar('value')->maxLength('value', 255)->allowEmptyString('value');
		//$ao_validator->allowEmptyString('language_shortcode');

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
			$ao_rules->isUnique(
				[
					'realm',
					'scope',
					'identifier',
					'languageShortcode',
				]
			),
			'identifierUniqueForScope',
			[
				'errorField' => 'identifier',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'configuration', 'error_identifier_unique'),
			]
		);


		$ao_rules->add(function (Configuration $ao_entity): bool {
			return in_array($ao_entity->realm, Awyiss::getRealms(), true);
		}, 'validRealm', [
			'errorField' => 'realm',
			'message' => __d($this->getI18nDomain(), 'error_valid_realm'),
		]);


		$ao_rules->add(function (Configuration $ao_entity/*, array $aa_options*/): bool {
			$la_configScopes = ConfigOptionsProvider::getConfigOptionsFiles();


			//Check if the provided scope is configurable (having a ConfigOptions-class)
			return in_array(ConfigOptionsProvider::sanitizeScope($ao_entity->scope), array_keys($la_configScopes));
		}, 'validScope', [
			'errorField' => 'scope',
			'message' => __d($this->getI18nDomain(), 'error_valid_scope'),
		]);


		$ao_rules->add(function (Configuration $ao_entity/*, array $aa_options*/): bool|string {
			if ($ao_entity->getError('scope') || $ao_entity->getError('realm')) {
				return false;
			}

			$lb_valid = ConfigOptionsProvider::validateConfigValue(
				$ao_entity->scope,
				$ao_entity->realm,
				$ao_entity->identifier,
				$ao_entity->value,
				$ao_entity->languageShortcode
			);

			if (!$lb_valid) {
				$lx_value = ConfigOptionsProvider::typecastConfigValue(
					$ao_entity->scope,
					$ao_entity->realm,
					$ao_entity->identifier,
					$ao_entity->value
				);

				$lb_valid = ConfigOptionsProvider::validateConfigValue(
					$ao_entity->scope,
					$ao_entity->realm,
					$ao_entity->identifier,
					$lx_value,
					$ao_entity->languageShortcode
				);
			}

			//Validate the provided value for the scope, identifier and language.
			return $lb_valid;
		}, 'validValue', [
			'errorField' => 'value',
			'message' => __d($this->getI18nDomain(), 'error_valid_value'),
		]);


		$ao_rules->add(function (Configuration $ao_entity, array $aa_options) use ($ao_rules): bool {
			if (!$ao_entity->get('languageShortcode')) {
				return true;
			}

			$lo_existsIn = $ao_rules->existsIn([
				'realm',
				'languageShortcode',
			], 'Languages', [
				'errorField' => 'languageShortcode',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'configuration', 'error_language_exists'),
			]);


			return $lo_existsIn($ao_entity, $aa_options);
		}, 'languageExists');


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
			if ($lo_identity?->scopeIsAccessible($this->getTable(), ['scope' => $ls_scope], 'read')) {
				$this->configScopes[ $ls_scope ] = $ls_className;
			}
		}

		//Get all page roles from the database because we want them to have policies too
		$lo_pageRoles = $this->fetchTable('PageRoles')->find('active')->where(['identifier !=' => 'page'])->all();

		/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
		foreach ($lo_pageRoles as $lo_pageRole) {
			$ls_scope = Inflector::tableize($lo_pageRole->identifier);

			/*
			 * If there's no policy for the identifier yet, we add an instance of GenericPagesPolicy for the page role.
			 * This way, a custom policy for every page role can be set, but it'll fall back
			 * to a generic CRUD policy
			 */
			if (!isset($this->configScopes[ $ls_scope ]) && $lo_identity?->scopeIsAccessible($ls_scope, [], 'configure')) {
				$this->configScopes[ $ls_scope ] = GenericPagesConfigOptions::class;
			}
		}

		ksort($this->configScopes);

		return $this->configScopes;
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Configuration|\Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, Configuration|EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$ao_entity->value = ConfigOptionsProvider::typecastConfigValue(
			$ao_entity->scope,
			$ao_entity->realm,
			$ao_entity->identifier,
			$ao_entity->value,
		);
	}
}
