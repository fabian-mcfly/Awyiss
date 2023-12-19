<?php declare(strict_types=1);


namespace Awyiss\Queue\Task;


use Queue\Queue\Task;


/**
 * Deletes an existing constants.php file in the environment folder
 * and creates a new one, containing all custom constant definitions for this Awyiss installation
 */
class _____CreatePageRoleModelTask_____ extends Task {
	/**
	 * @inheritDoc
	 */
	public ?int $timeout = 5;
	/**
	 * @inheritDoc
	 */
	public ?int $retries = 3;


	/**
	 * @param array<string, mixed> $aa_data  The array passed to QueuedJobsTable::createJob()
	 * @param int                  $ai_jobId The id of the QueuedJob entity
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function run (array $aa_data, int $ai_jobId): void {
		if (empty($aa_data['name'])) {
			throw new \RuntimeException(sprintf('Missing page role name in `%s`', static::class));
		}

		$la_commands = [];


		$ls_command = 'bin/cake bake model ' . $aa_data['name'];
		$ls_command .= ' --namespace ' . CUSTOM_DIR;

		$ls_command .= ' --force';
		$ls_command .= ' --is-pagerole';
		$ls_command .= ' --no-associations';
		$ls_command .= ' --no-entity';
		$ls_command .= ' --no-fields';
		$ls_command .= ' --no-fixture';
		$ls_command .= ' --no-hidden';
		$ls_command .= ' --no-rules';
		$ls_command .= ' --no-test';
		$ls_command .= ' --no-validation';
		$ls_command .= ' --skip-relation-check';
		$ls_command .= ' --table pages';
		$ls_command .= ' --update';

		$la_commands[] = $ls_command;

		//Queue the job.
		$this->QueuedJobs->createJob('Queue.Execute', [
			'command' => '(' . implode(' && ', array_map('escapeshellcmd', $la_commands)) . ')',
			'escape' => FALSE,
			'log' => TRUE,
		], [
			'group' => 'general',
			'priority' => 1,
			'reference' => 'system::create_page_role_model',
		]);
	}
}
