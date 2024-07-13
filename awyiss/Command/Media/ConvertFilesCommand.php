<?php declare(strict_types=1);


namespace Awyiss\Command\Media;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ResultSetInterface;
use Symfony\Component\Process\Process;


/**
 * Fetches records from the media and tries to generate a preview image
 */
class ConvertFilesCommand extends Command {
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

			$lo_files = $this->fetchCropFiles((int)$args->getOption('limit'));

			if ($lo_files->count()) {
				$li_totalFiles += $lo_files->count();

				$li_result = $this->cropImages($lo_files, $io);
				if ($li_result !== static::CODE_SUCCESS) {
					$lb_errorOccurred = true;
					break;
				}
			}

			$lo_files = $this->fetchNonImageFiles(
				(int)$args->getOption('limit'),
				$args->getOption('retry-failed'),
				$args->getOption('include-webp')
			);

			if ($lo_files->count()) {
				$li_totalFiles += $lo_files->count();

				$li_result = $this->convertNonImages($lo_files, $io, $args->getOption('include-webp'));
				if ($li_result !== static::CODE_SUCCESS) {
					$lb_errorOccurred = true;
					break;
				}
			}

			$lo_files = $this->fetchFilesForWebpConversion((int)$args->getOption('limit'), $args->getOption('retry-failed'));

			if ($lo_files->count()) {
				$li_totalFiles += $lo_files->count();

				$li_result = $this->convertImages($lo_files, $io);
				if ($li_result !== static::CODE_SUCCESS) {
					$lb_errorOccurred = true;
					break;
				}
			}

			$lo_files = $this->fetchFilesForResizing((int)$args->getOption('limit'), $args->getOption('retry-failed'));

			if ($lo_files->count()) {
				$li_totalFiles += $lo_files->count();

				$li_result = $this->resizeImages($lo_files, $io);
				if ($li_result !== static::CODE_SUCCESS) {
					$lb_errorOccurred = true;
					break;
				}
			}

			$lo_files = $this->fetchFilesForAverageColorCalculation((int)$args->getOption('limit'));

			if ($lo_files->count()) {
				$li_totalFiles += $lo_files->count();

				$li_result = $this->calculateAverageColors($lo_files, $io);
				if ($li_result !== static::CODE_SUCCESS) {
					$lb_errorOccurred = true;
					break;
				}
			}

			// If the script is running in quiet mode, break the loop if no files were found
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
	 * Calculate the average color of the images
	 *
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function calculateAverageColors(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
			$ls_path = $lo_file->isImage() ? $lo_file->pathAbsolute : $lo_file->previewPathAbsolute;

			$io->out(sprintf('Calculating average color for file `%s`', $lo_file->path));

			if (!file_exists($ls_path)) {
				$io->error('Status: File does not exist');

				// If the file does not exist or is a png, set the average color to a fully transparent black
				$lo_file->averageColor = '00000000';
				continue;
			}

			if ($lo_file->mimeType === 'image/png') {
				$io->info('Status: Cannot calculate average color for png files');

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

		/**
		 * If all files have the same webp status, use a simple updateAll command
		 */
		$lo_table->updateAll(function (QueryExpression $expression) use ($files) {
			$lo_averageColorCases = $expression->case();

			/** @var \Awyiss\Model\Entity\Media $lo_file */
			foreach ($files as $lo_file) {
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
		if (function_exists('imagecreatefromstring')) {
			return $this->calculateAverageColorGD($path);
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
		$lo_process = new Process([
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
	protected function convertImages(ResultSetInterface $files, ConsoleIo $io): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
			$io->out(sprintf('Creating webp image for file `%s`', $lo_file->path));

			$lo_process = $this->convertToWebp($lo_file);

			if ($lo_process === false) {
				$io->error('Status: Cannot create webp file');
			}
			elseif ($lo_process->isSuccessful()) {
				$io->success('Status: ' . $lo_process->getExitCodeText());
				$lo_file->webp = ProcessStatus::Success;
			}
			else {
				$io->error('Status: ' . $lo_process->getExitCodeText());
				$io->out('Command: ' . $lo_process->getCommandLine());
				$io->out('Message: ' . $lo_process->getErrorOutput(), 0);

				$lo_file->webp = ProcessStatus::Fail;
			}

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
			$lo_table->updateAll(function (QueryExpression $expression) use ($files) {
				$lo_webpCases = $expression->case();

				/** @var \Awyiss\Model\Entity\Media $lo_file */
				foreach ($files as $lo_file) {
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
	 * @param \Cake\Datasource\ResultSetInterface $files
	 * @param \Cake\Console\ConsoleIo $io
	 * @param bool $includeWebp
	 * @return int
	 */
	protected function convertNonImages(ResultSetInterface $files, ConsoleIo $io, bool $includeWebp): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($files as $lo_file) {
			$io->out(sprintf('Creating preview for file `%s`', $lo_file->path));

			$ls_previewPathAbsolute = $lo_file->previewPathAbsolute;
			if (!$ls_previewPathAbsolute) {
				$io->error('Status: Cannot convert file without a path');
				$io->hr();

				continue;
			}

			if (!file_exists(dirname($ls_previewPathAbsolute))) {
				mkdir(dirname($ls_previewPathAbsolute));
			}

			$la_command = $this->getCommand($lo_file, 'preview');

			if (!$la_command) {
				$io->warning(sprintf('Status: Cannot convert filetype `%s`', $lo_file->extension));
				$io->hr();

				$lo_file->preview = ProcessStatus::Fail;
				$lo_file->webp = ProcessStatus::Fail;

				continue;
			}

			$lo_process = new Process($la_command);
			$lo_process->run();
			if ($lo_process->isSuccessful()) {
				$io->success('Status: ' . $lo_process->getExitCodeText());

				$la_imageSize = $this->getRealImageSize($ls_previewPathAbsolute);

				$lo_file->width = $la_imageSize[0] ?? null;
				$lo_file->height = $la_imageSize[1] ?? null;
				$lo_file->preview = ProcessStatus::Success;

				if ($includeWebp) {
					$io->out(sprintf('Creating WebP file for file `%s`', $lo_file->path));

					$lo_webpStatusProcess = $this->convertToWebp($lo_file);

					if ($lo_webpStatusProcess === false) {
						$io->error('Status: Cannot create webp file');
					}
					elseif ($lo_webpStatusProcess->isSuccessful()) {
						$io->success('Status: ' . $lo_webpStatusProcess->getExitCodeText());
						$lo_file->webp = ProcessStatus::Success;
					}
					else {
						$io->error('Status: ' . $lo_webpStatusProcess->getExitCodeText());
						$lo_file->webp = ProcessStatus::Fail;
					}
				}
			}
			else {
				$io->error('Status: ' . $lo_process->getExitCodeText());
				$io->out('Command: ' . $lo_process->getCommandLine());
				$io->out('Message: ' . $lo_process->getErrorOutput(), 0);

				$lo_file->preview = ProcessStatus::Fail;
				$lo_file->webp = ProcessStatus::Undefined;
			}

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
			$lo_table->updateAll(function (QueryExpression $expression) use ($files, $includeWebp) {
				$lo_widthCases = $expression->case();
				$lo_heightCases = $expression->case();
				$lo_previewCases = $expression->case();

				if ($includeWebp) {
					$lo_webpCases = $expression->case();
				}

				/** @var \Awyiss\Model\Entity\Media $lo_file */
				foreach ($files as $lo_file) {
					$lo_widthCases->when(['id = ' . $lo_file->id])->then($lo_file->width, 'float');
					$lo_heightCases->when(['id = ' . $lo_file->id])->then($lo_file->height, 'float');
					$lo_previewCases->when(['id = ' . $lo_file->id])->then($lo_file->preview->value, 'integer');

					if ($includeWebp) {
						$lo_webpCases->when(['id = ' . $lo_file->id])->then($lo_file->webp->value, 'integer');
					}
				}

				$la_cases = [
					'width' => $lo_widthCases,
					'height' => $lo_heightCases,
					'preview' => $lo_previewCases,
				];

				if ($includeWebp) {
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
	 * @return \Symfony\Component\Process\Process|false
	 */
	protected function convertToWebp(Media $file): Process|false {
		$ls_webpPathAbsolute = $file->webpPathAbsolute;

		if (!$ls_webpPathAbsolute) {
			return false;
		}

		if (!file_exists(dirname($ls_webpPathAbsolute))) {
			mkdir(dirname($ls_webpPathAbsolute));
		}

		$lo_process = new Process($this->getCommand($file, 'webp'));
		$lo_process->run();

		return $lo_process;
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

			$la_commands = $this->getCommand($lo_file, 'crop');

			$lo_process = new Process($la_commands['original']);
			$lo_process->run();

			if ($lo_process->isSuccessful()) {
				$io->success('Status: ' . $lo_process->getExitCodeText());

				// If there's a webp command, run it and crop the webp file as well
				if ($la_commands['webp']) {
					$lo_process = new Process($la_commands['webp']);
					$lo_process->run();
				}

				$la_imageSize = $this->getRealImageSize($lo_file->isImage() ? $lo_file->pathAbsolute : $lo_file->previewPathAbsolute);

				$lo_file->width = $la_imageSize[0] ?? null;
				$lo_file->height = $la_imageSize[1] ?? null;

				if (!empty($lo_file->crop['resize_width']) || !empty($lo_file->crop['resize_height'])) {
					// Delete all resized files. They will be recreated when needed.
					// Previously set sizes might no longer be required OR even too large
					$lo_file->deleteResizedFiles();
				}

				$lo_file->crop = null;
			}
			else {
				$io->error('Status: ' . $lo_process->getExitCodeText());
				$io->out('Command: ' . $lo_process->getCommandLine());
				$io->out('Message: ' . $lo_process->getErrorOutput(), 0);
			}

			$io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$lo_table->updateAll(function (QueryExpression $expression) use ($files) {
			$lo_widthCases = $expression->case();
			$lo_heightCases = $expression->case();
			$lo_cropCases = $expression->case();

			/** @var \Awyiss\Model\Entity\Media $lo_file */
			foreach ($files as $lo_file) {
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

			if (!$lo_file->media->isImage() && $lo_file->media->preview === ProcessStatus::Fail) {
				$io->error('Status: Cannot resize non-image file without a preview');
				$io->hr();

				$lo_file->status = ProcessStatus::Fail;
				continue;
			}

			if (!file_exists(dirname($lo_file->pathAbsolute))) {
				mkdir(dirname($lo_file->pathAbsolute));
			}

			$lo_process = new Process($this->getCommand($lo_file, 'resize'));
			$lo_process->run();

			if ($lo_process->isSuccessful()) {
				$io->success('Status: ' . $lo_process->getExitCodeText());

				$la_imageSize = $this->getRealImageSize($lo_file->pathAbsolute);

				$lo_file->realWidth = $la_imageSize[0] ?? null;
				$lo_file->realHeight = $la_imageSize[1] ?? null;
				$lo_file->status = ProcessStatus::Success;
			}
			else {
				$io->error('Status: ' . $lo_process->getExitCodeText());
				$io->out('Command: ' . $lo_process->getCommandLine());
				$io->out('Message: ' . $lo_process->getErrorOutput(), 0);

				$lo_file->status = ProcessStatus::Fail;
			}

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
			$lo_table->updateAll(function (QueryExpression $expression) use ($files) {
				$lo_realWidthCases = $expression->case();
				$lo_realHeightCases = $expression->case();
				$lo_statusCases = $expression->case();

				/** @var \Awyiss\Model\Entity\MediaResizedImage $lo_file */
				foreach ($files as $lo_file) {
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
	 * @param int $limit
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	public function fetchCropFiles(int $limit): ResultSetInterface {
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
	 * @param \Awyiss\Model\Entity\Media|\Awyiss\Model\Entity\MediaResizedImage $file
	 * @param string $outputFilePath
	 * @return array<int, string>|false
	 */
	protected function getCommand(Media|MediaResizedImage $file, string $type): array|false {
		if ($type === 'preview') {
			return $this->getPreviewCommand($file);
		}
		elseif ($type === 'webp') {
			return $this->getWebPCommand($file);
		}
		elseif ($type === 'crop') {
			return $this->getCropCommand($file);
		}
		elseif ($type === 'resize') {
			return $this->getResizeCommand($file);
		}

		return false;
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage|\Awyiss\Model\Entity\Media $file
	 * @return array|false
	 */
	protected function getPreviewCommand(MediaResizedImage|Media $file): array|false {
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
	 * @param \Awyiss\Model\Entity\MediaResizedImage|\Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getWebPCommand(MediaResizedImage|Media $file): array {
		$ls_inputPath = $file->pathAbsolute;

		if (!$file->isImage()) {
			$ls_inputPath = $file->previewPathAbsolute;
		}

		return [
			'convert',
			$ls_inputPath,
			$file->webpPathAbsolute,
		];
	}


	/**
	 * @param \Awyiss\Model\Entity\MediaResizedImage|\Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getCropCommand(MediaResizedImage|Media $file): array {
		$ls_inputPath = $file->pathAbsolute;
		$lb_crop = false;
		$lb_resize = false;

		if ($file instanceof Media && !$file->isImage()) {
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

		if ($file->width !== (int)$file->crop['width'] || $file->height !== (int)$file->crop['height']) {
			$lb_crop = true;
			$la_commandOriginal = array_merge($la_commandOriginal, [
				'-crop',
				sprintf('%dx%d+%d+%d', $file->crop['width'], $file->crop['height'], $file->crop['x'], $file->crop['y']),
			]);
		}

		if ($file->crop['width'] !== $file->crop['resize_width'] || $file->crop['height'] !== $file->crop['resize_height']) {
			$lb_resize = true;
			$la_commandOriginal = array_merge($la_commandOriginal, [
				'-resize',
				sprintf('%dx%d', $file->crop['resize_width'], $file->crop['resize_height']),
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
	 * @param \Awyiss\Model\Entity\MediaResizedImage|\Awyiss\Model\Entity\Media $file
	 * @return array
	 */
	protected function getResizeCommand(MediaResizedImage|Media $file): array {
		$ls_inputPath = $file->media->pathAbsolute;

		if (!$file->media->isImage()) {
			$ls_inputPath = $file->media->previewPathAbsolute;
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
				'center',
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

		$lo_process = new Process([
			'identify',
			'-format',
			'%wx%h',
			$imagePath,
		]);

		$lo_process->run();

		return explode('x', $lo_process->getOutput());
	}
}
