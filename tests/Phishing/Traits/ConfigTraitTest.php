<?php

use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use Mockery as m;

class ConfigTraitTest extends TestCase
{
	private $mockConfig;
	private $configTrait;

	protected function setUp()
	{
		parent::setUp();

		$this->configTrait = $this->getObjectForTrait('SomeCompanyNamespace\Services\Phishing\Traits\ConfigTrait');
	}

	protected function tearDown()
	{
		unset($this->configTrait);
		parent::tearDown();
	}

	public function test_getConfigValue_isDevelopmentEnvironment(): void
	{
		$this->assertThat(
			$this->configTrait->isDevelopmentEnvironment(),
			$this->logicalOr(
				$this->equalTo(1),
				$this->equalTo(0)
			)
		);
	}

	public function test_getConfigValue_ApiKey(): void
	{
		$this->assertNotEmpty($this->configTrait->getApiKey());
		$this->assertThat(
			$this->configTrait->getApiKey(),
			$this->isType('string')
		);
	}

	public function test_getConfigValue_ScanUrlEndpoint(): void
	{
		$this->assertNotEmpty($this->configTrait->getScanUrlEndpoint());
		$this->assertMatchesRegEx(
			'/(https?:\/\/)?([\w\-])+\.{1}([a-zA-Z]{2,63})([\/\w-]*)*\/?\??([^#\n\r]*)?#?([^\n\r]*)/',
			$this->configTrait->getScanUrlEndpoint()
		);
	}

	public function test_getConfigValue_UrlsToScanQueue(): void
	{
		$this->assertNotEmpty($this->configTrait->getUrlsToScanQueue());
		$this->assertMatchesRegEx(
			'/(https?:\/\/)?([\w\-])+\.{1}([a-zA-Z]{2,63})([\/\w-]*)*\/?\??([^#\n\r]*)?#?([^\n\r]*)/',
			$this->configTrait->getUrlsToScanQueue()
		);
	}

	public function test_getConfigValue_ScannedUrlsQueue(): void
	{
		$this->assertNotEmpty($this->configTrait->getScannedUrlsQueue());
		$this->assertMatchesRegEx(
			'/(https?:\/\/)?([\w\-])+\.{1}([a-zA-Z]{2,63})([\/\w-]*)*\/?\??([^#\n\r]*)?#?([^\n\r]*)/',
			$this->configTrait->getScannedUrlsQueue()
		);
	}
}
