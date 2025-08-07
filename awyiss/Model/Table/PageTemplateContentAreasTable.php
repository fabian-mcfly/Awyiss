<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * PageTemplateContentAreas Model
 *
 * @property \Awyiss\Model\Table\ContentAreasTable&\Awyiss\ORM\Association\BelongsTo $ContentAreas
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\BelongsTo $PageTemplates
 * @method \Awyiss\Model\Entity\PageTemplateContentArea newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class PageTemplateContentAreasTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'page_template_content_areas';


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

		$this->belongsTo('PageTemplates');
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('pageTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('contentAreaId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('systemOrder', [
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
		$rules->add($rules->existsIn('pageTemplateId', 'PageTemplates'), 'pageTemplateExists', [
			'errorField' => 'pageTemplateId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_page_template_exists'),
		]);


		$rules->add($rules->existsIn('contentAreaId', 'ContentAreas'), 'contentAreaExists', [
			'errorField' => 'contentAreaId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_content_area_exists'),
		]);


		return $rules;
	}
}
