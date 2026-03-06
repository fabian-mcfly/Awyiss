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
 * @property string $identifier
 * @property bool $deleted
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 */
class SurveyEntry extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'surveyId' => true,
		'pageId' => true,
		'data' => true,
		'ipHash' => true,
		'postHash' => true,
		'identifier' => true,
	];
}
