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
 * @method \Awyiss\Model\Entity\ContentTemplateContentArea newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class ContentTemplateContentAreasTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'content_template_content_areas';


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
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'contentTemplateId',
		], function (array $context): bool {
			return empty($context['data']['page_template_id']) && $context['newRecord'];
		});


		$validator->requirePresence([
			'pageTemplateId',
		], function (array $context): bool {
			return empty($context['data']['content_template_id']) && $context['newRecord'];
		});


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('contentTemplateId');
		$validator->add('contentTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('contentAreaId');
		$validator->add('contentAreaId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('pageTemplateId');
		$validator->add('pageTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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
		$rules->add($rules->existsIn('contentTemplateId', 'ContentTemplates'), 'contentTemplateExists', [
			'errorField' => 'contentTemplateId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_content_template_exists'),
		]);


		$rules->add($rules->existsIn('contentAreaId', 'ContentAreas'), 'contentAreaExists', [
			'errorField' => 'contentAreaId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_content_area_exists'),
		]);


		$rules->add($rules->existsIn('pageTemplateId', 'PageTemplates'), 'pageTemplateExists', [
			'errorField' => 'pageTemplateId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_page_template_exists'),
		]);


		$rules->add(function (ContentTemplateContentArea $entity/*, array $options*/): bool {
			/** @var \Awyiss\Model\Table\PageTemplateContentAreasTable $pageTemplateContentAreasTable */
			$pageTemplateContentAreasTable = FactoryLocator::get('Table')->get('PageTemplateContentAreas');


			return (bool)$pageTemplateContentAreasTable->find()->where([
				'page_template_id' => $entity->pageTemplateId,
				'content_area_id' => $entity->contentAreaId,
			])->first();
		}, 'contentTemplateContentAreas', [
			'errorField' => '_general',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_page_template_content_area_exists'),
		]);


		return $rules;
	}
}
