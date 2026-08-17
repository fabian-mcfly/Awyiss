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
		/** @var \Awyiss\Model\Table\UsersTable $usersTable */
		$usersTable = $this->fetchTable('Users');
		$users = $usersTable
			->find('all')
			->all()
			->indexBy('username')
			->toArray()
		;

		$username = $io->askChoice('Please enter the username of the user you want to reset the password for', array_keys($users));

		if (!$username) {
			$io->out('No username provided. Using the first one found: ', 0);
			$username = key($users);
			$io->info($username);
		}

		// Ask for a password
		while (true) {
			$password = $io->ask('Please enter a new password');

			if (strlen($password) >= 8 && strlen($password) <= 100) {
				break;
			}

			$io->error('The password must be between 8 and 100 characters long. Please try again.');
		}

		if (!$password) {
			$io->out('No password provided. Creating a random one...', 0);
			$password = Security::randomString(16);
			$io->info($password);
		}

		/** @var \Awyiss\Model\Entity\User $user */
		$user = $users[ $username ];
		// Hash the password. Happens automatically in the entity class
		$usersTable->patchEntity($user, [
			'password' => $password,
			'passwordConfirm' => $password,
		]);

		$io->out('Generating password hash... ', 0);

		if (!$user->hasErrors()) {
			$io->success('Done.');
			$io->info('Password hash: ' . $user->password);
			$io->info('You need to set this password in the database manually. Until then, the old password is still valid.');

			return static::CODE_SUCCESS;
		}

		$io->error('Failed to generate password hash.');
		foreach ($user->getErrors() as $field => $errors) {
			$io->error($field . ': ' . implode(', ', $errors));
		}

		return static::CODE_ERROR;
	}
}
