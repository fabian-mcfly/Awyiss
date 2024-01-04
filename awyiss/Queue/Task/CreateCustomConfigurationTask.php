<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Queue\Queue\Task;


/**
 * Deletes existing custom config files in the environment folder
 * and creates a new one, containing all custom config values for this Awyiss installation
 */
class CreateCustomConfigurationTask extends Task {
	/**
	 * @inheritDoc
	 */
	public ?int $timeout = 5;
	/**
	 * @inheritDoc
	 */
	public ?int $retries = 3;


	/**
	 * @param array<string, mixed> $aa_data The array passed to QueuedJobsTable::createJob()
	 * @param int $ai_jobId The id of the QueuedJob entity
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @throws \Exception
	 * @see Awyiss::loadConfiguration
	 */
	public function run(array $aa_data, int $ai_jobId): void {
		//Delete all files
		$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE) . '\[??\]\[??\].php';
		foreach (glob(ENV_CUSTOM_CONFIG . $ls_fileName) as $ls_filePath) {
			unlink($ls_filePath);
		}

		$la_languages = LocaleMiddleware::getLanguages();
		foreach ($la_languages as &$la_realmLanguages) {
			$la_realmLanguages = array_keys($la_realmLanguages);
		}
		unset($la_realmLanguages);

		foreach (collection($la_languages)->cartesianProduct()->toArray() as $la_languages) {
			//Load the config with the provided languages
			Awyiss::loadConfiguration(...$la_languages);

			$ls_frontendLanguage = $la_languages[0] ?? null;
			$ls_backendLanguage = $la_languages[1] ?? null;

			$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE);
			if ($ls_frontendLanguage) {
				$ls_fileName .= '[' . $ls_frontendLanguage . ']';

				if ($ls_backendLanguage) {
					$ls_fileName .= '[' . $ls_backendLanguage . ']';
				}
			}

			//Dump the config to a file
			Configure::dump($ls_fileName, 'default', ['Awyiss']);
		}
	}
}
