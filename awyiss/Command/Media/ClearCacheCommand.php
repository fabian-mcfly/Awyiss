<?php declare(strict_types=1);


namespace Awyiss\Command\Media;


use Awyiss\Model\Enum\ProcessStatus;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Expression\QueryExpression;
use Symfony\Component\Process\Process;


/**
 * Removes media folders, depending on the provided type
 */
class ClearCacheCommand extends Command {
	/**
	 * @inheritDoc
	 */
	public static function getDescription(): string {
		return 'Removes files (resized, previews, effects, webp), deleted folders, and resets the database status (preview, webp) of media records';
	}



	/**
	 * @inheritDoc
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($parser);

		$lo_parser->addOption('only-database', [
			'bool' => true,
			'help' => 'Reset only the database status',
		]);


		$lo_parser->addOption('type', [
			'choices' => [
				'all',
				'deleted',
				'effects',
				'previews',
				'resized',
				'webp',
			],
			'default' => 'all',
			'help' => 'The type of cache to be cleared',
			'short' => 't',
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
		$io->out('Fetching folders... ', 0);

		$ls_type = $args->getOption('type');
		$la_folders = $args->getOption('only-database') ? [] : $this->fetchFolders($ls_type);

		$io->out(sprintf('%d folders found.', count($la_folders)));

		if ($la_folders) {
			foreach ($la_folders as $ls_folderPath) {
				$io->out(sprintf('Processing folder `%s`... ', substr($ls_folderPath, strlen(WWW_ROOT))), 0);
				$lb_deleted = $this->deleteFolder($ls_folderPath);
				if ($lb_deleted) {
					$io->success('Success');
				}
				else {
					$io->error('failed');
				}
			}
		}


		if (in_array($ls_type, ['deleted', 'all'])) {
			$io->out('Removing deleted folders from the database... ', 0);
			/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
			$lo_table = $this->fetchTable('MediaFolders');
			$li_deletedRows = $lo_table->deleteAll(['deleted' => true]);

			if ($li_deletedRows) {
				$io->success(sprintf('Deleted %d rows', $li_deletedRows));
			}
			else {
				$io->out('Deleted 0 rows');
			}
		}


		$io->out('Resetting database records... ', 0);

		$li_updatedRows = $this->resetMediaRecords($ls_type);
		if ($li_updatedRows) {
			$io->success(sprintf('Updated %d media rows', $li_updatedRows));
		}
		else {
			$io->out('Updated 0 media rows');
		}


		if (in_array($ls_type, ['all', 'resized'])) {
			$io->out('Deleting resized database records... ', 0);

			/** @var \Awyiss\Model\Table\MediaResizedImagesTable $lo_table */
			$lo_table = $this->fetchTable('MediaResizedImages');

			$li_resizedRows = $lo_table->deleteAll([]);
			if ($li_resizedRows) {
				$io->success(sprintf('Deleted %d resized media rows', $li_resizedRows));
			}
			else {
				$io->out('Updated 0 resized media rows');
			}
		}


		return static::CODE_SUCCESS;
	}


	/**
	 * @param string $type
	 * @return array
	 */
	protected function fetchFolders(string $type): array {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $this->fetchTable('MediaFolders');

		$lo_query = $lo_table->find();

		if ($type === 'deleted') {
			$lo_query->find('deleted');
		}
		elseif ($type === 'all') {
			$lo_query->find('withDeleted');
		}

		$lo_records = $lo_query->all();

		$la_folders = [];
		/** @var \Awyiss\Model\Entity\MediaFolder $lo_folder */
		foreach ($lo_records as $lo_folder) {
			if ($lo_folder->deleted === true) {
				$ls_folder = WWW_ROOT . $lo_folder->path;
				if (file_exists($ls_folder)) {
					$la_folders[] = $ls_folder;
				}
			}
			else {
				if (in_array($type, ['all', 'effects'])) {
					$ls_folder = WWW_ROOT . $lo_folder->path . '/_effects';

					if (file_exists($ls_folder)) {
						$la_folders[] = $ls_folder;
					}
				}

				if (in_array($type, ['all', 'previews'])) {
					$la_folders = array_merge($la_folders, glob(WWW_ROOT . $lo_folder->path . '/_*_preview', GLOB_ONLYDIR) ?: []);
				}

				if (in_array($type, ['all', 'resized'])) {
					$ls_folder = WWW_ROOT . $lo_folder->path . '/_resized';
					if (file_exists($ls_folder)) {
						$la_folders[] = $ls_folder;
					}
				}

				if (in_array($type, ['all', 'webp'])) {
					$ls_folder = WWW_ROOT . $lo_folder->path . '/_webp';

					if (file_exists($ls_folder)) {
						$la_folders[] = $ls_folder;
					}
				}
			}
		}


		return $la_folders;
	}


	/**
	 * @param mixed $ls_folderPath
	 * @return bool
	 */
	protected function deleteFolder(mixed $ls_folderPath): bool {
		$lo_process = new Process([
			'rm',
			'-r',
			$ls_folderPath,
		]);
		$lo_process->run();


		return $lo_process->isSuccessful();
	}


	/**
	 * Resets the status of the `preview` and `webp` fields of the media records
	 *
	 * @param string $type
	 * @return int
	 */
	protected function resetMediaRecords(string $type): int {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		$ls_type = $type;

		return $lo_table->updateAll(function (QueryExpression $expression) use ($ls_type) {
			$la_cases = [];

			if (in_array($ls_type, ['all', 'previews'])) {
				$lo_previewCases = $expression->case()->when([
					'preview !=' => ProcessStatus::NotRequired->value,
				])->then(ProcessStatus::Undefined->value, 'integer')->else(ProcessStatus::NotRequired->value, 'integer');
				$la_cases['preview'] = $lo_previewCases;
			}

			if (in_array($ls_type, ['all', 'webp'])) {
				$lo_webpCases = $expression->case()->when([
					'webp !=' => ProcessStatus::NotRequired->value,
				])->then(ProcessStatus::Undefined->value, 'integer')->else(ProcessStatus::NotRequired->value, 'integer');
				$la_cases['webp'] = $lo_webpCases;
			}

			return $la_cases;
		}, []);
	}
}
