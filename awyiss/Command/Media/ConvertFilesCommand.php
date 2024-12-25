<?php declare(strict_types=1);


namespace Awyiss\Command\Media;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandFactoryInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ResultSetInterface;
use Exception;
use Intervention\Image\ImageManager;
use Symfony\Component\Process\Process;


/**
 * Fetches records from the media and tries to generate a preview image
 */
class ConvertFilesCommand extends Command {
	protected ImageManager $imageManager;
	/**
	 * @var bool Whether to use the ImageMagick commands for image manipulation
	 */
	protected bool $useImageMagick = false;


	/**
	 * @inheritDoc
	 */
	public function __construct(?CommandFactoryInterface $factory = null) {
		parent::__construct($factory);

		$this->useImageMagick = Configure::read('AvailableCommands.imageMagick') !== false;

		if (!$this->useImageMagick) {
			$this->imageManager = ImageManager::gd(autoOrientation: false);
		}
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($parser);

		$lo_parser->addOption('include-webp', [
			'boolean' => true,
			'help' => 'Include the creation of webp files after converting non-images to jpgs.',
			'short' => 'w',
		]);

		$lo_parser->addOption('limit', [
			'default' => '20',
			'help' => 'The maximum amount of files to convert per run.',
			'short' => 'l',
		]);

		$lo_parser->addOption('retry-failed', [
			'boolean' => true,
			'help' => 'Retry generating files for records with the "fail" status.',
			'short' => 'r',
		]);

		return $lo_parser;
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$li_startTime = time();
		$lb_errorOccurred = false;

		// Keep this job running for 60 seconds to process as many files as possible
		while (time() - $li_startTime < 60) {
			$li_totalFiles = 0;

			$la_processMethods = [
				'processCropFiles',
				'processNonImageFiles',
				'processWebpConversion',
				'processResizing',
				'processAverageColorCalculation',
			];

			foreach ($la_processMethods as $ls_method) {
				$li_files = $this->$ls_method($args, $io);
				if ($li_files === false) {
					$lb_errorOccurred = true;
					break 2;
				}
				else {
					$li_totalFiles += $li_files;
				}
			}

			// If the script is not running in quiet mode, break the loop if no files were found
			// This is just an assumption that the script was called manually and should not run indefinitely
			if (!$args->getOption('quiet')) {
				break;
			}

			// If no files were found, sleep for 10 seconds before checking again
			if ($li_totalFiles === 0) {
				sleep(10);
			}
		}

		return $lb_errorOccurred ? static::CODE_ERROR : static::CODE_SUCCESS;
	}


	/**
	 * @param array $command
	 * @param mixed $args
	 * @return \Symfony\Component\Process\Process
	 */
	public function getProcess(array $command, mixed ...$args): Process {
		return new Process($command, ...$args);
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processCropFiles(Arguments $args, ConsoleIo $io): int|false {
		$lo_files = $this->fetchCropFiles((int)$args->getOption('limit'));

		if ($lo_files->count()) {
			$li_result = $this->cropImages($lo_files, $io);

			if ($li_result !== static::CODE_SUCCESS) {
				return false;
			}

			return $lo_files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processNonImageFiles(Arguments $args, ConsoleIo $io): int|false {
		$lo_files = $this->fetchNonImageFiles(
			(int)$args->getOption('limit'),
			$args->getOption('retry-failed'),
			$args->getOption('include-webp')
		);

		if ($lo_files->count()) {
			$li_result = $this->convertNonImages($lo_files, $io, $args->getOption('include-webp'));
			if ($li_result !== static::CODE_SUCCESS) {
				return false;
			}

			return $lo_files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processWebpConversion(Arguments $args, ConsoleIo $io): int|false {
		$lo_files = $this->fetchFilesForWebpConversion((int)$args->getOption('limit'), $args->getOption('retry-failed'));

		if ($lo_files->count()) {
			$li_result = $this->convertImagesToWebp($lo_files, $io);
			if ($li_result !== static::CODE_SUCCESS) {
				return false;
			}

			return $lo_files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processResizing(Arguments $args, ConsoleIo $io): int|false {
		$lo_files = $this->fetchFilesForResizing((int)$args->getOption('limit'), $args->getOption('retry-failed'));

		if ($lo_files->count()) {
			$li_result = $this->resizeImages($lo_files, $io);
			if ($li_result !== static::CODE_SUCCESS) {
				return false;
			}

			return $lo_files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processAverageColorCalculation(Arguments $args, ConsoleIo $io): int|false {
		$lo_files = $this->fetchFilesForAverageColorCalculation((int)$args->getOption('limit'));

		if ($lo_files->count()) {
			$li_result = $this->calculateAverageColors($lo_files, $io);
			if ($li_result !== static::CODE_SUCCESS) {
				return false;
			}

			return $lo_files->count();
		}

		return 0;
	}


	/**
	 * Calculate the average color of the images
	 *
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function calculateAverageColors(ResultSetInterface $files, ConsoleIo $io): int {
		$lo_files = $files;

		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($lo_files as $lo_file) {
			$ls_path = $lo_file->isImage() ? $lo_file->pathAbsolute : $lo_file->previewPathAbsolute;

			$io->out(sprintf('Calculating average color for file `%s`', $lo_file->path));

			if (!file_exists($ls_path)) {
				$io->error('Status: File does not exist');
				$io->hr();

				// If the file does not exist or is a png, set the average color to a fully transparent black
				$lo_file->averageColor = '00000000';
				continue;
			}

			if ($lo_file->mimeType === 'image/png') {
				$io->info('Status: Cannot calculate average color for png files');
				$io->hr();

				// If the file does not exist or is a png, set the average color to a fully transparent black
				$lo_file->averageColor = '00000000';
				continue;
			}

			$la_colors = $this->calculateAverageColor($ls_path);

			if (!$la_colors) {
				$io->error('Status: Cannot calculate average color for file');
				$io->hr();

				$lo_file->averageColor = '00000000';
				continue;
			}

			// If alpha is full transparent, set it to FF
			$la_colors['alpha'] = $la_colors['alpha'] === 0 ? 255 : $la_colors['alpha'];

			$lo_file->averageColor = sprintf('%02X%02X%02X%02X', $la_colors['red'], $la_colors['green'], $la_colors['blue'], $la_colors['alpha']);

			$io->success('Status: Average color calculated successfully (#' . $lo_file->averageColor . ')');
			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		// Update all files with the average color
		$lo_table->updateAll(function (QueryExpression $expression) use ($lo_files) {
			$lo_averageColorCases = $expression->case();

			/** @var \Awyiss\Model\Entity\Media $lo_file */
			foreach ($lo_files as $lo_file) {
				$lo_averageColorCases->when(['id = ' . $lo_file->id])->then($lo_file->averageColor, 'string');
			}

			return [
				'average_color' => $lo_averageColorCases,
			];
		}, [
			'id IN' => $files->extract('id')->toArray(),
		]);

		return static::CODE_SUCCESS;
	}


	/**
	 * @param string $path
	 * @return array|false
	 */
	protected function calculateAverageColor(string $path): array|false {
		// Prefer the GD library for calculating the average color as it should be faster
		if (function_exists('imagecreatefromstring')) {
			return $this->calculateAverageColorGD($path);
		}

		// If the ImageMagick command line tool is not available, return false
		if (!$this->useImageMagick) {
			return false;
		}

		return $this->calculateAverageColorIM($path);
	}


	/**
	 * Calculate the average color of an image using the ImageMagick command line tool
	 *
	 * @param string $path
	 * @return array|false
	 */
	protected function calculateAverageColorIM(string $path): array|false {
		$lo_process = $this->getProcess([
			'convert',
			$path,
			'-resize',
			'1x1!',
			'-format',
			'%[fx:int(255*r+.5)],%[fx:int(255*g+.5)],%[fx:int(255*b+.5)]',
			'info:-',
		]);

		$lo_process->run();

		if (!$lo_process->isSuccessful()) {
			return false;
		}

		$la_colors = explode(',', $lo_process->getOutput());

		return [
			'red' => (int)$la_colors[0],
			'green' => (int)$la_colors[1],
			'blue' => (int)$la_colors[2],
			'alpha' => 255,
		];
	}


	/**
	 * Calculate the average color of an image using the GD library
	 *
	 * @param string $path
	 * @return array|false
	 */
	protected function calculateAverageColorGD(string $path): array|false {
		$lo_image = imagecreatefromstring(file_get_contents($path));

		if (!$lo_image) {
			return false;
		}

		// Resize the imag to 1x1 pixel
		$lo_pixel = imagecreatetruecolor(1, 1);

		imagecopyresampled($lo_pixel, $lo_image, 0, 0, 0, 0, 1, 1, imagesx($lo_image), imagesy($lo_image));
		$li_index = imagecolorat($lo_pixel, 0, 0);
		$la_colors = imagecolorsforindex($lo_pixel, $li_index);

		// Free up memory
		imagedestroy($lo_image);
		imagedestroy($lo_pixel);
		$lo_image = null; //phpcs:ignore
		$lo_pixel = null; //phpcs:ignore

		return $la_colors;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function convertImagesToWebp(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
			$io->out(sprintf('Creating webp image for file `%s`', $lo_file->path));

			$this->convertImageToWebp($lo_file, $io);

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$la_webpStatus = array_unique($files->extract('webp')->toArray(), SORT_REGULAR);
		if (count($la_webpStatus) === 1) {
			/**
			 * If all files have the same webp status, use a simple updateAll command
			 */
			$lo_table->updateAll([
				'webp' => $la_webpStatus[0],
			], [
				'id IN' => $files->extract('id')->toArray(),
			]);
		}
		else {
			$lo_files = $files;
			$lo_table->updateAll(function (QueryExpression $expression) use ($lo_files) {
				$lo_webpCases = $expression->case();

				/** @var \Awyiss\Model\Entity\Media $lo_file */
				foreach ($lo_files as $lo_file) {
					$lo_webpCases->when(['id = ' . $lo_file->id])->then($lo_file->webp->value, 'integer');
				}

				return [
					'webp' => $lo_webpCases,
				];
			}, [
				'id IN' => $files->extract('id')->toArray(),
			]);
		}


		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToWebp(Media $file, ConsoleIo $io): bool {
		if (!$file->webpPathAbsolute) {
			$file->webp = ProcessStatus::Fail;

			return false;
		}

		if (!file_exists(dirname($file->webpPathAbsolute))) {
			$io->out(sprintf('Creating directory (%s) for webp file', dirname($file->webpPath)));

			if (!mkdir(dirname($file->webpPathAbsolute))) {
				$io->error('Status: Cannot create directory for webp file');

				$file->webp = ProcessStatus::Fail;

				return false;
			}

			$io->success('Status: Directory created');
		}

		if (!$this->useImageMagick) {
			return $this->convertImageToWebpGD($file, $io);
		}

		return $this->convertImageToWebpIM($file, $io);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToWebpGD(Media $file, ConsoleIo $io): bool {
		if (!function_exists('imagewebp')) {
			$io->error('Status: Cannot create webp file without imagewebp function');
			$file->webp = ProcessStatus::Fail;

			return false;
		}

		$ls_inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		$lo_image = $this->imageManager->read($ls_inputPath);

		try {
			$lo_image->toWebp(90)->save($file->webpPathAbsolute);
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());

			$file->webp = ProcessStatus::Fail;

			return false;
		}

		$io->success('Status: Webp file created');
		$file->webp = ProcessStatus::Success;

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToWebpIM(Media $file, ConsoleIo $io): bool {
		$lo_process = $this->getProcess($this->getWebPCommand($file));
		$lo_process->run();

		if (!$lo_process->isSuccessful()) {
			$io->error('Status: ' . $lo_process->getExitCodeText());
			$io->out('Command: ' . $lo_process->getCommandLine());
			$io->out('Message: ' . $lo_process->getErrorOutput(), 0);

			$file->webp = ProcessStatus::Fail;

			return false;
		}

		$io->success('Status: ' . $lo_process->getExitCodeText());
		$file->webp = ProcessStatus::Success;

		return true;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeWebp
	 * @return int
	 */
	protected function convertNonImages(ResultSetInterface $files, ConsoleIo $io, bool $includeWebp): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
			$io->out(sprintf('Creating preview for file `%s`', $lo_file->path));

			$this->convertNonImage($lo_file, $io, $includeWebp);

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$la_previewStatus = array_unique($files->extract('preview')->toArray(), SORT_REGULAR);
		/**
		 * If all files have the same preview status "failed", use a simple updateAll command
		 * This also means no webp was created, even though it was requested.
		 * Set the webp status to undefined in this case.
		 */
		if (count($la_previewStatus) === 1 && $la_previewStatus[0] === ProcessStatus::Fail) {
			$lo_table->updateAll([
				'preview' => $la_previewStatus[0],
				'webp' => ProcessStatus::Undefined,
			], [
				'id IN' => $files->extract('id')->toArray(),
			]);
		}
		else {
			$lo_files = $files;
			$lb_includeWebp = $includeWebp;

			$lo_table->updateAll(function (QueryExpression $expression) use ($lo_files, $lb_includeWebp) {
				$lo_widthCases = $expression->case();
				$lo_heightCases = $expression->case();
				$lo_previewCases = $expression->case();

				if ($lb_includeWebp) {
					$lo_webpCases = $expression->case();
				}

				/** @var \Awyiss\Model\Entity\Media $lo_file */
				foreach ($lo_files as $lo_file) {
					$lo_widthCases->when(['id = ' . $lo_file->id])->then($lo_file->width, 'float');
					$lo_heightCases->when(['id = ' . $lo_file->id])->then($lo_file->height, 'float');
					$lo_previewCases->when(['id = ' . $lo_file->id])->then($lo_file->preview->value, 'integer');

					if ($lb_includeWebp) {
						$lo_webpCases->when(['id = ' . $lo_file->id])->then($lo_file->webp->value, 'integer');
					}
				}

				$la_cases = [
					'width' => $lo_widthCases,
					'height' => $lo_heightCases,
					'preview' => $lo_previewCases,
				];

				if ($lb_includeWebp) {
					$la_cases['webp'] = $lo_webpCases;
				}

				return $la_cases;
			}, [
				'id IN' => $files->extract('id')->toArray(),
			]);
		}

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeWebp
	 * @return bool
	 */
	protected function convertNonImage(Media $file, ConsoleIo $io, bool $includeWebp): bool {
		if (!$file->previewPathAbsolute) {
			$io->error('Status: Cannot convert file without a path');
			$io->hr();

			return false;
		}

		if (!file_exists(dirname($file->previewPathAbsolute))) {
			$io->out(sprintf('Creating directory (%s) for file preview', dirname($file->previewPath)));

			if (!mkdir(dirname($file->previewPathAbsolute))) {
				$io->error('Status: Cannot create directory for file preview');

				return false;
			}

			$io->success('Status: Directory created');
		}

		if (!$this->useImageMagick) {
			return $this->convertNonImageGD($file, $io, $includeWebp);
		}

		return $this->convertNonImageIM($file, $io, $includeWebp);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeWebp
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function convertNonImageGD(Media $file, ConsoleIo $io, bool $includeWebp): bool {
		// For now, converting non-image files is not supported by GD
		$io->warning(sprintf('Status: Cannot convert filetype `%s`', $file->extension));
		$io->hr();

		$file->preview = ProcessStatus::Fail;
		$file->webp = ProcessStatus::Fail;

		return false;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeWebp
	 * @return bool
	 */
	protected function convertNonImageIM(Media $file, ConsoleIo $io, bool $includeWebp): bool {
		$la_command = $this->getPreviewCommand($file);

		if (!$la_command) {
			$io->warning(sprintf('Status: Cannot convert filetype `%s`', $file->extension));
			$io->hr();

			$file->preview = ProcessStatus::Fail;
			$file->webp = ProcessStatus::Fail;

			return false;
		}

		$lo_process = $this->getProcess($la_command);
		$lo_process->run();

		if (!$lo_process->isSuccessful()) {
			$io->error('Status: ' . $lo_process->getExitCodeText());
			$io->out('Command: ' . $lo_process->getCommandLine());
			$io->out('Message: ' . $lo_process->getErrorOutput(), 0);

			$file->preview = ProcessStatus::Fail;
			$file->webp = ProcessStatus::Undefined;

			return false;
		}

		$io->success('Status: ' . $lo_process->getExitCodeText());

		$la_imageSize = $this->getRealImageSize($file->previewPathAbsolute);

		$file->width = $la_imageSize[0] ?? null;
		$file->height = $la_imageSize[1] ?? null;
		$file->preview = ProcessStatus::Success;

		if ($includeWebp) {
			$io->out(sprintf('Creating WebP file for file `%s`', $file->path));

			$this->convertImageToWebp($file, $io);
		}

		return true;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 */
	protected function cropImages(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
			$io->out(sprintf('Cropping file `%s`', $lo_file->path));

			$this->cropImage($lo_file, $io);

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$lo_files = $files;
		$lo_table->updateAll(function (QueryExpression $expression) use ($lo_files) {
			$lo_widthCases = $expression->case();
			$lo_heightCases = $expression->case();
			$lo_cropCases = $expression->case();

			/** @var \Awyiss\Model\Entity\Media $lo_file */
			foreach ($lo_files as $lo_file) {
				$lo_widthCases->when(['id = ' . $lo_file->id])->then($lo_file->width, 'float');
				$lo_heightCases->when(['id = ' . $lo_file->id])->then($lo_file->height, 'float');
				$lo_cropCases->when(['id = ' . $lo_file->id])->then($lo_file->crop, 'integer');
			}

			return [
				'width' => $lo_widthCases,
				'height' => $lo_heightCases,
				'crop' => $lo_cropCases,
			];
		}, [
			'id IN' => $files->extract('id')->toArray(),
		]);

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function cropImage(Media $file, ConsoleIo $io): bool {
		if (!$this->useImageMagick) {
			$lb_cropped = $this->cropImageGD($file, $io);
		}
		else {
			$lb_cropped = $this->cropImageIM($file, $io);
		}

		if (!$lb_cropped) {
			return false;
		}

		if (!empty($file->crop['resize_width']) || !empty($file->crop['resize_height'])) {
			// Delete all resized files. They will be recreated when needed.
			// Previously set sizes might no longer be required OR even too large
			$file->deleteResizedFiles();
		}

		$la_imageSize = $this->getRealImageSize($file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute);

		$file->width = $la_imageSize[0] ?? null;
		$file->height = $la_imageSize[1] ?? null;
		$file->crop = null;

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function cropImageGD(Media $file, ConsoleIo $io): bool {
		$ls_inputPath = $file->pathAbsolute;
		$lb_crop = false;
		$lb_resize = false;

		if (!$file->isImage()) {
			$ls_inputPath = $file->previewPathAbsolute;
		}

		if (isset($file->crop['rotate']) && $file->crop['rotate'] === 'auto') {
			if (!$this->autoRotateImageGD($ls_inputPath)) {
				return false;
			}

			// If a webp file exists, rotate it as well
			if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
				try {
					$this->autoRotateImageGD($file->webpPathAbsolute);
				}
				catch (Exception) {
					// Ignore the exception for webp auto rotatation
				}
			}

			return true;
		}

		$lo_image = $this->imageManager->read($ls_inputPath);

		if ($file->width !== (float)$file->crop['width'] || $file->height !== (float)$file->crop['height']) {
			$lb_crop = true;

			$lo_image->crop((int)$file->crop['width'], (int)$file->crop['height'], (int)$file->crop['x'], (int)$file->crop['y']);
		}

		if ((float)$file->crop['width'] !== (float)$file->crop['resize_width'] || (float)$file->crop['height'] !== (float)$file->crop['resize_height']) {
			$lb_resize = true;

			$lo_image->scaleDown((int)$file->crop['resize_width'], (int)$file->crop['resize_height']);
		}

		if (!$lb_crop && !$lb_resize) {
			$lo_image = null; //phpcs:ignore
			return true;
		}

		try {
			$lo_image->save($ls_inputPath, quality: 90, progressive: true);
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());

			return false;
		}

		// If a webp file exists, crop it as well
		if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
			$lo_image = $this->imageManager->read($file->webpPathAbsolute);

			if ($lb_crop) {
				$lo_image->crop((int)$file->crop['width'], (int)$file->crop['height'], (int)$file->crop['x'], (int)$file->crop['y']);
			}

			if ($lb_resize) {
				$lo_image->scaleDown((int)$file->crop['resize_width'], (int)$file->crop['resize_height']);
			}

			try {
				$lo_image->save($ls_inputPath, quality: 90, progressive: true);
			}
			catch (Exception) {
				// Ignore the exception for webp cropping
			}
		}

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function cropImageIM(Media $file, ConsoleIo $io): bool {
		$la_commands = $this->getCropCommand($file);

		$lo_process = $this->getProcess($la_commands['original']);
		$lo_process->run();

		if (!$lo_process->isSuccessful()) {
			$io->error('Status: ' . $lo_process->getExitCodeText());
			$io->out('Command: ' . $lo_process->getCommandLine());
			$io->out('Message: ' . $lo_process->getErrorOutput(), 0);

			return false;
		}

		$io->success('Status: ' . $lo_process->getExitCodeText());

		// If there's a webp command, run it and crop the webp file as well
		if ($la_commands['webp']) {
			$lo_process = $this->getProcess($la_commands['webp']);
			$lo_process->run();
		}

		return true;
	}


	/**
	 * This method resizes the images in the ResultSet and updates the status of the files
	 * Each file is processed individually and resized according to the strategy set in the database
	 *
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function resizeImages(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\MediaResizedImage $lo_file */
		foreach ($files as $lo_file) {
			$io->out(sprintf('Resizing file `%s` to `%s', $lo_file->media->path, $lo_file->path));

			$this->resizeImage($lo_file, $io);

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $lo_table */
		$lo_table = $this->fetchTable('MediaResizedImages');

		$la_status = array_unique($files->extract('status')->toArray(), SORT_REGULAR);
		/**
		 * If all files have the same status "failed", use a simple updateAll command
		 */
		if (count($la_status) === 1 && $la_status[0] === ProcessStatus::Fail) {
			$lo_table->updateAll([
				'status' => ProcessStatus::Fail,
			], [
				'id IN' => $files->extract('id')->toArray(),
			]);
		}
		else {
			$lo_files = $files;
			$lo_table->updateAll(function (QueryExpression $expression) use ($lo_files) {
				$lo_realWidthCases = $expression->case();
				$lo_realHeightCases = $expression->case();
				$lo_statusCases = $expression->case();

				/** @var \Awyiss\Model\Entity\MediaResizedImage $lo_file */
				foreach ($lo_files as $lo_file) {
					$lo_realWidthCases->when(['id = ' . $lo_file->id])->then($lo_file->realWidth, 'integer');
					$lo_realHeightCases->when(['id = ' . $lo_file->id])->then($lo_file->realHeight, 'integer');
					$lo_statusCases->when(['id = ' . $lo_file->id])->then($lo_file->status->value, 'integer');
				}

				return [
					'real_width' => $lo_realWidthCases,
					'real_height' => $lo_realHeightCases,
					'status' => $lo_statusCases,
				];
			}, [
				'id IN' => $files->extract('id')->toArray(),
			]);
		}

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function resizeImage(MediaResizedImage $file, ConsoleIo $io): bool {
		if (!$file->media->isImage() && $file->media->preview === ProcessStatus::Fail) {
			$io->error('Status: Cannot resize non-image file without a preview');
			$io->hr();

			$file->status = ProcessStatus::Fail;

			return false;
		}

		if (!file_exists(dirname($file->pathAbsolute))) {
			$io->out(sprintf('Creating directory (%s) for resized file', dirname($file->path)));

			if (!mkdir(dirname($file->pathAbsolute))) {
				$io->error('Status: Cannot create directory for resized file');

				return false;
			}

			$io->success('Status: Directory created');
		}

		if (!$this->useImageMagick) {
			$lb_resized = $this->resizeImageGD($file, $io);
		}
		else {
			$lb_resized = $this->resizeImageIM($file, $io);
		}

		if (!$lb_resized) {
			$file->status = ProcessStatus::Fail;

			return false;
		}

		$la_imageSize = $this->getRealImageSize($file->pathAbsolute);

		$file->realWidth = $la_imageSize[0] ?? null;
		$file->realHeight = $la_imageSize[1] ?? null;
		$file->status = ProcessStatus::Success;

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function resizeImageGD(MediaResizedImage $file, ConsoleIo $io): bool {
		$lo_image = $this->imageManager->read($file->media->isImage() ? $file->media->pathAbsolute : $file->media->previewPathAbsolute);

		if ($file->strategy === ResizeStrategy::Contain) {
			$lo_image->scaleDown($file->width, $file->height);
		}
		elseif ($file->strategy === ResizeStrategy::Cover) {
			// Calculate aspect ratios
			$lf_originalRatio = $file->media->width / $file->media->height;
			$lf_targetRatio = $lf_originalRatio;
			if ($file->width && $file->height) {
				$lf_targetRatio = $file->width / $file->height;
			}

			// Resize logic mimicking ^
			if ($lf_originalRatio > $lf_targetRatio) {
				// Image is wider - scale by height
				$lo_image->scaleDown(null, $file->height);
			}
			else {
				// Image is taller - scale by width
				$lo_image->scaleDown($file->width);
			}
		}
		elseif ($file->strategy === ResizeStrategy::Crop) {
			$ls_position = 'center';

			if ($file->media->focusPoint) {
				// Focus point is in the format "[0|1|2],[0|1|2]"
				$la_focusPoint = explode(',', $file->media->focusPoint);

				if (count($la_focusPoint) !== 2) {
					$la_focusPoint = [1, 1];
				}

				// Convert the focus point to a position value
				// Possible values should be "top-left", "top", "top-right", "left", "center", "right", "bottom-left", "bottom", "bottom-right"
				$la_positionValues = [
					'top-left',
					'top',
					'top-right',
					'left',
					'center',
					'right',
					'bottom-left',
					'bottom',
					'bottom-right',
				];

				$ls_position = $la_positionValues[ (int)$la_focusPoint[0] * 3 + (int)$la_focusPoint[1] ];
			}

			$lo_image->coverDown($file->width, $file->height, $ls_position);
		}
		elseif ($file->strategy === ResizeStrategy::Stretch) {
			$lo_image->resizeDown($file->width, $file->height);
		}
		else {
			$io->error('Status: Unsupported resize strategy');
			return false;
		}

		try {
			$lo_image->save($file->pathAbsolute, quality: 90, progressive: true);
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());

			$file->status = ProcessStatus::Fail;

			return false;
		}

		$io->success('Status: Resized image successfully');

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function resizeImageIM(MediaResizedImage $file, ConsoleIo $io): bool {
		$lo_process = $this->getProcess($this->getResizeCommand($file));
		$lo_process->run();

		if (!$lo_process->isSuccessful()) {
			$io->error('Status: ' . $lo_process->getExitCodeText());
			$io->out('Command: ' . $lo_process->getCommandLine());
			$io->out('Message: ' . $lo_process->getErrorOutput(), 0);

			return false;
		}

		$io->success('Status: ' . $lo_process->getExitCodeText());

		return true;
	}


	/**
	 * @param int $limit
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchCropFiles(int $limit): ResultSetInterface {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		return $lo_table->find()->where([
			'crop IS NOT' => null,
			'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
		])->limit($limit)->all();
	}


	/**
	 * @param int $limit
	 * @param bool $retryFailed
	 * @param bool $includeWebp
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchNonImageFiles(int $limit, bool $retryFailed, bool $includeWebp): ResultSetInterface {
		$la_where = [
			'preview' => ProcessStatus::Undefined,
		];

		if ($retryFailed) {
			$la_where['preview'] = ProcessStatus::Fail;
		}

		$la_processStatusColumns = [
			'preview',
		];

		if ($includeWebp) {
			$la_processStatusColumns[] = 'webp';
		}


		return $this->fetchFiles($la_where, $limit, $la_processStatusColumns);
	}


	/**
	 * Get files that have no average color set yet
	 *
	 * @param int $limit
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForAverageColorCalculation(int $limit): ResultSetInterface {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		return $lo_table->find()->where([
			'average_color IS' => null,
			'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
		])->limit($limit)->all();
	}


	/**
	 * Fetch files that need to be resized
	 *
	 * @param int $limit
	 * @param bool $retryFailed
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForResizing(int $limit, bool $retryFailed): ResultSetInterface {
		$la_where = [
			'status' => ProcessStatus::Undefined,
		];

		if ($retryFailed) {
			$la_where['status'] = ProcessStatus::Fail;
		}

		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $lo_table */
		$lo_table = $this->fetchTable('MediaResizedImages');
		$lo_records = $lo_table->find()->where($la_where)->contain(['Media'])->limit($limit)->all();

		if ($lo_records->count()) {
			$lo_table->updateAll([
				'status' => ProcessStatus::InProgress,
			], [
				'id IN' => $lo_records->extract('id')->toArray(),
			]);
		}

		return $lo_records;
	}


	/**
	 * @param int $limit
	 * @param bool $retryFailed
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForWebpConversion(int $limit, bool $retryFailed): ResultSetInterface {
		$la_where = [
			'webp' => ProcessStatus::Undefined,
			'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
		];

		if ($retryFailed) {
			$la_where['webp'] = ProcessStatus::Fail;
		}

		return $this->fetchFiles($la_where, $limit, ['webp']);
	}


	/**
	 * @param array $where
	 * @param int $limit
	 * @param array $processStatusColumns
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFiles(array $where, int $limit, array $processStatusColumns): ResultSetInterface {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$lo_records = $lo_table->find()->where($where)->limit($limit)->all();

		if ($lo_records->count()) {
			$la_processStatusColumns = [];
			foreach ($processStatusColumns as $ls_column) {
				$la_processStatusColumns[ $ls_column ] = ProcessStatus::InProgress;
			}

			$lo_table->updateAll($la_processStatusColumns, [
				'id IN' => $lo_records->extract('id')->toArray(),
			]);
		}

		return $lo_records;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @return array|false
	 */
	protected function getPreviewCommand(Media $file): array|false {
		if (in_array($file->mimeType, ['video/mp4', 'video/x-msvideo'])) {
			if (!Configure::read('AvailableCommands.ffmpeg')) {
				return false;
			}

			$la_command = [
				'ffmpeg',
				'-y',
				'-i',
				WWW_ROOT . str_replace('/', DS, $file->path),
				'-vf',
				'thumbnail',
				'-frames:v',
				'1',
				$file->previewPathAbsolute,
			];
		}
		else {
			if (!Configure::read('AvailableCommands.imageMagick.' . $file->extension)) {
				return false;
			}

			$ls_inputPath = $file->path;
			if (in_array($file->extension, ['pdf', 'psd'])) {
				$ls_inputPath .= '[0]';
			}

			$la_command = [
				'convert',
				'-density',
				300,
				'-quality',
				90,
				'-colorspace',
				'sRGB',
				'-alpha',
				'remove',
				WWW_ROOT . str_replace('/', DS, $ls_inputPath),
				$file->previewPathAbsolute,
			];
		}

		return $la_command;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getWebPCommand(Media $file): array {
		$ls_inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		return [
			'convert',
			$ls_inputPath,
			$file->webpPathAbsolute,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getCropCommand(Media $file): array {
		$ls_inputPath = $file->pathAbsolute;
		$lb_crop = false;
		$lb_resize = false;

		if (!$file->isImage()) {
			$ls_inputPath = $file->previewPathAbsolute;
		}

		if (isset($file->crop['rotate']) && $file->crop['rotate'] === 'auto') {
			$la_commandWebp = null;
			if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
				$la_commandWebp = [
					'mogrify',
					'-auto-orient',
					$file->webpPathAbsolute,
				];
			}

			return [
				'original' => [
					'mogrify',
					'-auto-orient',
					$ls_inputPath,
				],
				'webp' => $la_commandWebp,
			];
		}

		$la_commandOriginal = [
			'convert',
			$ls_inputPath,
		];

		if ($file->width !== (float)$file->crop['width'] || $file->height !== (float)$file->crop['height']) {
			$lb_crop = true;
			$la_commandOriginal = array_merge($la_commandOriginal, [
				'-crop',
				sprintf('%dx%d+%d+%d', (int)$file->crop['width'], (int)$file->crop['height'], (int)$file->crop['x'], (int)$file->crop['y']),
			]);
		}

		if ((float)$file->crop['width'] !== (float)$file->crop['resize_width'] || (float)$file->crop['height'] !== (float)$file->crop['resize_height']) {
			$lb_resize = true;
			$la_commandOriginal = array_merge($la_commandOriginal, [
				'-resize',
				sprintf('%dx%d', (int)$file->crop['resize_width'], (int)$file->crop['resize_height']),
			]);
		}

		$la_commandOriginal[] = $ls_inputPath;

		$la_commandWebp = null;
		if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
			$la_commandWebp = [
				'convert',
				$file->webpPathAbsolute,
			];

			if ($lb_crop) {
				$la_commandWebp = array_merge($la_commandWebp, [
					'-crop',
					sprintf('%dx%d+%d+%d', $file->crop['width'], $file->crop['height'], $file->crop['x'], $file->crop['y']),
				]);
			}

			if ($lb_resize) {
				$la_commandWebp = array_merge($la_commandWebp, [
					'-resize',
					sprintf('%dx%d', $file->crop['resize_width'], $file->crop['resize_height']),
				]);
			}

			$la_commandWebp[] = $file->webpPathAbsolute;
		}

		if (!$lb_crop && !$lb_resize) {
			$la_commandOriginal = $la_commandWebp = null;
		}

		return [
			'original' => $la_commandOriginal,
			'webp' => $la_commandWebp,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage $file
	 * @return array
	 */
	protected function getResizeCommand(MediaResizedImage $file): array {
		$ls_inputPath = $file->media->isImage() ? $file->media->pathAbsolute : $file->media->previewPathAbsolute;

		$ls_gravity = 'center';
		if ($file->media->focusPoint) {
			// Focus point is in the format "[0|1|2],[0|1|2]"
			$la_focusPoint = explode(',', $file->media->focusPoint);

			if (count($la_focusPoint) !== 2) {
				$la_focusPoint = [1, 1];
			}

			// Convert the focus point to a gravity value
			// Possible values should be "NorthWest", "North", "NorthEast", "West", "Center", "East", "SouthWest", "South", "SouthEast"
			$la_gravityValues = [
				'NorthWest',
				'North',
				'NorthEast',
				'West',
				'Center',
				'East',
				'SouthWest',
				'South',
				'SouthEast',
			];

			$ls_gravity = $la_gravityValues[ (int)$la_focusPoint[0] * 3 + (int)$la_focusPoint[1]];
		}

		return match ($file->strategy) {
			ResizeStrategy::Contain => [
				'convert',
				$ls_inputPath,
				'-resize',
				$file->width . 'x' . $file->height,
				$file->pathAbsolute,
			],
			ResizeStrategy::Cover => [
				'convert',
				$ls_inputPath,
				'-resize',
				$file->width . 'x' . $file->height . '^',
				$file->pathAbsolute,
			],
			ResizeStrategy::Crop => [
				'convert',
				$ls_inputPath,
				'-resize',
				$file->width . 'x' . $file->height . '^',
				'-gravity',
				$ls_gravity,
				'-extent',
				$file->width . 'x' . $file->height,
				$file->pathAbsolute,
			],
			ResizeStrategy::Stretch => [
				'convert',
				$ls_inputPath,
				'-resize',
				$file->width . 'x' . $file->height . '!',
				$file->pathAbsolute,
			],
		};
	}


	/**
	 * Return the real size of an image
	 * Uses getimagesize if available, otherwise falls back to the identify command
	 *
	 * @param string $imagePath
	 * @return array
	 */
	protected function getRealImageSize(string $imagePath): array {
		if (function_exists('getimagesize')) {
			return getimagesize($imagePath);
		}

		/**
		 * If the ImageMagick command is not available, we cannot get the image size.
		 * Return an empty array in this case.
		 */
		if (!$this->useImageMagick) {
			return [];
		}

		$lo_process = $this->getProcess([
			'identify',
			'-format',
			'%wx%h',
			$imagePath,
		]);

		$lo_process->run();

		return explode('x', $lo_process->getOutput());
	}


	/**
	 * @param string $inputPath
	 * @return bool
	 */
	protected function autoRotateImageGD(string $inputPath): bool {
		$lo_image = $this->imageManager->read($inputPath);

		$lo_image = $lo_image->orient();

		try {
			$lo_image->save($inputPath, quality: 90, progressive: true);
		}
		catch (Exception) {
			return false;
		}

		return true;
	}
}
