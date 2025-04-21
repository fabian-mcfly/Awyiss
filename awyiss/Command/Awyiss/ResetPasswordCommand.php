<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss;


use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Utility\Security;


/**
 * Class ResetPasswordCommand
 * This class handles the password reset process
 *
 * The user still needs to manually set the password
 * in the database
 */
class ResetPasswordCommand extends Command {
	/**
	 * Main execution method
	 *
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$lo_usersTable = $this->fetchTable('Users');
		$la_users = $lo_usersTable->find('all')->all()->indexBy('username')->toArray();

		$ls_username = $io->askChoice('Please enter the username of the user you want to reset the password for', array_keys($la_users));

		if (!$ls_username) {
			$io->out('No username provided. Using the first one found: ', 0);
			$ls_username = key($la_users);
			$io->info($ls_username);
		}

		// Ask for a password
		while (true) {
			$ls_password = $io->ask('Please enter a new password');

			if (strlen($ls_password) >= 8 && strlen($ls_password) <= 100) {
				break;
			}

			$io->error('The password must be between 8 and 100 characters long. Please try again.');
		}

		if (!$ls_password) {
			$io->out('No password provided. Creating a random one...', 0);
			$ls_password = Security::randomString(16);
			$io->info($ls_password);
		}

		/** @var \Awyiss\Model\Entity\User $lo_user */
		$lo_user = $la_users[ $ls_username ];
		// Hash the password. Happens automatically in the entity class
		$lo_usersTable->patchEntity($lo_user, [
			'password' => $ls_password,
			'password_confirm' => $ls_password,
		]);

		$io->out('Generating password hash... ', 0);

		if (!$lo_user->hasErrors()) {
			$io->success('Done.');
			$io->info('Password hash: ' . $lo_user->password);
			$io->info('You need to set this password in the database manually. Until then, the old password is still valid.');

			return static::CODE_SUCCESS;
		}

		$io->error('Failed to generate password hash.');
		foreach ($lo_user->getErrors() as $ls_field => $la_errors) {
			$io->error($ls_field . ': ' . implode(', ', $la_errors));
		}

		return static::CODE_ERROR;
	}
}
