<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


#use ArrayObject;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\Configuration;
use Awyiss\Model\Table;
#use Cake\Collection\Iterator\MapReduce;
#use Cake\Event\EventInterface;
#use Cake\ORM\Query;
use Awyiss\ORM\RulesChecker;
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
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'configuration';
	protected array $configScopes;


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'foreignKey' => 'language_shortcode',
			'joinType' => 'LEFT',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('scope')->maxLength('scope', 50)->requirePresence('scope')->notEmptyString('scope');

		$ao_validator->scalar('name')->maxLength('name', 255)->requirePresence('name', 'create')->notEmptyString('name');

		$ao_validator->scalar('value')->maxLength('value', 255)->allowEmptyString('value');

		$ao_validator->allowEmptyString('language_shortcode');

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['scope', 'name', 'language_shortcode'], ['access' => ['skip' => TRUE]]), ['errorField' => 'name']);

		$ao_rules->add($ao_rules->existsIn('language_shortcode', 'Languages', ['access' => ['skip' => TRUE]]), ['errorField' => 'language_shortcode']);

		$ao_rules->add(function(Configuration $ao_entity/*, array $aa_options*/): bool|string {
			$la_configScopes = ConfigOptionsProvider::getConfigurationFiles();

			//Check if the provided scope is configurable (having a ConfigOptions-class)
			return in_array($ao_entity->scope, array_keys($la_configScopes));
		}, 'validScope', [
			'errorField' => 'scope',
			'message' => __('configuration::error_invalid_scope'),
		]);

		$ao_rules->add(function(Configuration $ao_entity/*, array $aa_options*/): bool|string {
			//Validate the provided value for the scope, name and language.
			return ConfigOptionsProvider::validateConfigValue($ao_entity->scope, $ao_entity->name, $ao_entity->value, $ao_entity->language_shortcode);
		}, 'validValue', [
			'errorField' => 'value',
			'message' => __('configuration::error_invalid_value'),
		]);

		return $ao_rules;
	}


	/**
	 * Returns all configurable and accessible scopes
	 *
	 * @throws \ReflectionException
	 */
	public function getScopes (): array {
		if ( ! isset($this->configScopes)) {
			$this->configScopes = [];

			foreach (ConfigOptionsProvider::getConfigurationFiles() as $ls_scope => $ls_className) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				if ($ls_scope === 'system' || $this->getBehavior('Access')->scopeIsAccessible($ls_scope, NULL, NULL, 'configure')) {
					$this->configScopes[ $ls_scope ] = $ls_className;
				}
			}
		}

		return $this->configScopes;
	}
}
