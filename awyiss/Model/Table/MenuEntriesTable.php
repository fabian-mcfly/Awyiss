<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * MenuEntries Model
 *
 * @property ContentsTable&BelongsTo $ParentMenuEntries
 * @property MenuEntriesTable&HasMany $ChildMenuEntries
 * @property MenusTable&BelongsTo $Menus
 *
 * @method MenuEntry newDefaultEntity(array $aa_additionalData = [])
 * @method CollectionInterface|NULL getNestedChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|NULL getChildren(EntityInterface $ao_entity)
 * @method MenuEntry getParent(EntityInterface $ao_entity)
 * @method CollectionInterface|NULL getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 */
class MenuEntriesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = TRUE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'menu_entries';
	protected array $_defaultConfig = [
		'nest' => [
			'relatedColumns' => ['languageShortcode', 'menuId'],
		],
		'systemOrder' => [
			'relatedColumns' => ['languageShortcode', 'menuId', 'parentId'],
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		$this->addBehavior('Nest', $this->getConfig('nest', []));

		$this->belongsTo('Menus', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['realm' => Awyiss::REALM_FRONTEND],
			'foreignKey' => 'language_shortcode',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'menuId',
			'languageShortcode',
			'title',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('menuId');
		$ao_validator->add('menuId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('languageShortcode');
		$ao_validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'rule' => function (string $as_shortcode): bool {
					return strlen($as_shortcode) == 2;
				},
			],
		]);


		$ao_validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('link', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('external', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add(
			$ao_rules->existsIn(
				'menuId',
				'Menus',
				['authorize' => ['skip' => TRUE]]
			),
			'validMenuId',
			[
				'errorField' => 'menuId',
				'message' => __d($this->getI18nDomain(), 'error_valid_menu_id'),
			]
		);


		$ao_rules->add(function (MenuEntry $ao_entity, array $aa_options) use ($ao_rules): bool {
			if (!$aa_options['checkRules']) {
				dd(__FILE__, __LINE__);
			}

			if (!$ao_entity->get('parentId')) {
				return TRUE;
			}

			$lo_existsIn = $ao_rules->existsIn(['parentId', 'menuId', 'languageShortcode'], 'ParentMenuEntries', [
				'errorField' => 'parentId',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'menu_entries', 'error_valid_parent_id'),
			]);


			return $lo_existsIn($ao_entity, $aa_options);
		}, 'validParentId');


		$ao_rules->add(
			$ao_rules->existsIn(
				'languageShortcode',
				'Languages',
				['authorize' => ['skip' => TRUE]]
			),
			'languageExists',
			[
				'errorField' => 'languageShortcode',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'menu_entries', 'error_language_exists'),
			]
		);


		return $ao_rules;
	}


	/**
	 * @return void
	 */
	public function disableCascadeCallbacks(): void {
		$this->ChildMenuEntries->setDependent(FALSE)->setCascadeCallbacks(FALSE);
	}


	/**
	 * @return void
	 */
	public function enableCascadeCallbacks(): void {
		$this->ChildMenuEntries->setDependent(TRUE)->setCascadeCallbacks(TRUE);
	}


	/**
	 * Creates a threaded list of men entries from a query, adding the `level`-property to each menu entry and returns
	 * a collection
	 *
	 * @noinspection PhpUnused
	 */
	public function listNested(SelectQuery $ao_query): CollectionInterface {
		$lo_menuEntries = $ao_query->find('threaded')->all()->listNested();

		/** @var MenuEntry $lo_menuEntry */
		foreach ($lo_menuEntries as $lo_menuEntry) {
			$lo_menuEntry->setVirtual(['level']);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_menuEntry->level = $lo_menuEntries->getDepth();
		}


		return $lo_menuEntries;
	}
}
