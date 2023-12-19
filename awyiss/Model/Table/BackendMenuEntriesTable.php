<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Model\Table;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utilities\Menu\BackendMenu;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * BackendMenuEntries Model
 *
 * @property BackendMenuEntriesTable&BelongsTo $ParentBackendMenuEntries
 * @property BackendMenuEntriesTable&HasMany $ChildBackendMenuEntries
 *
 * @method BackendMenuEntry newDefaultEntity(array $aa_additionalData = [])
 * @method CollectionInterface|NULL getNestedChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|NULL getChildren(EntityInterface $ao_entity)
 * @method BackendMenuEntry getParent(EntityInterface $ao_entity)
 * @method CollectionInterface|NULL getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 */
class BackendMenuEntriesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'backend_menu_entries';


	protected array $_defaultConfig = [
		'nest' => [
			'relatedColumns' => [],
		],
		'systemOrder' => [
			'relatedColumns' => ['parentId', 'insertAfterId'],
		],
		'translate' => [
			'fields' => ['title'],
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->addBehavior('Nest', $this->getConfig('nest', []));
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return Validator
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'parentId' => [
				'mode' => function(array $aa_context): bool {
					/** @var BackendMenuEntriesTable $lo_table */
					$lo_table = $aa_context['providers']['table'];
					/** @var BackendMenuEntry $ls_entityClass */
					$ls_entityClass = $lo_table->getEntityClass();

					$la_data = $aa_context['data'];

					return empty($la_data[ $ls_entityClass::unmapField('insertAfterId') ]) ;
				}
			],
			'insertAfterId' => [
				'mode' => function(array $aa_context): bool {
					/** @var BackendMenuEntriesTable $lo_table */
					$lo_table = $aa_context['providers']['table'];
					/** @var BackendMenuEntry $ls_entityClass */
					$ls_entityClass = $lo_table->getEntityClass();

					$la_data = $aa_context['data'];

					return empty($la_data[ $ls_entityClass::unmapField('parentId') ]);
				}
			],
			'title',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('parentId', NULL, function(array $aa_context): bool {
			/** @var BackendMenuEntriesTable $lo_table */
			$lo_table = $aa_context['providers']['table'];
			/** @var BackendMenuEntry $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();

			$la_data = $aa_context['data'];

			return empty($la_data[ $ls_entityClass::unmapField('insertAfterId') ]);
		});
		$ao_validator->add('parentId', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$ao_validator->notEmptyString('insertAfterId', NULL, function(array $aa_context): bool {
			/** @var BackendMenuEntriesTable $lo_table */
			$lo_table = $aa_context['providers']['table'];
			/** @var BackendMenuEntry $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();

			$la_data = $aa_context['data'];

			return empty($la_data[ $ls_entityClass::unmapField('parentId') ]);
		});
		$ao_validator->add('insertAfterId', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
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
	public function buildRules (RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add(function(BackendMenuEntry $ao_entity, array $aa_options) use ($ao_rules): bool {
			static $lo_menu;

			if ( ! $aa_options['checkRules']) {
				dd(__FILE__, __LINE__);
			}

			if ( ! ($lx_parentId = $ao_entity->get('parentId'))) {
				return TRUE;
			}

			if ( ! is_numeric($lx_parentId)) {
				if ( ! isset($lo_menu)) {
					$lo_menu = new BackendMenu();
				}

				return (bool) ($lo_menu->getCustomMenu() ?? $lo_menu->getMenu())->getItem($lx_parentId);
			}

			$lo_existsIn = $ao_rules->existsIn(['parentId'], 'ParentBackendMenuEntries', [
				'errorField' => 'parentId',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'backend_menu_entries', 'error_valid_parent_id'),
			]);

			return $lo_existsIn($ao_entity, $aa_options);
		}, 'validParentId');


		return $ao_rules;
	}


	/**
	 * Creates a threaded list of men entries from a query, adding the `level`-property to each menu entry and returns
	 * a collection
	 *
	 * @noinspection PhpUnused
	 */
	public function listNested (SelectQuery $ao_query): CollectionInterface {
		$lo_menuEntries = $ao_query->find('threaded')->all()->listNested();

		/** @var BackendMenuEntry $lo_menuEntry */
		foreach ($lo_menuEntries as $lo_menuEntry) {
			$lo_menuEntry->setVirtual(['level']);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_menuEntry->level = $lo_menuEntries->getDepth();
		}

		return $lo_menuEntries;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema (TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);;

		$ao_schema->setColumnType('access', 'json');
	}
}
