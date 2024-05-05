<?php declare(strict_types=1);


namespace Awyiss\Command\Media;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Enum\ProcessStatus;
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
	 * @param \Cake\Console\ConsoleOptionParser $ao_parser
	 * @return \Cake\Console\ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

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
	 * @param \Cake\Console\Arguments $ao_args
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @return int
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function execute(Arguments $ao_args, ConsoleIo $ao_io): int {
		$li_startTime = time();
		$lb_errorOccurred = false;

		// Keep this job running for 60 seconds to process as many files as possible
		while (time() - $li_startTime < 60) {
			$lo_files = $this->fetchNonImageFiles(
				(int)$ao_args->getOption('limit'),
				$ao_args->getOption('retry-failed'),
				$ao_args->getOption('include-webp')
			);

			if ($lo_files->count()) {
				$li_result = $this->convertNonImages($lo_files, $ao_io, $ao_args->getOption('include-webp'));
				if ($li_result !== static::CODE_SUCCESS) {
					$lb_errorOccurred = true;
				}
			}

			$lo_files = $this->fetchFilesForWebpConversion((int)$ao_args->getOption('limit'), $ao_args->getOption('retry-failed'));

			if ($lo_files->count()) {
				$li_result = $this->convertImages($lo_files, $ao_io);
				if ($li_result !== static::CODE_SUCCESS) {
					$lb_errorOccurred = true;
				}
			}

			$lo_files = $this->fetchFilesForAverageColorCalculation((int)$ao_args->getOption('limit'));

			if ($lo_files->count()) {
				$li_result = $this->calculateAverageColors($lo_files, $ao_io);
				if ($li_result !== static::CODE_SUCCESS) {
					$lb_errorOccurred = true;
				}
			}

			if (!$ao_args->getOption('quiet')) {
				break;
			}
		}

		return $lb_errorOccurred ? static::CODE_ERROR : static::CODE_SUCCESS;
	}


	/**
	 * Calculate the average color of the images
	 *
	 * @param \Cake\Datasource\ResultSetInterface $ao_files
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @return int
	 */
	protected function calculateAverageColors(ResultSetInterface $ao_files, ConsoleIo $ao_io): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($ao_files as $lo_file) {
			$ls_path = $lo_file->isImage() ? $lo_file->pathAbsolute : $lo_file->previewPathAbsolute;

			if (
				!file_exists($ls_path) ||
				$lo_file->mimeType === 'image/png'
			) {
				// If the file does not exist or is a png, set the average color to a fully transparent black
				$lo_file->averageColor = '00000000';
				continue;
			}

			$lo_image = imagecreatefromstring(file_get_contents($ls_path));

			if (!$lo_image) {
				$lo_file->averageColor = '00000000';
				continue;
			}

			// Resize the imag to 1x1 pixel
			$lo_pixel = imagecreatetruecolor(1, 1);

			imagecopyresampled($lo_pixel, $lo_image, 0, 0, 0, 0, 1, 1, imagesx($lo_image), imagesy($lo_image));
			$li_index = imagecolorat($lo_pixel, 0, 0);
			$la_colors = imagecolorsforindex($lo_pixel, $li_index);

			$lo_file->averageColor = sprintf('%02X%02X%02X%02X', $la_colors['red'], $la_colors['green'], $la_colors['blue'], $la_colors['alpha']);

			imagedestroy($lo_image);
			$lo_image = null;
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		/**
		 * If all files have the same webp status, use a simple updateAll command
		 */
		$lo_table->updateAll(function (QueryExpression $ao_expression) use ($ao_files) {
			$lo_averageColorCases = $ao_expression->case();

			/** @var \Awyiss\Model\Entity\Media $lo_file */
			foreach ($ao_files as $lo_file) {
				$lo_averageColorCases->when(['id = ' . $lo_file->id])->then($lo_file->averageColor, 'string');
			}

			return [
				'average_color' => $lo_averageColorCases,
			];
		}, [
			'id IN' => $ao_files->extract('id')->toArray(),
		]);

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $ao_args
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @return int
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function convertImages(ResultSetInterface $ao_files, ConsoleIo $ao_io): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($ao_files as $lo_file) {
			$ao_io->out(sprintf('Creating webp image for file `%s`', $lo_file->path));

			$lo_process = $this->convertToWebp($lo_file);

			if ($lo_process === false) {
				$ao_io->error('Status: Cannot create webp file');
			}
			elseif ($lo_process->isSuccessful()) {
				$ao_io->success('Status: ' . $lo_process->getExitCodeText());
				$lo_file->webp = ProcessStatus::Success;
			}
			else {
				$ao_io->error('Status: ' . $lo_process->getExitCodeText());
				$ao_io->out('Command: ' . $lo_process->getCommandLine());
				$ao_io->out('Message: ' . $lo_process->getErrorOutput(), 0);

				$lo_file->webp = ProcessStatus::Fail;
			}

			$ao_io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$la_webpStatus = array_unique($ao_files->extract('webp')->toArray(), SORT_REGULAR);
		if (count($la_webpStatus) === 1) {
			/**
			 * If all files have the same webp status, use a simple updateAll command
			 */
			$lo_table->updateAll([
				'webp' => $la_webpStatus[0],
			], [
				'id IN' => $ao_files->extract('id')->toArray(),
			]);
		}
		else {
			$lo_table->updateAll(function (QueryExpression $ao_expression) use ($ao_files) {
				$lo_webpCases = $ao_expression->case();

				/** @var \Awyiss\Model\Entity\Media $lo_file */
				foreach ($ao_files as $lo_file) {
					$lo_webpCases->when(['id = ' . $lo_file->id])->then($lo_file->webp->value, 'integer');
				}

				return [
					'webp' => $lo_webpCases,
				];
			}, [
				'id IN' => $ao_files->extract('id')->toArray(),
			]);
		}


		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Cake\Datasource\ResultSetInterface $ao_files
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @param bool $ab_includeWebp
	 * @return int
	 */
	protected function convertNonImages(ResultSetInterface $ao_files, ConsoleIo $ao_io, bool $ab_includeWebp): int {
		/** @var \Awyiss\Model\Entity\Media $lo_file */
		foreach ($ao_files as $lo_file) {
			$ao_io->out(sprintf('Creating preview for file `%s`', $lo_file->path));

			$ls_previewPathAbsolute = $lo_file->previewPathAbsolute;
			if (!$ls_previewPathAbsolute) {
				$ao_io->error('Status: Cannot convert file without a path');
				$ao_io->hr();

				continue;
			}

			if (!file_exists(dirname($ls_previewPathAbsolute))) {
				mkdir(dirname($ls_previewPathAbsolute));
			}

			$la_command = $this->getCommand($lo_file, 'preview');

			if (!$la_command) {
				$ao_io->warning(sprintf('Status: Cannot convert filetype `%s`', $lo_file->extension));
				$ao_io->hr();

				$lo_file->preview = ProcessStatus::Fail;
				$lo_file->webp = ProcessStatus::Fail;

				continue;
			}

			$lo_process = new Process($la_command);
			$lo_process->run();
			if ($lo_process->isSuccessful()) {
				$ao_io->success('Status: ' . $lo_process->getExitCodeText());

				$la_imageSize = getimagesize($ls_previewPathAbsolute);

				$lo_file->width = $la_imageSize[0];
				$lo_file->height = $la_imageSize[1];
				$lo_file->preview = ProcessStatus::Success;

				if ($ab_includeWebp) {
					$ao_io->out(sprintf('Creating WebP file for file `%s`', $lo_file->path));

					$lo_webpStatusProcess = $this->convertToWebp($lo_file);

					if ($lo_webpStatusProcess === false) {
						$ao_io->error('Status: Cannot create webp file');
					}
					elseif ($lo_webpStatusProcess->isSuccessful()) {
						$ao_io->success('Status: ' . $lo_webpStatusProcess->getExitCodeText());
						$lo_file->webp = ProcessStatus::Success;
					}
					else {
						$ao_io->error('Status: ' . $lo_webpStatusProcess->getExitCodeText());
						$lo_file->webp = ProcessStatus::Fail;
					}
				}
			}
			else {
				$ao_io->error('Status: ' . $lo_process->getExitCodeText());
				$ao_io->out('Command: ' . $lo_process->getCommandLine());
				$ao_io->out('Message: ' . $lo_process->getErrorOutput(), 0);

				$lo_file->preview = ProcessStatus::Fail;
				$lo_file->webp = ProcessStatus::Undefined;
			}

			$ao_io->hr();
		}

		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$la_previewStatus = array_unique($ao_files->extract('preview')->toArray(), SORT_REGULAR);
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
				'id IN' => $ao_files->extract('id')->toArray(),
			]);
		}
		else {
			$lo_table->updateAll(function (QueryExpression $ao_expression) use ($ao_files, $ab_includeWebp) {
				$lo_widthCases = $ao_expression->case();
				$lo_heightCases = $ao_expression->case();
				$lo_previewCases = $ao_expression->case();

				if ($ab_includeWebp) {
					$lo_webpCases = $ao_expression->case();
				}

				/** @var \Awyiss\Model\Entity\Media $lo_file */
				foreach ($ao_files as $lo_file) {
					$lo_widthCases->when(['id = ' . $lo_file->id])->then($lo_file->width, 'float');
					$lo_heightCases->when(['id = ' . $lo_file->id])->then($lo_file->height, 'float');
					$lo_previewCases->when(['id = ' . $lo_file->id])->then($lo_file->preview->value, 'integer');

					if ($ab_includeWebp) {
						$lo_webpCases->when(['id = ' . $lo_file->id])->then($lo_file->webp->value, 'integer');
					}
				}

				$la_cases = [
					'width' => $lo_widthCases,
					'height' => $lo_heightCases,
					'preview' => $lo_previewCases,
				];

				if ($ab_includeWebp) {
					$la_cases['webp'] = $lo_webpCases;
				}

				return $la_cases;
			}, [
				'id IN' => $ao_files->extract('id')->toArray(),
			]);
		}

		return static::CODE_SUCCESS;
	}


	/**
	 * @param int $ai_limit
	 * @param bool $ab_retryFailed
	 * @param bool $ab_includeWebp
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchNonImageFiles(int $ai_limit, bool $ab_retryFailed, bool $ab_includeWebp): ResultSetInterface {
		$la_where = [
			'preview' => ProcessStatus::Undefined,
		];

		if ($ab_retryFailed) {
			$la_where['preview'] = ProcessStatus::Fail;
		}

		$la_processStatusColumns = [
			'preview',
		];

		if ($ab_includeWebp) {
			$la_processStatusColumns[] = 'webp';
		}


		return $this->fetchFiles($la_where, $ai_limit, $la_processStatusColumns);
	}


	/**
	 * Get files that have no average color set yet
	 *
	 * @param int $ai_limit
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForAverageColorCalculation(int $ai_limit): ResultSetInterface {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$lo_records = $lo_table->find()->where([
			'average_color IS' => null,
			'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
		])->limit($ai_limit)->all();

		return $lo_records;
	}


	/**
	 * @param int $ai_limit
	 * @param bool $ab_retryFailed
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFilesForWebpConversion(int $ai_limit, bool $ab_retryFailed): ResultSetInterface {
		$la_where = [
			'webp' => ProcessStatus::Undefined,
			'preview IN' => [ProcessStatus::Success, ProcessStatus::NotRequired],
		];

		if ($ab_retryFailed) {
			$la_where['webp'] = ProcessStatus::Fail;
		}


		return $this->fetchFiles($la_where, $ai_limit, ['webp']);
	}


	/**
	 * @param array $aa_where
	 * @param int $ai_limit
	 * @param array $aa_processStatusColumns
	 * @return \Cake\Datasource\ResultSetInterface
	 */
	protected function fetchFiles(array $aa_where, int $ai_limit, array $aa_processStatusColumns): ResultSetInterface {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$lo_records = $lo_table->find()->where($aa_where)->limit($ai_limit)->all();

		if ($lo_records->count()) {
			$la_processStatusColumns = [];
			foreach ($aa_processStatusColumns as $ls_column) {
				$la_processStatusColumns[ $ls_column ] = ProcessStatus::InProgress;
			}

			$lo_table->updateAll($la_processStatusColumns, [
				'id IN' => $lo_records->extract('id')->toArray(),
			]);
		}


		return $lo_records;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $ao_file
	 * @param string $as_outputFilePath
	 * @return array<int, string>|false
	 */
	protected function getCommand(Media $ao_file, string $as_type): array|false {
		if ($as_type === 'preview') {
			if (in_array($ao_file->mimeType, ['video/mp4', 'video/x-msvideo'])) {
				if (!Configure::read('AvailableCommands.ffmpeg')) {
					return false;
				}

				$la_command = [
					'ffmpeg',
					'-y',
					'-i',
					WWW_ROOT . str_replace('/', DS, $ao_file->path),
					'-vf',
					'thumbnail',
					'-frames:v',
					'1',
					$ao_file->previewPathAbsolute,
				];
			}
			else {
				if (!Configure::read('AvailableCommands.imageMagick.' . $ao_file->extension)) {
					return false;
				}

				$ls_inputPath = $ao_file->path;
				if (in_array($ao_file->extension, ['pdf', 'psd'])) {
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
					$ao_file->previewPathAbsolute,
				];
			}

			return $la_command;
		}
		elseif ($as_type === 'webp') {
			$ls_inputPath = $ao_file->pathAbsolute;

			if (!$ao_file->isImage()) {
				$ls_inputPath = $ao_file->previewPathAbsolute;
			}

			return [
				'convert',
				$ls_inputPath,
				$ao_file->webpPathAbsolute,
			];
		}

		return false;
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $ao_file
	 * @return \Symfony\Component\Process\Process|false
	 */
	protected function convertToWebp(Media $ao_file): Process|false {
		$ls_webpPathAbsolute = $ao_file->webpPathAbsolute;

		if (!$ls_webpPathAbsolute) {
			return false;
		}

		if (!file_exists(dirname($ls_webpPathAbsolute))) {
			mkdir(dirname($ls_webpPathAbsolute));
		}


		$lo_process = new Process($this->getCommand($ao_file, 'webp'));
		$lo_process->run();


		return $lo_process;
	}
}
