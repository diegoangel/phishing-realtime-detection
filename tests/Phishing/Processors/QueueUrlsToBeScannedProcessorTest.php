<?php declare(strict_types=1);

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use SomeCompanyNamespace\Services\Phishing\PhishingState;
use SomeCompanyNamespace\Services\Phishing\Processors\QueueUrlsToBeScannedProcessor;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;
use SomeCompanyNamespace\Services\Phishing\Requests\Request;

/**
 * Request State Flow tested in this test suite
 * Chart refs: State name along with what processor is responsible for setting the corresponding state
 *
 *  +---------------------+
 *  |  URL SCAN REQUEST  |
 *  +--------------------+
 *
 *  CACHED (handler) ---> QUEUED (queue first scan)
 *
 *  +----------------+
 *  | SCAN URL       |
 *  +----------------+
 *  EMPTY (queue first scan) ---> DEQUEUED_FOR_FIRST_SCAN (queue first scan)
 *
 * @covers SomeCompanyNamespace\Services\Phishing\Processors\QueueUrlsToBeScannedProcessor
 */
class QueueUrlsToBeScannedProcessorTest extends TestCase
{

	private $mockQueueClient;
	private $sqsMessagesFromFirstScanQueue;
	private $memcacheClientMock;

	public function setUp(): void
	{
		parent::setUp();

		$this->mockQueueClient = m::mock(
			'overload:SomeCompanyNamespace\Services\Phishing\Clients\QueueClient',
			'SomeCompanyNamespace\Services\Phishing\Interfaces\QueueClientInterface'
		)->makePartial();

		$this->mockMemcacheClient = m::mock(
			'overload:SomeCompanyNamespace\Services\Phishing\Clients\CacheClient',
			'SomeCompanyNamespace\Services\Phishing\Interfaces\CacheClientInterface'
		)->makePartial();

		// The file containing a JSON string to simulate what returns by gathering messages from the queue
		$this->sqsMessagesFromFirstScanQueue = file_get_contents(__DIR__ . '/../Fixtures/sqs_queue_processor_first_scan_messages_consumer.json');
	}

	public function tearDown(): void
	{
		unset($this->mockQueueClient);
		unset($this->sqsMessagesFromFirstScanQueue);
		unset($this->mockMemcacheClient);
		parent::tearDown();
	}

	/**
	 * Test when the cron for processing first scan url runs and it creates the composite Request object which is
	 * sent later to the HTTP processor
	 * It's expected that 3 request onjects are added to the $requests attribute
	 *
	 * See the SCAN URL flow chart above
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_CompositeRequest_isCreated_WhenCronForFirstScanRuns(): void
	{
		$request = new ScanUrlRequest();

		$this->mockQueueClient->shouldReceive('getNumberOfMessagesInQueue')
			->once()
			->withNoArgs()
			->andReturn(3);
		$this->mockQueueClient->shouldReceive('consumeMessage')
			->once()
			->andReturnUsing(function() {
				$arrayOfMessages = json_decode($this->sqsMessagesFromFirstScanQueue, true);
				return $arrayOfMessages['Messages'];
			});
		$this->mockQueueClient->shouldReceive('deleteMessage')
			->times(3)
			->withAnyArgs()
			->andReturn([]);

		$this->mockMemcacheClient->shouldReceive('get')
			->withAnyArgs()
			->andReturn(true);

		(new QueueUrlsToBeScannedProcessor(null))->process($request);

		$this->assertTrue($request->isComposite(), 'Check if the request object is composite (a.k.a has nested requests objects)');
		$this->assertEquals(PhishingState::DEQUEUED_FOR_FIRST_SCAN, $request->getState());
	}

	/**
	 * Test when we received an URL for its first scan and it has to be added to the queue after being cached
	 *
	 *  See the URL SCAN REQUEST flow chart above in the class comment
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_Request_isAddedToFirstScanQueue_WhenStateisCached(): void
	{
		$request = new ScanUrlRequest();
		$request->setUrl('www.alchemer.com/survey.php');
		$request->setSurveyId(122223);
		$request->setCustomerId(233334);
		$request->setState(PhishingState::CACHED);
		$request->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$request->setAttempts(0);

		$this->mockQueueClient->shouldReceive('sendMessage')
			->times(1)
			->withAnyArgs()
			->andReturn(
				$this->awsResultReturnTypeBuilder()
			);

		(new QueueUrlsToBeScannedProcessor(null))->process($request);

		$this->assertEquals(PhishingState::QUEUED, $request->getState());
	}

	/**
	 * A sample of the AWS\Result return for the sendMessage method
	 *
	 * @return string[]
	 */
	private function awsResultReturnTypeBuilder(): array
	{
		return [
			'MD5OfMessageAttributes' => 'non_url_encoded_message_attribute_string',
			'MD5OfMessageBody' => 'non_url_encoded_message_body_string',
			'MD5OfMessageSystemAttributes' => 'non_url_encoded_message system attribute string',
			'MessageId' => 'messageId_of_the_message_sent_to_the_queue',
			'SequenceNumber' => 'this_parameter_applies_only_to_fifo_queues',
		];
	}
}
