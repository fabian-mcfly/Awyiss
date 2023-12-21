<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Configuration Model
 *
 * @method Configuration newDefaultEntity(array $aa_additionalData = [])
 */
class ConfigurationTable extends Table {
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


		$ao_validator->notEmptyString('identifier');
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
				],
				['authorize' => ['skip' => true]]
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


			//Validate the provided value for the scope, identifier and language.
			return ConfigOptionsProvider::validateConfigValue(
				$ao_entity->scope,
				$ao_entity->realm,
				$ao_entity->identifier,
				$ao_entity->value,
				$ao_entity->languageShortcode
			);
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
				'authorize' => ['skip' => true],
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
		if (!isset($this->configScopes)) {
			$this->configScopes = [];

			foreach (ConfigOptionsProvider::getConfigOptionsFiles() as $ls_scope => $ls_className) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				if ($this->getBehavior('Authorize')->scopeIsAccessible($this->getTable(), ['scope' => $ls_scope], 'read')) {
					$this->configScopes[ $ls_scope ] = $ls_className;
				}
			}
		}


		return $this->configScopes;
	}
}
