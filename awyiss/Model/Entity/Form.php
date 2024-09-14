<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * Form Entity
 *
 * @property int $id
 * @property string $title
 * @property string $identifier
 * @property bool $sendEmail
 * @property int|null $emailTemplateId
 * @property bool $sendConfirmationEmail
 * @property int|null $confirmationEmailTemplateId
 * @property string|null $ownerEmail
 * @property string|null $ownerName
 * @property string|null $userEmail
 * @property string|null $userName
 * @property string|null $cc
 * @property string|null $bcc
 * @property string|null $subject
 * @property string|null $subjectConfirmation
 * @property string|null $salutation
 * @property string|null $salutationConfirmation
 * @property string|null $successMessage
 * @property bool $multistep
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\EmailTemplate $emailTemplate
 * @property \Awyiss\Model\Entity\EmailTemplate $confirmationEmailTemplate
 * @property \Awyiss\Model\Entity\FormElement[]|\Cake\Collection\CollectionInterface $formElements
 * @property \Awyiss\Model\Entity\FormEntry[]|\Cake\Collection\CollectionInterface $formEntries
 */
class Form extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'send_email' => 'sendEmail',
		'email_template_id' => 'emailTemplateId',
		'send_confirmation_email' => 'sendConfirmationEmail',
		'confirmation_email_template_id' => 'confirmationEmailTemplateId',
		'owner_email' => 'ownerEmail',
		'owner_name' => 'ownerName',
		'user_email' => 'userEmail',
		'user_name' => 'userName',
		'subject_confirmation' => 'subjectConfirmation',
		'salutation_confirmation' => 'salutationConfirmation',
		'success_message' => 'successMessage',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'email_template' => 'emailTemplate',
		'confirmation_email_template' => 'confirmationEmailTemplate',
		'form_elements' => 'formElements',
		'form_entries' => 'formEntries',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => true,
		'identifier' => true,
		'sendEmail' => true,
		'emailTemplateId' => true,
		'sendConfirmationEmail' => true,
		'confirmationEmailTemplateId' => true,
		'ownerEmail' => true,
		'ownerName' => true,
		'userEmail' => true,
		'userName' => true,
		'cc' => true,
		'bcc' => true,
		'subject' => true,
		'subjectConfirmation' => true,
		'salutation' => true,
		'salutationConfirmation' => true,
		'successMessage' => true,
		'multistep' => true,
		'active' => true,
	];


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Form::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		$ls_identifier = Text::slug($identifier, ['replacement' => '_']);

		return mb_strtolower($ls_identifier);
	}
}
