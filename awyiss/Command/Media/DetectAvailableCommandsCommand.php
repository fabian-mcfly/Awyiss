<?php declare(strict_types=1);


namespace Awyiss\Command\Media;


use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


/**
 * Detect available commands using \Symfony\Component\Process\Process
 * Result will be
 */
class DetectAvailableCommandsCommand extends Command {
	/**
	 * @inheritDoc
	 * @param \Cake\Console\ConsoleOptionParser $ao_parser
	 * @return \Cake\Console\ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('retry', [
			'boolean' => true,
			'help' => 'Retry the detection of available commands, even if the config setting exists.',
			'short' => 'r',
		]);


		return $lo_parser;
	}

	/**
	 * @param \Cake\Console\Arguments $ao_args
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @return int
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function execute(Arguments $ao_args, ConsoleIo $ao_io): int {
		if (Configure::read('AvailableCommands') && !$ao_args->getOption('retry')) {
			$ao_io->out('Commands already detected.');


			return static::CODE_SUCCESS;
		}

		return $this->detectCommands($ao_io);
	}


	/**
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @return int
	 */
	protected function detectCommands(ConsoleIo $ao_io): int {
		$ao_io->out('Testing ffmpg... ', 0);
		$lb_ffmpeg = $this->testProcess(['ffmpeg', '-version']);
		if ($lb_ffmpeg) {
			$ao_io->success('ffmpg available');
		}
		else {
			$ao_io->error('ffmpg not available');
		}

		$ao_io->out('Testing ImageMagick (`convert`)... ', 0);
		$lb_imageMagick = $this->testProcess(['convert', '-version']);
		$la_imageMagick = false;
		if ($lb_imageMagick) {
			$ao_io->success('convert available');

			$lb_imageMagickPdf = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.pdf', TMP . 'logo-awyiss.jpg'], 'PDF support', $ao_io);

			$lb_imageMagickDocx = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.docx', TMP . 'logo-awyiss.jpg'], 'DOCX support', $ao_io);

			$lb_imageMagickPptx = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.pptx', TMP . 'logo-awyiss.jpg'], 'PPTX support', $ao_io);

			$lb_imageMagickPsd = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.psd', TMP . 'logo-awyiss.jpg'], 'PSD support', $ao_io);

			$lb_imageMagickXlxs = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.xlsx', TMP . 'logo-awyiss.jpg'], 'XLSX support', $ao_io);

			$la_imageMagick = [
				'pdf' => $lb_imageMagickPdf,
				'doc' => $lb_imageMagickDocx,
				'docx' => $lb_imageMagickDocx,
				'ppt' => $lb_imageMagickPptx,
				'pptx' => $lb_imageMagickPptx,
				'psd' => $lb_imageMagickPsd,
				'xls' => $lb_imageMagickXlxs,
				'xlsx' => $lb_imageMagickXlxs,
			];
		}
		else {
			$ao_io->error('convert not available');
			$ao_io->out('Skipping specific file type detection for `convert`...');
		}

		if (file_exists(TMP . 'logo-awyiss.jpg')) {
			unlink(TMP . 'logo-awyiss.jpg');
		}

		$ao_io->out('Writing config... ', 0);

		//Remember the current config
		$la_rememberedConfig = Configure::read();
		Configure::clear();

		if (file_exists(ENV_CUSTOM_CONFIG . 'awyiss.php')) {
			Configure::write(include ENV_CUSTOM_CONFIG . 'awyiss.php');
		}

		Configure::write([
			'AvailableCommands' => [
				'ffmpeg' => $lb_ffmpeg,
				'imageMagick' => $la_imageMagick,
			],
		]);

		//Dump the config to a file
		Configure::dump('awyiss');

		$ao_io->out('Done');

		Configure::clear();
		Configure::write($la_rememberedConfig);


		return static::CODE_SUCCESS;
	}


	/**
	 * @param array $aa_command
	 * @param string|null $as_type
	 * @param \Cake\Console\ConsoleIo|null $ao_io
	 * @return bool
	 */
	protected function testProcess(array $aa_command, ?string $as_type = null, ?ConsoleIo $ao_io = null): bool {
		if ($as_type && $ao_io) {
			$ao_io->out(sprintf('Testing %s... ', $as_type), 0);
		}

		$lo_process = new Process($aa_command);

		try {
			$lo_process->mustRun();
		}
		catch (ProcessFailedException) {
			if ($as_type && $ao_io) {
				$ao_io->error(sprintf('%s not available', $as_type));
			}


			return false;
		}

		if ($as_type && $ao_io) {
			$ao_io->success(sprintf('%s available', $as_type));
		}

		return true;
	}
}
