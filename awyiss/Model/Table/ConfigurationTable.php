<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
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

			if (isset($la_pageRoles[ $ls_identifier ]) && $ls_identifier !== 'pages') {
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
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
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
			'inList' => ['rule' => ['inList', Awyiss::getRealms()]],
			'maxLength' => ['rule' => ['maxLength', 20]],
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


		$validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 2]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('description', [
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
				'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique_for_scope'),
			]
		);


		$rules->add(function (Configuration $entity): bool {
			return in_array($entity->realm, Awyiss::getRealms(), true);
		}, 'validRealm', [
			'errorField' => 'realm',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_realm'),
		]);


		//Validate the provided value for the scope, identifier and language.
		$rules->add(function (Configuration $entity/*, array $options*/): bool|string {
			if ($entity->getError('scope') || $entity->getError('realm')) {
				return false;
			}

			$lb_valid = ConfigOptionsProvider::validateConfigValue(
				$entity->scope,
				$entity->realm,
				$entity->identifier,
				$entity->value,
				$entity->languageShortcode
			);

			if (!$lb_valid) {
				$lx_value = ConfigOptionsProvider::typecastConfigValue(
					$entity->scope,
					$entity->realm,
					$entity->identifier,
					$entity->value,
					$entity->languageShortcode
				);

				//A typecast to null cannot be valid if the initial value wasn't null.
				//This happens when one tries to json_decode a string, for example.
				if ($entity->value !== null && $lx_value !== null) {
					$lb_valid = ConfigOptionsProvider::validateConfigValue(
						$entity->scope,
						$entity->realm,
						$entity->identifier,
						$lx_value,
						$entity->languageShortcode
					);
				}
			}

			return $lb_valid;
		}, 'validValue', [
			'errorField' => 'value',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_value'),
		]);


		$rules->add(function (Configuration $entity, array $options) use ($rules): bool {
			if (!$entity->get('languageShortcode')) {
				return true;
			}

			$lo_existsIn = $rules->existsIn([
				'realm',
				'languageShortcode',
			], 'Languages', [
				'errorField' => 'languageShortcode',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_language_exists'),
			]);


			return $lo_existsIn($entity, $options);
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

		$lo_identity = $this->getIdentity();

		foreach (ConfigOptionsProvider::getConfigOptionsFiles() as $ls_scope => $ls_className) {
			if ($lo_identity?->scopeIsAccessible($this->getTable(), ['scope' => $ls_scope], 'read')) {
				$this->configScopes[ $ls_scope ] = $ls_className;
			}
		}

		ksort($this->configScopes);


		return $this->configScopes;
	}
}
