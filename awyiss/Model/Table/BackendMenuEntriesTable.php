<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Menu\BackendMenu;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * BackendMenuEntries Model
 *
 * @property \Awyiss\Model\Table\BackendMenuEntriesTable&\Awyiss\ORM\Association\BelongsTo $ParentBackendMenuEntries
 * @property \Awyiss\Model\Table\BackendMenuEntriesTable&\Awyiss\ORM\Association\HasMany $ChildBackendMenuEntries
 * @method \Awyiss\Model\Entity\BackendMenuEntry newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Awyiss\Model\Entity\BackendMenuEntry getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 * @noinspection PhpFullyQualifiedNameUsageInspection
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
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'title',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->allowEmptyString('parentId');
		$validator->add('parentId', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->allowEmptyString('insertAfterId');
		$validator->add('insertAfterId', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('link', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('external', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
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
		$lo_rules = $rules;
		$rules->add(function (BackendMenuEntry $entity, array $options) use ($lo_rules): bool {
			static $lo_menu;

			if (!$options['checkRules']) {
				dd(__FILE__, __LINE__);
			}

			$lx_parentId = $entity->get('parentId');
			if (!$lx_parentId) {
				return true;
			}

			if (!is_numeric($lx_parentId)) {
				if (!isset($lo_menu)) {
					$lo_menu = new BackendMenu();
				}


				return (bool)($lo_menu->getCustomMenu() ?? $lo_menu->getMenu())->getItem($lx_parentId);
			}

			$lo_existsIn = $lo_rules->existsIn(
				'parentId',
				'ParentBackendMenuEntries',
				[
					'errorField' => 'parentId',
					'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_parent_id'),
				]
			);


			return $lo_existsIn($entity, $options);
		}, 'validParentId');


		return $rules;
	}


	/**
	 * @param \Awyiss\Model\Entity $entity
	 * @param string $controller
	 * @param string $scope
	 * @param string $insertAfterId
	 * @return void
	 */
	public function createEntries(Entity $entity, string $controller, string $scope, string $insertAfterId = 'pages'): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$la_data = [
			'title' => $entity->title,
			'insert_after_id' => $insertAfterId,
			'link' => $controller . '::overview',
			'access' => [
				'scope' => $scope,
				'identifier' => 'read',
			],
			'child_backend_menu_entries' => [
				[
					'title' => 'generic_datatables::menu_overview',
					'link' => $controller . '::overview',
					'access' => [
						'scope' => $scope,
						'identifier' => 'read',
					],
					'system_order' => 1,
				],
				[
					'title' => 'generic_datatables::menu_add',
					'link' => $controller . '::add',
					'access' => [
						'scope' => $scope,
						'identifier' => 'create',
					],
					'system_order' => 2,
				],
				[
					'title' => 'generic_datatables::menu_configure',
					'link' => 'Configuration::overview::scope:' . $scope,
					'access' => [
						'scope' => $scope,
						'identifier' => 'configure',
					],
					'system_order' => 3,
				],
			],
		];

		if (isset($entity->_translations)) {
			/** @var \Awyiss\Model\Entity $lo_translation */
			foreach ($entity->_translations as $ls_shortcode => $lo_translation) {
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
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('access', 'json');
	}
}
