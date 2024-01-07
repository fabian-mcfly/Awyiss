<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * PageTemplates Model
 *
 * @property ContentAreasTable&\Awyiss\ORM\Association\BelongsToMany $ContentAreas
 * @property PageRolesTable&\Awyiss\ORM\Association\BelongsTo $PageRoles
 * @method \Awyiss\Model\Entity\PageTemplate newDefaultEntity(array $aa_additionalData = [])
 */
class PageTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'page_templates';


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
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		$this->belongsToMany('ContentAreas', [
			'sort' => ['system_order' => 'ASC'],
			'through' => 'PageTemplateContentAreas',
		]);

		$this->belongsTo('PageRoles', [
			'joinType' => 'INNER',
		]);

		$this->hasMany('Pages', [
			'cascadeCallbacks' => true,
			'dependent' => true,
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
					'skipPageRoleCheck' => true,
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
			'filename',
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


		$ao_validator->notEmptyString('filename');
		$ao_validator->add('filename', [
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
		$ao_rules->add($ao_rules->isUnique(['filename']), 'uniqueFilename', [
			'errorField' => 'filename',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'page_templates', 'error_unique_filename'),
		]);


		$ao_rules->add($ao_rules->existsIn(['contentAreaId'], 'ContentAreas'), 'validContentAreas', [
			'errorField' => 'contentAreas',
			'message' => __d($this->getI18nDomain(), 'error_valid_content_areas'),
		]);


		$ao_rules->add($ao_rules->existsIn(['pageRoleId'], 'PageRoles'), 'validPageRole', [
			'errorField' => 'pageRoleId',
			'message' => __d($this->getI18nDomain(), 'error_valid_page_role'),
		]);


		$ao_rules->addDelete(
			$ao_rules->isNotLinkedTo('Pages', 'pages'),
			'noLinkedPages',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'system', 'error_linked_pages'),
			]
		);


		return $ao_rules;
	}
}
