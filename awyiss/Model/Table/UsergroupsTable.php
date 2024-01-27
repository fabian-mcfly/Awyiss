<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Routing\Router;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Usergroups Model
 *
 * @property UsergroupPermissionsTable&\Awyiss\ORM\Association\HasMany $UsergroupPermissions
 * @property \Awyiss\ORM\Association\BelongsToMany $Users
 * @method \Awyiss\Model\Entity\Usergroup newDefaultEntity(array $aa_additionalData = [])
 */
class UsergroupsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'usergroups';


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
		$this->hasMany('UsergroupPermissions', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);

		$this->belongsToMany('Users');
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
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
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
		$ao_rules->add($ao_rules->isUnique(['title']), 'titleUnique', [
			'errorField' => 'title',
			'message' => __d($this->getI18nDomain(), 'error_title_unique'),
		]);


		return $ao_rules;
	}


	/**
	 * Set the `changedOn`-field for all associated users.
	 * This will allow the SessionAuthenticator to reset the usergroups for every logged-in user on the next page request
	 *
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Usergroup $ao_entity
	 * @param ArrayObject $ao_options
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$lo_query = $this->Users->find()->matching('UsergroupsUsers', function (SelectQuery $ao_query) use ($ao_entity) {
			return $ao_query->where(['UsergroupsUsers.usergroup_id' => $ao_entity->id]);
		});

		$lo_users = $lo_query->all();

		if (!$lo_users->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		$lo_currentUser = null;
		$lo_request = Router::getRequest();
		if ($lo_request) {
			/** @var \Cake\Http\Session $lo_session */
			$lo_session = $lo_request->getAttribute('session');
			/** @var User|\Awyiss\Model\Entity\UsersExternal $lo_currentUser */
			$lo_currentUser = $lo_session->read('Auth');
		}

		$lo_now = FrozenTime::now();
		//Decrease the system order of all records
		$lo_users->each(function (User $ao_user) use ($lo_now, $lo_currentUser): void {
			$ao_user->changedOn = $lo_now;

			if ($ao_user->id === $lo_currentUser->id) {
				$lo_currentUser->usergroups = null;
			}
		});

		//Save all found records, but skip the audit and the system order behavior on those to avoid recursion.
		$this->Users->saveMany($lo_users, [
			'audit' => ['skip' => true],
			'checkRules' => false,
			'systemOrder' => ['skip' => true],
		]);
	}
}
