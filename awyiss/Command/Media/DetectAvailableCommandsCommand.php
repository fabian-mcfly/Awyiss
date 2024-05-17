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
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($parser);

		$lo_parser->addOption('retry', [
			'boolean' => true,
			'help' => 'Retry the detection of available commands, even if the config setting exists.',
			'short' => 'r',
		]);


		return $lo_parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		if (Configure::read('AvailableCommands') && !$args->getOption('retry')) {
			$io->out('Commands already detected.');


			return static::CODE_SUCCESS;
		}

		return $this->detectCommands($io);
	}


	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	protected function detectCommands(ConsoleIo $io): int {
		$io->out('Testing ffmpg... ', 0);
		$lb_ffmpeg = $this->testProcess(['ffmpeg', '-version']);
		if ($lb_ffmpeg) {
			$io->success('ffmpg available');
		}
		else {
			$io->error('ffmpg not available');
		}

		$io->out('Testing ImageMagick (`convert`)... ', 0);
		$lb_imageMagick = $this->testProcess(['convert', '-version']);
		$la_imageMagick = false;
		if ($lb_imageMagick) {
			$io->success('convert available');

			$lb_imageMagickPdf = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.pdf', TMP . 'logo-awyiss.jpg'], 'PDF support', $io);

			$lb_imageMagickSvg = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.svg', TMP . 'logo-awyiss.jpg'], 'SVG support', $io);

			$lb_imageMagickDocx = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.docx', TMP . 'logo-awyiss.jpg'], 'DOCX support', $io);

			$lb_imageMagickPptx = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.pptx', TMP . 'logo-awyiss.jpg'], 'PPTX support', $io);

			$lb_imageMagickPsd = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.psd', TMP . 'logo-awyiss.jpg'], 'PSD support', $io);

			$lb_imageMagickXlxs = $this->testProcess(['convert', 'awyiss/Command/Media/TestFiles/logo-awyiss.xlsx', TMP . 'logo-awyiss.jpg'], 'XLSX support', $io);

			$la_imageMagick = [
				'pdf' => $lb_imageMagickPdf,
				'svg' => $lb_imageMagickSvg,
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
			$io->error('convert not available');
			$io->out('Skipping specific file type detection for `convert`...');
		}

		if (file_exists(TMP . 'logo-awyiss.jpg')) {
			unlink(TMP . 'logo-awyiss.jpg');
		}

		$io->out('Writing config... ', 0);

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

		$io->out('Done');

		Configure::clear();
		Configure::write($la_rememberedConfig);


		return static::CODE_SUCCESS;
	}


	/**
	 * @param array $command
	 * @param string|null $type
	 * @param \Cake\Console\ConsoleIo|null $io
	 * @return bool
	 */
	protected function testProcess(array $command, ?string $type = null, ?ConsoleIo $io = null): bool {
		if ($type && $io) {
			$io->out(sprintf('Testing %s... ', $type), 0);
		}

		$lo_process = new Process($command);

		try {
			$lo_process->mustRun();
		}
		catch (ProcessFailedException) {
			if ($type && $io) {
				$io->error(sprintf('%s not available', $type));
			}


			return false;
		}

		if ($type && $io) {
			$io->success(sprintf('%s available', $type));
		}

		return true;
	}
}
