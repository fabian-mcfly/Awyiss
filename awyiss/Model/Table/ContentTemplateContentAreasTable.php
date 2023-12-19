<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\ContentTemplateContentArea;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
use Cake\Validation\Validator;


/**
 * ContentTemplateContentAreas Model
 *
 * @method ContentTemplateContentArea newDefaultEntity(array $aa_additionalData = [])
 */
class ContentTemplateContentAreasTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'content_template_content_areas';
	/**
	 * @var array|array[]
	 */
	protected array $_defaultConfig = [
		'audit' => [
			'enabled' => FALSE,
		],
		'authorize' => [
			'identifiers' => [
				//We use the content templates-scope, creating an association will occur when creating or updating a content template
				'Entity.create' => [['create', 'update']],
				'Entity.update' => 'update',
				'Model.beforeFind' => [['read', 'create', 'update', 'delete']],
				//We use the content templates-scope, deleting an association will occur when updating or deleting a content template
				'Model.beforeDelete' => [['update', 'delete']],
			],
			'scope' => 'content_templates',
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->belongsTo('ContentAreas');

		$this->belongsTo('ContentTemplates');

		$this->belongsTo('PageTemplates');
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			//'contentTemplateId',
			//'contentAreaId',
			'pageTemplateId',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('contentTemplateId');
		$ao_validator->add('contentTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('contentAreaId');
		$ao_validator->add('contentAreaId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('pageTemplateId');
		$ao_validator->add('pageTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @param RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['contentTemplateId'], 'ContentTemplates'), 'contentTemplateExists', [
			'errorField' => 'contentTemplateId',
			'message' => __d($this->getI18nDomain(), 'error_content_template_exists'),
		]);


		$ao_rules->add($ao_rules->existsIn(['contentAreaId'], 'ContentAreas', ['authorize' => ['skip' => TRUE]]), 'contentAreaExists', [
			'errorField' => 'contentAreaId',
			'message' => __d($this->getI18nDomain(), 'error_content_area_exists'),
		]);


		$ao_rules->add($ao_rules->existsIn(['pageTemplateId'], 'PageTemplates', ['authorize' => ['skip' => TRUE]]), 'pageTemplateExists', [
			'errorField' => 'pageTemplateId',
			'message' => __d($this->getI18nDomain(), 'error_page_template_exists'),
		]);


		$ao_rules->add(function(ContentTemplateContentArea $ao_entity/*, array $aa_options*/): bool {
			$lo_pageTemplateContentAreasTable = FactoryLocator::get('Table')->get('PageTemplateContentAreas');

			return (bool) $lo_pageTemplateContentAreasTable->find()->where([
				'page_template_id' => $ao_entity->pageTemplateId,
				'content_area_id' => $ao_entity->contentAreaId,
			])->first();
		}, 'contentTemplateContentAreas', [
			'errorField' => '_general',
			'message' => __d($this->getI18nDomain(), 'error_page_template_content_area_exists'),
		]);

		return $ao_rules;
	}
}
