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
 * @property ConfigurationTable&\Awyiss\ORM\Association\HasMany $Configuration
 * @method Language newDefaultEntity(array $aa_additionalData = [])
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
				'languageShortcode',
			],
		]);

		$this->hasMany('MenuEntries', [
			'bindingKey' => 'shortcode',
			'cascadeCallbacks' => true,
			'dependent' => true,
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


		return $ao_rules;
	}
}
