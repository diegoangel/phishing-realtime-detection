<?php declare(strict_types=1);

use DateTime;
use Iterator;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use SomeCompanyNamespace\Services\Phishing\PhishingState;
use SomeCompanyNamespace\Services\Phishing\Processors\CacheProcessor;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;
use SomeCompanyNamespace\Services\Phishing\Requests\Request;

/**
 * Request State Flow tested in this test suite
 * Chart refs: State name along with what processor is responsible for setting the corresponding state
 *
 * These flows are handled by the UrlScanRequestHandler
 *
 * +-------------------------+
 * | NOT HIGH PRIORITY SCAN  |
 * +-------------------------+
 *
 * RECEIVED (handler) ---> EXISTS (cache)
 *       |
 *      v
 * CACHED (cache) ---> QUEUED (queue first)
 *
 * +---------------------+
 * | HIGH PRIORITY SCAN  |
 * +---------------------+
 *
 * RECEIVED (handler) ---> MALICIOUS (http)
 *      |
 *     v
 * DELAYED (http) ---> QUEUED (queue report)
 *
 * @covers \SomeCompanyNamespace\Services\Phishing\Processors\CacheProcessor
 */
class CacheProcessorTest extends TestCase
{
	const SAMPLE_URL = 'https://example.com/';
	private $memcacheClientMock;

	public function setUp(): void
	{
		parent::setUp();

		// Mock and overload the cache client instantiated by the factory class passing the interface it has to implement as a 2th argument
		$this->memcacheClientMock = m::mock(
			'overload:SomeCompanyNamespace\Services\Phishing\Clients\CacheClient',
			'SomeCompanyNamespace\Services\Phishing\Interfaces\CacheClientInterface'
		)->makePartial();
	}

	public function tearDown(): void
	{
		unset($this->memcacheClientMock);
		parent::tearDown();
	}

	/**
	 * Test when the URL is not cached yet and the priority is not high
	 * It's expected that the request state change from RECEIVED to CACHED
	 *
	 * Important: 'states' are the way we manage which logic is executed, so testing states is what we should take care of
	 *
	 * Procedure:
	 * A ScanURlRequest object is instantiated with some state by default and pass it to the Processor class
	 * which may or may not modify the state of the ScanUrlRequest object (see state flow chart above).
	 * After passing trough the processor the test should evaluate if the state is what we expected.
	 *
	 * Note: This test will be fully inline commented as an example and reference to understand the rest of the tests
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_Request_ChangeStateToCached_WhenCachedForFirstTimeAndPriorityLow(): void
	{
		// The cache key shouldn't exists
		$this->memcacheClientMock->shouldReceive('get')
			->once()
			->with(md5(self::SAMPLE_URL))
			->andReturn(false);
		$this->memcacheClientMock->shouldReceive('add')
			->once()
			->with(md5(self::SAMPLE_URL), (new DateTime())->getTimestamp())
			->andReturn(true);
		$this->memcacheClientMock->shouldReceive('setTTL')
			->with(ScanUrlRequest::PRIORITY_LOW)
			->andReturn(true);

		// Create a ScanUrlRequest object
		$request = new ScanUrlRequest();
		$request->setState(PhishingState::RECEIVED);
		$request->setPriority(ScanUrlRequest::PRIORITY_LOW);
		$request->setUrl(self::SAMPLE_URL);

		$this->assertInstanceOf(Request::class, $request, 'Check ScanUrlRequest inherits from Request');
		$this->assertInstanceOf(Iterator::class, $request, 'Check ScanUrlRequest is iterable');

		// Pass the request object (by default objects are passed by reference) to the cache processor
		(new CacheProcessor(null))->process($request);

		// Check if the state changed accordingly (because objects are passed by reference we should receive any modifications made to the request)
		$this->assertEquals(PhishingState::CACHED, $request->getState(), 'Check if Request state changed to CACHED');
	}

	/**
	 * Test when the URL is already cached and the priority is not high
	 * It's expected that the request state change from RECEIVED to EXISTS
	 *
	 * Procedure:
	 * A ScanURlRequest object is instantiated with some state by default and pass it to the Processor class
	 * which may or may not modify the state of the ScanUrlRequest object (see state flow chart above).
	 * After passing trough the processor the test should evaluate if the state is what we expected.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @medium
	 */
	public function test_Request_ChangeStateToExists_WhenAlreadyCachedAndPriorityLow(): void
	{
		// The cache key should exists
		$this->memcacheClientMock->shouldReceive('get')
			->once()
			->with(md5(self::SAMPLE_URL))
			->andReturn(true);
		// When the cache key exists we recreate it (a.k.a delete/add new)
		$this->memcacheClientMock->shouldReceive('delete')
			->once()
			->with(md5(self::SAMPLE_URL))
			->andReturn(true);
		$this->memcacheClientMock->shouldReceive('add')
			->once()
			->with(md5(self::SAMPLE_URL), (new DateTime())->getTimestamp())
			->andReturn(true);
		$this->memcacheClientMock->shouldReceive('setTTL')
			->with(ScanUrlRequest::PRIORITY_LOW)
			->andReturn(true);

		$request = new ScanUrlRequest();
		$request->setState(PhishingState::RECEIVED);
		$request->setPriority(ScanUrlRequest::PRIORITY_LOW);
		$request->setUrl(self::SAMPLE_URL);

		$this->assertInstanceOf(Request::class, $request, 'Check ScanUrlRequest inherits from Request');
		$this->assertInstanceOf(Iterator::class, $request, 'Check ScanUrlRequest is iterable');

		(new CacheProcessor(null))->process($request);

		$this->assertEquals(PhishingState::EXISTS, $request->getState(), 'Check if Request state changed to EXISTS');
	}

	/**
	 * Test when the URL has priority equals to high
	 * It's expected that the request state doesn't change because the cache logic is bypassed (see above the HIGH PRIORITY SCAN flow)
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @small
	 */
	public function test_Request_IsNotCached_WhenPriorityHigh(): void
	{
		$request = new ScanUrlRequest();
		$request->setState(PhishingState::RECEIVED);
		$request->setPriority(ScanUrlRequest::PRIORITY_HIGH);

		(new CacheProcessor(null))->process($request);

		$this->assertEquals(PhishingState::RECEIVED, $request->getState(), 'Check if Request state remains to RECEIVED');
	}
}
