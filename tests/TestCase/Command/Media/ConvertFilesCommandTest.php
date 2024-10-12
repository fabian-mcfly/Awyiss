<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Command\Media;


use Awyiss\Command\Media\ConvertFilesCommand;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table\MediaTable;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Console\Arguments;
use Cake\Console\CommandInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ResultSetInterface;
use Symfony\Component\Process\Process;


/**
 * Class ConvertFilesCommandTest
 */
class ConvertFilesCommandTest extends TestCase {
	/**
	 * @var object
	 */
	protected object $args;
	/**
	 * @var object
	 * @noinspection PhpPropertyNamingConventionInspection
	 */
	protected object $io;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->args = $this->createMock(Arguments::class);
		$this->args->method('getOption')->willReturnMap([
			['quiet', false],
			['limit', '20'],
			['include-webp', true],
			['retry-failed', true],
		]);

		$this->io = $this->createMock(ConsoleIo::class);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildOptionParserIncludesSetOptions(): void {
		$parser = new ConsoleOptionParser('test');

		$command = new ConvertFilesCommand();

		$command->buildOptionParser($parser);
		$options = $parser->options();

		$this->assertArrayHasKey('include-webp', $options);
		$this->assertTrue($options['include-webp']->isBoolean());

		$this->assertArrayHasKey('limit', $options);
		$this->assertEquals('20', $options['limit']->defaultValue());

		$this->assertArrayHasKey('retry-failed', $options);
		$this->assertTrue($options['retry-failed']->isBoolean());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExecuteSuccess(): void {
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'processCropFiles',
			'processNonImageFiles',
			'processWebpConversion',
			'processResizing',
			'processAverageColorCalculation',
		])->getMock();

		// Mock methods in the command to avoid full processing
		$command->expects($this->once())->method('processCropFiles')->willReturn(3);

		$command->expects($this->once())->method('processNonImageFiles')->willReturn(3);

		$command->expects($this->once())->method('processWebpConversion')->willReturn(3);

		$command->expects($this->once())->method('processResizing')->willReturn(3);

		$command->expects($this->once())->method('processAverageColorCalculation')->willReturn(3);

		$result = $command->execute($this->args, $this->io);

		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExecuteWithError(): void {
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'processCropFiles',
			'processNonImageFiles',
			'processWebpConversion',
			'processResizing',
			'processAverageColorCalculation',
		])->getMock();

		// Mock methods in the command to avoid full processing
		$command->expects($this->once())->method('processCropFiles')->willReturn(3);

		$command->expects($this->once())->method('processNonImageFiles')->willReturn(3);

		$command->expects($this->once())->method('processWebpConversion')->willReturn(false);

		$command->expects($this->never())->method('processResizing')->willReturn(3);

		$command->expects($this->never())->method('processAverageColorCalculation')->willReturn(3);

		$result = $command->execute($this->args, $this->io);

		$this->assertEquals(CommandInterface::CODE_ERROR, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessCropFilesReturnsFileCountWhenFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchCropFiles',
			'cropImages',
		])->getMock();

		$command->method('fetchCropFiles')->with(20)->willReturn($files);
		$command->method('cropImages')->with($files, $this->io)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processCropFiles($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessCropFilesReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchCropFiles',
			'cropImages',
		])->getMock();

		$command->method('fetchCropFiles')->with(20)->willReturn($files);

		$result = $command->processCropFiles($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessCropFilesReturnsFalseWhenCropImagesFails(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchCropFiles',
			'cropImages',
		])->getMock();

		$command->method('fetchCropFiles')->with(20)->willReturn($files);
		$command->method('cropImages')->with($files, $this->io)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processCropFiles($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessNonImageFilesReturnsFileCountWhenFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchNonImageFiles',
			'convertNonImages',
		])->getMock();

		$command->method('fetchNonImageFiles')->with(20)->willReturn($files);
		$command->method('convertNonImages')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processNonImageFiles($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessNonImageFilesReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchNonImageFiles',
		])->getMock();

		$command->method('fetchNonImageFiles')->with(20)->willReturn($files);

		$result = $command->processNonImageFiles($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessNonImageFilesReturnsFalseWhenConvertNonImagesFails(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchNonImageFiles',
			'convertNonImages',
		])->getMock();

		$command->method('fetchNonImageFiles')->with(20)->willReturn($files);
		$command->method('convertNonImages')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processNonImageFiles($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessWebpConversionReturnsFileCountWhenFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForWebpConversion',
			'convertImages',
		])->getMock();

		$command->method('fetchFilesForWebpConversion')->with(20)->willReturn($files);
		$command->method('convertImages')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processWebpConversion($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessWebpConversionReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForWebpConversion',
			'convertImages',
		])->getMock();

		$command->method('fetchFilesForWebpConversion')->with(20)->willReturn($files);

		$result = $command->processWebpConversion($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessWebpConversionReturnsFalseWhenConvertImagesFails(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForWebpConversion',
			'convertImages',
		])->getMock();

		$command->method('fetchFilesForWebpConversion')->with(20)->willReturn($files);
		$command->method('convertImages')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processWebpConversion($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessResizingReturnsFileCountWhenFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForResizing',
			'resizeImages',
		])->getMock();

		$command->method('fetchFilesForResizing')->with(20)->willReturn($files);
		$command->method('resizeImages')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processResizing($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessResizingReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForResizing',
			'resizeImages',
		])->getMock();

		$command->method('fetchFilesForResizing')->with(20)->willReturn($files);

		$result = $command->processResizing($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessResizingReturnsFalseWhenResizeImagesFails(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForResizing',
			'resizeImages',
		])->getMock();

		$command->method('fetchFilesForResizing')->with(20)->willReturn($files);
		$command->method('resizeImages')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processResizing($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessAverageColorCalculationReturnsFileCountWhenFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAverageColorCalculation',
			'calculateAverageColors',
		])->getMock();

		$command->method('fetchFilesForAverageColorCalculation')->with(20)->willReturn($files);
		$command->method('calculateAverageColors')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processAverageColorCalculation($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessAverageColorCalculationReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAverageColorCalculation',
			'calculateAverageColors',
		])->getMock();

		$command->method('fetchFilesForAverageColorCalculation')->with(20)->willReturn($files);

		$result = $command->processAverageColorCalculation($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testProcessAverageColorCalculationReturnsFalseWhenCalculateAverageColorsFails(): void {
		$files = $this->createMock(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAverageColorCalculation',
			'calculateAverageColors',
		])->getMock();

		$command->method('fetchFilesForAverageColorCalculation')->with(20)->willReturn($files);
		$command->method('calculateAverageColors')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processAverageColorCalculation($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCalculatesAverageColorForValidFiles(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->limit(5)->all();

		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll');

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'calculateAverageColor',
			'fetchTable',
		])->getMock();

		$command->method('calculateAverageColor')->willReturn(['red' => 100, 'green' => 150, 'blue' => 200, 'alpha' => 255]);
		$command->method('fetchTable')->willReturn($table);

		$result = $this->callProtectedMethod($command, 'calculateAverageColors', $resultSet, $this->io);

		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCalculatesAverageColorForNonExistentFile(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->where(['id' => 9])->all();

		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll');

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'calculateAverageColor',
			'fetchTable',
		])->getMock();

		$command->method('calculateAverageColor')->willReturn(['red' => 100, 'green' => 150, 'blue' => 200, 'alpha' => 255]);
		$command->method('fetchTable')->willReturn($table);

		$this->io->expects($this->once())->method('error')->with('Status: File does not exist');

		$result = $this->callProtectedMethod($command, 'calculateAverageColors', $resultSet, $this->io);

		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCalculatesAverageColorForPngFile(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->where(['id' => 4])->all();

		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll');

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'calculateAverageColor',
			'fetchTable',
		])->getMock();

		$command->method('calculateAverageColor')->willReturn(['red' => 100, 'green' => 150, 'blue' => 200, 'alpha' => 255]);
		$command->method('fetchTable')->willReturn($table);

		$this->io->expects($this->once())->method('info')->with('Status: Cannot calculate average color for png files');

		$result = $this->callProtectedMethod($command, 'calculateAverageColors', $resultSet, $this->io);

		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCalculatesAverageColorForFile(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->where(['id' => 2])->all();

		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll');

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'calculateAverageColor',
			'fetchTable',
		])->getMock();

		$command->method('calculateAverageColor')->willReturn(['red' => 100, 'green' => 150, 'blue' => 200, 'alpha' => 255]);
		$command->method('fetchTable')->willReturn($table);

		$this->io->expects($this->once())->method('success')->with('Status: Average color calculated successfully (#6496C8FF)');

		$result = $this->callProtectedMethod($command, 'calculateAverageColors', $resultSet, $this->io);

		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testConvertImages(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->where(['id' => 2])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->equalTo(['webp' => ProcessStatus::Success]),
			$this->equalTo(['id IN' => [2]])
		);

		// Mock the Process
		$process = $this->createMock(Process::class);
		$process->method('isSuccessful')->willReturn(true);
		$process->method('getExitCodeText')->willReturn('');

		// Mock the ConsoleIo
		$this->io->expects($this->once())->method('out')->with('Creating webp image for file `../awyiss/Command/Media/TestFiles/logo-awyiss.jpg`');
		$this->io->expects($this->once())->method('success')->with('Status: ');

		// Mock the ConvertFilesCommand
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods(['fetchTable', 'convertToWebp'])->getMock();

		$command->method('fetchTable')->willReturn($table);
		$command->method('convertToWebp')->willReturn($process);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertImages', $resultSet, $this->io);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testConvertImagesFailed(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->where(['id' => 2])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->equalTo(['webp' => ProcessStatus::Fail]),
			$this->equalTo(['id IN' => [2]])
		);

		// Mock the Process
		$process = $this->createMock(Process::class);
		$process->method('isSuccessful')->willReturn(false);
		$process->method('getExitCodeText')->willReturn('DummyError');

		// Mock the ConsoleIo
		$this->io->expects($this->once())->method('error')->with('Status: DummyError');

		// Mock the ConvertFilesCommand
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods(['fetchTable', 'convertToWebp'])->getMock();

		$command->method('fetchTable')->willReturn($table);
		$command->method('convertToWebp')->willReturn($process);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertImages', $resultSet, $this->io);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}



	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testConvertNonImages(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->where(['id' => 3])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->callback(function (): true {
				return true;
			}),
			$this->equalTo(['id IN' => [3]])
		);

		// Mock the Process
		$process = $this->createMock(Process::class);
		$process->method('isSuccessful')->willReturn(true);
		$process->method('getExitCodeText')->willReturn('');

		// Mock the ConsoleIo
		$this->io->expects($this->once())->method('out')->with('Creating preview for file `../awyiss/Command/Media/TestFiles/logo-awyiss.pdf`');
		$this->io->expects($this->once())->method('success')->with('Status: ');

		// Mock the ConvertFilesCommand
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'convertToWebp',
			'fetchTable',
			'getCommand',
			'getProcess',
			'getRealImageSize',
		])->getMock();

		$command->method('convertToWebp')->willReturn($process);
		$command->method('fetchTable')->willReturn($table);
		$command->method('getCommand')->willReturn(['dummy']);
		$command->method('getProcess')->willReturn($process);
		$command->method('getRealImageSize')->willReturn([100, 100]);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertNonImages', $resultSet, $this->io, false);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);

		// Remove the preview folder created by the command
		$ls_dummyFolder = ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_pdf_preview';
		if (is_dir($ls_dummyFolder)) {
			rmdir($ls_dummyFolder);
		}
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testConvertNonImagesWithUnknownCommand(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->where(['id' => 3])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->equalTo(['preview' => ProcessStatus::Fail, 'webp' => ProcessStatus::Undefined]),
			$this->equalTo(['id IN' => [3]])
		);

		// Mock the Process
		$process = $this->createMock(Process::class);
		$process->method('isSuccessful')->willReturn(true);
		$process->method('getExitCodeText')->willReturn('');

		// Mock the ConsoleIo
		$this->io->expects($this->once())->method('out')->with('Creating preview for file `../awyiss/Command/Media/TestFiles/logo-awyiss.pdf`');
		$this->io->expects($this->once())->method('warning')->with('Status: Cannot convert filetype `pdf`');

		// Mock the ConvertFilesCommand
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'convertToWebp',
			'fetchTable',
			'getCommand',
			'getProcess',
		])->getMock();

		$command->method('convertToWebp')->willReturn($process);
		$command->method('fetchTable')->willReturn($table);
		$command->method('getCommand')->willReturn(false);
		$command->method('getProcess')->willReturn($process);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertNonImages', $resultSet, $this->io, false);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);

		// Remove the preview folder created by the command
		$ls_dummyFolder = ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_pdf_preview';
		if (is_dir($ls_dummyFolder)) {
			rmdir($ls_dummyFolder);
		}
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetCropCommand(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->all();

		// Mock the ConvertFilesCommand
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->getMock();

		// Set different crop properties on the files
		$resultSet->each(function (Media $file) use ($command) {
			if ($file->id === 1) {
				$file->crop = [
					'rotate' => 'auto',
				];
			}
			elseif ($file->id === 2) {
				$file->crop = [
					'width' => 800,
					'height' => 600,
					'resize_width' => 800,
					'resize_height' => 600,
					'x' => 100,
					'y' => 50,
				];
			}
			elseif ($file->id === 4) {
				$file->crop = [
					'width' => 1000,
					'height' => 1000,
					'resize_width' => 500,
					'resize_height' => 500,
					'x' => 80,
					'y' => 60,
				];
			}
			else {
				return;
			}

			// Call the method
			$result = $this->callProtectedMethod($command, 'getCommand', $file, 'crop');

			if ($file->id === 1) {
				$this->assertSame([
					'original' => [
						'mogrify',
						'-auto-orient',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/_docx_preview/logo-awyiss.jpg',
					],
					'webp' => null,
				], $result);
			}
			elseif ($file->id === 2) {
				$this->assertSame([
					'original' => [
						'convert',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
						'-crop',
						'800x600+100+50',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
					],
					'webp' => null,
				], $result);
			}
			elseif ($file->id === 4) {
				$this->assertSame([
					'original' => [
						'convert',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
						'-crop',
						'1000x1000+80+60',
						'-resize',
						'500x500',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
					],
					'webp' => null,
				], $result);
			}
			else {
				$this->assertSame('0x0+0+0', $result['original'][3]);
			}
		});
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testGetResizeCommand(): void {
		/** @var \Awyiss\Model\Table\MediaTable $lo_table */
		$lo_table = $this->fetchTable('Media');
		$resultSet = $lo_table->find()->all();

		// Mock the ConvertFilesCommand
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->getMock();

		// Set different crop properties on the files
		$resultSet->each(function (Media $file) use ($command) {
			$resizedFile = new MediaResizedImage();

			$resizedFile->name = $file->name;
			$resizedFile->path = $file->path;
			$resizedFile->width = 1280;
			$resizedFile->height = 1280;
			$resizedFile->media = $file;

			if ($file->id === 1) {
				$resizedFile->strategy = ResizeStrategy::Contain;
				$file->focusPoint = '2,1';
			}
			elseif ($file->id === 2) {
				$resizedFile->strategy = ResizeStrategy::Cover;
				$file->focusPoint = '0,2';
			}
			elseif ($file->id === 3) {
				$resizedFile->strategy = ResizeStrategy::Crop;
				$file->focusPoint = '1,0';
			}
			elseif ($file->id === 4) {
				$resizedFile->strategy = ResizeStrategy::Stretch;
				$file->focusPoint = '2,2';
			}
			else {
				return;
			}

			// Call the method
			$result = $this->callProtectedMethod($command, 'getCommand', $resizedFile, 'resize');

			if ($file->id === 1) {
				$this->assertSame([
					'convert',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/_docx_preview/logo-awyiss.jpg',
					'-resize',
					'1280x1280',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.docx',
				], $result);
			}
			elseif ($file->id === 2) {
				$this->assertSame([
					'convert',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
					'-resize',
					'1280x1280^',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
				], $result);
			}
			elseif ($file->id === 3) {
				$this->assertSame([
					'convert',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/_pdf_preview/logo-awyiss.jpg',
					'-resize',
					'1280x1280^',
					'-gravity',
					'West',
					'-extent',
					'1280x1280',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.pdf',
				], $result);
			}
			elseif ($file->id === 4) {
				$this->assertSame([
					'convert',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
					'-resize',
					'1280x1280!',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
				], $result);
			}
		});
	}
}
