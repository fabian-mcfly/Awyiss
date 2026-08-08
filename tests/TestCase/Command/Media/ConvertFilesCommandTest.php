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
use Cake\Core\Configure;
use Cake\Datasource\ResultSetInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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

		$this->args = $this->createStub(Arguments::class);
		$this->args->method('getOption')->willReturnMap([
			['quiet', false],
			['limit', '20'],
			['include-avif', true],
			['include-webp', true],
			['retry-failed', true],
		]);

		$this->io = $this->createMock(ConsoleIo::class);
	}


	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		if (file_exists(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_avif')) {
			new Process(['rm', '-r', ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_avif'])->run();
		}

		if (file_exists(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_webp')) {
			new Process(['rm', '-r', ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_webp'])->run();
		}

		if (file_exists(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_docx_preview')) {
			new Process(['rm', '-r', ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_docx_preview'])->run();
		}

		if (file_exists(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_pdf_preview')) {
			new Process(['rm', '-r', ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_pdf_preview'])->run();
		}
	}


	/**
	 * @return void
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testBuildOptionParserIncludesSetOptions(): void {
		$parser = new ConsoleOptionParser('test');

		$command = new ConvertFilesCommand();

		$command->buildOptionParser($parser);
		$options = $parser->options();

		$this->assertArrayHasKey('include-avif', $options);
		$this->assertTrue($options['include-avif']->isBoolean());

		$this->assertArrayHasKey('include-webp', $options);
		$this->assertTrue($options['include-webp']->isBoolean());

		$this->assertArrayHasKey('limit', $options);
		$this->assertEquals('5', $options['limit']->defaultValue());

		$this->assertArrayHasKey('retry-failed', $options);
		$this->assertTrue($options['retry-failed']->isBoolean());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testExecuteSuccess(): void {
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'processCropFiles',
			'processNonImageFiles',
			'processAvifConversion',
			'processWebpConversion',
			'processResizing',
			'processAverageColorCalculation',
		])->getMock();

		// Mock methods in the command to avoid full processing
		$command->expects($this->once())->method('processCropFiles')->willReturn(3);

		$command->expects($this->once())->method('processNonImageFiles')->willReturn(3);

		$command->expects($this->once())->method('processAvifConversion')->willReturn(3);

		$command->expects($this->once())->method('processWebpConversion')->willReturn(3);

		$command->expects($this->once())->method('processResizing')->willReturn(3);

		$command->expects($this->once())->method('processAverageColorCalculation')->willReturn(3);

		$result = $command->execute($this->args, $this->io);

		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testExecuteWithError(): void {
		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'processCropFiles',
			'processNonImageFiles',
			'processAvifConversion',
			'processWebpConversion',
			'processResizing',
			'processAverageColorCalculation',
		])->getMock();

		// Mock methods in the command to avoid full processing
		$command->expects($this->once())->method('processCropFiles')->willReturn(3);

		$command->expects($this->once())->method('processNonImageFiles')->willReturn(3);

		$command->expects($this->once())->method('processAvifConversion')->willReturn(false);

		$command->expects($this->never())->method('processWebpConversion');

		$command->expects($this->never())->method('processResizing');

		$command->expects($this->never())->method('processAverageColorCalculation');

		$result = $command->execute($this->args, $this->io);

		$this->assertEquals(CommandInterface::CODE_ERROR, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessCropFilesReturnsFileCountWhenFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchCropFiles',
			'cropImages',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchCropFiles')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('cropImages')->with($files, $this->io)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processCropFiles($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessCropFilesReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchCropFiles',
			'cropImages',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchCropFiles')->with(20)->willReturn($files);

		$result = $command->processCropFiles($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessCropFilesReturnsFalseWhenCropImagesFails(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchCropFiles',
			'cropImages',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchCropFiles')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('cropImages')->with($files, $this->io)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processCropFiles($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessNonImageFilesReturnsFileCountWhenFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchNonImageFiles',
			'convertNonImages',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchNonImageFiles')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('convertNonImages')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processNonImageFiles($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessNonImageFilesReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchNonImageFiles',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchNonImageFiles')->with(20)->willReturn($files);

		$result = $command->processNonImageFiles($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessNonImageFilesReturnsFalseWhenConvertNonImagesFails(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchNonImageFiles',
			'convertNonImages',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchNonImageFiles')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('convertNonImages')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processNonImageFiles($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessAvifConversionReturnsFileCountWhenFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAvifConversion',
			'convertImagesToAvif',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForAvifConversion')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('convertImagesToAvif')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processAvifConversion($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessAvifConversionReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAvifConversion',
			'convertImagesToAvif',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForAvifConversion')->with(20)->willReturn($files);

		$result = $command->processAvifConversion($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessAvifConversionReturnsFalseWhenConvertImagesFails(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAvifConversion',
			'convertImagesToAvif',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForAvifConversion')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('convertImagesToAvif')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processAvifConversion($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessWebpConversionReturnsFileCountWhenFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForWebpConversion',
			'convertImagesToWebp',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForWebpConversion')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('convertImagesToWebp')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processWebpConversion($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessWebpConversionReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForWebpConversion',
			'convertImagesToWebp',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForWebpConversion')->with(20)->willReturn($files);

		$result = $command->processWebpConversion($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessWebpConversionReturnsFalseWhenConvertImagesFails(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForWebpConversion',
			'convertImagesToWebp',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForWebpConversion')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('convertImagesToWebp')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processWebpConversion($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessResizingReturnsFileCountWhenFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForResizing',
			'resizeImages',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForResizing')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('resizeImages')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processResizing($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessResizingReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForResizing',
			'resizeImages',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForResizing')->with(20)->willReturn($files);

		$result = $command->processResizing($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessResizingReturnsFalseWhenResizeImagesFails(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForResizing',
			'resizeImages',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForResizing')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('resizeImages')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processResizing($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessAverageColorCalculationReturnsFileCountWhenFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAverageColorCalculation',
			'calculateAverageColors',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForAverageColorCalculation')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('calculateAverageColors')->with($files)->willReturn(CommandInterface::CODE_SUCCESS);

		$result = $command->processAverageColorCalculation($this->args, $this->io);

		$this->assertEquals(5, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessAverageColorCalculationReturnsZeroWhenNoFilesExist(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(0);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAverageColorCalculation',
			'calculateAverageColors',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForAverageColorCalculation')->with(20)->willReturn($files);

		$result = $command->processAverageColorCalculation($this->args, $this->io);

		$this->assertEquals(0, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testProcessAverageColorCalculationReturnsFalseWhenCalculateAverageColorsFails(): void {
		$files = $this->createStub(ResultSetInterface::class);
		$files->method('count')->willReturn(5);

		$command = $this->getMockBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchFilesForAverageColorCalculation',
			'calculateAverageColors',
		])->getMock();

		$command->expects($this->atLeastOnce())->method('fetchFilesForAverageColorCalculation')->with(20)->willReturn($files);
		$command->expects($this->atLeastOnce())->method('calculateAverageColors')->with($files)->willReturn(CommandInterface::CODE_ERROR);

		$result = $command->processAverageColorCalculation($this->args, $this->io);

		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testCalculateAverageColorsForValidFiles(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->limit(5)->all();

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
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testCalculateAverageColorsForNonExistentFile(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 9])->all();

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
	 */
	public function testCalculateAverageColorsForPngFile(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 4])->all();

		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll');

		$command = $this->getStubBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchTable',
		])->getStub();

		$command->method('fetchTable')->willReturn($table);

		$this->io->expects($this->once())->method('info')->with('Status: Cannot calculate average color for png files');

		$result = $this->callProtectedMethod($command, 'calculateAverageColors', $resultSet, $this->io);

		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testCalculateAverageColorsForFile(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 2])->all();

		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll');

		$command = $this->getStubBuilder(ConvertFilesCommand::class)->onlyMethods([
			'calculateAverageColor',
			'fetchTable',
		])->getStub();

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
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testCalculateAverageColor(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $table->find()->where(['id' => 2])->first();

		$command = $this->getStubBuilder(ConvertFilesCommand::class)->getStub();

		$result = $this->callProtectedMethod($command, 'calculateAverageColor', $media->pathAbsolute, $this->io);

		$this->assertEquals([
			'red' => 57,
			'green' => 75,
			'blue' => 76,
			'alpha' => 255,
		], $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testConvertImagesToAvif(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 2])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->equalTo(['avif' => ProcessStatus::Success]),
			$this->equalTo(['id IN' => [2]])
		);

		// Mock the ConsoleIo
		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('out')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Creating directory `../awyiss/Command/Media/TestFiles/_avif` for Avif file', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Creating Avif file for file `../awyiss/Command/Media/TestFiles/logo-awyiss.jpg`', $parameters);
			}
		});

		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('success')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Status: Directory created', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Status: Avif file created', $parameters);
			}
		});

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->onlyMethods(['fetchTable'])->getStub();

		$command->method('fetchTable')->willReturn($table);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertImagesToAvif', $resultSet, $this->io);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
		$this->assertFileExists(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_avif' . DS . 'logo-awyiss.jpg.avif');
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testConvertImagesToAvifFailed(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 10])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->equalTo(['avif' => ProcessStatus::Fail]),
			$this->equalTo(['id IN' => [10]])
		);

		// Mock the ConsoleIo
		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('out')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Creating directory `../awyiss/Command/Media/TestFiles/_avif` for Avif file', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Creating Avif file for file `../awyiss/Command/Media/TestFiles/logo-awyiss2.jpg`', $parameters);
			}
		});
		$this->io->expects($this->once())
			->method('error')
			->with(sprintf('Status: File "logo-awyiss2.jpg" not found in directory "%s/webroot/../awyiss/Command/Media/TestFiles"', ROOT));

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->onlyMethods(['fetchTable'])->getStub();

		$command->method('fetchTable')->willReturn($table);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertImagesToAvif', $resultSet, $this->io);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
		$this->assertFileDoesNotExist(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_avif' . DS . 'logo-awyiss2.jpg.avif');
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testConvertImagesToWebp(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 2])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->equalTo(['webp' => ProcessStatus::Success]),
			$this->equalTo(['id IN' => [2]])
		);

		// Mock the ConsoleIo
		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('out')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Creating directory `../awyiss/Command/Media/TestFiles/_webp` for WebP file', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Creating WebP file for file `../awyiss/Command/Media/TestFiles/logo-awyiss.jpg`', $parameters);
			}
		});

		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('success')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Status: Directory created', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Status: WebP file created', $parameters);
			}
		});

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->onlyMethods(['fetchTable'])->getStub();

		$command->method('fetchTable')->willReturn($table);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertImagesToWebp', $resultSet, $this->io);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
		$this->assertFileExists(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_webp' . DS . 'logo-awyiss.jpg.webp');
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testConvertImagesToWebpFailed(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 10])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->equalTo(['webp' => ProcessStatus::Fail]),
			$this->equalTo(['id IN' => [10]])
		);

		// Mock the ConsoleIo
		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('out')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Creating directory `../awyiss/Command/Media/TestFiles/_webp` for WebP file', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Creating WebP file for file `../awyiss/Command/Media/TestFiles/logo-awyiss2.jpg`', $parameters);
			}
		});
		$this->io->expects($this->once())
			->method('error')
			->with(sprintf('Status: File "logo-awyiss2.jpg" not found in directory "%s/webroot/../awyiss/Command/Media/TestFiles"', ROOT));

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->onlyMethods(['fetchTable'])->getStub();

		$command->method('fetchTable')->willReturn($table);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertImagesToWebp', $resultSet, $this->io);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
		$this->assertFileDoesNotExist(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_webp' . DS . 'logo-awyiss2.jpg.webp');
	}



	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	public function testConvertNonImages(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 3])->all();

		Configure::write('AvailableCommands.imageMagick.pdf', true);

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->callback(function (): true {
				return true;
			}),
			$this->equalTo(['id IN' => [3]])
		);

		// Mock the ConsoleIo
		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('out')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Creating directory `../awyiss/Command/Media/TestFiles/_pdf_preview` for file preview', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Creating preview for file `../awyiss/Command/Media/TestFiles/logo-awyiss.pdf`', $parameters);
			}
		});

		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('success')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Status: Directory created', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Status: OK', $parameters);
			}
		});

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchTable',
			'getRealImageSize',
		])->getStub();

		$command->method('fetchTable')->willReturn($table);
		$command->method('getRealImageSize')->willReturn([100, 100]);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertNonImages', $resultSet, $this->io, false, false);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
		$this->assertFileExists(ROOT . DS . 'awyiss' . DS . 'Command' . DS . 'Media' . DS . 'TestFiles' . DS . '_pdf_preview' . DS . 'logo-awyiss.jpg');

		Configure::delete('AvailableCommands.imageMagick.pdf');
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testConvertNonImagesWithUnknownCommand(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->where(['id' => 1])->all();

		// Mock the MediaTable
		$table = $this->createMock(MediaTable::class);
		$table->expects($this->once())->method('updateAll')->with(
			$this->equalTo(['preview' => ProcessStatus::Fail, 'avif' => ProcessStatus::Undefined, 'webp' => ProcessStatus::Undefined]),
			$this->equalTo(['id IN' => [1]])
		);

		// Mock the Process
		$process = $this->createStub(Process::class);
		$process->method('isSuccessful')->willReturn(true);
		$process->method('getExitCodeText')->willReturn('');

		// Mock the ConsoleIo
		$invokedCount = $this->exactly(2);
		$this->io->expects($invokedCount)->method('out')->willReturnCallback(function ($parameters) use ($invokedCount) {
			if ($invokedCount->numberOfInvocations() === 1) {
				$this->assertSame('Creating directory `../awyiss/Command/Media/TestFiles/_docx_preview` for file preview', $parameters);
			}
			elseif ($invokedCount->numberOfInvocations() === 2) {
				$this->assertSame('Creating preview for file `../awyiss/Command/Media/TestFiles/logo-awyiss.docx`', $parameters);
			}
		});
		$this->io->expects($this->once())->method('warning')->with('Status: Cannot convert filetype `docx`');

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->onlyMethods([
			'fetchTable',
			'getPreviewCommand',
			'getProcess',
		])->getStub();

		$command->method('fetchTable')->willReturn($table);
		$command->method('getPreviewCommand')->willReturn(false);
		$command->method('getProcess')->willReturn($process);

		// Call the method
		$result = $this->callProtectedMethod($command, 'convertNonImages', $resultSet, $this->io, false, false);

		// Assert the result
		$this->assertEquals(CommandInterface::CODE_SUCCESS, $result);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetCropCommand(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->all();

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->getStub();

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
					'resizeWidth' => 800,
					'resizeHeight' => 600,
					'x' => 100,
					'y' => 50,
				];
			}
			elseif ($file->id === 4) {
				$file->crop = [
					'width' => 1000,
					'height' => 1000,
					'resizeWidth' => 500,
					'resizeHeight' => 500,
					'x' => 80,
					'y' => 60,
				];
			}
			else {
				return;
			}

			// Call the method
			$result = $this->callProtectedMethod($command, 'getCropCommand', $file);

			if ($file->id === 1) {
				$this->assertSame([
					'original' => [
						'magick',
						'mogrify',
						'-auto-orient',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/_docx_preview/logo-awyiss.jpg',
					],
					'avif' => null,
					'webp' => null,
				], $result);
			}
			elseif ($file->id === 2) {
				$this->assertSame([
					'original' => [
						'magick',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
						'-crop',
						'800x600+100+50',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
					],
					'avif' => null,
					'webp' => null,
				], $result);
			}
			elseif ($file->id === 4) {
				$this->assertSame([
					'original' => [
						'magick',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
						'-crop',
						'1000x1000+80+60',
						'-resize',
						'500x500',
						WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
					],
					'avif' => null,
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
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetResizeCommand(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		$resultSet = $table->find()->all();

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->getStub();

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
			$result = $this->callProtectedMethod($command, 'getResizeCommand', $resizedFile);

			if ($file->id === 1) {
				$this->assertSame([
					'magick',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/_docx_preview/logo-awyiss.jpg',
					'-resize',
					'1280x1280',
					'-quality',
					70,
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.docx',
				], $result);
			}
			elseif ($file->id === 2) {
				$this->assertSame([
					'magick',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
					'-resize',
					'1280x1280^',
					'-quality',
					70,
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
				], $result);
			}
			elseif ($file->id === 3) {
				$this->assertSame([
					'magick',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/_pdf_preview/logo-awyiss.jpg',
					'-resize',
					'1280x1280^',
					'-gravity',
					'West',
					'-extent',
					'1280x1280',
					'-quality',
					70,
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.pdf',
				], $result);
			}
			elseif ($file->id === 4) {
				$this->assertSame([
					'magick',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
					'-resize',
					'1280x1280!',
					'-quality',
					70,
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
				], $result);
			}
		});
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testGetResizeCommandRespectsQualitySetting(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = $this->fetchTable('Media');
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $table->find()->where(['id' => 2])->first();

		Configure::write('Awyiss.Media.Frontend.resizing.quality', 54);

		// Mock the ConvertFilesCommand
		$command = $this->getStubBuilder(ConvertFilesCommand::class)->getStub();

		// Set different crop properties on the files
		for ($i = 1; $i <= 4; $i++) {
			$resizedFile = new MediaResizedImage();

			$resizedFile->name = $media->name;
			$resizedFile->path = $media->path;
			$resizedFile->width = 1280;
			$resizedFile->height = 1280;
			$resizedFile->media = $media;

			if ($media->id === 1) {
				$resizedFile->strategy = ResizeStrategy::Contain;
				$media->focusPoint = '2,1';
			}
			elseif ($media->id === 2) {
				$resizedFile->strategy = ResizeStrategy::Cover;
				$media->focusPoint = '0,2';
			}
			elseif ($media->id === 3) {
				$resizedFile->strategy = ResizeStrategy::Crop;
				$media->focusPoint = '1,0';
			}
			elseif ($media->id === 4) {
				$resizedFile->strategy = ResizeStrategy::Stretch;
				$media->focusPoint = '2,2';
			}
			else {
				return;
			}

			// Call the method
			$result = $this->callProtectedMethod($command, 'getResizeCommand', $resizedFile);

			if ($media->id === 1) {
				$this->assertSame([
					'magick',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/_docx_preview/logo-awyiss.jpg',
					'-resize',
					'1280x1280',
					'-quality',
					54,
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.docx',
				], $result);
			}
			elseif ($media->id === 2) {
				$this->assertSame([
					'magick',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
					'-resize',
					'1280x1280^',
					'-quality',
					54,
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.jpg',
				], $result);
			}
			elseif ($media->id === 3) {
				$this->assertSame([
					'magick',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/_pdf_preview/logo-awyiss.jpg',
					'-resize',
					'1280x1280^',
					'-gravity',
					'West',
					'-extent',
					'1280x1280',
					'-quality',
					54,
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.pdf',
				], $result);
			}
			elseif ($media->id === 4) {
				$this->assertSame([
					'magick',
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
					'-resize',
					'1280x1280!',
					'-quality',
					54,
					WWW_ROOT . '../awyiss/Command/Media/TestFiles/logo-awyiss.png',
				], $result);
			}
		}
	}
}
