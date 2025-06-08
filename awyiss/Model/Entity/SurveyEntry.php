<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * SurveyEntry Entity
 *
 * @property int $id
 * @property int $surveyId
 * @property int|null $pageId
 * @property string|null $data
 * @property string $ipHash
 * @property string|null $postHash
 * @property bool $deleted
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class SurveyEntry extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'survey_id' => 'surveyId',
		'page_id' => 'pageId',
		'ip_hash' => 'ipHash',
		'post_hash' => 'postHash',
		'created_on' => 'createdOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'surveyId' => true,
		'pageId' => true,
		'data' => true,
		'ipHash' => true,
		'postHash' => true,
	];
}
