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
use Exception;
use Imagick;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Symfony\Component\Process\Process;


/**
 * Fetches records from the media and tries to generate a preview image
 */
class ConvertFilesCommand extends Command {
	/**
	 * An instance of the Intervention ImageManager,
	 * using the configured driver (gd or imagick)
	 *
	 * @var \Intervention\Image\ImageManager
	 */
	protected ImageManager $imageManager;
	/**
	 * Whether to use the ImageMagick commands
	 * for image manipulation.
	 *
	 * @var bool
	 */
	protected bool $cliMagickExists = false;
	/**
	 * The quality of the generated images.
	 * For Avif files, the quality can be lower while
	 * getting a similar or even better result.
	 *
	 * @var int
	 */
	protected int $quality;


	/**
	 * @inheritDoc
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

		$ls_driver = Configure::read('Awyiss.Media.Frontend.resizing.driver', 'imagick');
		$this->imageManager = $ls_driver === 'gd' ? ImageManager::gd(autoOrientation: false) : ImageManager::imagick(autoOrientation: false);

		$this->quality = Configure::read('Awyiss.Media.Frontend.resizing.quality', 70);
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($parser);

		$lo_parser->addOption('include-avif', [
			'boolean' => true,
			'help' => 'Include the creation of Avif files after converting non-images to jpgs.',
			'short' => 'w',
		]);

		$lo_parser->addOption('include-webp', [
			'boolean' => true,
			'help' => 'Include the creation of WebP files after converting non-images to jpgs.',
			'short' => 'w',
		]);

		$lo_parser->addOption('limit', [
			// If ImageMagick is available via command line, set the default to 20
			// otherwise set it to 5 to avoid potential memory issues
			'default' => $this->cliMagickExists ? '20' : '5',
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

		$ls_driver = Configure::read('Awyiss.Media.Frontend.resizing.driver', 'imagick');
		$io->out(sprintf('Starting media processing using %s with %u%% quality...', $this->cliMagickExists ? 'ImageMagick (CLI)' : 'Intervention Image (' . $ls_driver . ')', $this->quality));

		// Keep this job running for 60 seconds to process as many files as possible
		while (time() - $li_startTime < 60) {
			$li_totalFiles = 0;

			$la_processMethods = [
				'processCropFiles',
				'processNonImageFiles',
				'processAvifConversion',
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
				$io->out(sprintf('Finished processing %u files.', $li_totalFiles));

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
			$args->getOption('include-avif'),
			$args->getOption('include-webp')
		);

		if ($lo_files->count()) {
			$li_result = $this->convertNonImages($lo_files, $io, $args->getOption('include-avif'), $args->getOption('include-webp'));
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
	public function processAvifConversion(Arguments $args, ConsoleIo $io): int|false {
		$lo_files = $this->fetchFilesForAvifConversion((int)$args->getOption('limit'), $args->getOption('retry-failed'));

		if ($lo_files->count()) {
			$li_result = $this->convertImagesToAvif($lo_files, $io);
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

			$la_colors = $this->calculateAverageColor($ls_path, $io);

			if (!$la_colors) {
				$io->error('Status: Cannot calculate average color for file');
				$io->hr();

				$lo_file->averageColor = '00000000';
				continue;
			}

			// If alpha is fully transparent, set it to FF
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
	 * @param \Cake\Console\ConsoleIo $io
	 * @return array|false
	 */
	protected function calculateAverageColor(string $path, ConsoleIo $io): array|false {
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
		$lo_process = $this->getProcess([
			'magick',
			$path,
			'-resize',
			'1x1!',
			'-format',
			'%[fx:int(255*r+.5)],%[fx:int(255*g+.5)],%[fx:int(255*b+.5)]',
			'info:-',
		]);

		$lb_process = $this->runProcess($lo_process, $io);

		if (!$lb_process) {
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
	 * Calculate the average color of an image
	 * using the Intervention Image library
	 *
	 * @param string $filePath
	 * @param \Cake\Console\ConsoleIo $io
	 * @return array|false
	 */
	protected function calculateAverageColorIntervention(string $filePath, ConsoleIo $io): array|false {
		try {
			$lo_image = $this->imageManager->read($filePath);

			// Resize the image to 1x1 pixel
			$lo_color = $lo_image->resize(1, 1)->pickColor(0, 0);
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());

			return false;
		}

		$la_colors = $lo_color->toArray();

		return [
			'red' => $la_colors[0],
			'green' => $la_colors[1],
			'blue' => $la_colors[2],
			'alpha' => 255,
		];
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function convertImagesToAvif(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
			$this->convertImageToAvif($lo_file, $io);
			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$la_avifStatus = array_unique($files->extract('avif')->toArray(), SORT_REGULAR);
		if (count($la_avifStatus) === 1) {
			/**
			 * If all files have the same avif status, use a simple updateAll command
			 */
			$lo_table->updateAll([
				'avif' => $la_avifStatus[0],
			], [
				'id IN' => $files->extract('id')->toArray(),
			]);
		}
		else {
			$lo_files = $files;
			$lo_table->updateAll(function (QueryExpression $expression) use ($lo_files) {
				$lo_avifCases = $expression->case();

				/** @var \Awyiss\Model\Entity\Media $lo_file */
				foreach ($lo_files as $lo_file) {
					$lo_avifCases->when(['id = ' . $lo_file->id])->then($lo_file->avif->value, 'integer');
				}

				return [
					'avif' => $lo_avifCases,
				];
			}, [
				'id IN' => $files->extract('id')->toArray(),
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
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
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

		// If magick is not available or cannot convert web, use the Intervention library
		if (
			!$this->cliMagickExists ||
			Configure::read('AvailableCommands.imageMagick.avif', false) === false
		) {
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

		// If magick is not available or cannot convert web, use the Intervention library
		if (
			!$this->cliMagickExists ||
			Configure::read('AvailableCommands.imageMagick.webp', false) === false
		) {
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
		$ls_inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		try {
			$lo_image = $this->imageManager->read($ls_inputPath);

			$lo_image->toAvif($this->quality)->save($file->avifPathAbsolute);
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
		$ls_inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		try {
			$lo_image = $this->imageManager->read($ls_inputPath);

			$lo_image->toWebp($this->quality)->save($file->webpPathAbsolute);
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
		$lo_process = $this->getProcess($this->getAvifCommand($file));

		$lb_process = $this->runProcess($lo_process, $io);

		$file->avif = $lb_process ? ProcessStatus::Success : ProcessStatus::Fail;

		return $lb_process;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function convertImageToWebpCli(Media $file, ConsoleIo $io): bool {
		$lo_process = $this->getProcess($this->getWebPCommand($file));

		$lb_process = $this->runProcess($lo_process, $io);

		$file->webp = $lb_process ? ProcessStatus::Success : ProcessStatus::Fail;

		return $lb_process;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeAvif
	 * @param bool $includeWebp
	 * @return int
	 */
	protected function convertNonImages(ResultSetInterface $files, ConsoleIo $io, bool $includeAvif, bool $includeWebp): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
			$this->convertNonImage($lo_file, $io, $includeAvif, $includeWebp);

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$la_previewStatus = array_unique($files->extract('preview')->toArray(), SORT_REGULAR);
		/**
		 * If all files have the same preview status "failed", use a simple updateAll command
		 * This also means no preview was created, even though it was requested.
		 * Set the avif and webp status to undefined in this case.
		 */
		if (count($la_previewStatus) === 1 && $la_previewStatus[0] === ProcessStatus::Fail) {
			$lo_table->updateAll([
				'preview' => $la_previewStatus[0],
				'avif' => ProcessStatus::Undefined,
				'webp' => ProcessStatus::Undefined,
			], [
				'id IN' => $files->extract('id')->toArray(),
			]);
		}
		else {
			$lo_files = $files;
			$lb_includeAvif = $includeAvif;
			$lb_includeWebp = $includeWebp;

			$lo_table->updateAll(function (QueryExpression $expression) use ($lo_files, $lb_includeAvif, $lb_includeWebp) {
				$lo_widthCases = $expression->case();
				$lo_heightCases = $expression->case();
				$lo_previewCases = $expression->case();

				if ($lb_includeAvif) {
					$lo_avifCases = $expression->case();
				}

				if ($lb_includeWebp) {
					$lo_webpCases = $expression->case();
				}

				/** @var \Awyiss\Model\Entity\Media $lo_file */
				foreach ($lo_files as $lo_file) {
					$lo_widthCases->when(['id = ' . $lo_file->id])->then($lo_file->width, 'float');
					$lo_heightCases->when(['id = ' . $lo_file->id])->then($lo_file->height, 'float');
					$lo_previewCases->when(['id = ' . $lo_file->id])->then($lo_file->preview->value, 'integer');

					if ($lb_includeAvif) {
						$lo_avifCases->when(['id = ' . $lo_file->id])->then($lo_file->avif->value, 'integer');
					}

					if ($lb_includeWebp) {
						$lo_webpCases->when(['id = ' . $lo_file->id])->then($lo_file->webp->value, 'integer');
					}
				}

				$la_cases = [
					'width' => $lo_widthCases,
					'height' => $lo_heightCases,
					'preview' => $lo_previewCases,
				];

				if ($lb_includeAvif) {
					$la_cases['avif'] = $lo_avifCases;
				}

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
	 * @param bool $includeAvif
	 * @param bool $includeWebp
	 * @return bool
	 */
	protected function convertNonImage(Media $file, ConsoleIo $io, bool $includeAvif, bool $includeWebp): bool {
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
			!$this->cliMagickExists ||
			Configure::read('AvailableCommands.imageMagick.' . $file->extension, false) === false
		) {
			return $this->convertNonImageIntervention($file, $io, $includeAvif, $includeWebp);
		}

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
				!class_exists('Imagick') ||
				!in_array($file->extension, ['pdf', 'eps', 'ai', 'psd'], true)
			) {
				throw new Exception(sprintf('Cannot convert filetype `%s`', $file->extension));
			}

			$lo_image = new Imagick();
			$lo_image->setResolution(150, 150);
			$lo_image->readImage($file->pathAbsolute . '[' . 0 . ']');
			$lo_image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
			$lo_image->stripImage();
			$lo_image->setImageColorspace(Imagick::COLORSPACE_SRGB);
			$lo_image->setImageBackgroundColor('#FFFFFF');

			$lo_image = $lo_image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

			$lo_image->setImageFormat('jpeg');
			$lo_image->setCompression(Imagick::COMPRESSION_JPEG);

			// Preview files are always saved as JPEG and in a higher quality
			$lo_image->setCompressionQuality(95);

			if (!$lo_image->writeImage($file->previewPathAbsolute)) {
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
		$la_imageSize = $this->getRealImageSize($file->previewPathAbsolute, $io);

		$file->width = $la_imageSize[0] ?? null;
		$file->height = $la_imageSize[1] ?? null;
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
		$la_command = $this->getPreviewCommand($file);

		if (!$la_command) {
			$io->warning(sprintf('Status: Cannot convert filetype `%s`', $file->extension));

			$file->preview = ProcessStatus::Fail;
			$file->avif = ProcessStatus::Fail;
			$file->webp = ProcessStatus::Fail;

			return false;
		}

		$lo_process = $this->getProcess($la_command);

		$lb_process = $this->runProcess($lo_process, $io);

		if (!$lb_process) {
			$file->preview = ProcessStatus::Fail;
			$file->avif = ProcessStatus::Undefined;
			$file->webp = ProcessStatus::Undefined;

			return false;
		}

		/** @noinspection DuplicatedCode */
		$la_imageSize = $this->getRealImageSize($file->previewPathAbsolute, $io);

		$file->width = $la_imageSize[0] ?? null;
		$file->height = $la_imageSize[1] ?? null;
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
		if (
			!$this->cliMagickExists ||
			Configure::read('AvailableCommands.imageMagick.' . $file->extension, false) === false
		) {
			$lb_cropped = $this->cropImageIntervention($file, $io);
		}
		else {
			$lb_cropped = $this->cropImageCli($file, $io);
		}

		if (!$lb_cropped) {
			return false;
		}

		if (!empty($file->crop['resize_width']) || !empty($file->crop['resize_height'])) {
			// Delete all resized files. They will be recreated when needed.
			// Previously set sizes might no longer be required OR even too large
			$file->deleteResizedFiles();
		}

		$la_imageSize = $this->getRealImageSize($file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute, $io);

		$file->width = $la_imageSize[0] ?? null;
		$file->height = $la_imageSize[1] ?? null;
		$file->crop = null;

		return true;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @param \Cake\Console\ConsoleIo $io
	 * @return bool
	 */
	protected function cropImageIntervention(Media $file, ConsoleIo $io): bool {
		$ls_inputPath = $file->pathAbsolute;
		$la_crop = [];
		$la_resize = [];

		if (!$file->isImage()) {
			$ls_inputPath = $file->previewPathAbsolute;
		}

		if (isset($file->crop['rotate']) && $file->crop['rotate'] === 'auto') {
			if (!$this->autoRotateImageIntervention($ls_inputPath)) {
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
			$lo_image = $this->imageManager->read($ls_inputPath);

			if ($file->width !== (float)$file->crop['width'] || $file->height !== (float)$file->crop['height']) {
				$la_crop = [(int)$file->crop['width'], (int)$file->crop['height'], (int)$file->crop['x'], (int)$file->crop['y']];

				$lo_image->crop(...$la_crop);
			}

			if ((float)$file->crop['width'] !== (float)$file->crop['resize_width'] || (float)$file->crop['height'] !== (float)$file->crop['resize_height']) {
				$la_resize = [(int)$file->crop['resize_width'], (int)$file->crop['resize_height']];

				$lo_image->scaleDown(...$la_resize);
			}

			if (!$la_crop && !$la_resize) {
				$lo_image = null; //phpcs:ignore
				return true;
			}

			$lo_image->save($ls_inputPath, quality: $this->quality, progressive: true);
		}
		catch (Exception $ex) {
			$io->error('Status: ' . $ex->getMessage());

			return false;
		}

		// If an avif file exists, crop it as well
		if ($file->avifPathAbsolute && file_exists($file->avifPathAbsolute)) {
			try {
				$this->cropAndResizeIntervention($file->avifPathAbsolute, $la_crop, $la_resize, $ls_inputPath);
			}
			catch (Exception) {
				// Ignore the exception for avif cropping
			}
		}

		// If a webp file exists, crop it as well
		if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
			try {
				$this->cropAndResizeIntervention($file->webpPathAbsolute, $la_crop, $la_resize, $ls_inputPath);
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
		$la_commands = $this->getCropCommand($file);

		$lo_process = $this->getProcess($la_commands['original']);

		$lb_process = $this->runProcess($lo_process, $io);

		if (!$lb_process) {
			return false;
		}

		// If there's an avif command, run it and crop the avif file as well
		if ($la_commands['avif']) {
			$lo_process = $this->getProcess($la_commands['avif']);
			$lo_process->run();
		}

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
			!$this->cliMagickExists ||
			Configure::read('AvailableCommands.imageMagick.' . $file->extension, false) === false
		) {
			$lb_resized = $this->resizeImageIntervention($file, $io);
		}
		else {
			$lb_resized = $this->resizeImageCli($file, $io);
		}

		if (!$lb_resized) {
			$file->status = ProcessStatus::Fail;

			return false;
		}

		$la_imageSize = $this->getRealImageSize($file->pathAbsolute, $io);

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
	protected function resizeImageIntervention(MediaResizedImage $file, ConsoleIo $io): bool {
		try {
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
					// The focus point is in the format "[0|1|2],[0|1|2]"
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

			$lo_image->save($file->pathAbsolute, quality: $this->quality, progressive: true);
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
		$lo_process = $this->getProcess($this->getResizeCommand($file));

		return $this->runProcess($lo_process, $io);
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
	 * @param bool $includeAvif
	 * @param bool $includeWebp
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchNonImageFiles(int $limit, bool $retryFailed, bool $includeAvif, bool $includeWebp): ResultSetInterface {
		$la_where = [
			'preview' => ProcessStatus::Undefined,
		];

		if ($retryFailed) {
			$la_where['preview'] = ProcessStatus::Fail;
		}

		$la_processStatusColumns = [
			'preview',
		];

		if ($includeAvif) {
			$la_processStatusColumns[] = 'avif';
		}

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
	protected function fetchFilesForAvifConversion(int $limit, bool $retryFailed): ResultSetInterface {
		$la_where = [
			'avif' => ProcessStatus::Undefined,
			'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
		];

		if ($retryFailed) {
			$la_where['avif'] = ProcessStatus::Fail;
		}

		return $this->fetchFiles($la_where, $limit, ['avif']);
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

			return [
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

		if (!Configure::read('AvailableCommands.imageMagick.' . $file->extension)) {
			return false;
		}

		$ls_inputPath = $file->path;
		if (in_array($file->extension, ['pdf', 'psd'])) {
			$ls_inputPath .= '[0]';
		}

		return [
			'magick',
			'-density',
			150,
			WWW_ROOT . str_replace('/', DS, $ls_inputPath),
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
		$ls_inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		return [
			'magick',
			$ls_inputPath,
			$file->avifPathAbsolute,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getWebPCommand(Media $file): array {
		$ls_inputPath = $file->isImage() ? $file->pathAbsolute : $file->previewPathAbsolute;

		return [
			'magick',
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
		$la_crop = [];
		$la_resize = [];

		if (!$file->isImage()) {
			$ls_inputPath = $file->previewPathAbsolute;
		}

		if (isset($file->crop['rotate']) && $file->crop['rotate'] === 'auto') {
			$la_commandAvif = null;
			if ($file->avifPathAbsolute && file_exists($file->avifPathAbsolute)) {
				$la_commandAvif = [
					'magick',
					'mogrify',
					'-auto-orient',
					$file->avifPathAbsolute,
				];
			}

			$la_commandWebp = null;
			if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
				$la_commandWebp = [
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
					$ls_inputPath,
				],
				'avif' => $la_commandAvif,
				'webp' => $la_commandWebp,
			];
		}

		$la_commandOriginal = [
			'magick',
			$ls_inputPath,
		];

		if ($file->width !== (float)$file->crop['width'] || $file->height !== (float)$file->crop['height']) {
			$la_crop = [(int)$file->crop['width'], (int)$file->crop['height'], (int)$file->crop['x'], (int)$file->crop['y']];
			$la_commandOriginal = array_merge($la_commandOriginal, [
				'-crop',
				sprintf('%dx%d+%d+%d', ...$la_crop),
			]);
		}

		if ((float)$file->crop['width'] !== (float)$file->crop['resize_width'] || (float)$file->crop['height'] !== (float)$file->crop['resize_height']) {
			$la_resize = [(int)$file->crop['resize_width'], (int)$file->crop['resize_height']];
			$la_commandOriginal = array_merge($la_commandOriginal, [
				'-resize',
				sprintf('%dx%d', ...$la_resize),
			]);
		}

		$la_commandOriginal[] = $ls_inputPath;

		$la_commandAvif = null;
		if ($file->avifPathAbsolute && file_exists($file->avifPathAbsolute)) {
			$la_commandAvif = [
				'magick',
				$file->avifPathAbsolute,
			];

			if ($la_crop) {
				$la_commandAvif = array_merge($la_commandAvif, [
					'-crop',
					sprintf('%dx%d+%d+%d', ...$la_crop),
				]);
			}

			if ($la_resize) {
				$la_commandAvif = array_merge($la_commandAvif, [
					'-resize',
					sprintf('%dx%d', ...$la_resize),
				]);
			}

			$la_commandAvif[] = $file->avifPathAbsolute;
		}

		$la_commandWebp = null;
		if ($file->webpPathAbsolute && file_exists($file->webpPathAbsolute)) {
			$la_commandWebp = [
				'magick',
				$file->webpPathAbsolute,
			];

			if ($la_crop) {
				$la_commandWebp = array_merge($la_commandWebp, [
					'-crop',
					sprintf('%dx%d+%d+%d', ...$la_crop),
				]);
			}

			if ($la_resize) {
				$la_commandWebp = array_merge($la_commandWebp, [
					'-resize',
					sprintf('%dx%d', ...$la_resize),
				]);
			}

			$la_commandWebp[] = $file->webpPathAbsolute;
		}

		if (!$la_crop && !$la_resize) {
			$la_commandOriginal = $la_commandAvif = $la_commandWebp = null;
		}

		return [
			'original' => $la_commandOriginal,
			'avif' => $la_commandAvif,
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
			// The focus point is in the format "[0|1|2],[0|1|2]"
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
				'magick',
				$ls_inputPath,
				'-resize',
				$file->width . 'x' . $file->height,
				$file->pathAbsolute,
			],
			ResizeStrategy::Cover => [
				'magick',
				$ls_inputPath,
				'-resize',
				$file->width . 'x' . $file->height . '^',
				$file->pathAbsolute,
			],
			ResizeStrategy::Crop => [
				'magick',
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
				'magick',
				$ls_inputPath,
				'-resize',
				$file->width . 'x' . $file->height . '!',
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
				$lo_image = $this->imageManager->read($filePath);
			}
			catch (Exception) {
				$io->error('Status: Cannot get image size');
				$io->hr();

				return [];
			}

			return [$lo_image->width(), $lo_image->height()];
		}

		$lo_process = $this->getProcess([
			'magick',
			'identify',
			'-format',
			'%wx%h',
			$filePath,
		]);

		$lb_process = $this->runProcess($lo_process, $io);

		if (!$lb_process) {
			return [];
		}

		return explode('x', $lo_process->getOutput());
	}


	/**
	 * @param string $inputPath
	 * @return bool
	 */
	protected function autoRotateImageIntervention(string $inputPath): bool {
		try {
			$lo_image = $this->imageManager->read($inputPath);

			$lo_image = $lo_image->orient();

			$lo_image->save($inputPath, quality: $this->quality, progressive: true);
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
		$process->run();

		if (!$process->isSuccessful()) {
			$io->error('Status: ' . $process->getExitCodeText());
			$io->out('Command: ' . str_replace('\' \'', ' ', $process->getCommandLine()));
			$io->out('Message: ' . $process->getErrorOutput(), 0);

			return false;
		}

		$io->success('Status: ' . $process->getExitCodeText());

		return true;
	}


	/**
	 * @param string $filePath
	 * @param array $crop
	 * @param array $resize
	 * @param string|null $outputPath
	 * @return \Intervention\Image\Interfaces\ImageInterface
	 */
	protected function cropAndResizeIntervention(string $filePath, array $crop, array $resize, ?string $outputPath): ImageInterface {
		$lo_image = $this->imageManager->read($filePath);

		if ($crop) {
			$lo_image->crop(...$crop);
		}

		if ($resize) {
			$lo_image->scaleDown(...$resize);
		}

		$lo_image->save($outputPath, quality: $this->quality, progressive: true);

		return $lo_image;
	}
}
