<?php declare(strict_types=1);

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase as Testcase;
use SomeCompanyNamespace\Services\Phishing\PhishingState;
use SomeCompanyNamespace\Services\Phishing\Processors\HttpProcessor;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;

/**
 * Request State Flow tested in this test suite
 * Chart refs: State name along with what processor is responsible for setting the corresponding state
 *
 *  +--------------------+
 *  |  URL SCAN REQUEST  |
 *  +--------------------+
 *
 *  RECEIVED (handler) ---> DELAYED (http)
 *      |              \
 *      v               ------> VENDOR RESPONSE RECEIVED (http)
 *  MALICIOUS (http)
 *
 *  +----------------+
 *  | SCAN URL       |
 *  +----------------+
 *  DEQUEUED_FOR_FIRST_SCAN (queue first scan) ---> DELAYED_FIRST_SCAN (http)
 *      |                  \
 *      v                   ------> VENDOR RESPONSE RECEIVED (http)
 *  MALICIOUS (http)
 *
 *  +----------------+
 *  | GET REPORT     |
 *  +----------------+
 *  DEQUEUED_FOR_GETTING_REPORTS (queue delayed) ---> DELAYED (http)
 *      |                  \
 *      v                   ------> VENDOR RESPONSE RECEIVED (http)
 *  MALICIOUS (http)
 *
 * @covers \SomeCompanyNamespace\Services\Phishing\Processors\HttpProcessor
 */
class HttpProcessorTest extends TestCase
{
	private $mockSlashNextClient;
	private $mockSaveMaliciousUrl;
	private $mockLogger;
	private $compositeRequest;

	public function setUp(): void
	{
		parent::setUp();

		$this->mockSlashNextClient = m::mock(
			'overload:SomeCompanyNamespace\Services\Phishing\Clients\SlashNextClient',
			'SomeCompanyNamespace\Services\Phishing\Interfaces\PhishingClientInterface'
		)->makePartial();

		$this->mockLogger = m::mock('overload:SomeCompanyNamespace\Services\Phishing\Logger')->makePartial();

		m::getConfiguration()->setConstantsMap([
			'SomeCompanyNamespace\Services\Phishing\Helpers\SaveMaliciousUrl' => [
				'STATUS' => 'malicious'
			]
		]);
		$this->mockSaveMaliciousUrl = m::mock('alias:SomeCompanyNamespace\Services\Phishing\Helpers\SaveMaliciousUrl');

		// Composite Request object
		$request = new ScanUrlRequest();
		// Benign URL
		$benignRequest = new ScanUrlRequest();
		$benignRequest->setUrl('www.good.com/index.php');
		$benignRequest->setSurveyId(122223);
		$benignRequest->setCustomerId(233334);
		$benignRequest->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$request->add($benignRequest);
		// Delayed URL
		$delayedRequest = new ScanUrlRequest();
		$delayedRequest->setUrl('www.delayed.com/index.php');
		$delayedRequest->setSurveyId(566667);
		$delayedRequest->setCustomerId(677778);
		$delayedRequest->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$request->add($delayedRequest);
		// Malicious URL
		$maliciousRequest = new ScanUrlRequest();
		$maliciousRequest->setUrl('www.bad.com/index.php');
		$maliciousRequest->setSurveyId(344445);
		$maliciousRequest->setCustomerId(455556);
		$maliciousRequest->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$request->add($maliciousRequest);
		// Redirector URL, verdict checked agaisnt "landingURL" attribute
		$maliciousRedirectorRequest = new ScanUrlRequest();
		$maliciousRedirectorRequest->setUrl('www.badredirector.com/index.php');
		$maliciousRedirectorRequest->setSurveyId(566667);
		$maliciousRedirectorRequest->setCustomerId(677778);
		$maliciousRedirectorRequest->setPhishingScanId('YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY');
		$request->add($maliciousRedirectorRequest);

		$this->compositeRequest = $request;
	}

	public function tearDown(): void
	{
		unset($this->mockSlashNextClient);
		unset($this->compositeRequest);
		unset($this->mockLogger);
		unset($this->mockSaveMaliciousUrl);
		parent::tearDown();
	}

	/**
	 * Test when the ScanUrlRequest object has high priority and its state is RECEIVED
	 * Each ScanUrlRquest object will be processed and its state should be changed to the exepected state  determined by the provider
	 *
	 * See the URL SCAN REQUEST flow chart above
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider slashNextApiResponseProvider
	 */
	public function test_Request_changeStateToDelayed_WhenHasHighPriority(string $slashNextApiResponse, string $expectedState, string $url): void
	{
		$request = new ScanUrlRequest();
		$request->setUrl($url);
		$request->setState(PhishingState::RECEIVED);
		$request->setPriority(ScanUrlRequest::PRIORITY_HIGH);
		$request->setCustomerId(566667);

		$this->mockSlashNextClient->shouldReceive('scan')
			->once()
			->with($url)
			->andReturn($slashNextApiResponse);

		$this->mockLogger->shouldReceive('logToAll')
			->withAnyArgs()
			->andReturn(31416);

		$this->mockSaveMaliciousUrl->shouldReceive('save')
			->with($request, 31416)
			->andReturnTrue();

		(new HttpProcessor(null))->process($request);

		$this->assertEquals($expectedState, $request->getState());
	}

	/**
	 * Test when the ScanUrlRequest object has nested ScanUrlRequest objects and come from the queue of first scan reports
	 * It's expected that only one the request  with state DELAYED remains after going trough HttpProcessor. All the others
	 * nested request objects are removed  because their report were succesfully generated by the vendor
	 *
	 * See the SCAN URL flow chart above
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_Requests_areProcessed_WhenRequestIsCompositeAndCameFromFirstQueue(): void
	{
		$this->compositeRequest->setState(PhishingState::DEQUEUED_FOR_FIRST_SCAN);

		$this->mockSlashNextClient->shouldReceive('scan')
			->times(4)
			->with(m::type('string'))
			->andReturn(
				file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_benign.json'),
				file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_delayed_response.json'),
				file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_malicious.json'),
				file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_malicious_landing_url.json')
			);

		$this->mockLogger->shouldReceive('logToAll')
			->withAnyArgs()
			->andReturn(31416);

		$this->mockSaveMaliciousUrl->shouldReceive('save')
			->with(m::capture($request), 31416)
			->andReturnTrue();

		(new HttpProcessor(null))->process($this->compositeRequest);

		// Every nested request object which doesn't have state equals to DELAYED are removed, so in this scenario it should remain only one request
		$this->assertTrue($this->compositeRequest->isComposite(), 'Check if the request object is composite (a.k.a has nested requests objects)');
		foreach($this->compositeRequest as $request) {
			$this->assertEquals(PhishingState::DELAYED, $request->getState(), 'Check if the nested request state is equals to DELAYED');
		}
	}

	/**
	 * Test when the ScanUrlRequest object has nested ScanUrlRequest objects and come from the queue of delayed reports
	 * It's expected that only one the request  with state DELAYED remains after going trough HttpProcessor. All the others
	 * nested request objects are removed  because their report were succesfully generated by the vendor
	 *
	 * See the GET REPORT flow chart above
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_Request_areProcessed_WhenRequestIsCompositeAndCameFromDelayedQueue(): void
	{
		$this->compositeRequest->setState(PhishingState::DEQUEUED_FOR_GETTING_REPORTS);

		$this->mockSlashNextClient->shouldReceive('getReport')
			->times(4)
			->with(m::type('string'))
			->andReturn(
				file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_benign.json'),
				file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_delayed_response.json'),
				file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_malicious.json'),
				file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_malicious_landing_url.json')
			);

		$this->mockLogger->shouldReceive('logToAll')
			->withAnyArgs()
			->andReturn(31416);

		$this->mockSaveMaliciousUrl->shouldReceive('save')
			->with(m::capture($request), 31416)
			->andReturnTrue();

		(new HttpProcessor(null))->process($this->compositeRequest);

		$this->assertEquals(PhishingState::PROCESSING, $this->compositeRequest->getState());
		$this->assertTrue($this->compositeRequest->isComposite(), 'Check if the request object is composite (a.k.a has nested requests objects)');
		foreach($this->compositeRequest as $request) {
			$this->assertEquals(PhishingState::DELAYED, $request->getState(), 'Check if the nested request state is equals to DELAYED');
		}
	}

	/**
	 * SlashNext API responses
	 *
	 * @return array[]
	 */
	public function slashNextApiResponseProvider()
	{
		return [
			[file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_benign.json'), PhishingState::RECEIVED, 'www.good.com/index.php'],
			[file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_delayed_response.json'), PhishingState::DELAYED, 'www.delayed.com/index.php'],
			[file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_malicious.json'), PhishingState::MALICIOUS, 'www.bad.com/index.php'],
			[file_get_contents(__DIR__ . '/../Providers/Clients/SlashNextClient/slashnext_api_verdict_malicious_landing_url.json'), PhishingState::MALICIOUS, 'www.badredirector.com/index.php'],
		];
	}
}
