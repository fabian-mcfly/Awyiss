<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Pages Model
 *
 * @property \Awyiss\Model\Table\LanguagesTable&\Awyiss\ORM\Association\BelongsTo $Languages
 * @property \Awyiss\Model\Table\PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\BelongsTo $PageTemplates
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $DuplicatingPages
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\BelongsTo $DuplicateOfPages
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\BelongsTo $ParentPages
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $ChildPages
 * @property \Awyiss\Model\Table\ContentsTable&\Awyiss\ORM\Association\HasMany $Contents
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Forms
 * @property \Awyiss\Model\Table\SurveysTable&\Awyiss\ORM\Association\BelongsTo $Surveys
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $UrlHistory
 * @method \Awyiss\Model\Entity\Page newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Awyiss\Model\Entity\Page $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Awyiss\Model\Entity\Page $entity, array $options = [])
 * @method \Awyiss\Model\Entity\Page getParent(\Awyiss\Model\Entity\Page $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Awyiss\Model\Entity\Page $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class PagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'pages';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		'finder' => 'forCurrentLanguage',
		'foreignKey' => 'parent_id',
	];
	/**
	 * @inheritDoc
	 */
	protected array $nest = [
		'enabled' => true,
		'relatedColumns' => ['languageShortcode', 'pageRoleId'],
	];
	/**
	 * @var \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	protected PageRoleEnumInterface $pageRole;
	/**
	 * @inheritDoc
	 */
	protected array $search = [
		'blocklistedColumns' => ['language_shortcode', 'page_role_id'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['languageShortcode', 'pageRoleId', 'parentId'],
	];


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function initialize(array $config): void {
		if (!isset($this->pageRole)) {
			/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
			$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

			$la_parts = explode('\\', static::class);

			$this->pageRole = $ls_pageRoleEnum::tryFromName(substr(end($la_parts), 0, -5));
		}

		parent::initialize($config);
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->buildPagesAssociations();

		$this->hasMany('Contents', [
			'cascadeCallbacks' => true,
			'className' => 'Contents',
			'dependent' => true,
			'foreignKey' => 'page_id',
		]);

		$this->belongsTo('Forms');

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['realm' => Awyiss::REALM_FRONTEND],
			'foreignKey' => 'language_shortcode',
		]);

		$this->belongsTo('PageRoles');

		$this->belongsTo('PageTemplates', [
			'bindingKey' => [
				'id',
				'page_role_id',
			],
			'foreignKey' => [
				'page_template_id',
				'page_role_id',
			],
		]);

		$this->belongsTo('Surveys');

		$this->hasMany('UrlHistory', [
			'cascadeCallbacks' => true,
			'conditions' => [
				'scope' => 'pages',
			],
			'dependent' => true,
			'foreignKey' => 'foreign_key',
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findActive(SelectQuery $query): SelectQuery {
		$query->where([
			'active' => true,
			'parents_active' => true,
		]);


		return $query;
	}


	/**
	 * @return \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	public function getPageRole(): PageRoleEnumInterface {
		return $this->pageRole;
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'languageShortcode',
			'slug',
			'title',
			'pageRoleId',
			'pageTemplateId',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('languageShortcode');
		$validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'validation', 'error_exact_length', 2),
				'rule' => function (string $shortcode): bool {
					return strlen($shortcode) == 2;
				},
			],
		]);


		$validator->notEmptyString('slug');
		$validator->add('slug', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'minLength' => ['rule' => ['minLength', 3]],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('redirectLink', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
			'url' => ['rule' => ['url', true]],
		]);


		$validator->add('metaTitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('metaDescription', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('robotsIndex', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('robotsFollow', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->notEmptyString('pageRoleId');
		/** @var class-string<\Awyiss\Model\Enum\PageRole> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		$validator->add('pageRoleId', [
			'enum' => ['rule' => ['enum', $ls_pageRoleEnum]],
		]);


		$validator->notEmptyString('pageTemplateId');
		$validator->add('pageTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('duplicateOf', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('formId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('surveyId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('parentsActive', [
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
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 * @noinspection DuplicatedCode
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$ls_pageRole = Inflector::camelize(Inflector::pluralize($this->pageRole->name));


		$rules->add(
			$rules->existsIn('languageShortcode', 'Languages'),
			'languageExists',
			[
				'errorField' => 'languageShortcode',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_language_exists'),
			]
		);


		$rules->add(
			function (Page $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
				$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

				if (is_int($entity->pageRoleId)) {
					return $ls_pageRoleEnum::tryFrom($entity->pageRoleId) !== null;
				}

				return in_array($entity->pageRoleId, $ls_pageRoleEnum::cases());
			},
			'validPageRoleId',
			[
				'errorField' => 'pageRoleId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_page_role_id'),
			]
		);


		$rules->add(
			$rules->existsIn(['pageTemplateId', 'page_role_id'], 'PageTemplates'),
			'validPageTemplate',
			[
				'errorField' => 'pageTemplateId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_page_template'),
			]
		);


		$rules->add($rules->existsIn(['formId'], 'Forms', ['allowNullableNulls' => true]), 'validFormId', ['errorField' => 'formId']);


		$rules->add($rules->existsIn(['surveyId'], 'Surveys', ['allowNullableNulls' => true]), 'validSurveyId', ['errorField' => 'surveyId']);


		$rules->add(function (Page $entity): bool|string {
			if (empty($entity->duplicateOf)) {
				return true;
			}

			if (!$entity->isNew() && $entity->id === $entity->duplicateOf) {
				return __df($this->getI18nDomain(), 'validation', 'error_not_self_duplicating');
			}

			// Prevent a page (current) from duplicating another one (target),
			// if the (current) page is already duplicated by a page (third).
			if ($entity->id && $this->exists(['duplicate_of' => $entity->id], ['skipPageRoleCheck' => true])) {
				return __df($this->getI18nDomain(), 'validation', 'error_not_duplicating_duplicated');
			}

			/** @var \Awyiss\Model\Entity\Page $lo_duplicateOf */
			$lo_duplicateOf = $this->find('all', skipPageRoleCheck: true)->where([
				'id' => $entity->duplicateOf,
				'page_role_id' => $entity->pageRoleId,
			])->first();

			// Disallow duplicating pages that do not exist
			if (!$lo_duplicateOf) {
				return __df($this->getI18nDomain(), 'validation', 'error_valid_duplicate_of');
			}

			// Prevents a page (current) from duplicating another page (target),
			// if the (target) page is already duplicating another page (third).
			if ($lo_duplicateOf->duplicateOf) {
				return __df($this->getI18nDomain(), 'validation', 'error_not_duplicating_duplicating');
			}

			return true;
		}, 'validDuplicateOf', [
			'errorField' => 'duplicateOf',
		]);


		//Ensure that a page has no linked duplicating pages when deleting it.
		$rules->addDelete(
			function (Page $entity): bool {
				/** @var \Awyiss\Model\Table\PagesTable $lo_pagesTable */
				$lo_pagesTable = FactoryLocator::get('Table')->get('Pages');

				if ($lo_pagesTable->exists(['duplicate_of' => $entity->id], ['skipPageRoleCheck' => true])) {
					// If the page is duplicated by another page, we cannot delete it.
					return false;
				}

				$la_nestedChildren = $this->getNestedPages($entity)?->toArray();

				// No nested children? Allow deletion.
				if (!$la_nestedChildren) {
					return true;
				}

				$la_nestedChildrenIds = array_values(array_map(fn (Page $entity) => $entity->id, $la_nestedChildren));

				// If any of the nested pages is duplicated by another page, we cannot delete it.
				return !$lo_pagesTable->find('all', skipPageRoleCheck: true)->where([
					'duplicate_of IN' => $la_nestedChildrenIds,
					'id NOT IN' => $la_nestedChildrenIds,
				])->count();
			},
			'noDuplicating' . $ls_pageRole,
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_no_duplicating_pages'),
			]
		);


		$rules->addDelete(function (Page $entity/*, array $options = []*/): string|bool {
			/** @var \Awyiss\Model\Table\ContentsTable $lo_contentsTable */
			$lo_contentsTable = FactoryLocator::get('Table')->get('Contents');

			// Get all contents of the current page
			$la_contents = $lo_contentsTable->find()->where(['page_id' => $entity->id])->all()->indexBy('id')->toArray();

			if ($la_contents) {
				// Find contents that duplicate the current page's contents
				if ($lo_contentsTable->find()->where(['duplicate_of IN' => array_keys($la_contents)])->count()) {
					return false;
				}
			}

			$la_nestedChildren = $this->getNestedPages($entity)?->toArray();

			if (!$la_nestedChildren) {
				return true;
			}

			$la_nestedChildrenIds = array_values(array_map(fn (Page $entity) => $entity->id, $la_nestedChildren));

			// Get all contents of all nested children
			$la_contents = $lo_contentsTable->find()->where(['page_id IN' => $la_nestedChildrenIds])->all()->indexBy('id')->toArray();
			if (!$la_contents) {
				return true;
			}

			// If any of the nested pages has a content that is duplicated by other contents,
			// we cannot delete the current page. Except if the duplicating contents
			// are also contents of the nested pages.
			return !$lo_contentsTable->find()->where([
				'duplicate_of IN' => array_keys($la_contents),
				'page_id NOT IN' => $la_nestedChildrenIds,
			])->count();
		}, 'noDuplicatedContents', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_no_duplicated_contents'),
		]);


		$rules->addDelete(function (Page $entity/*, array $options = []*/): bool {
			return !$this->hasDescendantsWithDifferentPageRole($entity);
		}, 'noNestedChildrenWithDifferentPageRole', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_no_nested_children_with_different_page_role'),
		]);


		return $rules;
	}


	/**
	 * @return void
	 */
	protected function buildPagesAssociations(): void {
		$ls_pageRole = Inflector::camelize(Inflector::pluralize($this->pageRole->name));

		$this->hasMany('Duplicating' . $ls_pageRole, [
			'bindingKey' => 'duplicate_of',
			'className' => $ls_pageRole,
			'foreignKey' => 'id',
			'propertyName' => 'duplicated_by',
		]);

		// Singular DuplicateOf<Page/News/Product>
		$this->belongsTo('DuplicateOf' . Inflector::camelize($this->pageRole->name), [
			'bindingKey' => 'id',
			'className' => $ls_pageRole,
			'foreignKey' => 'duplicate_of',
			'propertyName' => 'duplicate',
		]);
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\PageRole> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$schema->setColumnType('page_role_id', EnumType::from($ls_pageRoleEnum));
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return \Cake\Collection\CollectionInterface|null
	 */
	public function getNestedPages(Page $page): ?CollectionInterface {
		$lo_children = $this->getNestedChildren($page, [
			'forceEnable' => true,
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
			'skipFields' => [
				'pageRoleId',
			],
		]);

		if (!$lo_children?->count()) {
			return null;
		}


		return $lo_children;
	}


	/**
	 * @param \Awyiss\Model\Entity\Page $page
	 * @return bool
	 */
	public function hasDescendantsWithDifferentPageRole(Page $page): bool {
		$lo_children = $this->getNestedPages($page);

		if (!$lo_children) {
			return false;
		}

		$la_pageRoles = array_unique($lo_children->extract('pageRoleId')->toList(), SORT_REGULAR);
		$la_pageRoles = array_filter($la_pageRoles, fn (PageRoleEnumInterface $pageRole) => $pageRole != $page->pageRoleId);

		return (bool)$la_pageRoles;
	}


	/**
	 * Get possible field values for the search form.
	 *
	 * @param string $column
	 * @param string|null $type
	 * @return array|null
	 * @throws \ReflectionException
	 */
	public function getPossibleFieldValues(string $column, ?string $type = null): ?array {
		if ($column === 'form_id') {
			return $this->getAssociation('Forms')->find('list', valueField: 'label')->toArray();
		}

		if ($column === 'duplicate_of') {
			/**
			 * @uses \Awyiss\Model\Table::findForCurrentLanguage()
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			return $this->find('forCurrentLanguage')->find('threaded')->all()->listNested()->printer('label', 'id', '- ')->toArray();
		}

		if ($column === 'page_template_id') {
			return $this->getAssociation('PageTemplates')->find('list', valueField: 'label')->where([
				'page_role_id' => $this->getPageRole()->value,
			])->toArray();
		}

		if ($column === 'survey_id') {
			return $this->getAssociation('Surveys')->find('list', valueField: 'label')->toArray();
		}


		return $this->getBehavior('Search')->getPossibleFieldValues($column, $type);
	}
}
