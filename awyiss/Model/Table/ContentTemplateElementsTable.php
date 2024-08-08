<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\BootstrapColumnSystem;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * ContentTemplateElements Model
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable&\Awyiss\ORM\Association\BelongsTo $ContentTemplates
 * @method \Awyiss\Model\Entity\UsergroupPermission newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class ContentTemplateElementsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'content_template_elements';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];
	/**
	 * @var array The column widths
	 */
	protected array $columnSpans;
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->columnSpans = BootstrapColumnSystem::getColumnWidths();
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('ContentTemplates', [
			'joinType' => 'INNER',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			//'contentTemplateId',
			'identifier',
			'fieldset',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('contentTemplateId');
		$validator->add('contentTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 61]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('fieldset');
		$validator->add('fieldset', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('columSpan', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->columnSpans),
				],
			],
		]);


		$validator->add('required', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn('contentTemplateId', 'ContentTemplates'), 'contentTemplateExists', [
			'errorField' => 'contentTemplateId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_content_template_exists'),
		]);


		return $rules;
	}


	/**
	 * @return array
	 */
	public function getColumnSpans(): array {
		return $this->columnSpans;
	}
}
