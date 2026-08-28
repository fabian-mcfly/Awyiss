<?php declare(strict_types=1);


namespace Awyiss\Command\Media;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
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
use Cake\Log\Log;
use Exception;
use Imagick;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Symfony\Component\Process\Process;


/**
 * Fetches records from the media and tries to generate a preview image
 */
class ConvertFilesCommand extends Command {
	/**
	 * Whether to use the ImageMagick commands
	 * for image manipulation.
	 *
	 * @var bool
	 */
	protected bool $cliMagickExists = false;
	/**
	 * @var string
	 */
	protected string $driver;
	/**
	 * An instance of the Intervention ImageManager,
	 * using the configured driver (gd or imagick)
	 *
	 * @var \Intervention\Image\ImageManager
	 */
	protected ImageManager $imageManager;
	/**
	 * The quality of the generated images.
	 * For Avif files, the quality can be lower while
	 * getting a similar or even better result.
	 *
	 * @var int
	 */
	protected int $quality;
	/**
	 * Whether to output debug information to the log.
	 *
	 * @var bool
	 */
	protected bool $verbose = false;


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function __construct(?CommandFactoryInterface $factory = null) {
		parent::__construct($factory);

		$this->cliMagickExists = Configure::read('AvailableCommands.imageMagick', false) !== false;

		try {
			Awyiss::loadConfiguration(
				LocaleMiddleware::getLanguage()->shortcode,
				LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND)->shortcode,
			);
		}
		catch (Exception) {
			// Ignore exception.
		}

		$this->driver = Configure::read('Awyiss.Media.Frontend.resizing.driver', 'imagick');
		if ($this->driver === 'imagick' && !extension_loaded('Imagick')) {
			// Try to fall back to GD if Imagick is not available
			$this->driver = 'gd';
		}

		if ($this->driver === 'gd' && !extension_loaded('gd')) {
			throw new Exception('The GD extension is not loaded. Please install the GD extension to use this command.');
		}

		$this->createImageManager();

		$this->quality = Configure::read('Awyiss.Media.Frontend.resizing.quality', 70);
	}


	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	protected function debug(string $message, array $context = []): void {
		if (!$this->verbose) {
			return;
		}

		$logMessage = '[ConvertFilesCommand] ' . $message;

		if ($context) {
			$jsonContext = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			$logMessage .= ' | ' . ($jsonContext === false ? '[context-encode-failed]' : $jsonContext);
		}

		Log::debug($logMessage);
	}


	/**
	 * Creates an instance of the Intervention ImageManager
	 *
	 * @return void
	 * @throws \Intervention\Image\Exceptions\InvalidArgumentException
	 */
	protected function createImageManager(): void {
		$driver = $this->driver === 'gd' ? GdDriver::class : ImagickDriver::class;
		$this->imageManager = ImageManager::usingDriver($driver, autoOrientation: false);
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('include-avif', [
			'boolean' => true,
			'help' => 'Include the creation of Avif files after converting non-images to jpgs.',
			'short' => 'a',
		]);

		$parser->addOption('include-webp', [
			'boolean' => true,
			'help' => 'Include the creation of WebP files after converting non-images to jpgs.',
			'short' => 'w',
		]);

		$parser->addOption('limit', [
			// If ImageMagick is available via command line, set the default to 20
			// otherwise set it to 5 to avoid potential memory issues
			'default' => $this->cliMagickExists ? '20' : '5',
			'help' => 'The maximum amount of files to convert per run.',
			'short' => 'l',
		]);

		$parser->addOption('retry-failed', [
			'boolean' => true,
			'help' => 'Retry generating files for records with the "fail" status.',
			'short' => 'r',
		]);

		return $parser;
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$this->verbose = (bool)$args->getOption('verbose');

		$startTime = time();
		$errorOccurred = false;
		$this->debug('Execute started', [
			'includeAvif' => (bool)$args->getOption('include-avif'),
			'includeWebp' => (bool)$args->getOption('include-webp'),
			'limit' => (int)$args->getOption('limit'),
			'retryFailed' => (bool)$args->getOption('retry-failed'),
			'quiet' => (bool)$args->getOption('quiet'),
		]);

		$io->out(
			sprintf(
				'Starting media processing using %s with %u%% quality...',
				$this->cliMagickExists ? 'ImageMagick (CLI)' : 'Intervention Image (' . $this->driver . ')',
				$this->quality
			)
		);

		/**
		 * @see self::processCropFiles()
		 * @see self::processNonImageFiles()
		 */
		$processMethods = [
			'processCropFiles',
			'processNonImageFiles',
		];

		if ($args->getOption('include-avif')) {
			/** @see self::processAvifConversion() */
			$processMethods[] = 'processAvifConversion';
		}

		if ($args->getOption('include-webp')) {
			/** @see self::processWebpConversion() */
			$processMethods[] = 'processWebpConversion';
		}

		/** @see self::processResizing() */
		$processMethods[] = 'processResizing';
		/** @see self::processAverageColorCalculation() */
		$processMethods[] = 'processAverageColorCalculation';

		// Keep this job running for 60 seconds to process as many files as possible
		while (time() - $startTime < 60) {
			$totalFiles = 0;

			foreach ($processMethods as $method) {
				$files = $this->$method($args, $io);

				if ($files === false) {
					$errorOccurred = true;
					$this->debug('Execution aborted after failed process method', ['method' => $method]);
					break 2;
				}
				else {
					$totalFiles += $files;
				}
			}

			$this->debug('Execution loop finished', ['totalFiles' => $totalFiles]);

			// If the script is not running in quiet mode, break the loop if no files were found
			// This is just an assumption that the script was called manually and should not run indefinitely
			if (!$args->getOption('quiet')) {
				$io->out(sprintf('Finished processing %u files.', $totalFiles));

				break;
			}

			// If no files were found, sleep for 10 seconds before checking again
			if ($totalFiles === 0) {
				sleep(10);
			}
		}

		return $errorOccurred ? static::CODE_ERROR : static::CODE_SUCCESS;
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
		$files = $this->fetchCropFiles((int)$args->getOption('limit'));

		if ($files->count()) {
			$this->debug('Fetched crop files', ['count' => $files->count()]);

			$result = $this->cropImages($files, $io);

			if ($result !== static::CODE_SUCCESS) {
				return false;
			}

			return $files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processNonImageFiles(Arguments $args, ConsoleIo $io): int|false {
		$files = $this->fetchNonImageFiles(
			(int)$args->getOption('limit'),
			$args->getOption('retry-failed'),
			$args->getOption('include-avif'),
			$args->getOption('include-webp')
		);

		if ($files->count()) {
			$this->debug('Fetched non-image files', [
				'count' => $files->count(),
				'includeAvif' => (bool)$args->getOption('include-avif'),
				'includeWebp' => (bool)$args->getOption('include-webp'),
				'retryFailed' => (bool)$args->getOption('retry-failed'),
			]);

			$result = $this->convertNonImages($files, $io, $args->getOption('include-avif'), $args->getOption('include-webp'));
			if ($result !== static::CODE_SUCCESS) {
				return false;
			}

			return $files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processAvifConversion(Arguments $args, ConsoleIo $io): int|false {
		$files = $this->fetchFilesForAvifConversion((int)$args->getOption('limit'), $args->getOption('retry-failed'));

		if ($files->count()) {
			$this->debug(
				'Fetched avif conversion files',
				[
					'count' => $files->count(),
					'retryFailed' => (bool)$args->getOption('retry-failed'),
				]
			);

			$result = $this->convertImagesToAvif($files, $io);
			if ($result !== static::CODE_SUCCESS) {
				return false;
			}

			return $files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processWebpConversion(Arguments $args, ConsoleIo $io): int|false {
		$files = $this->fetchFilesForWebpConversion((int)$args->getOption('limit'), $args->getOption('retry-failed'));

		if ($files->count()) {
			$this->debug(
				'Fetched webp conversion files',
				[
					'count' => $files->count(),
					'retryFailed' => (bool)$args->getOption('retry-failed'),
				]
			);

			$result = $this->convertImagesToWebp($files, $io);
			if ($result !== static::CODE_SUCCESS) {
				return false;
			}

			return $files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processResizing(Arguments $args, ConsoleIo $io): int|false {
		$files = $this->fetchFilesForResizing((int)$args->getOption('limit'), $args->getOption('retry-failed'));

		if ($files->count()) {
			$this->debug('Fetched resize files', ['count' => $files->count(), 'retryFailed' => (bool)$args->getOption('retry-failed')]);

			$result = $this->resizeImages($files, $io);
			if ($result !== static::CODE_SUCCESS) {
				return false;
			}

			return $files->count();
		}

		return 0;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|false
	 */
	public function processAverageColorCalculation(Arguments $args, ConsoleIo $io): int|false {
		$files = $this->fetchFilesForAverageColorCalculation((int)$args->getOption('limit'));

		if ($files->count()) {
			$this->debug('Fetched average-color files', ['count' => $files->count()]);

			$result = $this->calculateAverageColors($files, $io);
			if ($result !== static::CODE_SUCCESS) {
				return false;
			}

			return $files->count();
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
		/** @var \Awyiss\Model\Entity\Media $file */
		foreach ($files as $file) {
			$path = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;
			$this->debug('Average-color file processing started', [
				'mediaId' => $file->id,
				'path' => $file->path,
				'absolutePath' => $path,
				'isImage' => $file->isImage(),
				'mimeType' => $file->mimeType,
			]);

			$io->out(sprintf('Calculating average color for file `%s`', $file->path));

			if (!file_exists($path)) {
				$io->error('Status: File does not exist');
				$io->hr();

				// If the file does not exist or is a png, set the average color to a fully transparent black
				$file->averageColor = '00000000';
				$this->debug('Average-color file skipped because source does not exist', [
					'mediaId' => $file->id,
					'assignedColor' => $file->averageColor,
				]);
				continue;
			}

			if ($file->mimeType === 'image/png') {
				$io->info('Status: Cannot calculate average color for png files');
				$io->hr();

				// If the file does not exist or is a png, set the average color to a fully transparent black
				$file->averageColor = '00000000';
				$this->debug('Average-color file skipped because mime type is png', [
					'mediaId' => $file->id,
					'assignedColor' => $file->averageColor,
				]);
				continue;
			}

			$colors = $this->calculateAverageColor($path, $io);

			if (!$colors) {
				$io->error('Status: Cannot calculate average color for file');
				$io->hr();

				$file->averageColor = '00000000';
				$this->debug('Average-color calculation returned no color values', [
					'mediaId' => $file->id,
					'assignedColor' => $file->averageColor,
				]);
				continue;
			}

			$this->debug('Average-color raw values', [
				'mediaId' => $file->id,
				'colors' => $colors,
			]);

			// If alpha is fully transparent, set it to FF
			$alphaWasTransparent = $colors['alpha'] === 0;
			$colors['alpha'] = $colors['alpha'] === 0 ? 255 : $colors['alpha'];

			$file->averageColor = sprintf('%02X%02X%02X%02X', $colors['red'], $colors['green'], $colors['blue'], $colors['alpha']);
			$this->debug('Average-color file processing finished', [
				'mediaId' => $file->id,
				'alphaWasTransparent' => $alphaWasTransparent,
				'assignedColor' => $file->averageColor,
			]);

			$io->success('Status: Average color calculated successfully (#' . $file->averageColor . ')');
			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->fetchTable('Media');
		// Update all files with the average color
		$mediaTable->updateAll(function (QueryExpression $expression) use ($files) {
			$averageColorCases = $expression->case();

			/** @var \Awyiss\Model\Entity\Media $file */
			foreach ($files as $file) {
				$averageColorCases
					->when(['id = ' . $file->id])
					->then($file->averageColor, 'string')
				;
			}

			return [
				'averageColor' => $averageColorCases,
			];
		}, [
			'id IN' => $files
				->extract('id')
				->toArray(),
		]);

		return static::CODE_SUCCESS;
	}


	/**
	 * @param string $path
	 * @param \Cake\Console\ConsoleIo $io
	 * @return array|false
	 */
	protected function calculateAverageColor(string $path, ConsoleIo $io): array|false {
		$this->debug('Calculating average color', ['path' => $path, 'usesCli' => $this->cliMagickExists]);

		// If magick is not available, use Intervention
		if (!$this->cliMagickExists) {
			return $this->calculateAverageColorIntervention($path, $io);
		}

		return $this->calculateAverageColorCli($path, $io);
	}


	/**
	 * Calculate the average color of an image using the ImageMagick command line tool
	 *
	 * @param string $path
	 * @param \Cake\Console\ConsoleIo $io
	 * @return array|false
	 */
	protected function calculateAverageColorCli(string $path, ConsoleIo $io): array|false {
		$process = $this->getProcess([
			'magick',
			$path,
			'-resize',
			'1x1!',
			'-format',
			'%[fx:int(255*r+.5)],%[fx:int(255*g+.5)],%[fx:int(255*b+.5)]',
			'info:-',
		]);

		$result = $this->runProcess($process, $io);

		if (!$result) {
			return false;
		}

		$colorParts = explode(',', $process->getOutput());

		return [
			'red' => (int)$colorParts[0],
			'green' => (int)$colorParts[1],
			'blue' => (int)$colorParts[2],
			'alpha' => 255,
		];
	}


	/**
	 * Calculate the average color of an image
	 * using the Intervention Image library
	 *
	 * @param string $filePath
	 * @param \Cake\Console\ConsoleIo $io
	 * @return array{red: int, green: int, blue: int, alpha: int}|false
	 */
	protected function calculateAverageColorIntervention(string $filePath, ConsoleIo $io): array|false {
		try {
			$image = $this->imageManager->decodePath($filePath);

			// Resize the image to 1x1 pixel
			$color = $image
				->resize(1, 1)
				->colorAt(0, 0)
			;
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());
			$this->debug('Average-color Intervention failed', ['path' => $filePath, 'error' => $ex->getMessage()]);

			return false;
		}

		$colorParts = $color->channels();
		$this->debug('Average-color Intervention raw output', ['path' => $filePath, 'output' => $colorParts]);

		return [
			'red' => $colorParts[0]->value(),
			'green' => $colorParts[1]->value(),
			'blue' => $colorParts[2]->value(),
			'alpha' => 255,
		];
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function convertImagesToAvif(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\Media $file */
		foreach ($files as $file) {
			$this->convertImageToAvif($file, $io);
			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->fetchTable('Media');

		$avifStatus = array_unique(
			$files
				->extract('avif')
				->toArray(),
			SORT_REGULAR
		);
		if (count($avifStatus) === 1) {
			/**
			 * If all files have the same avif status, use a simple updateAll command
			 */
			$mediaTable->updateAll([
				'avif' => $avifStatus[0],
			], [
				'id IN' => $files
					->extract('id')
					->toArray(),
			]);
		}
		else {
			$mediaTable->updateAll(function (QueryExpression $expression) use ($files) {
				$avifCases = $expression->case();

				/** @var \Awyiss\Model\Entity\Media $file */
				foreach ($files as $file) {
					$avifCases
						->when(['id = ' . $file->id])
						->then($file->avif->value, 'integer')
					;
				}

				return [
					'avif' => $avifCases,
				];
			}, [
				'id IN' => $files
					->extract('id')
					->toArray(),
			]);
		}


		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function convertImagesToWebp(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\Media $file */
		foreach ($files as $file) {
			$this->convertImageToWebp($file, $io);
			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->fetchTable('Media');

		$webpStatus = array_unique(
			$files
				->extract('webp')
				->toArray(),
			SORT_REGULAR
		);
		if (count($webpStatus) === 1) {
			/**
			 * If all files have the same webp status, use a simple updateAll command
			 */
			$mediaTable->updateAll([
				'webp' => $webpStatus[0],
			], [
				'id IN' => $files
					->extract('id')
					->toArray(),
			]);
		}
		else {
			$mediaTable->updateAll(function (QueryExpression $expression) use ($files) {
				$webpCases = $expression->case();

				/** @var \Awyiss\Model\Entity\Media $file */
				foreach ($files as $file) {
					$webpCases
						->when(['id = ' . $file->id])
						->then($file->webp->value, 'integer')
					;
				}

				return [
					'webp' => $webpCases,
				];
			}, [
				'id IN' => $files
					->extract('id')
					->toArray(),
			]);
		}


		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToAvif(Media $file, ConsoleIo $io): bool {
		if (!$file->avifPathAbsolute) {
			$io->out(sprintf('Creating Avif file for file `%s`', $file->path));
			$io->error('Status: Cannot convert file without a path');
			$io->hr();

			$file->avif = ProcessStatus::Fail;

			return false;
		}

		if (!file_exists(dirname($file->avifPathAbsolute))) {
			$io->out(sprintf('Creating directory `%s` for Avif file', dirname($file->avifPath)));

			if (!mkdir(dirname($file->avifPathAbsolute))) {
				$io->error('Status: Cannot create directory for Avif file');
				$io->hr();

				$file->avif = ProcessStatus::Fail;

				return false;
			}

			$io->success('Status: Directory created');
			$io->hr();
		}

		$io->out(sprintf('Creating Avif file for file `%s`', $file->path));

		// If magick is not available or cannot convert to AVIF, use the Intervention library
		if (!$this->cliMagickExists	|| Configure::read('AvailableCommands.imageMagick.avif', false) === false) {
			return $this->convertImageToAvifIntervention($file, $io);
		}

		return $this->convertImageToAvifCli($file, $io);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToWebp(Media $file, ConsoleIo $io): bool {
		if (!$file->webpPathAbsolute) {
			$io->out(sprintf('Creating WebP file for file `%s`', $file->path));
			$io->error('Status: Cannot convert file without a path');
			$io->hr();

			$file->webp = ProcessStatus::Fail;

			return false;
		}

		if (!file_exists(dirname($file->webpPathAbsolute))) {
			$io->out(sprintf('Creating directory `%s` for WebP file', dirname($file->webpPath)));

			if (!mkdir(dirname($file->webpPathAbsolute))) {
				$io->error('Status: Cannot create directory for WebP file');
				$io->hr();

				$file->webp = ProcessStatus::Fail;

				return false;
			}

			$io->success('Status: Directory created');
			$io->hr();
		}

		$io->out(sprintf('Creating WebP file for file `%s`', $file->path));

		// If magick is not available or cannot convert to WebP, use the Intervention library
		if (!$this->cliMagickExists || Configure::read('AvailableCommands.imageMagick.webp', false) === false) {
			return $this->convertImageToWebpIntervention($file, $io);
		}

		return $this->convertImageToWebpCli($file, $io);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToAvifIntervention(Media $file, ConsoleIo $io): bool {
		$inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		try {
			$image = $this->imageManager->decodePath($inputPath);

			$image
				->encodeUsingFormat(Format::AVIF, quality: $this->quality)
				->save($file->avifPathAbsolute)
			;
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());

			$file->avif = ProcessStatus::Fail;

			return false;
		}

		$io->success('Status: Avif file created');
		$file->avif = ProcessStatus::Success;

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToWebpIntervention(Media $file, ConsoleIo $io): bool {
		$inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		try {
			$image = $this->imageManager->decodePath($inputPath);

			$image
				->encodeUsingFormat(Format::WEBP, quality: $this->quality)
				->save($file->webpPathAbsolute)
			;
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());

			$file->webp = ProcessStatus::Fail;

			return false;
		}

		$io->success('Status: WebP file created');
		$file->webp = ProcessStatus::Success;

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToAvifCli(Media $file, ConsoleIo $io): bool {
		$process = $this->getProcess($this->getAvifCommand($file));

		$result = $this->runProcess($process, $io);

		$file->avif = $result ? ProcessStatus::Success : ProcessStatus::Fail;

		return $result;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToWebpCli(Media $file, ConsoleIo $io): bool {
		$process = $this->getProcess($this->getWebPCommand($file));

		$result = $this->runProcess($process, $io);

		$file->webp = $result ? ProcessStatus::Success : ProcessStatus::Fail;

		return $result;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeAvif
	 * @param bool $includeWebp
	 * @return int
	 */
	protected function convertNonImages(ResultSetInterface $files, ConsoleIo $io, bool $includeAvif, bool $includeWebp): int {
		/** @var \Awyiss\Model\Entity\Media $file */
		foreach ($files as $file) {
			$this->convertNonImage($file, $io, $includeAvif, $includeWebp);

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->fetchTable('Media');

		$previewStatus = array_unique(
			$files
				->extract('preview')
				->toArray(),
			SORT_REGULAR
		);
		/**
		 * If all files have the same preview status "failed", use a simple updateAll command
		 * This also means no preview was created, even though it was requested.
		 * Set the avif and webp status to undefined in this case.
		 */
		if (count($previewStatus) === 1 && $previewStatus[0] === ProcessStatus::Fail) {
			$mediaTable->updateAll([
				'preview' => $previewStatus[0],
				'avif' => ProcessStatus::Undefined,
				'webp' => ProcessStatus::Undefined,
			], [
				'id IN' => $files
					->extract('id')
					->toArray(),
			]);
		}
		else {
			$mediaTable->updateAll(function (QueryExpression $expression) use ($files, $includeAvif, $includeWebp) {
				$widthCases = $expression->case();
				$heightCases = $expression->case();
				$previewCases = $expression->case();

				if ($includeAvif) {
					$avifCases = $expression->case();
				}

				if ($includeWebp) {
					$webpCases = $expression->case();
				}

				/** @var \Awyiss\Model\Entity\Media $file */
				foreach ($files as $file) {
					$widthCases
						->when(['id = ' . $file->id])
						->then($file->width, 'float')
					;
					$heightCases
						->when(['id = ' . $file->id])
						->then($file->height, 'float')
					;
					$previewCases
						->when(['id = ' . $file->id])
						->then($file->preview->value, 'integer')
					;

					if ($includeAvif) {
						$avifCases
							->when(['id = ' . $file->id])
							->then($file->avif->value, 'integer')
						;
					}

					if ($includeWebp) {
						$webpCases
							->when(['id = ' . $file->id])
							->then($file->webp->value, 'integer')
						;
					}
				}

				$cases = [
					'width' => $widthCases,
					'height' => $heightCases,
					'preview' => $previewCases,
				];

				if ($includeAvif) {
					$cases['avif'] = $avifCases;
				}

				if ($includeWebp) {
					$cases['webp'] = $webpCases;
				}

				return $cases;
			}, [
				'id IN' => $files
					->extract('id')
					->toArray(),
			]);
		}

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeAvif
	 * @param bool $includeWebp
	 * @return bool
	 */
	protected function convertNonImage(Media $file, ConsoleIo $io, bool $includeAvif, bool $includeWebp): bool {
		$this->debug('Converting non-image', [
			'mediaId' => $file->id,
			'path' => $file->path,
			'mimeType' => $file->mimeType,
			'extension' => $file->extension,
			'includeAvif' => $includeAvif,
			'includeWebp' => $includeWebp,
		]);

		if (!$file->previewPathAbsolute) {
			$io->out(sprintf('Creating preview for file `%s`', $file->path));
			$io->error('Status: Cannot convert file without a path');
			$io->hr();

			return false;
		}

		if (!file_exists(dirname($file->previewPathAbsolute))) {
			$io->out(sprintf('Creating directory `%s` for file preview', dirname($file->previewPath)));

			if (!mkdir(dirname($file->previewPathAbsolute))) {
				$io->error('Status: Cannot create directory for file preview');
				$io->hr();

				return false;
			}

			$io->success('Status: Directory created');
			$io->hr();
		}

		$io->out(sprintf('Creating preview for file `%s`', $file->path));

		if (
			!in_array($file->mimeType, ['video/mp4', 'video/x-msvideo'])
			&& (
				!$this->cliMagickExists
				|| Configure::read('AvailableCommands.imageMagick.' . $file->extension, false) === false
			)
		) {
			$this->debug('Using Intervention pipeline for non-image conversion', ['mediaId' => $file->id]);

			return $this->convertNonImageIntervention($file, $io, $includeAvif, $includeWebp);
		}

		$this->debug('Using CLI pipeline for non-image conversion', ['mediaId' => $file->id]);

		return $this->convertNonImageCli($file, $io, $includeAvif, $includeWebp);
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeAvif
	 * @param bool $includeWebp
	 * @return bool
	 */
	protected function convertNonImageIntervention(Media $file, ConsoleIo $io, bool $includeAvif, bool $includeWebp): bool {
		try {
			// If Imagick does not exist, nothing can be done
			if (
				!class_exists('Imagick')
				|| !in_array($file->extension, ['pdf', 'eps', 'ai', 'psd'], true)
			) {
				throw new Exception(sprintf('Cannot convert filetype `%s`', $file->extension));
			}

			$image = new Imagick();
			$image->setResolution(150, 150);
			$image->readImage($file->pathAbsolute . '[' . 0 . ']');
			$image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);

			$iccProfiles = $image->getImageProfiles('*', false);
			$hasIccProfile = (bool)array_search('icc', $iccProfiles);

			if ($hasIccProfile === false) {
				$image->stripImage();
				$cmykProfile = file_get_contents(APP . 'assets' . DS . 'icc_profiles' . DS . 'cmyk.icc');
				$image->profileImage('icc', $cmykProfile);
				$image->stripImage();
				$srgbProfile = file_get_contents(APP . 'assets' . DS . 'icc_profiles' . DS . 'srgb.icc');
				$image->profileImage('icc', $srgbProfile);
			}

			if ($image->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
				$image->negateImage(false);
			}

			$image->stripImage();
			$image->setImageColorspace(Imagick::COLORSPACE_SRGB);
			$image->setImageBackgroundColor('#FFFFFF');

			$image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

			$image->setImageFormat('jpeg');
			$image->setCompression(Imagick::COMPRESSION_JPEG);

			// Preview files are always saved as JPEG and in a higher quality
			$image->setCompressionQuality(95);

			if (!$image->writeImage($file->previewPathAbsolute)) {
				throw new Exception('Cannot write image to file');
			}
		}
		catch (Exception $ex) {
			$io->warning('Status: ' . $ex->getMessage());

			$file->preview = ProcessStatus::Fail;
			$file->avif = ProcessStatus::Fail;
			$file->webp = ProcessStatus::Fail;

			return false;
		}

		$io->success('Status: Preview file created');

		/** @noinspection DuplicatedCode */
		$imageSize = $this->getRealImageSize($file->previewPathAbsolute, $io);

		$file->width = $imageSize[0] ?? null;
		$file->height = $imageSize[1] ?? null;
		$file->preview = ProcessStatus::Success;

		if ($includeAvif) {
			$this->convertImageToAvif($file, $io);
		}

		if ($includeWebp) {
			$this->convertImageToWebp($file, $io);
		}

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeAvif
	 * @param bool $includeWebp
	 * @return bool
	 */
	protected function convertNonImageCli(Media $file, ConsoleIo $io, bool $includeAvif, bool $includeWebp): bool {
		$command = $this->getPreviewCommand($file);

		if (!$command) {
			$io->warning(sprintf('Status: Cannot convert filetype `%s`', $file->extension));

			$file->preview = ProcessStatus::Fail;
			$file->avif = ProcessStatus::Fail;
			$file->webp = ProcessStatus::Fail;

			return false;
		}

		$process = $this->getProcess($command);

		$result = $this->runProcess($process, $io);

		if (!$result) {
			$file->preview = ProcessStatus::Fail;
			$file->avif = ProcessStatus::Undefined;
			$file->webp = ProcessStatus::Undefined;

			return false;
		}

		/** @noinspection DuplicatedCode */
		$imageSize = $this->getRealImageSize($file->previewPathAbsolute, $io);

		$file->width = $imageSize[0] ?? null;
		$file->height = $imageSize[1] ?? null;
		$file->preview = ProcessStatus::Success;

		if ($includeAvif) {
			$this->convertImageToAvif($file, $io);
		}

		if ($includeWebp) {
			$this->convertImageToWebp($file, $io);
		}

		return true;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function cropImages(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\Media $file */
		foreach ($files as $file) {
			$io->out(sprintf('Cropping file `%s`', $file->path));

			$this->cropImage($file, $io);

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->fetchTable('Media');

		$mediaTable->updateAll(function (QueryExpression $expression) use ($files) {
			$widthCases = $expression->case();
			$heightCases = $expression->case();
			$cropCases = $expression->case();

			/** @var \Awyiss\Model\Entity\Media $file */
			foreach ($files as $file) {
				$widthCases
					->when(['id = ' . $file->id])
					->then($file->width, 'float')
				;
				$heightCases
					->when(['id = ' . $file->id])
					->then($file->height, 'float')
				;
				$cropCases
					->when(['id = ' . $file->id])
					->then($file->crop, 'integer')
				;
			}

			return [
				'width' => $widthCases,
				'height' => $heightCases,
				'crop' => $cropCases,
			];
		}, [
			'id IN' => $files
				->extract('id')
				->toArray(),
		]);

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function cropImage(Media $file, ConsoleIo $io): bool {
		$this->debug('Cropping media', [
			'mediaId' => $file->id,
			'path' => $file->path,
			'extension' => $file->extension,
		]);

		if (
			!$this->cliMagickExists
			|| (
				Configure::read('AvailableCommands.imageMagick.' . $file->extension, false) === false
				&& !in_array(strtolower($file->extension), ['gif', 'png', 'jpg', 'jpeg'], true)
			)
		) {
			$this->debug('Crop via Intervention', ['mediaId' => $file->id]);
			$cropped = $this->cropImageIntervention($file, $io);
		}
		else {
			$this->debug('Crop via CLI', ['mediaId' => $file->id]);
			$cropped = $this->cropImageCli($file, $io);
		}

		if (!$cropped) {
			return false;
		}

		if (!empty($file->crop['resizeWidth']) || !empty($file->crop['resizeHeight'])) {
			// Delete all resized files. They will be recreated when needed.
			// Previously set sizes might no longer be required OR even too large
			$file->deleteResizedFiles();
		}

		$imageSize = $this->getRealImageSize($file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute, $io);

		$file->width = $imageSize[0] ?? null;
		$file->height = $imageSize[1] ?? null;
		$file->crop = null;

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function cropImageIntervention(Media $file, ConsoleIo $io): bool {
		$inputPath = $file->pathAbsolute;
		$crop = [];
		$resize = [];

		if (!$file->isImage()) {
			$inputPath = $file->previewPathAbsolute;
		}

		if (isset($file->crop['rotate']) && $file->crop['rotate'] === 'auto') {
			if (!$this->autoRotateImageIntervention($inputPath)) {
				return false;
			}

			// If an avif file exists, rotate it as well
			if ($file->avifPathAbsolute && file_exists($file->avifPathAbsolute)) {
				try {
					$this->autoRotateImageIntervention($file->avifPathAbsolute);
				}
				catch (Exception) {
					// Ignore the exception for avif autorotation
				}
			}


			// If a webp file exists, rotate it as well
			if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
				try {
					$this->autoRotateImageIntervention($file->webpPathAbsolute);
				}
				catch (Exception) {
					// Ignore the exception for webp autorotation
				}
			}

			return true;
		}

		try {
			$image = $this->imageManager->decodePath($inputPath);

			$this->debug('Cropping file with Intervention', [
				'mediaId' => $file->id,
				'path' => $file->path,
				'crop' => $file->crop,
			]);

			if ($file->width !== (float)$file->crop['width'] || $file->height !== (float)$file->crop['height']) {
				$crop = [(int)$file->crop['width'], (int)$file->crop['height'], (int)$file->crop['x'], (int)$file->crop['y']];

				$image->crop(...$crop);
			}

			if (
				(float)$file->crop['width'] !== (float)$file->crop['resizeWidth']
				|| (float)$file->crop['height'] !== (float)$file->crop['resizeHeight']
			) {
				$resize = [(int)$file->crop['resizeWidth'], (int)$file->crop['resizeHeight']];

				$image->scaleDown(...$resize);
			}

			if (!$crop && !$resize) {
				$image = null; //phpcs:ignore

				return true;
			}

			$image->save($inputPath, quality: $this->quality, progressive: true);
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());

			return false;
		}

		// If an avif file exists, crop it as well
		if ($file->avifPathAbsolute && file_exists($file->avifPathAbsolute)) {
			try {
				$this->cropAndResizeIntervention($file->avifPathAbsolute, $crop, $resize, $file->avifPathAbsolute);
			}
			catch (Exception) {
				// Ignore the exception for avif cropping
			}
		}

		// If a webp file exists, crop it as well
		if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
			try {
				$this->cropAndResizeIntervention($file->webpPathAbsolute, $crop, $resize, $file->webpPathAbsolute);
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
	protected function cropImageCli(Media $file, ConsoleIo $io): bool {
		$commands = $this->getCropCommand($file);

		if (empty($commands['original'])) {
			$this->debug('Skipping CLI crop because no crop/resize operation is required', ['mediaId' => $file->id]);

			return true;
		}

		$this->debug('Cropping file with CLI command', ['mediaId' => $file->id, 'command' => $commands['original']]);
		$process = $this->getProcess($commands['original']);

		$result = $this->runProcess($process, $io);

		if (!$result) {
			return false;
		}

		// If there's an avif command, run it and crop the avif file as well
		if ($commands['avif']) {
			$this->debug('Cropping AVIF derivative', ['mediaId' => $file->id, 'command' => $commands['avif']]);
			$process = $this->getProcess($commands['avif']);
			$process->run();
		}

		// If there's a webp command, run it and crop the webp file as well
		if ($commands['webp']) {
			$this->debug('Cropping WebP derivative', ['mediaId' => $file->id, 'command' => $commands['webp']]);
			$process = $this->getProcess($commands['webp']);
			$process->run();
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
		/** @var \Awyiss\Model\Entity\MediaResizedImage $file */
		foreach ($files as $file) {
			$this->resizeImage($file, $io);

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $mediaTable */
		$mediaTable = $this->fetchTable('MediaResizedImages');

		$status = array_unique(
			$files
				->extract('status')
				->toArray(),
			SORT_REGULAR
		);
		/**
		 * If all files have the same status "failed", use a simple updateAll command
		 */
		if (count($status) === 1 && $status[0] === ProcessStatus::Fail) {
			$mediaTable->updateAll([
				'status' => ProcessStatus::Fail,
			], [
				'id IN' => $files
					->extract('id')
					->toArray(),
			]);
		}
		else {
			$mediaTable->updateAll(function (QueryExpression $expression) use ($files) {
				$realWidthCases = $expression->case();
				$realHeightCases = $expression->case();
				$statusCases = $expression->case();

				/** @var \Awyiss\Model\Entity\MediaResizedImage $file */
				foreach ($files as $file) {
					$realWidthCases
						->when(['id = ' . $file->id])
						->then($file->realWidth, 'integer')
					;
					$realHeightCases
						->when(['id = ' . $file->id])
						->then($file->realHeight, 'integer')
					;
					$statusCases
						->when(['id = ' . $file->id])
						->then($file->status->value, 'integer')
					;
				}

				return [
					'realWidth' => $realWidthCases,
					'realHeight' => $realHeightCases,
					'status' => $statusCases,
				];
			}, [
				'id IN' => $files
					->extract('id')
					->toArray(),
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
		$this->debug('Resizing media', [
			'resizedImageId' => $file->id,
			'mediaId' => $file->media->id,
			'path' => $file->path,
			'strategy' => $file->strategy->value,
			'extension' => $file->extension,
		]);

		if (!$file->media->isImage() && $file->media->preview === ProcessStatus::Fail) {
			$io->out(sprintf('Resizing file `%s` to `%s', $file->media->path, $file->path));
			$io->error('Status: Cannot resize non-image file without a preview');
			$io->hr();

			$file->status = ProcessStatus::Fail;

			return false;
		}

		if (!file_exists(dirname($file->pathAbsolute))) {
			$io->out(sprintf('Creating directory `%s` for resized file', dirname($file->path)));

			if (!mkdir(dirname($file->pathAbsolute))) {
				$io->error('Status: Cannot create directory for resized file');
				$io->hr();

				return false;
			}

			$io->success('Status: Directory created');
			$io->hr();
		}

		$io->out(sprintf('Resizing file `%s` to `%s', $file->media->path, $file->path));

		if (
			!$this->cliMagickExists
			|| (
				Configure::read('AvailableCommands.imageMagick.' . $file->extension, false) === false
				&& !in_array(
					strtolower($file->extension),
					['gif', 'png', 'jpg', 'jpeg'],
					true
				)
			)
		) {
			$this->debug('Resize via Intervention', ['resizedImageId' => $file->id]);
			$resized = $this->resizeImageIntervention($file, $io);
		}
		else {
			$this->debug('Resize via CLI', ['resizedImageId' => $file->id]);
			$resized = $this->resizeImageCli($file, $io);
		}

		if (!$resized) {
			$file->status = ProcessStatus::Fail;

			return false;
		}

		$imageSize = $this->getRealImageSize($file->pathAbsolute, $io);

		$file->realWidth = $imageSize[0] ?? null;
		$file->realHeight = $imageSize[1] ?? null;
		$file->status = ProcessStatus::Success;

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function resizeImageIntervention(MediaResizedImage $file, ConsoleIo $io): bool {
		try {
			$image = $this->imageManager->decodePath(
				$file->media->isImage() ? $file->media->pathAbsolute : $file->media->previewPathAbsolute
			);

			if ($file->strategy === ResizeStrategy::Contain) {
				$image->scaleDown($file->width, $file->height);
			}
			elseif ($file->strategy === ResizeStrategy::Cover) {
				// Calculate aspect ratios
				$originalRatio = $file->media->width / $file->media->height;
				$targetRatio = $originalRatio;
				if ($file->width && $file->height) {
					$targetRatio = $file->width / $file->height;
				}

				// Resize logic mimicking ^
				if ($originalRatio > $targetRatio) {
					// Image is wider - scale by height
					$image->scaleDown(null, $file->height);
				}
				else {
					// Image is taller - scale by width
					$image->scaleDown($file->width);
				}
			}
			elseif ($file->strategy === ResizeStrategy::Crop) {
				$position = 'center';

				if ($file->media->focusPoint) {
					// The focus point is in the format "[0|1|2],[0|1|2]"
					$focusPoint = explode(',', $file->media->focusPoint);

					if (count($focusPoint) !== 2) {
						$focusPoint = [1, 1];
					}

					// Convert the focus point to a position value
					// Possible values should be "top-left", "top", "top-right", "left", "center", "right", "bottom-left", "bottom", "bottom-right"
					$positionValues = [
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

					$position = $positionValues[ (int)$focusPoint[0] * 3 + (int)$focusPoint[1] ];
				}

				$image->coverDown($file->width, $file->height, $position);
			}
			elseif ($file->strategy === ResizeStrategy::Stretch) {
				$image->resizeDown($file->width, $file->height);
			}
			else {
				$io->error('Status: Unsupported resize strategy');

				return false;
			}

			$image->save($file->pathAbsolute, quality: $this->quality, progressive: true);
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
	protected function resizeImageCli(MediaResizedImage $file, ConsoleIo $io): bool {
		$process = $this->getProcess($this->getResizeCommand($file));

		return $this->runProcess($process, $io);
	}


	/**
	 * @param int $limit
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchCropFiles(int $limit): ResultSetInterface {
		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->fetchTable('Media');

		return $mediaTable
			->find()
			->where([
				'crop IS NOT' => null,
				'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
			])
			->limit($limit)
			->all()
		;
	}


	/**
	 * @param int $limit
	 * @param bool $retryFailed
	 * @param bool $includeAvif
	 * @param bool $includeWebp
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchNonImageFiles(int $limit, bool $retryFailed, bool $includeAvif, bool $includeWebp): ResultSetInterface {
		$where = [
			'preview' => ProcessStatus::Undefined,
		];

		if ($retryFailed) {
			$where['preview'] = ProcessStatus::Fail;
		}

		$processStatusColumns = [
			'preview',
		];

		if ($includeAvif) {
			$processStatusColumns[] = 'avif';
		}

		if ($includeWebp) {
			$processStatusColumns[] = 'webp';
		}


		return $this->fetchFiles($where, $limit, $processStatusColumns);
	}


	/**
	 * Get files that have no average color set yet
	 *
	 * @param int $limit
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForAverageColorCalculation(int $limit): ResultSetInterface {
		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->fetchTable('Media');

		return $mediaTable
			->find()
			->where([
				'averageColor IS' => null,
				'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
			])
			->limit($limit)
			->all()
		;
	}


	/**
	 * Fetch files that need to be resized
	 *
	 * @param int $limit
	 * @param bool $retryFailed
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForResizing(int $limit, bool $retryFailed): ResultSetInterface {
		$where = [
			'status' => ProcessStatus::Undefined,
		];

		if ($retryFailed) {
			$where['status'] = ProcessStatus::Fail;
		}

		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $mediaResizedImagesTable */
		$mediaResizedImagesTable = $this->fetchTable('MediaResizedImages');
		$records = $mediaResizedImagesTable
			->find()
			->where($where)
			->contain(['Media'])
			->limit($limit)
			->all()
		;

		if ($records->count()) {
			$mediaResizedImagesTable->updateAll([
				'status' => ProcessStatus::InProgress,
			], [
				'id IN' => $records
					->extract('id')
					->toArray(),
			]);
		}

		return $records;
	}


	/**
	 * @param int $limit
	 * @param bool $retryFailed
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForAvifConversion(int $limit, bool $retryFailed): ResultSetInterface {
		$where = [
			'avif' => ProcessStatus::Undefined,
			'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
		];

		if ($retryFailed) {
			$where['avif'] = ProcessStatus::Fail;
		}

		return $this->fetchFiles($where, $limit, ['avif']);
	}


	/**
	 * @param int $limit
	 * @param bool $retryFailed
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForWebpConversion(int $limit, bool $retryFailed): ResultSetInterface {
		$where = [
			'webp' => ProcessStatus::Undefined,
			'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
		];

		if ($retryFailed) {
			$where['webp'] = ProcessStatus::Fail;
		}

		return $this->fetchFiles($where, $limit, ['webp']);
	}


	/**
	 * @param array $where
	 * @param int $limit
	 * @param array $processStatusColumns
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFiles(array $where, int $limit, array $processStatusColumns): ResultSetInterface {
		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->fetchTable('Media');
		$records = $mediaTable
			->find()
			->where($where)
			->limit($limit)
			->all()
		;

		if ($records->count()) {
			$this->debug('Fetched media records', ['count' => $records->count()]);

			$updatedProcessStatusColumns = [];
			foreach ($processStatusColumns as $column) {
				$updatedProcessStatusColumns[ $column ] = ProcessStatus::InProgress;
			}

			$mediaTable->updateAll($updatedProcessStatusColumns, [
				'id IN' => $records
					->extract('id')
					->toArray(),
			]);
		}

		return $records;
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

			return [
				'ffmpeg',
				'-y',
				'-i',
				WWW_ROOT . str_replace('/', DS, $file->path),
				'-ss',
				'0',
				'-frames:v',
				'1',
				$file->previewPathAbsolute,
			];
		}

		if (!Configure::read('AvailableCommands.imageMagick.' . $file->extension)) {
			return false;
		}

		$inputPath = $file->path;
		if (in_array($file->extension, ['pdf', 'psd'])) {
			$inputPath .= '[0]';
		}

		return [
			'magick',
			'-density',
			150,
			WWW_ROOT . str_replace('/', DS, $inputPath),
			'-colorspace',
			'sRGB',
			'-background',
			'white',
			'-quality',
			$this->quality,
			'-alpha',
			'remove',
			'-flatten',
			$file->previewPathAbsolute,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getAvifCommand(Media $file): array {
		$inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		return [
			'magick',
			$inputPath,
			$file->avifPathAbsolute,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getWebPCommand(Media $file): array {
		$inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		return [
			'magick',
			$inputPath,
			$file->webpPathAbsolute,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getCropCommand(Media $file): array {
		$inputPath = $file->pathAbsolute;
		$crop = [];
		$resize = [];

		if (!$file->isImage()) {
			$inputPath = $file->previewPathAbsolute;
		}

		if (isset($file->crop['rotate']) && $file->crop['rotate'] === 'auto') {
			$commandAvif = null;
			if ($file->avifPathAbsolute && file_exists($file->avifPathAbsolute)) {
				$commandAvif = [
					'magick',
					'mogrify',
					'-auto-orient',
					$file->avifPathAbsolute,
				];
			}

			$commandWebp = null;
			if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
				$commandWebp = [
					'magick',
					'mogrify',
					'-auto-orient',
					$file->webpPathAbsolute,
				];
			}

			return [
				'original' => [
					'magick',
					'mogrify',
					'-auto-orient',
					$inputPath,
				],
				'avif' => $commandAvif,
				'webp' => $commandWebp,
			];
		}

		$commandOriginal = [
			'magick',
			$inputPath,
		];

		if ($file->width !== (float)$file->crop['width'] || $file->height !== (float)$file->crop['height']) {
			$crop = [(int)$file->crop['width'], (int)$file->crop['height'], (int)$file->crop['x'], (int)$file->crop['y']];
			$commandOriginal = array_merge($commandOriginal, [
				'-crop',
				sprintf('%dx%d+%d+%d', ...$crop),
			]);
		}

		if (
			(float)$file->crop['width'] !== (float)$file->crop['resizeWidth']
			|| (float)$file->crop['height'] !== (float)$file->crop['resizeHeight']
		) {
			$resize = [(int)$file->crop['resizeWidth'], (int)$file->crop['resizeHeight']];
			$commandOriginal = array_merge($commandOriginal, [
				'-resize',
				sprintf('%dx%d', ...$resize),
			]);
		}

		$commandOriginal[] = $inputPath;

		$commandAvif = null;
		if ($file->avifPathAbsolute && file_exists($file->avifPathAbsolute)) {
			$commandAvif = [
				'magick',
				$file->avifPathAbsolute,
			];

			if ($crop) {
				$commandAvif = array_merge($commandAvif, [
					'-crop',
					sprintf('%dx%d+%d+%d', ...$crop),
				]);
			}

			if ($resize) {
				$commandAvif = array_merge($commandAvif, [
					'-resize',
					sprintf('%dx%d', ...$resize),
				]);
			}

			$commandAvif[] = $file->avifPathAbsolute;
		}

		$commandWebp = null;
		if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
			$commandWebp = [
				'magick',
				$file->webpPathAbsolute,
			];

			if ($crop) {
				$commandWebp = array_merge($commandWebp, [
					'-crop',
					sprintf('%dx%d+%d+%d', ...$crop),
				]);
			}

			if ($resize) {
				$commandWebp = array_merge($commandWebp, [
					'-resize',
					sprintf('%dx%d', ...$resize),
				]);
			}

			$commandWebp[] = $file->webpPathAbsolute;
		}

		if (!$crop && !$resize) {
			$commandOriginal = $commandAvif = $commandWebp = null;
		}

		$this->debug('Built crop commands', [
			'mediaId' => $file->id,
			'hasCrop' => (bool)$crop,
			'hasResize' => (bool)$resize,
			'hasOriginal' => $commandOriginal !== null,
			'hasAvif' => $commandAvif !== null,
			'hasWebp' => $commandWebp !== null,
		]);

		return [
			'original' => $commandOriginal,
			'avif' => $commandAvif,
			'webp' => $commandWebp,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage $file
	 * @return array
	 */
	protected function getResizeCommand(MediaResizedImage $file): array {
		$inputPath = $file->media->isImage() ? $file->media->pathAbsolute : $file->media->previewPathAbsolute;

		$gravity = 'center';
		if ($file->media->focusPoint) {
			// The focus point is in the format "[0|1|2],[0|1|2]"
			$focusPoint = explode(',', $file->media->focusPoint);

			if (count($focusPoint) !== 2) {
				$focusPoint = [1, 1];
			}

			// Convert the focus point to a gravity value
			// Possible values should be "NorthWest", "North", "NorthEast", "West", "Center", "East", "SouthWest", "South", "SouthEast"
			$gravityValues = [
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

			$gravity = $gravityValues[ (int)$focusPoint[0] * 3 + (int)$focusPoint[1] ];
		}

		return match ($file->strategy) {
			ResizeStrategy::Contain => [
				'magick',
				$inputPath,
				'-resize',
				$file->width . 'x' . $file->height,
				'-quality',
				$this->quality,
				$file->pathAbsolute,
			],
			ResizeStrategy::Cover => [
				'magick',
				$inputPath,
				'-resize',
				$file->width . 'x' . $file->height . '^',
				'-quality',
				$this->quality,
				$file->pathAbsolute,
			],
			ResizeStrategy::Crop => [
				'magick',
				$inputPath,
				'-resize',
				$file->width . 'x' . $file->height . '^',
				'-gravity',
				$gravity,
				'-extent',
				$file->width . 'x' . $file->height,
				'-quality',
				$this->quality,
				$file->pathAbsolute,
			],
			ResizeStrategy::Stretch => [
				'magick',
				$inputPath,
				'-resize',
				$file->width . 'x' . $file->height . '!',
				'-quality',
				$this->quality,
				$file->pathAbsolute,
			],
		};
	}


	/**
	 * Return the real size of an image
	 * Uses `getimagesize()` if available, otherwise falls back to the identify command
	 *
	 * @param string $filePath
	 * @param \Cake\Console\ConsoleIo $io
	 * @return array
	 */
	protected function getRealImageSize(string $filePath, ConsoleIo $io): array {
		// Use GD lib's getimagesize() if available. This is faster than using ImageMagick
		if (function_exists('getimagesize')) {
			return getimagesize($filePath);
		}

		/**
		 * If the ImageMagick command is not available,
		 * use Intervention's width and height method to get the image size
		 */
		if (!$this->cliMagickExists) {
			try {
				$image = $this->imageManager->decodePath($filePath);
			}
			catch (Exception) {
				$io->error('Status: Cannot get image size');
				$io->hr();

				return [];
			}

			return [$image->width(), $image->height()];
		}

		$process = $this->getProcess([
			'magick',
			'identify',
			'-format',
			'%wx%h',
			$filePath,
		]);

		$result = $this->runProcess($process, $io);

		if (!$result) {
			return [];
		}

		return explode('x', $process->getOutput());
	}


	/**
	 * @param string $inputPath
	 * @return bool
	 */
	protected function autoRotateImageIntervention(string $inputPath): bool {
		try {
			$image = $this->imageManager->decodePath($inputPath);

			$image = $image->orient();

			$image->save($inputPath, quality: $this->quality, progressive: true);
		}
		catch (Exception) {
			return false;
		}

		return true;
	}


	/**
	 * @param \Symfony\Component\Process\Process $process
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function runProcess(Process $process, ConsoleIo $io): bool {
		$this->debug('Running process', ['command' => $process->getCommandLine()]);

		$process->run();

		if (!$process->isSuccessful()) {
			$this->debug('Process failed', [
				'exitCode' => $process->getExitCode(),
				'exitCodeText' => $process->getExitCodeText(),
				'stderr' => $process->getErrorOutput(),
			]);

			$io->error('Status: ' . $process->getExitCodeText());
			$io->out('Command: ' . str_replace('\' \'', ' ', $process->getCommandLine()));
			$io->out('Message: ' . $process->getErrorOutput(), 0);

			return false;
		}

		$io->success('Status: ' . $process->getExitCodeText());
		$this->debug('Process succeeded', [
			'exitCode' => $process->getExitCode(),
			'exitCodeText' => $process->getExitCodeText(),
		]);

		return true;
	}


	/**
	 * @param string $filePath
	 * @param array $crop
	 * @param array $resize
	 * @param string|null $outputPath
	 * @return \Intervention\Image\Interfaces\ImageInterface
	 * @throws \Intervention\Image\Exceptions\DriverException
	 * @throws \Intervention\Image\Exceptions\ImageDecoderException
	 * @throws \Intervention\Image\Exceptions\InvalidArgumentException
	 */
	protected function cropAndResizeIntervention(string $filePath, array $crop, array $resize, ?string $outputPath): ImageInterface {
		$image = $this->imageManager->decodePath($filePath);

		if ($crop) {
			$image->crop(...$crop);
		}

		if ($resize) {
			$image->scaleDown(...$resize);
		}

		$image->save($outputPath, quality: $this->quality, progressive: true);

		return $image;
	}
}
