<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
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
 * @property \Awyiss\Model\Table\ContentTemplateContentAreasTable&\Awyiss\ORM\Association\HasMany $ContentTemplateContentAreas
 * @property \Awyiss\Model\Table\PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $Pages
 * @method \Awyiss\Model\Entity\PageTemplate newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
#[MediaElementAssignable(MediaElementAssignable::ENTITY_LEVEL)]
class PageTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'page_templates';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		//'allowAggregation' => false,
		'associationName' => 'PageRoles',
		'enabled' => true,
		'identifier' => 'pageRole',
		'threaded' => false,
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

		$this->hasMany('ContentTemplateContentAreas', [
			'saveStrategy' => 'replace',
		]);

		$this->belongsTo('PageRoles');

		$this->hasMany('Pages', [
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findWithUsages(SelectQuery $query): SelectQuery {
		return $query->enableAutoFields()->select([
			'used_for_pages' => $query->func()->count('Pages.id'),
		])->leftJoinWith('Pages', function (SelectQuery $query) {
			return $query->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			]);
		})->groupBy('PageTemplates.id');
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'pageRoleId',
			'title',
			'fileName',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('pageRoleId');
		$validator->add('pageRoleId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('fileName');
		$validator->add('fileName', [
			'ascii' => ['rule' => 'ascii'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->isUnique(['fileName']),
			'fileNameUnique',
			[
				'errorField' => 'fileName',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_file_name_unique'),
			]
		);


		$lo_rules = $rules;
		$rules->addUpdate(function (PageTemplate $entity, array $options) use ($lo_rules): bool {
			if (
				($options['isCopy'] ?? false) === true ||
				!$entity->hasOriginal('pageRoleId') ||
				$entity->get('pageRoleId') === $entity->getOriginal('pageRoleId')
			) {
				return true;
			}

			$lo_linkedTo = $lo_rules->isNotLinkedTo(
				'Pages',
				'pageRoleId',
				__df($this->getI18nDomain(), 'validation', 'error_no_linked_pages')
			);

			return $lo_linkedTo($entity, $options);
		}, 'noLinkedPageTemplates');


		$rules->addDelete(
			$rules->isNotLinkedTo('Pages', 'pages'),
			'noLinkedPages',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_no_linked_pages'),
			]
		);


		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$schema->setColumnType('page_role_id', EnumType::from($ls_pageRoleEnum));
	}
}
