<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\ContentTemplateContentArea;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * ContentTemplateContentAreas Model
 *
 * @property \Awyiss\Model\Table\ContentAreasTable&\Awyiss\ORM\Association\BelongsTo $ContentAreas
 * @property \Awyiss\Model\Table\ContentTemplatesTable&\Awyiss\ORM\Association\BelongsTo $ContentTemplates
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\BelongsTo $PageTemplates
 * @method \Awyiss\Model\Entity\ContentTemplateContentArea newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ContentTemplateContentAreasTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'content_template_content_areas';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('ContentAreas');

		$this->belongsTo('ContentTemplates');

		$this->belongsTo('PageTemplates');
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault(Validator $ao_validator): Validator {
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
	 * @param BaseRulesChecker $ao_rules The rules object to be modified.
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn('content_template_id', 'ContentTemplates'), 'contentTemplateExists', [
			'errorField' => 'contentTemplateId',
			'message' => __d($this->getI18nDomain(), 'error_content_template_exists'),
		]);


		$ao_rules->add($ao_rules->existsIn('content_area_id', 'ContentAreas'), 'contentAreaExists', [
			'errorField' => 'contentAreaId',
			'message' => __d($this->getI18nDomain(), 'error_content_area_exists'),
		]);


		$ao_rules->add($ao_rules->existsIn('page_template_id', 'PageTemplates'), 'pageTemplateExists', [
			'errorField' => 'pageTemplateId',
			'message' => __d($this->getI18nDomain(), 'error_page_template_exists'),
		]);


		$ao_rules->add(function (ContentTemplateContentArea $ao_entity/*, array $aa_options*/): bool {
			/** @var \Awyiss\Model\Table\PageTemplateContentAreasTable $lo_pageTemplateContentAreasTable */
			$lo_pageTemplateContentAreasTable = FactoryLocator::get('Table')->get('PageTemplateContentAreas');


			return (bool)$lo_pageTemplateContentAreasTable->find()->where([
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
