<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Usergroups Model
 *
 * @property \Awyiss\Model\Table\UsergroupPermissionsTable&\Awyiss\ORM\Association\HasMany $UsergroupPermissions
 * @property \Awyiss\Model\Table\UsersTable&\Awyiss\ORM\Association\BelongsToMany $Users
 * @method \Awyiss\Model\Entity\Usergroup newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class UsergroupsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'usergroups';


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

		$auditBehavior = $this->getBehavior('Audit');
		$auditBehavior->setConfig('historyFields', ['users']);
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('UsergroupPermissions', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);

		$this->belongsToMany('Users', [
			'cascadeCallbacks' => true,
			'dependent' => true,
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
		], 'create');


		$validator->add('id', [
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
			$rules->isUnique(['title']),
			'titleUnique',
			[
				'errorField' => 'title',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_title_unique'),
			]
		);


		return $rules;
	}
}
