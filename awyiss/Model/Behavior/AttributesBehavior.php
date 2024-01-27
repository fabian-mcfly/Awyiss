<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\Attributes\AttributeOptionsProvider;
use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\Iterator\MapReduce;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Entity as BaseEntity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * This behavior saves the old and the new values when updating entities into a separate database table.
 * It also sets information when creating, updating or deleting an entity.
 */
class AttributesBehavior extends Behavior {
	use LocatorAwareTrait;


	/**
	 * The attributes table is name "attributes_<name>" with <name> being the current table's name.
	 *
	 * @var string
	 */
	protected string $attributesTable;
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'attributeOptionsProviderClass' => AttributeOptionsProvider::class,
		'foreignKey' => null,
		'implementedEvents' => [
			'buildRules',
			'beforeFind',
			'afterSave',
		],
		'implementedMethods' => [
			'getAttributes' => 'getAttributes',
			'getAttributesTable' => 'getAttributesTable',
			'hasAttributes' => 'hasAttributes',
		],
		'isAttributesTable' => false,
		'skip' => false,
		'sourceTable' => null,
	];
	/**
	 * A boolean value, indicating if the table has a corresponding attributes table.
	 *
	 * @var bool
	 */
	protected bool $hasAttributes = false;


	/**
	 * @var array<string, string|\Awyiss\Attributes\AttributeOptionsInterface>
	 */
	protected static array $attributeOptions;
	/**
	 * @var array<string, array<string, \Awyiss\Model\Entity\Attribute>>
	 */
	protected static array $attributes;


	/**
	 * Constructor hook method.
	 *
	 * @param array<string, mixed> $aa_config The configuration settings provided to this behavior.
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize(array $aa_config): void {
		if ($this->getConfig('isAttributesTable')) {
			$this->initializeTranslate();


			return;
		}

		$this->attributesTable = 'attributes_' . $this->getConfig('sourceTable');


		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		if (!$this->table()::ATTRIBUTABLE) {
			return;
		}

		$ls_identifier = Inflector::camelize($this->attributesTable);

		if (!App::className($ls_identifier, 'Model\Table', 'Table')) {
			return;
		}

		$this->hasAttributes = true;
		$this->table()->hasOne($ls_identifier, [
			'cascadeCallbacks' => true,
			//'className' => $ls_attributesClass,
			'dependent' => true,
			'foreignKey' => $this->getConfig('foreignKey'),
			'propertyName' => 'attributes',
		]);
	}


	/**
	 * @param bool $ab_camelized
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function getAttributesTable(bool $ab_camelized = false): string {
		return $ab_camelized ? Inflector::camelize(Inflector::tableize($this->attributesTable)) : $this->attributesTable;
	}


	/**
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function hasAttributes(): bool {
		return $this->hasAttributes;
	}


	/**
	 * @return array
	 */
	public function getAttributes(): array {
		if (!$this->getConfig('isAttributesTable')) {
			$ls_assocatiation = Inflector::camelize($this->attributesTable);

			if (!$this->table()->hasAssociation($ls_assocatiation)) {
				return [];
			}

			/** @var \Awyiss\Model\Table $lo_association */
			$lo_association = $this->table()->getAssociation($ls_assocatiation);


			return $lo_association->getAttributes();
		}

		$ls_scope = substr($this->table()->getTable(), 11);

		if (isset(static::$attributes)) {
			return static::$attributes[ $ls_scope ] ?? [];
		}

		/** @var \Awyiss\Model\Table\AttributesTable $lo_attributesTable */
		$lo_attributesTable = FactoryLocator::get('Table')->get('Attributes');
		$lo_attributesQuery = $lo_attributesTable->find('all');

		/**
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$lo_attributesQuery = $lo_attributesQuery->orderByAsc($lo_attributesQuery->newExpr($lo_attributesQuery->func()->FIELD([
			'fieldset' => 'identifier',
			...$lo_attributesTable->getAvailableFieldsets(),
		])));

		static::$attributes = $lo_attributesQuery->all()->groupBy('scope')->map(function ($aa_attributes) {
			return collection($aa_attributes)->indexBy('identifier')->toArray();
		})->toArray();

		return static::$attributes[ $ls_scope ] ?? [];
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 * Adds `validateValue()`-checks for each attribute
	 *
	 * @param Event $ao_event
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @throws \ReflectionException
	 * @see \Awyiss\Attributes\AttributeOptions::validateValue
	 */
	public function buildRules(Event $ao_event, RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		if (!$this->getConfig('isAttributesTable')) {
			return $ao_rules;
		}

		/** @var \Awyiss\Model\Table $lo_subject */
		$lo_subject = $ao_event->getSubject();
		$ls_source = substr($lo_subject->getTable(), 11);

		/** @var AttributeOptionsProvider $ls_attributeOptionsProvider */
		$ls_attributeOptionsProvider = $this->getConfig('attributeOptionsProviderClass');
		$lo_attributeOptions = static::$attributeOptions[ $ls_source ] = $ls_attributeOptionsProvider::getAttributeOptionsFile($ls_source, true);

		if (empty($lo_attributeOptions)) {
			return $ao_rules;
		}

		/** @var \Awyiss\Model\Entity\Attribute $lo_attribute */
		foreach ($this->getAttributes() as $lo_attribute) {
			if (!isset($lo_attributeOptions[ $lo_attribute->identifier ])) {
				continue;
			}

			$ao_rules->add(function (Entity $ao_entity/*, array $aa_options*/) use ($lo_attribute, $lo_attributeOptions): bool|string {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				return $lo_attributeOptions->validateValue($lo_attribute->identifier, $ao_entity->get($lo_attribute->identifier), $ao_entity->getEntity());
			}, 'validValue', [
				'errorField' => $lo_attribute->identifier,
				'message' => __d('attributes', 'error_valid_value'),
			]);
		}


		return $ao_rules;
	}


	/**
	 * @param EventInterface $ao_event
	 * @param SelectQuery $ao_query
	 * @param ArrayObject $ao_options
	 * @param bool $ab_primary
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(EventInterface $ao_event, SelectQuery $ao_query, ArrayObject $ao_options, bool $ab_primary): void {
		if ($this->getConfig('isAttributesTable') || !$this->hasAttributes()) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'attributes'));

		if ($la_options['skip'] === true) {
			return;
		}

		if ($ao_query->isEagerLoaded()) {
			throw new RuntimeException('Eager loaded associations should skip the attributes behavior');
		}

		$ao_query->contain([
			$this->getAttributesTable(true),
		]);

		$ao_query->mapReduce(function (array|Entity $ao_entity, int $ai_key, MapReduce $ao_mapReduce) use ($ao_query): void {
			if (!is_a($ao_entity, Entity::class)) {
				$ao_mapReduce->emit($ao_entity);


				return;
			}

			if (!$ao_entity->attributes) {
				/** @var \Awyiss\ORM\Association\HasOne|\Awyiss\Model\Table $lo_association */
				$lo_association = $this->table()->{$this->getAttributesTable(true)};

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$ao_entity->attributes = $lo_association->newDefaultEntity();

				/** @var static $ls_associationEntityClass */
				$ls_associationEntityClass = $lo_association->getEntityClass();

				/** @noinspection PhpUndefinedMethodInspection */
				$ls_foreignKey = $ls_associationEntityClass::mapField($lo_association->getForeignKey());

				$ao_entity->initAttributesField($lo_association, $ls_foreignKey);
			}

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			if (isset($ao_entity->attributes) && !$ao_entity->attributes->getEntity()) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$ao_entity->attributes->setEntity($ao_entity);
			}

			$ao_mapReduce->emit($ao_entity);
		});
	}


	/**
	 * @param EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $ao_event, Entity|BaseEntity $ao_entity/*, ArrayObject $ao_options*/): void {
		if (!$this->hasAttributes()) {
			return;
		}

		//If the `attributes`-property was set to false, delete the existings attributes for this entity
		if (!$ao_entity->isNew() && !$ao_entity->get('attributes')) {
			$this->table()->loadInto($ao_entity, [$this->getAttributesTable(true)]);

			if (!empty($ao_entity->attributes) && !$ao_entity->attributes->isNew()) {
				$this->fetchTable($this->getAttributesTable(true))->delete($ao_entity->attributes);
				unset($ao_entity->attributes);
			}
		}
	}


	/**
	 * @return void
	 */
	protected function initializeTranslate(): void {
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		$la_translatableFields = $lo_table->getConfig('translate.fields');
		foreach ($this->getAttributes() as $lo_attribute) {
			if (!$lo_attribute->translatable) {
				continue;
			}

			$la_translatableFields[] = $lo_attribute->identifier;
		}

		$lo_table->setConfig('translate.fields', $la_translatableFields);
	}
}
