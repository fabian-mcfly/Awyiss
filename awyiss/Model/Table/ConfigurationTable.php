<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
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
 * @property \Awyiss\Model\Table\LanguagesTable&\Awyiss\ORM\Association\BelongsTo $Languages
 * @method \Awyiss\Model\Entity\Configuration newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
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
				[
					'allowMultipleNulls' => false,
				]
			),
			'identifierUniqueForScope',
			[
				'errorField' => 'identifier',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'configuration', 'error_identifier_unique_for_scope'),
			]
		);


		$ao_rules->add(function (Configuration $ao_entity): bool {
			return in_array($ao_entity->realm, Awyiss::getRealms(), true);
		}, 'validRealm', [
			'errorField' => 'realm',
			'message' => __d($this->getI18nDomain(), 'error_valid_realm'),
		]);


		//Validate the provided value for the scope, identifier and language.
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
					$ao_entity->value,
					$ao_entity->languageShortcode
				);

				//A typecast to null cannot be valid if the initial value wasn't null.
				//This happens when one tries to json_decode a string, for example.
				if ($ao_entity->value !== null && $lx_value !== null) {
					$lb_valid = ConfigOptionsProvider::validateConfigValue(
						$ao_entity->scope,
						$ao_entity->realm,
						$ao_entity->identifier,
						$lx_value,
						$ao_entity->languageShortcode
					);
				}
			}

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

		ksort($this->configScopes);


		return $this->configScopes;
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Configuration|\Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \ReflectionException
	 */
	public function beforeSave(EventInterface $ao_event, Configuration|EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$ao_entity->value = ConfigOptionsProvider::typecastConfigValue(
			$ao_entity->scope,
			$ao_entity->realm,
			$ao_entity->identifier,
			$ao_entity->value,
			$ao_entity->languageShortcode
		);

		if (in_array(getType($ao_entity->value), ['array', 'object'])) {
			$ao_entity->value = json_encode($ao_entity->value);
		}
	}
}
