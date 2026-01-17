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
 * @property bool $isAudio
 * @property bool $isImage
 * @property bool $isVideo
 * @property int|null $filemtime
 * @property int|null $previewFilemtime
 */
class Media extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
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
		'media_resized_images' => 'mediaResizedImages',
		'media_assignments' => 'mediaAssignments',
		'usage_count' => 'usageCount',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_virtual = [ // phpcs:ignore
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
		'filemtime',
		'previewFilemtime',
		'usageCount',
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
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = FactoryLocator::get('Table')->get('Media');

		$name = $this->cleanName;
		if (!$name) {
			return null;
		}

		// Find files with the same name but different extension or -xx pattern
		$results = $table->find()->where([
			'id !=' => $this->id,
			'OR' => [
				['name LIKE' => $name . '.%'],
				['name LIKE' => $name . '-__.%'],
			],
		])->all()->toArray();

		return $results ?: null;
	}


	/**
	 * @return void
	 */
	public function moveConvertedFiles(): void {
		$sourceFile = $this->originalAvifPathAbsolute;
		if ($sourceFile && is_file($sourceFile)) {
			$targetFile = $this->avifPathAbsolute;
			if ($targetFile) {
				if (!is_dir(dirname($targetFile))) {
					mkdir(dirname($targetFile), 0755, true);
				}

				rename($sourceFile, $targetFile);
			}
			else {
				unlink($sourceFile);
			}
		}

		$sourceFile = $this->originalWebpPathAbsolute;
		if ($sourceFile && is_file($sourceFile)) {
			$targetFile = $this->webpPathAbsolute;
			if ($targetFile) {
				if (!is_dir(dirname($targetFile))) {
					mkdir(dirname($targetFile), 0755, true);
				}

				rename($sourceFile, $targetFile);
			}
			else {
				unlink($sourceFile);
			}
		}

		$sourceFile = $this->originalPreviewPathAbsolute;
		if ($sourceFile && is_file($sourceFile)) {
			$targetFile = $this->previewPathAbsolute;
			if ($targetFile) {
				if (!is_dir(dirname($targetFile))) {
					mkdir(dirname($targetFile), 0755, true);
				}

				rename($sourceFile, $targetFile);
			}
			else {
				unlink($sourceFile);
			}
		}
	}


	/**
	 * @return void
	 */
	public function moveResizedFiles(): void {
		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $table */
		$table = FactoryLocator::get('Table')->get('MediaResizedImages');
		$query = $table->updateQuery();

		$fileName = $this->name;
		$fileName = substr($fileName, 0, strrpos($fileName, '.'));
		$directory = substr($this->path, 0, strrpos($this->path, DS)) . DS . '_resized' . DS;
		$directory .= $fileName;

		$originalName = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		$originalName = substr($originalName, 0, strrpos($originalName, '.'));

		$originalDirectory = substr($this->getOriginal('path'), 0, strrpos($this->getOriginal('path'), DS)) . DS . '_resized' . DS;
		$originalDirectory .= $originalName;

		/**
		 * UPDATE media_resized_images SET
		 * 	name = (CONCAT('newname', substr(name, <strlen(oldname) + 1>))),
		 * 	path = (CONCAT('newpath', substr(path, <strlen(oldpath) + 1>)))
		 * WHERE media_id = 1
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 * @noinspection SpellCheckingInspection
		 */
		$query->update('media_resized_images')->set('name', $query->expr($query->func()->concat([
			$fileName,
			$query->func()->substr([
				'name' => 'identifier',
				mb_strlen($originalName) + 1,
			], [
				null,
				'integer',
			]),
		])))->set('path', $query->expr($query->func()->concat([
			$directory,
			$query->func()->substr([
				'path' => 'identifier',
				mb_strlen($originalDirectory) + 1,
			], [
				null,
				'integer',
			]),
		])))->where(['media_id' => $this->id])->execute();

		if ($this->isImage()) {
			$baseName = $this->originalCleanName ?? $this->cleanName;
		}
		else {
			$baseName = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		}

		$globFileName = $baseName . '-\[*\].*';

		$path = $this->getOriginal('path');
		$path = substr($path, 0, strrpos($path, DS)) . DS . '_resized' . DS;

		$resizedFiles = glob(WWW_ROOT . $path . $globFileName);
		if (!is_array($resizedFiles) || empty($resizedFiles)) {
			return;
		}

		$targetPath = $this->path;
		$targetPath = WWW_ROOT . substr($targetPath, 0, strrpos($targetPath, DS)) . DS . '_resized' . DS;

		foreach ($resizedFiles as $filePath) {
			$targetFileName = $fileName . substr(basename($filePath), strlen($originalName));
			$targetFilePath = $targetPath . $targetFileName;

			if (!is_dir($targetPath)) {
				mkdir($targetPath, 0755, true);
			}

			rename($filePath, $targetFilePath);
		}
	}


	/**
	 * @return void
	 */
	public function deleteConvertedFiles(): void {
		$filePath = $this->previewPathAbsolute;
		if ($filePath && is_file($filePath)) {
			unlink($filePath);
		}

		$filePath = $this->originalPreviewPathAbsolute;
		if ($filePath && is_file($filePath)) {
			unlink($filePath);
		}

		$filePath = $this->avifPathAbsolute;
		if ($filePath && is_file($filePath)) {
			unlink($filePath);
		}

		$filePath = $this->originalAvifPathAbsolute;
		if ($filePath && is_file($filePath)) {
			unlink($filePath);
		}

		$filePath = $this->webpPathAbsolute;
		if ($filePath && is_file($filePath)) {
			unlink($filePath);
		}

		$filePath = $this->originalWebpPathAbsolute;
		if ($filePath && is_file($filePath)) {
			unlink($filePath);
		}
	}


	/**
	 * @return void
	 */
	public function deleteResizedFiles(): void {
		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $table */
		$table = FactoryLocator::get('Table')->get('MediaResizedImages');
		$table->deleteAll([
			'media_id' => $this->id,
		]);

		$baseName = $this->isImage() ? $this->cleanName : $this->name;

		$name = $baseName . '-\[*\].*';

		$path = $this->path;
		$path = substr($path, 0, strrpos($path, DS)) . DS . '_resized' . DS . $name;

		$resizedFiles = glob(WWW_ROOT . $path);
		if (is_array($resizedFiles) && !empty($resizedFiles)) {
			array_map('unlink', $resizedFiles);
		}
	}


	/**
	 * @return bool
	 */
	protected function _getIsAudio(): bool {
		return $this->isAudio();
	}


	/**
	 * @return bool
	 */
	protected function _getIsImage(): bool {
		return $this->isImage();
	}


	/**
	 * @return bool
	 */
	protected function _getIsVideo(): bool {
		return $this->isVideo();
	}


	/**
	 * @return string|null
	 */
	protected function _getCleanName(): ?string {
		if (!$this->name) {
			return null;
		}

		$dotPos = strrpos($this->name, '.');

		if (!$dotPos) {
			return $this->name;
		}


		return substr($this->name, 0, $dotPos);
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalCleanName(): ?string {
		if (!$this->hasOriginal('name')) {
			return null;
		}

		$dotPos = strrpos($this->getOriginal('name'), '.');

		if (!$dotPos) {
			return $this->getOriginal('name');
		}


		return substr($this->getOriginal('name'), 0, $dotPos);
	}


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
	 * @return string|null
	 */
	protected function _getOriginalExtension(): ?string {
		if (!$this->hasOriginal('name')) {
			return null;
		}

		$dotPos = strrpos($this->getOriginal('name'), '.');

		if (!$dotPos) {
			return null;
		}


		return substr($this->getOriginal('name'), $dotPos + 1);
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

		$previewPath = substr($this->path, 0, -strlen($this->name));
		$previewPath .= '_' . $this->extension . '_preview';


		return $previewPath . DS . $this->previewName;
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalPreviewPath(): ?string {
		if (!$this->hasOriginal('path') || $this->originalIsImage()) {
			return null;
		}

		$name = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		$previewPath = substr($this->getOriginal('path'), 0, -strlen($name));
		$previewPath .= '_' . ($this->originalExtension ?? $this->extension) . '_preview';


		return $previewPath . DS . ($this->originalPreviewName ?? $this->previewName);
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
	protected function _getAvifName(): ?string {
		if (!$this->name) {
			return null;
		}

		return $this->name . '.avif';
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalAvifName(): ?string {
		if (!$this->hasOriginal('name')) {
			return null;
		}

		return $this->getOriginal('name') . '.avif';
	}


	/**
	 * @return string|null
	 */
	protected function _getAvifPath(): ?string {
		if (!$this->path || $this->mimeType === 'image/avif') {
			return null;
		}

		$avifPath = substr($this->path, 0, -strlen($this->name));
		$avifPath .= '_avif';


		return $avifPath . DS . $this->avifName;
	}


	/**
	 * @return string|null
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

		$name = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		$avifPath = substr($this->getOriginal('path'), 0, -strlen($name));
		$avifPath .= '_avif';

		return $avifPath . DS . ($this->originalAvifName ?? $this->avifName);
	}


	/**
	 * @return string|null
	 */
	protected function _getAvifPathAbsolute(): ?string {
		$avifPath = $this->avifPath;

		if (!$avifPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $avifPath);
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalAvifPathAbsolute(): ?string {
		$originalAvifPath = $this->originalAvifPath;

		if (!$originalAvifPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $originalAvifPath);
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

		$webpPath = substr($this->path, 0, -strlen($this->name));
		$webpPath .= '_webp';


		return $webpPath . DS . $this->webpName;
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

		$name = $this->hasOriginal('name') ? $this->getOriginal('name') : $this->name;
		$webpPath = substr($this->getOriginal('path'), 0, -strlen($name));
		$webpPath .= '_webp';

		return $webpPath . DS . ($this->originalWebpName ?? $this->webpName);
	}


	/**
	 * @return string|null
	 */
	protected function _getWebpPathAbsolute(): ?string {
		$webpPath = $this->webpPath;

		if (!$webpPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $webpPath);
	}


	/**
	 * @return string|null
	 */
	protected function _getOriginalWebpPathAbsolute(): ?string {
		$originalWebpPath = $this->originalWebpPath;

		if (!$originalWebpPath) {
			return null;
		}

		return WWW_ROOT . str_replace('/', DS, $originalWebpPath);
	}


	/**
	 * @return int|null
	 * @noinspection PhpUnused
	 */
	protected function _getFilemtime(): ?int {
		$path = $this->pathAbsolute;

		if (!$path || !file_exists($path)) {
			return null;
		}

		return filemtime($path);
	}


	/**
	 * @return int|null
	 * @noinspection PhpUnused
	 */
	protected function _getPreviewFilemtime(): ?int {
		$path = $this->previewPathAbsolute;

		if (!$path || !file_exists($path)) {
			return null;
		}

		return filemtime($path);
	}


	/**
	 * Make sure the average does not start with a hash
	 *
	 * @param string|null $color
	 * @return string|null
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
	 */
	protected function _setName(?string $path): ?string {
		if ($path === null) {
			return null;
		}

		//Get rid of all chained file suffixes, like ".foo.bar" in filename.foo.bar.jpg
		$parts = explode('.', $path);
		$extension = count($parts) > 1 ? end($parts) : null;

		$path = Text::slug($parts[0]);

		if ($extension) {
			$path .= '.' . $extension;
		}


		return mb_strtolower($path);
	}
}
