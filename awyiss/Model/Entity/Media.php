<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * Media Entity
 *
 * @property int $id
 * @property int|null $mediaFolderId
 * @property string $mimeType
 * @property string|null $name
 * @property string|null $path
 * @property string $alt
 * @property float $width
 * @property float $height
 * @property string $metaData
 * @property string $averageColor
 * @property \Awyiss\Model\Enum\ProcessStatus|null $preview
 * @property \Awyiss\Model\Enum\ProcessStatus|null $avif
 * @property \Awyiss\Model\Enum\ProcessStatus|null $webp
 * @property array|null $crop
 * @property string|null $focusPoint
 * @property int $systemOrder
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\MediaAssignment[] $mediaAssignments
 * @property \Awyiss\Model\Entity\MediaFolder $mediaFolder
 * @property \Awyiss\Model\Entity\MediaResizedImage|null $mediaResizedImages
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
 * @property string|null $avifName
 * @property string|null $originalAvifName
 * @property string|null $avifPath
 * @property string|null $originalAvifPath
 * @property string|null $avifPathAbsolute
 * @property string|null $originalAvifPathAbsolute
 * @property string|null $webpName
 * @property string|null $originalWebpName
 * @property string|null $webpPath
 * @property string|null $originalWebpPath
 * @property string|null $webpPathAbsolute
 * @property string|null $originalWebpPathAbsolute
 * @property int|null $usageCount
 */
class Media extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'mediaFolderId' => true,
		'name' => true,
		'path' => true,
		'alt' => true,
		'width' => true,
		'height' => true,
		'mimeType' => true,
		'metaData' => true,
		'averageColor' => true,
		'preview' => true,
		'avif' => true,
		'webp' => true,
		'crop' => true,
		'focusPoint' => true,
		'systemOrder' => true,
		'file' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'media_folder_id' => 'mediaFolderId',
		'mime_type' => 'mimeType',
		'meta_data' => 'metaData',
		'average_color' => 'averageColor',
		'focus_point' => 'focusPoint',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'media_resized_images' => 'mediaResizedImages',
		'media_assignments' => 'mediaAssignments',
		'usage_count' => 'usageCount',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_virtual = [
		'label',
		'isAudio',
		'isImage',
		'isVideo',
		'cleanName',
		'originalCleanName',
		'extension',
		'originalExtension',
		'pathAbsolute',
		'originalPathAbsolute',
		'previewName',
		'originalPreviewName',
		'previewPath',
		'originalPreviewPath',
		'previewPathAbsolute',
		'originalPreviewPathAbsolute',
		'avifName',
		'originalAvifName',
		'avifPath',
		'originalAvifPath',
		'avifPathAbsolute',
		'originalAvifPathAbsolute',
		'webpName',
		'originalWebpName',
		'webpPath',
		'originalWebpPath',
		'webpPathAbsolute',
		'originalWebpPathAbsolute',
	];


	/**
	 * return bool
	 */
	public function isAudio(): bool {
		return in_array($this->mimeType, [
			'audio/mpeg',
			'audio/ogg',
			'audio/wav',
			'audio/webm',
		]);
	}


	/**
	 * @return bool
	 */
	public function isImage(): bool {
		return in_array($this->mimeType, [
			'image/avif',
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
			'image/avif',
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		]);
	}


	/**
	 * @return bool
	 */
	public function isVideo(): bool {
		return in_array($this->mimeType, [
			'video/mp4',
			'video/ogg',
			'video/webm',
		]);
	}


	/**
	 * Finds alternative media records with the same base name but different extensions or variations in the name.
	 *
	 * @return array|null Returns an array of alternative media records if found, or null otherwise.
	 */
	public function findAlternatives(): ?array {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Media');

		$ls_name = $this->cleanName;
		if (!$ls_name) {
			return null;
		}

		// Find files with the same name but different extension or -xx pattern
		$la_results = $lo_table->find()->where([
			'id !=' => $this->id,
			'OR' => [
				['name LIKE' => $ls_name . '.%'],
				['name LIKE' => $ls_name . '-__.%'],
			],
		])->all()->toArray();

		return $la_results ?: null;
	}


	/**
	 * @return void
	 */
	public function moveConvertedFiles(): void {
		$ls_sourceFile = $this->originalAvifPathAbsolute;
		if ($ls_sourceFile && is_file($ls_sourceFile)) {
			$ls_targetFile = $this->avifPathAbsolute;
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
	public function moveResizedFiles(): void {
		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('MediaResizedImages');
		$lo_query = $lo_table->updateQuery();

		$ls_fileName = $this->name;
		$ls_fileName = substr($ls_fileName, 0, strrpos($ls_fileName, '.'));
		$ls_directory = substr($this->path, 0, strrpos($this->path, DS)) . DS . '_resized' . DS;
		$ls_directory .= $ls_fileName;

		$ls_originalName = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		$ls_originalName = substr($ls_originalName, 0, strrpos($ls_originalName, '.'));

		$ls_originalDirectory = substr($this->getOriginal('path'), 0, strrpos($this->getOriginal('path'), DS)) . DS . '_resized' . DS;
		$ls_originalDirectory .= $ls_originalName;

		/**
		 * UPDATE media_resized_images SET
		 * 	name = (CONCAT('newname', substr(name, <strlen(oldname) + 1>))),
		 * 	path = (CONCAT('newpath', substr(path, <strlen(oldpath) + 1>)))
		 * WHERE media_id = 1
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$lo_query->update('media_resized_images')->set('name', $lo_query->newExpr($lo_query->func()->concat([
			$ls_fileName,
			$lo_query->func()->substr([
				'name' => 'identifier',
				mb_strlen($ls_originalName) + 1,
			], [
				null,
				'integer',
			]),
		])))->set('path', $lo_query->newExpr($lo_query->func()->concat([
			$ls_directory,
			$lo_query->func()->substr([
				'path' => 'identifier',
				mb_strlen($ls_originalDirectory) + 1,
			], [
				null,
				'integer',
			]),
		])))->where(['media_id' => $this->id])->execute();


		if ($this->isImage()) {
			$ls_baseName = $this->originalCleanName ?? $this->cleanName;
		}
		else {
			$ls_baseName = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		}

		$ls_globFileName = $ls_baseName . '-\[*\].*';

		$ls_path = $this->getOriginal('path');
		$ls_path = substr($ls_path, 0, strrpos($ls_path, DS)) . DS . '_resized' . DS;

		$la_resizedFiles = glob($ls_path . $ls_globFileName);
		if (!is_array($la_resizedFiles) || empty($la_resizedFiles)) {
			return;
		}

		$ls_targetPath = $this->path;
		$ls_targetPath = substr($ls_targetPath, 0, strrpos($ls_targetPath, DS)) . DS . '_resized' . DS;

		foreach ($la_resizedFiles as $ls_filePath) {
			$ls_targetFileName = $ls_fileName . substr(basename($ls_filePath), strlen($ls_originalName));
			$ls_targetFilePath = $ls_targetPath . $ls_targetFileName;

			if (!is_dir($ls_targetPath)) {
				mkdir($ls_targetPath);
			}

			rename($ls_filePath, $ls_targetFilePath);
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

		$ls_filePath = $this->avifPathAbsolute;
		if ($ls_filePath && is_file($ls_filePath)) {
			unlink($ls_filePath);
		}

		$ls_filePath = $this->originalAvifPathAbsolute;
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
	 * @return void
	 */
	public function deleteResizedFiles(): void {
		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('MediaResizedImages');
		$lo_table->deleteAll([
			'media_id' => $this->id,
		]);

		$ls_baseName = $this->isImage() ? $this->cleanName : $this->name;

		$ls_name = $ls_baseName . '-\[*\].*';

		$ls_path = $this->path;
		$ls_path = substr($ls_path, 0, strrpos($ls_path, DS)) . DS . '_resized' . DS . $ls_name;

		$la_resizedFiles = glob($ls_path);
		if (is_array($la_resizedFiles) && !empty($la_resizedFiles)) {
			array_map('unlink', $la_resizedFiles);
		}
	}


	/**
	 * @return bool
	 * @noinspection PhpUnused
	 */
	protected function _getIsImage(): bool {
		return $this->isImage();
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
	 */
	protected function _getPathAbsolute(): ?string {
		if (!$this->path) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $this->path);
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getOriginalPathAbsolute(): ?string {
		if (!$this->hasOriginal('path')) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $this->getOriginal('path'));
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getPreviewName(): ?string {
		if (!$this->name || $this->isImage()) {
			return null;
		}

		return $this->cleanName . '.jpg';
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getOriginalPreviewName(): ?string {
		if (!$this->hasOriginal('name') || $this->originalIsImage()) {
			return null;
		}

		return $this->originalCleanName . '.jpg';
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
	 */
	protected function _getPreviewPathAbsolute(): ?string {
		if (!$this->path || $this->isImage()) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $this->previewPath);
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getOriginalPreviewPathAbsolute(): ?string {
		if (!$this->hasOriginal('path') || $this->originalIsImage()) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $this->originalPreviewPath);
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getAvifName(): ?string {
		if (!$this->name) {
			return null;
		}

		return $this->name . '.avif';
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getOriginalAvifName(): ?string {
		if (!$this->hasOriginal('name')) {
			return null;
		}

		return $this->getOriginal('name') . '.avif';
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getAvifPath(): ?string {
		if (!$this->path || $this->mimeType === 'image/avif') {
			return null;
		}

		$ls_avifPath = substr($this->path, 0, -strlen($this->name));
		$ls_avifPath .= '_avif';


		return $ls_avifPath . DS . $this->avifName;
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getOriginalAvifPath(): ?string {
		if (
			!$this->hasOriginal('path') ||
			(
				$this->hasOriginal('mimeType') &&
				$this->getOriginal('mimeType') === 'image/avif'
			)
		) {
			return null;
		}

		$ls_name = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		$ls_avifPath = substr($this->getOriginal('path'), 0, -strlen($ls_name));
		$ls_avifPath .= '_avif';

		return $ls_avifPath . DS . ($this->originalAvifName ?? $this->avifName);
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getAvifPathAbsolute(): ?string {
		$ls_avifPath = $this->avifPath;

		if (!$ls_avifPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $ls_avifPath);
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getOriginalAvifPathAbsolute(): ?string {
		$ls_originalAvifPath = $this->originalAvifPath;

		if (!$ls_originalAvifPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $ls_originalAvifPath);
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getWebpName(): ?string {
		if (!$this->name) {
			return null;
		}

		return $this->name . '.webp';
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _getOriginalWebpName(): ?string {
		if (!$this->hasOriginal('name')) {
			return null;
		}

		return $this->getOriginal('name') . '.webp';
	}


	/**
	 * @return string|null
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
	 */
	protected function _getOriginalWebpPathAbsolute(): ?string {
		$ls_originalWebpPath = $this->originalWebpPath;

		if (!$ls_originalWebpPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $ls_originalWebpPath);
	}


	/**
	 * Make sure the average does not start with a hash
	 *
	 * @param string|null $color
	 * @return string|null
	 * @noinspection PhpUnused
	 */
	protected function _setAverageColor(?string $color): ?string {
		if ($color === null) {
			return null;
		}

		return ltrim($color, '#');
	}


	/**
	 * Make sure the name is always lowercase, dashed and free of special characters
	 *
	 * @param string|null $path
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Page::$path
	 * @noinspection PhpUnused
	 */
	protected function _setName(?string $path): ?string {
		if ($path === null) {
			return null;
		}

		//Get rid of all chained file suffixes, like ".foo.bar" in filename.foo.bar.jpg
		$la_parts = explode('.', $path);
		$ls_extension = count($la_parts) > 1 ? end($la_parts) : null;

		$ls_path = Text::slug($la_parts[0]);

		if ($ls_extension) {
			$ls_path .= '.' . $ls_extension;
		}


		return mb_strtolower($ls_path);
	}
}
