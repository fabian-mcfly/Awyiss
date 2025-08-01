<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
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
 * @method \Awyiss\Model\Entity\Language newDefaultEntity(array $additionalData = [], array $options = [])
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
	protected array $translate = [
		'fields' => ['title'],
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
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'shortcode',
			'title',
			'timezone',
			'locale',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('realm');
		$validator->add('realm', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'inList' => ['rule' => ['inList', Awyiss::getRealms()]],
			'maxLength' => ['rule' => ['maxLength', 20]],
		]);


		$validator->notEmptyString('shortcode');
		$validator->add('shortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'validation', 'error_exact_length', 2),
				'rule' => function ($shortcode) {
					return strlen($shortcode) == 2;
				},
			],
		]);


		$validator->notEmptyString('timezone');
		$validator->add('timezone', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('locale');
		$validator->add('locale', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 5]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('dateFormat');
		$validator->add('dateFormat', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 30]],
		]);


		$validator->allowEmptyString('timeFormat');
		$validator->add('timeFormat', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 30]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->isUnique(['shortcode', 'realm']),
			'shortcodeUniqueForRealm',
			[
				'errorField' => 'shortcode',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_shortcode_unique_for_realm'),
			]
		);


		$rules->add(function (Language $entity): bool {
			return in_array($entity->realm, Awyiss::getRealms());
		}, 'validRealm', [
			'errorField' => 'realm',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_realm'),
		]);


		$rules->add(function (Language $entity): bool {
			return in_array($entity->timezone, DateTimeZone::listIdentifiers());
		}, 'validTimezone', [
			'errorField' => 'timezone',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_timezone'),
		]);


		$rules->add(function (Language $entity): bool {
			return in_array($entity->locale, ResourceBundle::getLocales(''));
		}, 'validLocale', [
			'errorField' => 'locale',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_locale'),
		]);


		$rules->addDelete(
			function (Language $entity): bool {
				$li_count = $this->find()->where(['realm' => $entity->realm])->count();

				return $li_count > 1;
			},
			'notLastLanguageInRealm',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_not_last_language_in_realm'),
			]
		);


		return $rules;
	}
}
