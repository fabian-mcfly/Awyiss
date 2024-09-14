<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Forms Model
 *
 * @property \Awyiss\Model\Table\EmailTemplatesTable&\Awyiss\ORM\Association\BelongsTo $EmailTemplates
 * @property \Awyiss\Model\Table\EmailTemplatesTable&\Awyiss\ORM\Association\BelongsTo $ConfirmationEmailTemplates
 * @property \Awyiss\Model\Table\FormElementsTable&\Awyiss\ORM\Association\HasMany $FormElements
 * @property \Awyiss\Model\Table\FormEntriesTable&\Awyiss\ORM\Association\HasMany $FormEntries
 * @method \Awyiss\Model\Entity\Form newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
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
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('EmailTemplates', [
			'className' => 'EmailTemplates',
			'foreignKey' => 'email_template_id',
		]);

		$this->belongsTo('ConfirmationEmailTemplates', [
			'className' => 'EmailTemplates',
			'foreignKey' => 'confirmation_email_template_id',
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
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
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
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('owner_name', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('user_email', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('user_name', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('cc', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);


		$validator->add('bcc', [
			'isArray' => ['rule' => 'isArray'],
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


		/*$validator->notEmptyString('salutation', null, function (array $context): bool {
			return !empty($context['data']['send_email']);
		});
		$validator->add('salutation', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);*/


		$validator->notEmptyString('salutation_confirmation', null, function (array $context): bool {
			return !empty($context['data']['send_confirmation_email']);
		});
		$validator->add('salutation_confirmation', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('success_message', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('multistep', [
			'boolean' => ['rule' => 'boolean'],
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

		return $rules;
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
