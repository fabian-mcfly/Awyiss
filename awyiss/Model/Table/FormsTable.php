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
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'forms';


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'subject',
			'subjectConfirmation',
			'salutationConfirmation',
			'successMessage',
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
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'title',
			'identifier',
			'transportProfile',
		], 'create');


		// Ensure that ownerEmail is required only if send_email or send_confirmation_email is true
		$validator->requirePresence('ownerEmail', function (array $context): bool {
			return !empty($context['data']['send_email']) || !empty($context['data']['send_confirmation_email']);
		});


		// Ensure that userEmail is required only if send_email or send_confirmation_email is true
		$validator->requirePresence('userEmail', function (array $context): bool {
			return !empty($context['data']['send_email']) || !empty($context['data']['send_confirmation_email']);
		});

		// Ensure that subject is required only if send_email is true
		$validator->requirePresence(['subject'], function (array $context): bool {
			return !empty($context['data']['send_email']);
		});


		// Ensure that subjectConfirmation is required only if send_confirmation_email is true
		$validator->requirePresence(['subjectConfirmation'], function (array $context): bool {
			return !empty($context['data']['send_confirmation_email']);
		});


		// Ensure that emailTemplateId is required only if send_email is true
		$validator->requirePresence('emailTemplateId', function (array $context): bool {
			return !empty($context['data']['send_email']);
		});


		// Ensure that confirmationEmailTemplateId is required only if send_confirmation_email is true
		$validator->requirePresence('confirmationEmailTemplateId', function (array $context): bool {
			return !empty($context['data']['send_confirmation_email']);
		});


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


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('sendEmail', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->notEmptyString('emailTemplateId', null, function (array $context): bool {
			return !empty($context['data']['send_email']);
		});
		$validator->add('emailTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('sendConfirmationEmail', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->notEmptyString('confirmationEmailTemplateId', null, function (array $context): bool {
			return !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('confirmationEmailTemplateId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('ownerEmail', null, function (array $context): bool {
			return !empty($context['data']['send_email']) || !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('ownerEmail', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'email' => ['rule' => 'email'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('ownerName', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('userEmail', null, function (array $context): bool {
			return !empty($context['data']['send_email']) || !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('userEmail', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
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


		$validator->add('userName', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
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
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('subjectConfirmation', null, function (array $context): bool {
			return !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('subjectConfirmation', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->allowEmptyString('salutationConfirmation');
		$validator->add('salutationConfirmation', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('summarizeErrors', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('successMessage', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('multistep', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->notEmptyString('conditionalRecipientsStrategy');
		$validator->add('conditionalRecipientsStrategy', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 20]],
			'inList' => ['rule' => ['inList', [
				FormConditionalRecipients::PROCESS_STRATEGY_MATCH_FIRST,
				FormConditionalRecipients::PROCESS_STRATEGY_MATCH_ALL,
				FormConditionalRecipients::PROCESS_STRATEGY_MATCH_LAST,
			]]],
		]);


		$validator->notEmptyString('transportProfile');
		$validator->add('transportProfile', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
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
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
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

		/** @var class-string<\Awyiss\Utility\Form\Templates\FormTemplateInterface> $ls_className */
		$la_templates = array_map(function ($ls_className) {
			return $ls_className::getTitle();
		}, $la_classes);

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
