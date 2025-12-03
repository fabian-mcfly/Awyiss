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
 * @property int|null $realWidth
 * @property int|null $realHeight
 * @property \Awyiss\Model\Enum\ResizeStrategy|null $strategy
 * @property \Awyiss\Model\Enum\ProcessStatus|null $status
 * @property \Awyiss\Model\Entity\Media|null $media
 * @property string|null $extension
 * @property string|null $pathAbsolute
 * @property int|null $filemtime
 */
class MediaResizedImage extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_id' => 'mediaId',
		'real_width' => 'realWidth',
		'real_height' => 'realHeight',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaId' => true,
		'name' => true,
		'path' => true,
		'width' => true,
		'height' => true,
		'realWidth' => true,
		'realHeight' => true,
		'strategy' => true,
		'status' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $_virtual = [
		'extension',
		'filemtime',
		'pathAbsolute',
		'realWidth',
		'realHeight',
	];


	/**
	 * @return string|null
	 */
	protected function _getExtension(): ?string {
		if (!$this->name) {
			return null;
		}

		$dotPos = strrpos($this->name, '.');

		if (!$dotPos) {
			return null;
		}


		return substr($this->name, $dotPos + 1);
	}


	/**
	 * @return int|null
	 * @noinspection PhpUnused
	 */
	public function _getFilemtime(): ?int {
		$path = $this->pathAbsolute;

		if (!$path || !file_exists($path)) {
			return null;
		}

		return filemtime($path);
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
