<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Language;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;
use DateTimeZone;
use ResourceBundle;


/**
 * Languages Model
 *
 * @property \Awyiss\Model\Table\ConfigurationTable&\Cake\ORM\Association\HasMany $Configuration
 *
 * @method Language newDefaultEntity(array $aa_additionalData = [])
 */
class LanguagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'systemOrder' => [
			'relatedColumns' => ['type'],
		],
		'translate' => [
			'fields' => ['title'],
		],
	];
	public const TABLE = 'languages';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setPrimaryKey('id');

		$this->hasMany('Configuration')
			->setBindingKey('shortcode')
			->setForeignKey('language_shortcode')
			->setSaveStrategy('replace')
			->setDependent(FALSE);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->ascii('shortcode')
			->minLength('shortcode', 2, __('validation::not_exact_length'))
			->maxLength('shortcode', 2, __('validation::not_exact_length'))
			->requirePresence('shortcode')
			->notEmptyString('shortcode');

		$ao_validator->scalar('title')->maxLength('title', 32)->requirePresence('title')->notEmptyString('title');

		$ao_validator->scalar('timezone')->maxLength('timezone', 32)->requirePresence('timezone')->notEmptyString('timezone');

		$ao_validator->scalar('locale')->maxLength('locale', 5)->requirePresence('locale')->notEmptyString('locale');

		$ao_validator->scalar('type')->notEmptyString('type');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['shortcode', 'type'], __('validation::shortcode_not_unique')));

		$ao_rules->add(function(Language $ao_entity) {
			return in_array($ao_entity->timezone, DateTimeZone::listIdentifiers());
		}, [
			'errorField' => 'timezone',
			'message' => __('::unknown_timezone'),
		]);

		$ao_rules->add(function(Language $ao_entity) {
			return in_array($ao_entity->locale, ResourceBundle::getLocales(''));
		}, [
			'errorField' => 'locale',
			'message' => __('::unknown_locale'),
		]);

		return $ao_rules;
	}
}
