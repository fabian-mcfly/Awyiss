<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Behavior\AccessBehavior;
use Awyiss\Model\Entity\Configuration;
use Cake\Collection\Iterator\MapReduce;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Configuration Model
 *
 * @method \Awyiss\Model\Entity\Configuration newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\Configuration patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class ConfigurationTable extends \Awyiss\Model\Table {
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
			'foreignKey' => 'languages_shortcode',
			'joinType' => 'LEFT',
		]);


		/** @var AccessBehavior $lo_accessBehavior */
		$lo_accessBehavior = $this->getBehavior('Access');

		if ( ! $lo_accessBehavior->getConfig('Model.buildRules')) {
			$lo_accessBehavior->setConfig('Model.buildRules', function(Configuration $ao_entity, array $aa_options, AccessBehavior $ao_behavior, ?bool $ab_accessible): ?bool {
				if ( ! $ab_accessible || $ao_entity->scope === 'system') {
					return $ab_accessible;
				}

				return $ao_behavior->isAccessible($ao_entity->scope, NULL, 'configure');
			});
		}

		if ( ! $lo_accessBehavior->getConfig('Model.beforeFind')) {
			$lo_accessBehavior->setConfig('Model.beforeFind', function(EventInterface $ao_event, Query $ao_subject, array $aa_options, AccessBehavior $ao_behavior, ?bool $ab_accessible): ?bool {
				if ( ! $ab_accessible) {
					return $ab_accessible;
				}

				$ao_subject->mapReduce(function(Configuration|array $ao_entity, int $ai_key, MapReduce $ao_mapReduce) use ($ao_behavior) {
					if ( ! $ao_entity instanceof Configuration) {
						return;
					}

					if ($ao_entity->scope === 'system' || $ao_behavior->isAccessible($ao_entity->scope, NULL, 'configure')) {
						$ao_mapReduce->emit($ao_entity);
					}
				});

				return TRUE;
			});
		}
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('scope')->maxLength('scope', 50)->requirePresence('scope')->notEmptyString('scope');

		$ao_validator->scalar('name')->maxLength('name', 255)->requirePresence('name', 'create')->notEmptyString('name');

		$ao_validator->scalar('value')->maxLength('value', 255)->allowEmptyString('value');

		$ao_validator->allowEmptyString('languages_shortcode');

		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['name', 'languages_shortcode']), ['errorField' => 'name']);

		$ao_rules->add($ao_rules->existsIn('languages_shortcode', 'Languages'), ['errorField' => 'languages_shortcode']);

		$ao_rules->add(function(EntityInterface $ao_entity/*, array $aa_options*/): bool|string {
			$la_configScopes = \Awyiss\Configuration\ConfigOptionsProvider::getConfigurationFiles();

			/** @var \Awyiss\Model\Entity\Configuration $ao_entity */
			return in_array($ao_entity->scope, array_keys($la_configScopes));
		}, 'validScope', [
			'errorField' => 'scope',
			'message' => __('configuration::error_invalid_scope'),
		]);

		$ao_rules->add(function(EntityInterface $ao_entity/*, array $aa_options*/): bool|string {
			/** @var Configuration $ao_entity */
			return ConfigOptionsProvider::validateConfigValue($ao_entity->scope, $ao_entity->name, $ao_entity->value, $ao_entity->languages_shortcode);
		}, 'validValue', [
			'errorField' => 'value',
			'message' => __('configuration::error_invalid_value'),
		]);

		return $ao_rules;
	}


	/**
	 * @throws \ReflectionException
	 */
	public function getScopes (): array {
		if ( ! isset($this->configScopes)) {
			$this->configScopes = [];

			foreach (\Awyiss\Configuration\ConfigOptionsProvider::getConfigurationFiles() as $ls_scope => $ls_className) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				if ($ls_scope === 'system' || $this->getBehavior('Access')->isAccessible($ls_scope, NULL, 'configure')) {
					$this->configScopes[ $ls_scope ] = $ls_className;
				}
			}
		}

		return $this->configScopes;
	}
}
