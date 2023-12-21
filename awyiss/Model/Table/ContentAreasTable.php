<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * ContentAreas Model
 *
 * @property PageTemplatesTable&\Awyiss\ORM\Association\BelongsToMany $PageTemplates
 * @method \Awyiss\Model\Entity\ContentArea newDefaultEntity(array $aa_additionalData = [])
 */
class ContentAreasTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'content_areas';
	/**
	 * @var array|array<array>
	 */
	protected array $_defaultConfig = [
		'authorize' => [
			'identifiers' => [
				//We use the page templates-scope, creating an association will occur when creating or updating a page template
				'Entity.create' => [['create', 'update']],
				//We use the page templates-scope, updating an association will occur when creating or updating a page template
				'Entity.update' => [['create', 'update']],
				'Model.beforeFind' => [['read', 'create', 'update', 'delete']],
				//We use the page templates-scope, deleting an association will occur when updating or deleting a page template
				'Model.beforeDelete' => [['update', 'delete']],
			],
			'scope' => 'page_templates',
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		/*$this->belongsToMany('ContentTemplates', [
			'foreignKey' => [
				'content_area_id',
				'page_template_id'
			],
			'through' => 'ContentTemplateContentAreas',
		]);*/

		$this->hasMany('ContentTemplateContentAreas', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);

		$this->belongsToMany('PageTemplates', [
			'sort' => ['system_order' => 'ASC'],
			'through' => 'PageTemplateContentAreas',
		]);
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
			'title',
			'identifier',
		], 'create');


		$ao_validator->notEmptyString('identifier');
		$ao_validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('active', [
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
		$ao_rules->add($ao_rules->isUnique(['identifier']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'content_areas', 'error_identifier_unique'),
		]);


		return $ao_rules;
	}
}
