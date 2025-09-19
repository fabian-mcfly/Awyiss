<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * ContentAreas Model
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable&\Awyiss\ORM\Association\BelongsToMany $ContentTemplates
 * @property \Awyiss\Model\Table\PageTemplatesTable&\Awyiss\ORM\Association\BelongsToMany $PageTemplates
 * @method \Awyiss\Model\Entity\ContentArea newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class ContentAreasTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'content_areas';


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
		$this->belongsToMany('ContentTemplates', [
			'through' => 'ContentTemplateContentAreas',
			'saveStrategy' => 'replace',
		]);

		$this->belongsToMany('PageTemplates', [
			'sort' => ['system_order' => 'ASC'],
			'through' => 'PageTemplateContentAreas',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'title',
			'identifier',
		], 'create');


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('active', [
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
			$rules->isUnique(['identifier']),
			'identifierUnique',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique'),
			]
		);


		return $rules;
	}
}
