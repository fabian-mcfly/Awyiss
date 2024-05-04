<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * Media Entity
 *
 * @property int $id
 * @property int|null $mediaFolderId
 * @property string $mimeType
 * @property int|null $parentId
 * @property string|null $name
 * @property string|null $path
 * @property string $alt
 * @property float $width
 * @property float $height
 * @property string $metaData
 * @property string $averageColor
 * @property \Awyiss\Model\Enum\ProcessStatus|null $preview
 * @property \Awyiss\Model\Enum\ProcessStatus|null $webp
 * @property int $systemOrder
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\MediaFolder $mediaFolder
 * @property \Laminas\Diactoros\UploadedFile|null $file
 * @property string|null $cleanName
 * @property string|null $originalCleanName
 * @property string|null $extension
 * @property string|null $originalExtension
 * @property string|null $pathAbsolute
 * @property string|null $originalPathAbsolute
 * @property string|null $previewName
 * @property string|null $originalPreviewName
 * @property string|null $previewPath
 * @property string|null $originalPreviewPath
 * @property string|null $previewPathAbsolute
 * @property string|null $originalPreviewPathAbsolute
 * @property string|null $webpName
 * @property string|null $originalWebpName
 * @property string|null $webpPath
 * @property string|null $originalWebpPath
 * @property string|null $webpPathAbsolute
 * @property string|null $originalWebpPathAbsolute
 */
class Media extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaFolderId' => true,
		'parentId' => true,
		'name' => true,
		'path' => true,
		'alt' => true,
		'width' => true,
		'height' => true,
		'mimeType' => true,
		'metaData' => true,
		'averageColor' => true,
		'preview' => true,
		'webp' => true,
		'systemOrder' => true,
		'file' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_folder_id' => 'mediaFolderId',
		'parent_id' => 'parentId',
		'mime_type' => 'mimeType',
		'meta_data' => 'metaData',
		'average_color' => 'averageColor',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * @return bool
	 */
	public function isImage(): bool {
		return in_array($this->mimeType, [
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		]);
	}


	/**
	 * @return bool
	 */
	public function originalIsImage(): bool {
		if (!$this->hasOriginal('mimeType')) {
			return false;
		}


		return in_array($this->getOriginal('mimeType'), [
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		]);
	}


	/**
	 * @return void
	 */
	public function moveConvertedFiles(): void {
		$ls_sourceFile = $this->originalWebpPathAbsolute;
		if ($ls_sourceFile && is_file($ls_sourceFile)) {
			$ls_targetFile = $this->webpPathAbsolute;
			if ($ls_targetFile) {
				if (!is_dir(dirname($ls_targetFile))) {
					mkdir(dirname($ls_targetFile));
				}

				rename($ls_sourceFile, $ls_targetFile);
			}
			else {
				unlink($ls_sourceFile);
			}
		}

		$ls_sourceFile = $this->originalPreviewPathAbsolute;
		if ($ls_sourceFile && is_file($ls_sourceFile)) {
			$ls_targetFile = $this->previewPathAbsolute;
			if ($ls_targetFile) {
				if (!is_dir(dirname($ls_targetFile))) {
					mkdir(dirname($ls_targetFile));
				}

				rename($ls_sourceFile, $ls_targetFile);
			}
			else {
				unlink($ls_sourceFile);
			}
		}
	}


	/**
	 * @return void
	 */
	public function deleteConvertedFiles(): void {
		$ls_filePath = $this->previewPathAbsolute;
		if ($ls_filePath && is_file($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_filePath = $this->originalPreviewPathAbsolute;
		if ($ls_filePath && is_file($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_filePath = $this->webpPathAbsolute;
		if ($ls_filePath && is_file($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_filePath = $this->originalWebpPathAbsolute;
		if ($ls_filePath && is_file($ls_filePath)) {
			unlink($ls_filePath);
		}
	}


	/**
	 * @return string|null
	 */
	protected function _getCleanName(): ?string {
		if (!$this->name) {
			return null;
		}

		$li_dotPos = strrpos($this->name, '.');

		if (!$li_dotPos) {
			return $this->name;
		}


		return substr($this->name, 0, $li_dotPos);
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalCleanName(): ?string {
		if (!$this->hasOriginal('name')) {
			return null;
		}

		$li_dotPos = strrpos($this->getOriginal('name'), '.');

		if (!$li_dotPos) {
			return $this->getOriginal('name');
		}


		return substr($this->getOriginal('name'), 0, $li_dotPos);
	}


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
	protected function _getOriginalExtension(): ?string {
		if (!$this->hasOriginal('name')) {
			return null;
		}

		$li_dotPos = strrpos($this->getOriginal('name'), '.');

		if (!$li_dotPos) {
			return null;
		}


		return substr($this->getOriginal('name'), $li_dotPos + 1);
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


	/**
	 * @return string|null
	 */
	protected function _getOriginalPathAbsolute(): ?string {
		if (!$this->hasOriginal('path')) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $this->getOriginal('path'));
	}


	/**
	 * @return string|null
	 */
	protected function _getPreviewName(): ?string {
		if (!$this->name || $this->isImage()) {
			return null;
		}

		return $this->cleanName . '.jpg';
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalPreviewName(): ?string {
		if (!$this->hasOriginal('name') || $this->originalIsImage()) {
			return null;
		}

		return $this->originalCleanName . '.jpg';
	}


	/**
	 * @return string|null
	 */
	protected function _getPreviewPath(): ?string {
		if (!$this->path || $this->isImage()) {
			return null;
		}

		$ls_previewPath = substr($this->path, 0, -strlen($this->name));
		$ls_previewPath .= '_' . $this->extension . '_preview';


		return $ls_previewPath . DS . $this->previewName;
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalPreviewPath(): ?string {
		if (!$this->hasOriginal('path') || $this->originalIsImage()) {
			return null;
		}

		$ls_name = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		$ls_previewPath = substr($this->getOriginal('path'), 0, -strlen($ls_name));
		$ls_previewPath .= '_' . ($this->originalExtension ?? $this->extension) . '_preview';


		return $ls_previewPath . DS . ($this->originalPreviewName ?? $this->previewName);
	}


	/**
	 * @return string|null
	 */
	protected function _getPreviewPathAbsolute(): ?string {
		if (!$this->path || $this->isImage()) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $this->previewPath);
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalPreviewPathAbsolute(): ?string {
		if (!$this->hasOriginal('path') || $this->originalIsImage()) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $this->originalPreviewPath);
	}


	/**
	 * @return string|null
	 */
	protected function _getWebpName(): ?string {
		if (!$this->name) {
			return null;
		}

		return $this->name . '.webp';
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalWebpName(): ?string {
		if (!$this->hasOriginal('name')) {
			return null;
		}

		return $this->getOriginal('name') . '.webp';
	}


	/**
	 * @return string|null
	 */
	protected function _getWebpPath(): ?string {
		if (!$this->path || $this->mimeType === 'image/webp') {
			return null;
		}

		$ls_webpPath = substr($this->path, 0, -strlen($this->name));
		$ls_webpPath .= '_webp';


		return $ls_webpPath . DS . $this->webpName;
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalWebpPath(): ?string {
		if (
			!$this->hasOriginal('path') ||
			(
				$this->hasOriginal('mimeType') &&
				$this->getOriginal('mimeType') === 'image/webp'
			)
		) {
			return null;
		}

		$ls_name = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		$ls_webpPath = substr($this->getOriginal('path'), 0, -strlen($ls_name));
		$ls_webpPath .= '_webp';

		return $ls_webpPath . DS . ($this->originalWebpName ?? $this->webpName);
	}


	/**
	 * @return string|null
	 */
	protected function _getWebpPathAbsolute(): ?string {
		$ls_webpPath = $this->webpPath;

		if (!$ls_webpPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $ls_webpPath);
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalWebpPathAbsolute(): ?string {
		$ls_originalWebpPath = $this->originalWebpPath;

		if (!$ls_originalWebpPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $ls_originalWebpPath);
	}


	/**
	 * Make sure the name is always lowercase, dashed and free of special characters
	 *
	 * @param string|null $as_path
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Page::$path
	 */
	protected function _setName(?string $as_path): ?string {
		if ($as_path === null) {
			return null;
		}

		//Get rid of all chained file suffixes, like ".foo.bar" in filename.foo.bar.jpg
		$la_parts = explode('.', $as_path);
		$ls_extension = count($la_parts) > 1 ? end($la_parts) : null;

		$ls_path = Text::slug($la_parts[0]);

		if ($ls_extension) {
			$ls_path .= '.' . $ls_extension;
		}


		return mb_strtolower($ls_path);
	}
}
