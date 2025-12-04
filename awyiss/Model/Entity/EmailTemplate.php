<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * EmailTemplate Entity
 *
 * @property int $id
 * @property string $title
 * @property string $textHtml
 * @property string $textPlain
 * @property string $fileName
 * @property string $layout
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property int|null $usedForEmails
 * @property int|null $usedForConfirmationEmails
 */
class EmailTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'text_html' => 'textHtml',
		'text_plain' => 'textPlain',
		'file_name' => 'fileName',
		'used_for_emails' => 'usedForEmails',
		'used_for_confirmation_emails' => 'usedForConfirmationEmails',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'title' => true,
		'textHtml' => true,
		'textPlain' => true,
		'fileName' => true,
		'layout' => true,
		'active' => true,
	];


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $fileName
	 * @return string|null
	 */
	protected function _setFileName(?string $fileName): ?string {
		if ($fileName === null) {
			return null;
		}

		$fileName = Text::slug($fileName, ['replacement' => '_']);


		return mb_strtolower($fileName);
	}
}
