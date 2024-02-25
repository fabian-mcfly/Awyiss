<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utilities\Menu\BackendMenu;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * BackendMenuEntries Model
 *
 * @property \Awyiss\Model\Table\BackendMenuEntriesTable&\Awyiss\ORM\Association\BelongsTo $ParentBackendMenuEntries
 * @property \Awyiss\Model\Table\BackendMenuEntriesTable&\Awyiss\ORM\Association\HasMany $ChildBackendMenuEntries
 * @method \Awyiss\Model\Entity\BackendMenuEntry newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $ao_entity, array $aa_options = [])
 * @method \Awyiss\Model\Entity\BackendMenuEntry getParent(\Cake\Datasource\EntityInterface $ao_entity, array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class BackendMenuEntriesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'backend_menu_entries';
	/**
	 * @inheritDoc
	 */
	protected array $nest = [
		'buildRules' => false,
		'enabled' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['parentId', 'insertAfterId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


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
			'parentId' => [
				'mode' => function (array $aa_context): bool {
					/** @var BackendMenuEntriesTable $lo_table */
					$lo_table = $aa_context['providers']['table'];
					/** @var BackendMenuEntry $ls_entityClass */
					$ls_entityClass = $lo_table->getEntityClass();

					$la_data = $aa_context['data'];


					return empty($la_data[ $ls_entityClass::unmapField('insertAfterId') ]);
				},
			],
			'insertAfterId' => [
				'mode' => function (array $aa_context): bool {
					/** @var BackendMenuEntriesTable $lo_table */
					$lo_table = $aa_context['providers']['table'];
					/** @var BackendMenuEntry $ls_entityClass */
					$ls_entityClass = $lo_table->getEntityClass();

					$la_data = $aa_context['data'];


					return empty($la_data[ $ls_entityClass::unmapField('parentId') ]);
				},
			],
			'title',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('parentId', null, function (array $aa_context): bool {
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


		$ao_validator->notEmptyString('insertAfterId', null, function (array $aa_context): bool {
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
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add(function (BackendMenuEntry $ao_entity, array $aa_options) use ($ao_rules): bool {
			static $lo_menu;

			if (!$aa_options['checkRules']) {
				dd(__FILE__, __LINE__);
			}

			$lx_parentId = $ao_entity->get('parentId');
			if (!$lx_parentId) {
				return true;
			}

			if (!is_numeric($lx_parentId)) {
				if (!isset($lo_menu)) {
					$lo_menu = new BackendMenu();
				}


				return (bool)($lo_menu->getCustomMenu() ?? $lo_menu->getMenu())->getItem($lx_parentId);
			}

			$lo_existsIn = $ao_rules->existsIn(
				'parentId',
				'ParentBackendMenuEntries',
				[
					'errorField' => 'parentId',
					'message' => __dfx($this->getI18nDomain(), 'validation', 'backend_menu_entries', 'error_valid_parent_id'),
				]
			);


			return $lo_existsIn($ao_entity, $aa_options);
		}, 'validParentId');


		return $ao_rules;
	}


	/**
	 * @param \Awyiss\Model\Entity $ao_entity
	 * @param string $as_controller
	 * @param string $as_scope
	 * @return void
	 */
	public function createEntries(Entity $ao_entity, string $as_controller, string $as_scope, string $as_insertAfterId = 'pages'): void {
		$la_data = [
			'title' => $ao_entity->title,
			'insert_after_id' => $as_insertAfterId,
			'link' => $as_controller . '::overview',
			'access' => [
				'scope' => $as_scope,
				'identifier' => 'read',
			],
			'child_backend_menu_entries' => [
				[
					'title' => $as_scope . '::menu_overview',
					'link' => $as_controller . '::overview',
					'access' => [
						'scope' => $as_scope,
						'identifier' => 'read',
					],
					'system_order' => 1,
				],
				[
					'title' => $as_scope . '::menu_add',
					'link' => $as_controller . '::add',
					'access' => [
						'scope' => $as_scope,
						'identifier' => 'create',
					],
					'system_order' => 2,
				],
				[
					'title' => $as_scope . '::menu_configure',
					'link' => 'Configuration::overview::scope:' . $as_scope,
					'access' => [
						'scope' => $as_scope,
						'identifier' => 'configure',
					],
					'system_order' => 3,
				],
			],
		];

		if (isset($ao_entity->_translations)) {
			/** @var \Awyiss\Model\Entity $lo_translation */
			foreach ($ao_entity->_translations as $ls_shortcode => $lo_translation) {
				$la_data['_translations'][ $ls_shortcode ] = $lo_translation->extract([], false, false);
			}
		}

		$lo_menuEntry = $this->patchEntity($this->newDefaultEntity(), $la_data, [
			'accessibleFields' => 'childBackendMenuEntries',
			'associated' => [
				'ChildBackendMenuEntries' => [
					'validate' => false,
				],
			],
			'validate' => false,
		]);

		$this->save($lo_menuEntry);
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$ao_schema->setColumnType('access', 'json');
	}
}
