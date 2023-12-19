<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Cake\Datasource\FactoryLocator;
use Queue\Queue\Task;


/**
 * Deletes an existing constants.php file in the environment folder
 * and creates a new one, containing all custom constant definitions for this Awyiss installation
 */
class CreateCustomConstantsTask extends Task {
	/**
	 * @param array<string, mixed> $aa_data The array passed to QueuedJobsTable::createJob()
	 * @param int $ai_jobId The id of the QueuedJob entity
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function run (array $aa_data, int $ai_jobId): void {
		$ls_environment = preg_replace('/[^a-z-_]/i', '', $aa_data['environment'] ?? CONFIG_ENV);

		$ls_filePath = CUSTOM_CONFIG . $ls_environment . DS . 'constants.php';

		$ls_constantsContents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL;

		$lo_pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
		foreach ($lo_pageRolesTable->find('all')->applyOptions(['access' => ['skip' => TRUE]]) as $lo_pageRole) {
			$ls_constant = 'PAGEROLE_' . strtoupper($lo_pageRole->identifier);
			$ls_constantsContents .= 'defined(\'' . $ls_constant . '\') || define(\'' . $ls_constant . '\', ' . $lo_pageRole->id . ');' . PHP_EOL;
			defined($ls_constant) || define($ls_constant, $lo_pageRole->id);
		}

		if (file_exists($ls_filePath)) {
			unlink($ls_filePath);
		}

		file_put_contents($ls_filePath, $ls_constantsContents);
		/*if (file_put_contents($ls_filePath, $ls_constantsContents) > 0) {
			chmod($ls_filePath, fileperms($ls_filePath) | 128 + 16 + 2);
		}*/
	}
}