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
	 * @param \Cake\Console\ConsoleOptionParser $ao_parser
	 * @return \Cake\Console\ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

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
				'thumbnails',
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
	 * @param \Cake\Console\Arguments $ao_args
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @return int
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function execute(Arguments $ao_args, ConsoleIo $ao_io): int {
		$ao_io->out('Fetching folders... ', 0);

		$ls_type = $ao_args->getOption('type');
		$la_folders = $ao_args->getOption('only-database') ? [] : $this->fetchFolders($ls_type);

		$ao_io->out(sprintf('%d folders found.', count($la_folders)));

		if ($la_folders) {
			foreach ($la_folders as $ls_folderPath) {
				$ao_io->out(sprintf('Processing folder `%s`... ', substr($ls_folderPath, strlen(WWW_ROOT))), 0);
				$lb_deleted = $this->deleteFolder($ls_folderPath);
				if ($lb_deleted) {
					$ao_io->success('Success');
				}
				else {
					$ao_io->error('failed');
				}
			}
		}


		if (in_array($ls_type, ['deleted', 'all'])) {
			$ao_io->out('Removing deleted folders from the database... ', 0);
			/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
			$lo_table = $this->fetchTable('MediaFolders');
			$li_deletedRows = $lo_table->deleteAll(['deleted' => true]);

			if ($li_deletedRows) {
				$ao_io->success(sprintf('Deleted %d rows', $li_deletedRows));
			}
			else {
				$ao_io->out('Deleted 0 rows');
			}
		}


		$ao_io->out('Resetting database records... ', 0);

		$li_updatedRows = $this->resetMediaRecords($ls_type);
		if ($li_updatedRows) {
			$ao_io->success(sprintf('Updated %d rows', $li_updatedRows));
		}
		else {
			$ao_io->out('Updated 0 rows');
		}


		return static::CODE_SUCCESS;
	}


	/**
	 * @param string $as_type
	 * @return array
	 */
	protected function fetchFolders(string $as_type): array {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $lo_table */
		$lo_table = $this->fetchTable('MediaFolders');

		$lo_query = $lo_table->find();

		if ($as_type === 'deleted') {
			$lo_query->find('deleted');
		}
		elseif ($as_type === 'all') {
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
				if (in_array($as_type, ['all', 'effects'])) {
					$ls_folder = WWW_ROOT . $lo_folder->path . '/_effects';

					if (file_exists($ls_folder)) {
						$la_folders[] = $ls_folder;
					}
				}

				if (in_array($as_type, ['all', 'previews'])) {
					$la_folders = array_merge($la_folders, glob(WWW_ROOT . $lo_folder->path . '/_*_preview', GLOB_ONLYDIR) ?: []);
				}

				if (in_array($as_type, ['all', 'thumbnails'])) {
					$ls_folder = WWW_ROOT . $lo_folder->path . '/_thumbnails';
					if (file_exists($ls_folder)) {
						$la_folders[] = $ls_folder;
					}
				}

				if (in_array($as_type, ['all', 'webp'])) {
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
	 * @param string $as_type
	 * @return int
	 */
	protected function resetMediaRecords(string $as_type): int {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');

		return $lo_table->updateAll(function (QueryExpression $ao_expression) use ($as_type) {
			$la_cases = [];

			if (in_array($as_type, ['all', 'previews'])) {
				$lo_previewCases = $ao_expression->case()->when([
					'preview !=' => ProcessStatus::NotRequired->value,
				])->then(ProcessStatus::Undefined->value, 'integer')->else(ProcessStatus::NotRequired->value, 'integer');
				$la_cases['preview'] = $lo_previewCases;
			}

			if (in_array($as_type, ['all', 'webp'])) {
				$lo_webpCases = $ao_expression->case()->when([
					'webp !=' => ProcessStatus::NotRequired->value,
				])->then(ProcessStatus::Undefined->value, 'integer')->else(ProcessStatus::NotRequired->value, 'integer');
				$la_cases['webp'] = $lo_webpCases;
			}

			return $la_cases;
		}, []);
	}
}
