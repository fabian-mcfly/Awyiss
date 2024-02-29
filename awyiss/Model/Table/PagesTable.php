<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
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
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $SlugHistory
 * @method \Awyiss\Model\Entity\Page newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(Page $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(Page $ao_entity, array $aa_options = [])
 * @method \Awyiss\Model\Entity\Page getParent(Page $ao_entity, array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(Page $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class PagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'pages';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
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
	protected array $systemOrder = [
		'relatedColumns' => ['languageShortcode', 'pageRoleId', 'parentId'],
	];


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function initialize(array $aa_config): void {
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$la_parts = explode('\\', static::class);

		$this->pageRole = $ls_pageRoleEnum::tryFromName(substr(end($la_parts), 0, -5));

		parent::initialize($aa_config);
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

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['realm' => Awyiss::REALM_FRONTEND],
			'foreignKey' => 'language_shortcode',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('PageRoles', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('PageTemplates', [
			'bindingKey' => [
				'id',
				'page_role_id',
			],
			'joinType' => 'INNER',
			'foreignKey' => [
				'page_template_id',
				'page_role_id',
			],
		]);

		$this->hasMany('SlugHistory', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'page_id',
		]);
	}


	/**
	 * @return \Awyiss\Model\Enum\PageRoleEnumInterface
	 */
	public function getPageRole(): PageRoleEnumInterface {
		return $this->pageRole;
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
			'languageShortcode',
			'slug',
			'title',
			'pageRoleId',
			'pageTemplateId',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('languageShortcode');
		$ao_validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'rule' => function ($as_shortcode) {
					return strlen($as_shortcode) == 2;
				},
			],
		]);


		$ao_validator->notEmptyString('slug');
		$ao_validator->add('slug', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('redirectLink', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
			'url' => ['rule' => ['url', true]],
		]);


		$ao_validator->add('metaTitle', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('metaDescription', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('robotsIndex', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('robotsFollow', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->notEmptyString('pageRoleId');
		$ao_validator->add('pageRoleId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('pageTemplateId');
		$ao_validator->add('pageTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('duplicateOf', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('parentsActive', [
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
		$ls_pageRole = Inflector::camelize(Inflector::pluralize($this->pageRole->name));


		$ao_rules->add(
			$ao_rules->existsIn('languageShortcode', 'Languages'),
			'languageExists',
			[
				'errorField' => 'languageShortcode',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'pages', 'error_language_exists'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn('pageRoleId', 'PageRoles'),
			'validPageRole',
			[
				'errorField' => 'pageRoleId',
				'message' => __d($this->getI18nDomain(), 'error_valid_page_role'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn(['pageTemplateId', 'page_role_id'], 'PageTemplates'),
			'validPageTemplate',
			[
				'errorField' => 'pageTemplateId',
				'message' => __d($this->getI18nDomain(), 'error_valid_page_template'),
			]
		);


		$ao_rules->add(
			$ao_rules->existsIn('duplicateOf', 'DuplicateOf' . $ls_pageRole),
			'validDuplicateOf',
			[
				'errorField' => 'duplicateOf',
				'message' => __d($this->getI18nDomain(), 'error_valid_duplicate_of'),
			]
		);


		//Ensure that a page has no linked duplicating pages when deleting it.
		$ao_rules->addDelete(
			$ao_rules->isNotLinkedTo(
				'DuplicatingPages',
				'_general',
				__d($this->getI18nDomain(), 'error_no_duplicating_pages')
			),
			'noDuplicatingPages'
		);


		$ao_rules->addDelete(function (Page $ao_page/*, array $aa_options = []*/): bool {
			$lo_children = $this->getNestedChildren($ao_page, [
				'finder' => [
					'all' => [
						'skipPageRoleCheck' => true,
					],
				],
				'skipFields' => [
					'pageRoleId',
				],
			]);

			if (!$lo_children->count()) {
				return true;
			}

			$la_pageRoles = array_unique($lo_children->extract('pageRoleId')->toList(), SORT_REGULAR);
			$la_pageRoles = array_filter($la_pageRoles, fn (PageRoleEnumInterface $ae_pageRole) => $ae_pageRole != $ao_page->pageRoleId);

			return !$la_pageRoles;
		}, 'noNestedChildrenWithDifferentPageRole', [
			'errorField' => '_general',
			'message' => __d($this->getI18nDomain(), 'error_no_nested_children_with_different_page_role'),
		]);


		return $ao_rules;
	}


	/**
	 * @return void
	 */
	protected function buildPagesAssociations(): void {
		$ls_pageRole = Inflector::camelize(Inflector::pluralize($this->pageRole->name));

		$this->hasMany('Duplicating' . $ls_pageRole, [
			'bindingKey' => 'duplicate_of',
			'className' => $ls_pageRole,
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'id',
		]);

		$this->belongsTo('DuplicateOf' . $ls_pageRole, [
			'bindingKey' => 'id',
			'className' => $ls_pageRole,
			'foreignKey' => 'duplicate_of',
		]);
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$this->getSchema()->setColumnType('page_role_id', EnumType::from($ls_pageRoleEnum));
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param array $aa_options
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findActive(SelectQuery $ao_query): SelectQuery {
		$ao_query->where([
			'active' => true,
			'parents_active' => true,
		]);


		return $ao_query;
	}
}
