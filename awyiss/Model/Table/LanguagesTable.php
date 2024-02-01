<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;
use DateTimeZone;
use ResourceBundle;


/**
 * Languages Model
 *
 * @property \Awyiss\Model\Table\ConfigurationTable&\Awyiss\ORM\Association\HasMany $Configuration
 * @property \Awyiss\Model\Table\MenuEntriesTable&\Awyiss\ORM\Association\HasMany $MenuEntries
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\HasMany $Pages
 * @method \Awyiss\Model\Entity\Language newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class LanguagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'languages';


	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['realm'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('Configuration', [
			'bindingKey' => [
				'realm',
				'shortcode',
			],
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => [
				'realm',
				'language_shortcode',
			],
		]);

		$this->hasMany('MenuEntries', [
			'bindingKey' => 'shortcode',
			'cascadeCallbacks' => true,
			//'dependent' => true,
			'foreignKey' => 'language_shortcode',
		]);

		$this->hasMany('Pages', [
			'bindingKey' => 'shortcode',
			'cascadeCallbacks' => true,
			//'dependent' => true,
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
			'foreignKey' => 'language_shortcode',
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
			'shortcode',
			'title',
			'timezone',
			'locale',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('shortcode');
		$ao_validator->add('shortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'rule' => function ($as_shortcode) {
					return strlen($as_shortcode) == 2;
				},
			],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('timezone');
		$ao_validator->add('timezone', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('locale');
		$ao_validator->add('locale', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 5]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('realm', [
			'isScalar' => ['rule' => 'isScalar'],
			'inList' => ['rule' => ['inList', Awyiss::getRealms()]],
			'maxLength' => ['rule' => ['maxLength', 20]],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
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
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Language $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSoftDelete(EventInterface $ao_event, Language $ao_entity, ArrayObject $ao_options): void {
		if ($ao_entity->realm === Awyiss::REALM_FRONTEND) {
			$this->MenuEntries->setDependent(true);
			$this->Pages->setDependent(true);
			$this->Pages->ChildPages->setDependent(false)->setCascadeCallbacks(false);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Language $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(EventInterface $ao_event, Language $ao_entity, ArrayObject $ao_options): void {
		$this->MenuEntries->setDependent(false);
		$this->Pages->setDependent(false);
		$this->Pages->ChildPages->setDependent(true)->setCascadeCallbacks(true);
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['shortcode', 'realm']), 'shortcodeUniqueForRealm', [
			'errorField' => 'shortcode',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'shortcode', 'error_shortcode_unique_for_realm'),
		]);


		$ao_rules->add(function (Language $ao_entity): bool {
			return in_array($ao_entity->realm, Awyiss::getRealms());
		}, 'validRealm', [
			'errorField' => 'realm',
			'message' => __d($this->getI18nDomain(), 'error_valid_realm'),
		]);


		$ao_rules->add(function (Language $ao_entity): bool {
			return in_array($ao_entity->timezone, DateTimeZone::listIdentifiers());
		}, 'validTimezone', [
			'errorField' => 'timezone',
			'message' => __d($this->getI18nDomain(), 'error_valid_timezone'),
		]);


		$ao_rules->add(function (Language $ao_entity): bool {
			return in_array($ao_entity->locale, ResourceBundle::getLocales(''));
		}, 'validLocale', [
			'errorField' => 'locale',
			'message' => __d($this->getI18nDomain(), 'error_valid_locale'),
		]);


		$ao_rules->addDelete(
			function (Language $ao_entity) use ($ao_rules): bool {
				$li_count = $this->find()->where(['realm' => $ao_entity->realm])->count();

				return $li_count > 1;
			},
			'notLastLanguageInRealm',
			[
				'errorField' => '_general',
				'message' => __d($this->getI18nDomain(), 'error_not_last_language_in_realm'),
			]
		);


		return $ao_rules;
	}
}
