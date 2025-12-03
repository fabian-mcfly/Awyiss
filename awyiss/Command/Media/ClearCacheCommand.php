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
		return 'Removes files (resized, previews, effects, avif, webp), deleted folders, and resets the database status (preview, avif, webp) of media records';
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('only-database', [
			'boolean' => true,
			'help' => 'Reset only the database status',
		]);


		$parser->addOption('type', [
			'choices' => [
				'all',
				'deleted',
				'effects',
				'previews',
				'resized',
				'avif',
				'webp',
			],
			'default' => 'all',
			'help' => 'The type of cache to be cleared',
			'short' => 't',
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
		$io->out('Fetching folders... ', 0);

		$type = $args->getOption('type');
		$folders = $args->getOption('only-database') ? [] : $this->fetchFolders($type);

		$io->out(sprintf('%d folders found.', count($folders)));

		$this->deleteFolders($folders, $io);

		$this->removeDeletedFoldersFromDatabase($type, $io);

		$this->resetDatabaseRecords($type, $io);

		$this->deleteResizedDatabaseRecords($type, $io);


		return static::CODE_SUCCESS;
	}


	/**
	 * @param array $folders
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 */
	protected function deleteFolders(array $folders, ConsoleIo $io): void {
		if (!$folders) {
			return;
		}

		foreach ($folders as $folderPath) {
			$io->out(sprintf('Processing folder `%s`... ', substr($folderPath, strlen(WWW_ROOT))), 0);

			$deleted = $this->deleteFolder($folderPath);

			if ($deleted) {
				$io->success('Succeeded');
			}
			else {
				$io->error('Failed');
			}
		}
	}


	/**
	 * @param string $type
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 */
	protected function removeDeletedFoldersFromDatabase(string $type, ConsoleIo $io): void {
		if (!in_array($type, ['deleted', 'all'])) {
			return;
		}

		$io->out('Removing deleted folders from the database... ', 0);

		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');
		$deletedRows = $table->deleteAll(['deleted' => true]);

		if ($deletedRows) {
			$io->success(sprintf('Deleted %d rows', $deletedRows));
		}
		else {
			$io->out('Deleted 0 rows');
		}
	}


	/**
	 * @param string $type
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 */
	protected function resetDatabaseRecords(string $type, ConsoleIo $io): void {
		$io->out('Resetting database records... ', 0);

		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');

		$updatedRows = $table->updateAll(function (QueryExpression $expression) use ($type) {
			$cases = [];

			if (in_array($type, ['all', 'previews'])) {
				$previewCases = $expression->case()->when([
					'preview !=' => ProcessStatus::NotRequired->value,
				])->then(ProcessStatus::Undefined->value, 'integer')->else(ProcessStatus::NotRequired->value, 'integer');
				$cases['preview'] = $previewCases;
			}

			if (in_array($type, ['all', 'avif'])) {
				$avifCases = $expression->case()->when([
					'avif !=' => ProcessStatus::NotRequired->value,
				])->then(ProcessStatus::Undefined->value, 'integer')->else(ProcessStatus::NotRequired->value, 'integer');
				$cases['avif'] = $avifCases;
			}

			if (in_array($type, ['all', 'webp'])) {
				$webpCases = $expression->case()->when([
					'webp !=' => ProcessStatus::NotRequired->value,
				])->then(ProcessStatus::Undefined->value, 'integer')->else(ProcessStatus::NotRequired->value, 'integer');
				$cases['webp'] = $webpCases;
			}

			return $cases;
		}, []);

		if ($updatedRows) {
			$io->success(sprintf('Updated %d media rows', $updatedRows));
		}
		else {
			$io->out('Updated 0 media rows');
		}
	}


	/**
	 * @param string $type
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 */
	protected function deleteResizedDatabaseRecords(string $type, ConsoleIo $io): void {
		if (!in_array($type, ['all', 'resized'])) {
			return;
		}

		$io->out('Deleting resized database records... ', 0);

		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $table */
		$table = $this->fetchTable('MediaResizedImages');

		$resizedRows = $table->deleteAll([]);

		if ($resizedRows) {
			$io->success(sprintf('Deleted %d resized media rows', $resizedRows));
		}
		else {
			$io->out('Updated 0 resized media rows');
		}
	}


	/**
	 * @param string $type
	 * @return array
	 */
	protected function fetchFolders(string $type): array {
		/** @var \Awyiss\Model\Table\MediaFoldersTable $table */
		$table = $this->fetchTable('MediaFolders');

		$query = $table->find();

		if ($type === 'deleted') {
			/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findDeleted() */
			$query->find('deleted');
		}
		elseif ($type === 'all') {
			/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
			$query->find('withDeleted');
		}

		$records = $query->all();

		$folders = [];
		/** @var \Awyiss\Model\Entity\MediaFolder $folder */
		foreach ($records as $folder) {
			if ($folder->deleted === true) {
				$folderPath = WWW_ROOT . $folder->path;
				if (file_exists($folderPath)) {
					$folders[] = $folderPath;
				}
				continue;
			}

			if (in_array($type, ['all', 'effects'])) {
				$folderPath = WWW_ROOT . $folder->path . DS . '_effects';

				if (file_exists($folderPath)) {
					$folders[] = $folderPath;
				}
			}

			if (in_array($type, ['all', 'previews'])) {
				$folders = array_merge($folders, glob(WWW_ROOT . $folder->path . DS . '_*_preview', GLOB_ONLYDIR) ?: []);
			}

			if (in_array($type, ['all', 'resized'])) {
				$folderPath = WWW_ROOT . $folder->path . DS . '_resized';
				if (file_exists($folderPath)) {
					$folders[] = $folderPath;
				}
			}

			if (in_array($type, ['all', 'avif'])) {
				$folderPath = WWW_ROOT . $folder->path . DS . '_avif';

				if (file_exists($folderPath)) {
					$folders[] = $folderPath;
				}
			}

			if (in_array($type, ['all', 'webp'])) {
				$folderPath = WWW_ROOT . $folder->path . DS . '_webp';

				if (file_exists($folderPath)) {
					$folders[] = $folderPath;
				}
			}
		}


		return $folders;
	}


	/**
	 * @param mixed $folderPath
	 * @return bool
	 */
	protected function deleteFolder(mixed $folderPath): bool {
		$process = new Process([
			'rm',
			'-r',
			$folderPath,
		]);
		$process->run();


		return $process->isSuccessful();
	}
}
