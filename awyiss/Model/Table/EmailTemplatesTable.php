<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\EmailTemplate;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Core\Configure;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * EmailTemplates Model
 *
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\HasMany $FormEmails
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\HasMany $FormConfirmationEmails
 * @method \Awyiss\Model\Entity\EmailTemplate newDefaultEntity(array $additionalData = [], array $options = [])
 */
class EmailTemplatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'email_templates';


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => [
			'title',
			'text_html',
			'text_plain',
		],
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('FormEmails', [
			'className' => 'Forms',
			'foreignKey' => 'email_template_id',
		]);

		$this->hasMany('FormConfirmationEmails', [
			'className' => 'Forms',
			'foreignKey' => 'confirmation_email_template_id',
		]);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array $options
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findWithUsages(SelectQuery $query): SelectQuery {
		return $query->enableAutoFields()->select([
			'used_for_emails' => $query->func()->count('FormEmails.id'),
			'used_for_confirmation_emails' => $query->func()->count('FormConfirmationEmails.id'),
		])->leftJoinWith('FormEmails', function (SelectQuery $query) {
			return $query->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			]);
		})->leftJoinWith('FormConfirmationEmails', function (SelectQuery $query) {
			return $query->applyOptions([
				'attributes' => [
					'skip' => true,
				],
			]);
		})->groupBy('EmailTemplates.id');
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'title',
			'fileName',
			'layout',
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


		$validator->add('textHtml', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->add('textPlain', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLengthBytes' => ['rule' => ['maxLengthBytes', 65535]],
		]);


		$validator->notEmptyString('fileName');
		$validator->add('fileName', [
			'ascii' => ['rule' => 'ascii'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('layout');
		$validator->add('layout', [
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
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->isUnique(['fileName']), 'fileNameUnique', [
			'errorField' => 'fileName',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_file_name_unique'),
		]);

		$rules->add(function (EmailTemplate $entity): bool {
			return in_array($entity->layout, $this->getAvailableLayouts(), true);
		}, 'validLayout', [
			'errorField' => 'layout',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_layout'),
		]);

		$rules->addDelete(
			$rules->isNotLinkedTo('FormEmails', 'formEmails'),
			'noLinkedFormEmails',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_form_emails'),
			]
		);

		$rules->addDelete(
			$rules->isNotLinkedTo('FormConfirmationEmails', 'formConfirmationEmails'),
			'noLinkedFormConfirmationEmails',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_form_confirmation_emails'),
			]
		);

		return $rules;
	}


	/**
	 * Returns a list of layout template files in the `layout/email` folder
	 * of either the custom or awyiss folder.
	 *
	 * @return array
	 */
	public function getAvailableLayouts(): array {
		$la_paths = Configure::read('App.paths.templates');

		$la_layouts = [];

		foreach ($la_paths as $ls_path) {
			$ls_path = $ls_path . 'Frontend' . DS . 'layout' . DS . 'email' . DS . '*.twig';
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_fileName = basename($ls_filePath);

				if (!isset($la_layouts[ $ls_fileName ])) {
					$la_layouts[ $ls_fileName ] = $ls_fileName;
				}
			}
		}

		return $la_layouts;
	}
}
