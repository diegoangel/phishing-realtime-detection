<?php declare(strict_types=1);

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use SomeCompanyNamespace\Services\Phishing\PhishingState;
use SomeCompanyNamespace\Services\Phishing\Processors\QueueProcessor;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;
use SomeCompanyNamespace\Services\Phishing\Requests\Request;

/**
 * Request State Flow tested in this test suite
 * Chart refs: State name along with what processor is responsible for setting the corresponding state
 *
 *  +------------------------------------+
 *  |  URL SCAN REQUEST (HIGH PRIORITY)  |
 *  +------------------------------------+
 *
 *  RECEIVED (handler) ---> DELAYED (http) ---> QUEUED (queue)
 *
 *  +----------------+
 *  | SCAN URL       |
 *  +----------------+
 *  DEQUEUED_FOR_FIRST_SCAN (queue first scan) ---> DELAYED_FIRST_SCAN (HTTP) ---> QUEUED (queue delayed)
 *
 *  +----------------+
 *  | GET REPORT     |
 *  +----------------+
 *  DEQUEUED_FOR_GETTING_REPORTS (queue delayed) ---> PROCESSING (HTTP) ---> added again to the queue if attemp is less than 5 (queue delayed)
 *
 * @covers SomeCompanyNamespace\Services\Phishing\Processors\QueueProcessor
 */
class QueueProcessorTest extends TestCase
{
	private $mockQueueClient;
	private $sqsMessagesFromDelayedQueue;

	public function setUp(): void
	{
		parent::setUp();

		$this->mockQueueClient = m::mock(
			'overload:SomeCompanyNamespace\Services\Phishing\Clients\QueueClient',
			'SomeCompanyNamespace\Services\Phishing\Interfaces\QueueClientInterface'
		)->makePartial();

		// The file containing a JSON string to simulate what returns by gathering messages from the queue
		$this->sqsMessagesFromDelayedQueue = file_get_contents(__DIR__ . '/../Fixtures/sqs_queue_processor_delayed_messages_consumer.json');
	}

	public function tearDown(): void
	{
		unset($this->mockQueueClient);
		unset($this->sqsMessagesFromDelayedQueue);
		parent::tearDown();
	}

	/**
	 * Test when the cron for processing delayed reports runs and it creates the composite Request object which is
	 * sent later to the HTTP processor
	 * It's expected that 3 request onjects are added to the $requests attribute
     *
	 * See the GET REPORT flow chart above
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_CompositeRequest_isCreated_WhenCronForGettingDelayedReportsRuns(): Request
	{
		//$this->markTestSkipped();
		$request = new ScanUrlRequest();

		$this->mockQueueClient->shouldReceive('getNumberOfMessagesInQueue')
			->once()
			->withNoArgs()
			->andReturn(3);
		$this->mockQueueClient->shouldReceive('consumeMessage')
			->once()
			->andReturnUsing(function() {
				$arrayOfMessages = json_decode($this->sqsMessagesFromDelayedQueue, true);
				return $arrayOfMessages['Messages'];
			});
		$this->mockQueueClient->shouldReceive('deleteMessage')
			->times(3)
			->withAnyArgs()
			->andReturn([]);

		(new QueueProcessor(null))->process($request);

		$this->assertTrue($request->isComposite(), 'Check if the request object is composite (a.k.a has nested requests objects)');
		$this->assertEquals(PhishingState::DEQUEUED_FOR_GETTING_REPORTS, $request->getState());

		return $request;
	}

	/**
	 * Test when the cron for processing delayed reports runs and after created the composite Request object and being processed
	 * by the HTTP procesor only 1 of 3 nested request objects is processed because is the only one with less than 5 attempts
	 *
	 * See the the last link from the GET REPORT flow chart above in the class comment
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @depends test_CompositeRequest_isCreated_WhenCronForGettingDelayedReportsRuns
	 * @medium
	 */
	public function test_Requests_AreAddedAgainToTheQueue_WhenAttemptIsLessOrEqualsToFive(Request $request): void
	{
		// force the state to PROCESSING which should be set by the previous processor (HttpProcessor)
		$request->setState(PhishingState::PROCESSING);

		$this->mockQueueClient->shouldReceive('sendMessage')
			->times(1)
			->withAnyArgs()
			->andReturn(
				$this->awsResultReturnTypeBuilder()
			);

		(new QueueProcessor(null))->process($request);

		$nestedRequests = 0;
		foreach ($request as $scanUrlRequest) {
			$this->assertGreaterThan(5, $scanUrlRequest->getAttempts());
			$nestedRequests++;
		}
		$this->assertEquals(2, $nestedRequests);
	}

	/**
	 * Test when the cron for processing first scan url runs and we add to the delayed queue those urls
	 * which don't have the report generated.
	 *
	 * See the SCAN URL flow chart above in the class comment
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_Requests_AreAddedToTheDelayedQueue_WhenFirstScanHasErrnoEqualsToOne(): void
	{
		$request = $this->compositeRequestBuilder();

		// force the state to DEQUEUED_FOR_FIRST_SCAN which should be set by the previous processor (HttpProcessor)
		$request->setState(PhishingState::DEQUEUED_FOR_FIRST_SCAN);

		$this->mockQueueClient->shouldReceive('sendMessage')
			->times(3)
			->withAnyArgs()
			->andReturn(
				$this->awsResultReturnTypeBuilder()
			);

		(new QueueProcessor(null))->process($request);
	}

	/**
	 * Test when we received an URL for its first scan whihc have HIGH PRIORITY and we have to add the URL to the
	 * delayed queue because its reports it's not ready yet
	 *
	 *  See the URL SCAN REQUEST (HIGH PRIORITY) flow chart above in the class comment
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_Request_isAddedToTheDelayedQueue_WhenHasHighPriorityAndIsFirstScan(): void
	{
		$request = new ScanUrlRequest();
		$request->setUrl('www.alchemer.com/survey.php');
		$request->setSurveyId(122223);
		$request->setCustomerId(233334);
		$request->setState(PhishingState::DELAYED);
		$request->setPriority(ScanUrlRequest::PRIORITY_HIGH);
		$request->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$request->setAttempts(0);

		$this->mockQueueClient->shouldReceive('sendMessage')
			->times(1)
			->withAnyArgs()
			->andReturn(
				$this->awsResultReturnTypeBuilder()
			);

		(new QueueProcessor(null))->process($request);

		$this->assertEquals(PhishingState::QUEUED, $request->getState());
	}

	/**
	 * Builder method for returning a mock of a ScanUrlRequest with nested ScanUrlRequests objects (composite)
	 *
	 * @return Request
	 */
	private function compositeRequestBuilder(): Request
	{
		// Composite Request object
		$request = new ScanUrlRequest();
		// First URL
		$firstRequest = new ScanUrlRequest();
		$firstRequest->setUrl('www.first.com/survey.php');
		$firstRequest->setSurveyId(122223);
		$firstRequest->setCustomerId(233334);
		$firstRequest->setState(PhishingState::DELAYED_FIRST_SCAN);
		$firstRequest->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$firstRequest->setAttempts(0);
		$request->add($firstRequest);
		// Second URL
		$secondRequest = new ScanUrlRequest();
		$secondRequest->setUrl('www.second.com/survey.php');
		$secondRequest->setSurveyId(566667);
		$secondRequest->setCustomerId(677778);
		$secondRequest->setState(PhishingState::DELAYED_FIRST_SCAN);
		$secondRequest->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$secondRequest->setAttempts(0);
		$request->add($secondRequest);
		// Third URL
		$thirdRequest = new ScanUrlRequest();
		$thirdRequest->setUrl('www.third.com/survey.php');
		$thirdRequest->setSurveyId(344445);
		$thirdRequest->setCustomerId(455556);
		$thirdRequest->setState(PhishingState::DELAYED_FIRST_SCAN);
		$thirdRequest->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$thirdRequest->setAttempts(0);
		$request->add($thirdRequest);

		return $request;
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
