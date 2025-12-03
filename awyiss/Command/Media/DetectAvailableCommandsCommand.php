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
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('retry', [
			'boolean' => true,
			'help' => 'Retry the detection of available commands, even if the config setting exists.',
			'short' => 'r',
		]);


		return $parser;
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
		$ffmpeg = $this->testProcess(['ffmpeg', '-version']);
		if ($ffmpeg) {
			$io->success('ffmpg available');
		}
		else {
			$io->error('ffmpg not available');
		}

		$io->out('Testing ImageMagick (`magick`)... ', 0);
		$imageMagick = $this->testProcess(['magick', '-version']);
		$imageMagickCommands = false;
		if ($imageMagick) {
			$io->success('magick available');

			$imageMagickAvif = $this->testProcess(['magick', 'awyiss/Command/Media/TestFiles/logo-awyiss.avif', TMP . 'logo-awyiss.jpg'], 'Avif support', $io);

			$imageMagickWebp = $this->testProcess(['magick', 'awyiss/Command/Media/TestFiles/logo-awyiss.webp', TMP . 'logo-awyiss.jpg'], 'WebP support', $io);

			$imageMagickPdf = $this->testProcess(['magick', 'awyiss/Command/Media/TestFiles/logo-awyiss.pdf', TMP . 'logo-awyiss.jpg'], 'PDF support', $io);

			$imageMagickSvg = $this->testProcess(['magick', 'awyiss/Command/Media/TestFiles/logo-awyiss.svg', TMP . 'logo-awyiss.jpg'], 'SVG support', $io);

			$imageMagickDocx = $this->testProcess(['magick', 'awyiss/Command/Media/TestFiles/logo-awyiss.docx', TMP . 'logo-awyiss.jpg'], 'DOCX support', $io);

			$imageMagickPptx = $this->testProcess(['magick', 'awyiss/Command/Media/TestFiles/logo-awyiss.pptx', TMP . 'logo-awyiss.jpg'], 'PPTX support', $io);

			$imageMagickPsd = $this->testProcess(['magick', 'awyiss/Command/Media/TestFiles/logo-awyiss.psd', TMP . 'logo-awyiss.jpg'], 'PSD support', $io);

			$imageMagickXlxs = $this->testProcess(['magick', 'awyiss/Command/Media/TestFiles/logo-awyiss.xlsx', TMP . 'logo-awyiss.jpg'], 'XLSX support', $io);

			$imageMagickCommands = [
				'avif' => $imageMagickAvif,
				'webp' => $imageMagickWebp,
				'pdf' => $imageMagickPdf,
				'svg' => $imageMagickSvg,
				'doc' => $imageMagickDocx,
				'docx' => $imageMagickDocx,
				'ppt' => $imageMagickPptx,
				'pptx' => $imageMagickPptx,
				'psd' => $imageMagickPsd,
				'xls' => $imageMagickXlxs,
				'xlsx' => $imageMagickXlxs,
			];
		}
		else {
			$io->error('magick not available');
			$io->out('Skipping specific file type detection for `magick`...');
		}

		if (file_exists(TMP . 'logo-awyiss.jpg')) {
			unlink(TMP . 'logo-awyiss.jpg');
		}

		$io->out('Writing config... ', 0);

		//Remember the current config
		$rememberedConfig = Configure::read();
		Configure::clear();

		if (file_exists(ENV_CUSTOM_CONFIG . 'awyiss.php')) {
			Configure::write(include ENV_CUSTOM_CONFIG . 'awyiss.php');
		}

		Configure::write([
			'AvailableCommands' => [
				'ffmpeg' => $ffmpeg,
				'imageMagick' => $imageMagickCommands,
			],
		]);

		//Dump the config to a file
		Configure::dump('awyiss');

		$io->out('Done');

		Configure::clear();
		Configure::write($rememberedConfig);


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

		$process = new Process($command);

		try {
			$process->mustRun();
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
