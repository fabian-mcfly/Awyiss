<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * MediaResizedImage Entity
 *
 * @property int $id
 * @property int $mediaId
 * @property string $name
 * @property string $path
 * @property int|null $width
 * @property int|null $height
 * @property \Awyiss\Model\Enum\ResizeStrategy|null $strategy
 * @property \Awyiss\Model\Enum\ProcessStatus|null $status
 * @property \Awyiss\Model\Entity\Media|null $media
 * @property string|null $extension
 * @property string|null $pathAbsolute
 */
class MediaResizedImage extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaId' => true,
		'name' => true,
		'path' => true,
		'width' => true,
		'height' => true,
		'strategy' => true,
		'status' => true,
	];
	/**
	 * Entity to be passed to the validation of attributes
	 */
	protected ?Entity $entity = null;


	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_id' => 'mediaId',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_virtual = [
		'extension',
		'pathAbsolute',
	];


	/**
	 * @return string|null
	 */
	protected function _getExtension(): ?string {
		if (!$this->name) {
			return null;
		}

		$li_dotPos = strrpos($this->name, '.');

		if (!$li_dotPos) {
			return null;
		}


		return substr($this->name, $li_dotPos + 1);
	}


	/**
	 * @return string|null
	 */
	protected function _getPathAbsolute(): ?string {
		if (!$this->path) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $this->path);
	}
}
