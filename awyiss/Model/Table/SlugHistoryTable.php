<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * SlugHistory Model
 *
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\BelongsTo $Pages
 * @method \Awyiss\Model\Entity\SlugHistory newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class SlugHistoryTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'slug_history';


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {

		$this->belongsTo('Pages', [
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
			'foreignKey' => 'page_id',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);

		$ao_validator->notEmptyString('slug');
		$ao_validator->add('slug', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		$ao_validator->notEmptyString('pageId');
		$ao_validator->add('pageId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param BaseRulesChecker $ao_rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['pageId'], 'Pages'), 'validPageId', [
			'errorField' => 'pageId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_page_id'),
		]);


		return $ao_rules;
	}
}
