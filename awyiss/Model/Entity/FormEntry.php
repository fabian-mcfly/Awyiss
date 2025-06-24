<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * FormEntry Entity
 *
 * @property int $id
 * @property int $formId
 * @property int $pageId
 * @property string|null $subject
 * @property string|null $subjectConfirmation
 * @property string|null $body
 * @property string|null $bodyConfirmation
 * @property string|null $data
 * @property string $ipHash
 * @property string $postHash
 * @property string $identifier
 * @property bool $deleted
 * @property \Cake\I18n\DateTime $createdOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Form|null $form
 */
class FormEntry extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'form_id' => 'formId',
		'page_id' => 'pageId',
		'subject_confirmation' => 'subjectConfirmation',
		'body_confirmation' => 'bodyConfirmation',
		'ip_hash' => 'ipHash',
		'post_hash' => 'postHash',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'formId' => true,
		'pageId' => true,
		'subject' => true,
		'subjectConfirmation' => true,
		'body' => true,
		'bodyConfirmation' => true,
		'data' => true,
		'ipHash' => true,
		'postHash' => true,
		'identifier' => true,
	];
}
