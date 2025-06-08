<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Form\FormConditionalRecipients;
use Awyiss\Model\Entity\Form;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Form\Templates\FormTemplateInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Mailer\TransportFactory;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validation;
use Cake\Validation\Validator;


/**
 * Forms Model
 *
 * @property \Awyiss\Model\Table\EmailTemplatesTable&\Awyiss\ORM\Association\BelongsTo $EmailTemplates
 * @property \Awyiss\Model\Table\EmailTemplatesTable&\Awyiss\ORM\Association\BelongsTo $ConfirmationEmailTemplates
 * @property \Awyiss\Model\Table\FormElementsTable&\Awyiss\ORM\Association\HasMany $FormElements
 * @property \Awyiss\Model\Table\FormEntriesTable&\Awyiss\ORM\Association\HasMany $FormEntries
 * @method \Awyiss\Model\Entity\Form newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class FormsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'forms';


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'subject',
			'subject_confirmation',
			'salutation_confirmation',
			'success_message',
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('Contents');

		$this->belongsTo('EmailTemplates', [
			'className' => 'EmailTemplates',
			'foreignKey' => 'email_template_id',
		]);

		$this->belongsTo('ConfirmationEmailTemplates', [
			'className' => 'EmailTemplates',
			'foreignKey' => 'confirmation_email_template_id',
		]);

		$this->hasMany('FormConditionalRecipients', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'form_id',
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('FormElements', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'form_id',
		]);

		$this->hasMany('FormEntries', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'form_id',
		]);

		$this->hasMany('Pages');

		$this->hasMany('Surveys');

		$this->hasMany('Widgets');
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'title',
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('send_email', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->notEmptyString('email_template_id', null, function (array $context): bool {
			return !empty($context['data']['send_email']);
		});
		$validator->add('email_template_id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('send_confirmation_email', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->notEmptyString('confirmation_email_template_id', null, function (array $context): bool {
			return !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('confirmation_email_template_id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('owner_email', null, function (array $context): bool {
			return !empty($context['data']['send_email']) || !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('owner_email', [
			'isScalar' => ['rule' => 'isScalar'],
			'email' => ['rule' => 'email'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('owner_name', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('user_email', null, function (array $context): bool {
			return !empty($context['data']['send_email']) || !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('user_email', [
			'isScalar' => ['rule' => 'isScalar'],
			'email' => ['rule' => function (string $value): bool {
				/**
				 * String must be a valid email address or a placeholder like:
				 * - `$identifier`
				 * * - `{{$identifier}}`
				 * * - `{{$identifier|Alternative Text}}`
				 * * - `{{$identifier1 $identifier 2|Alternative Text}}`
				 */
				if (Validation::email($value)) {
					return true;
				}

				/** @noinspection RegExpRedundantEscape */
				if (
					preg_match('/^\{\{(?<identifiers>[^\|\}]*?)(?:\|(?<alternative>[^\}]*?))?\}\}$/', $value, $la_matches) ||
					preg_match('/^(\$(?<identifier>[A-Za-z0-9_]+))$/', $value, $la_matches)
				) {
					if (!empty($la_matches['alternative'])) {
						return Validation::email($la_matches['alternative']);
					}

					return true;
				}

				return false;
			}],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('user_name', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('cc', [
			'isArray' => ['rule' => 'isArray'],
			'email' => ['rule' => function (array $value): bool {
				foreach ($value as $la_cc) {
					if (
						empty($la_cc['email']) ||
						!is_string($la_cc['email']) ||
						!Validation::email($la_cc['email'])
					) {
						return false;
					}
				}

				return true;
			}],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);


		$validator->add('bcc', [
			'isArray' => ['rule' => 'isArray'],
			'email' => ['rule' => function (array $value): bool {
				foreach ($value as $la_bcc) {
					if (
						empty($la_bcc['email']) ||
						!is_string($la_bcc['email']) ||
						!Validation::email($la_bcc['email'])
					) {
						return false;
					}
				}

				return true;
			}],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);


		$validator->notEmptyString('subject', null, function (array $context): bool {
			return !empty($context['data']['send_email']);
		});
		$validator->add('subject', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('subject_confirmation', null, function (array $context): bool {
			return !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('subject_confirmation', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('salutation_confirmation', null, function (array $context): bool {
			return !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('salutation_confirmation', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('summarize_errors', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('success_message', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('multistep', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->notEmptyString('conditional_recipients_strategy');
		$validator->add('conditional_recipients_strategy', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'inList' => ['rule' => ['inList', [
				FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
				FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL,
				FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST,
			]]],
		]);


		$validator->add('transport_profile', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
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
			$rules->existsIn('emailTemplateId', 'EmailTemplates'),
			'emailTemplateExists',
			[
				'errorField' => 'emailTemplateId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_email_template_exists'),
			]
		);

		$rules->add(
			$rules->existsIn('confirmationEmailTemplateId', 'ConfirmationEmailTemplates'),
			'confirmationEmailTemplateExists',
			[
				'errorField' => 'confirmationEmailTemplateId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_confirmation_email_template_exists'),
			]
		);

		$rules->add($rules->isUnique(['identifier']), 'identifierUnique', [
			'errorField' => 'identifier',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique'),
		]);

		$rules->add(function (Form $entity): bool {
			return array_key_exists($entity->transportProfile, $this->getTransportProfiles());
		},
		'transportProfileExists', [
			'errorField' => 'transportProfile',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_transport_profile_exists'),
		]);

		$rules->addDelete(
			$rules->isNotLinkedTo('Contents', 'contents'),
			'noLinkedContents',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_contents'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('Pages', 'pages'),
			'noLinkedPages',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_pages'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('Surveys', 'surveys'),
			'noLinkedSurveys',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_surveys'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('Widgets', 'widgets'),
			'noLinkedWidgets',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_widgets'),
			]
		);

		return $rules;
	}


	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getFormTemplates(): array {
		$la_classes = App::classes('*', 'Utility/Form/Templates', 'FormTemplate', FormTemplateInterface::class);

		$la_templates = [];

		/** @var class-string<\Awyiss\Utility\Form\Templates\FormTemplateInterface> $ls_className */
		foreach ($la_classes as $ls_templateName => $ls_className) {
			$la_templates[ $ls_templateName ] = $ls_className::getTitle();
		}

		uasort($la_templates, function ($a, $b) {
			return strnatcasecmp($a, $b);
		});

		return $la_templates;
	}


	/**
	 * @return array
	 */
	public function getTransportProfiles(): array {
		$la_profiles = [];

		foreach (TransportFactory::configured() ?: [] as $ls_profile) {
			$la_config = TransportFactory::get($ls_profile)->getConfig();
			unset($la_config['url'], $la_config['password']);

			$ls_label = __d('forms', 'transport_profile_' . $ls_profile, $la_config);
			$la_profiles[ $ls_profile ] = str_contains($ls_label, '::') ? $ls_profile : $ls_label;
		}

		return $la_profiles;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('cc', 'json');
		$schema->setColumnType('bcc', 'json');
	}
}
