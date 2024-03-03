<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * PageTemplates Model
 *
 * @property \Awyiss\Model\Table\ContentAreasTable&\Awyiss\ORM\Association\BelongsToMany $ContentAreas
 * @property \Awyiss\Model\Table\PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $Pages
 * @method \Awyiss\Model\Entity\PageTemplate newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class PageTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'page_templates';
	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'associationName' => 'PageRoles',
		'enabled' => true,
		'identifier' => 'pageRole',
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['pageRoleId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsToMany('ContentAreas', [
			'sort' => ['system_order' => 'ASC'],
			'through' => 'PageTemplateContentAreas',
		]);

		$this->belongsTo('PageRoles', [
			'joinType' => 'INNER',
		]);

		$this->hasMany('Pages', [
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
		]);
	}


	/**
	 * @param SelectQuery $ao_query
	 * @param array $aa_options
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findWithUsages(SelectQuery $ao_query): SelectQuery {
		return $ao_query->enableAutoFields()->select([
			'usedForPages' => $ao_query->func()->count('Pages.id'),
		])->leftJoinWith('Pages', function (SelectQuery $ao_query) {
			return $ao_query->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			]);
		})->groupBy('PageTemplates.id');
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
			'pageRoleId',
			'title',
			'fileName',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('pageRoleId');
		$ao_validator->add('pageRoleId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('fileName');
		$ao_validator->add('fileName', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
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
		$ao_rules->add(
			$ao_rules->isUnique(['fileName']),
			'fileNameUnique',
			[
				'errorField' => 'fileName',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'page_templates', 'error_file_name_unique'),
			]
		);


		$ao_rules->add($ao_rules->existsIn('contentAreaId', 'ContentAreas'), 'validContentAreas', [
			'errorField' => 'contentAreas',
			'message' => __d($this->getI18nDomain(), 'error_valid_content_areas'),
		]);


		$ao_rules->addUpdate(function (PageTemplate $ao_entity, array $aa_options) use ($ao_rules): bool {
			if (
				$aa_options['isCopy'] === true ||
				!$ao_entity->hasOriginal('pageRoleId') ||
				$ao_entity->get('pageRoleId') === $ao_entity->getOriginal('pageRoleId')
			) {
				return true;
			}

			$lo_linkedTo = $ao_rules->isNotLinkedTo(
				'Pages',
				'pageRoleId',
				__d($this->getI18nDomain(), 'error_no_linked_page_templates')
			);

			return $lo_linkedTo($ao_entity, $aa_options);
		}, 'noLinkedPageTemplates');


		$ao_rules->addDelete(
			$ao_rules->isNotLinkedTo('Pages', 'pages'),
			'noLinkedPages',
			[
				'errorField' => '_general',
				'message' => __d($this->getI18nDomain(), 'error_linked_pages'),
			]
		);


		return $ao_rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$this->getSchema()->setColumnType('page_role_id', EnumType::from($ls_pageRoleEnum));
	}
}
