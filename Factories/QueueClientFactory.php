<?php

namespace SomeCompanyNamespace\Services\Phishing\Factories;

use SGException;
use SomeCompanyNamespace\Classes\AwsServiceHelpers\AwsSqsHelper;
use SomeCompanyNamespace\Classes\AwsServiceHelpers\Factories\AwsServiceFactory;
use SomeCompanyNamespace\Services\Phishing\Clients\QueueClient;
use SomeCompanyNamespace\Services\Phishing\Interfaces\QueueClientInterface;
use SomeCompanyNamespace\Services\Phishing\Traits\ConfigTrait;
use SomeCompanyNamespace\Services\Phishing\Factories\LoggerFactory;

class QueueClientFactory implements QueueClientFactoryInterface
{
	use ConfigTrait;

	const QUEUE_URL_TO_SCAN = 1;
	const QUEUE_SCANNED_URLS = 2;

	private $awsSqsClient;
	private $logger;

	/**
	 * @throws \Zend_Exception
	 */
	public function __construct()
	{
		$this->awsSqsClient = $this->createAwsSqsClient($this->isDevelopmentEnvironment());
		$this->logger = (new LoggerFactory())->create();
	}

	/**
	 * @param bool $isDevEnvironment
	 * @return AwsSqsHelper
	 */
	private function createAwsSqsClient($isDevEnvironment): AwsSqsHelper
	{
		try {
			 return AwsServiceFactory::getInstance('sqs', $isDevEnvironment);
		} catch (SGException $e) {
			$this->logger->logException($e);
		}
	}

	/**
	 * @return QueueClient
	 */
	public function create(int $type): QueueClientInterface
	{
		switch ($type) {
			case self::QUEUE_URL_TO_SCAN:
				return new QueueClient($this->awsSqsClient, $this->getUrlsToScanQueue());
				break;
			case self::QUEUE_SCANNED_URLS:
				return new QueueClient($this->awsSqsClient, $this->getScannedUrlsQueue());
				break;
		}
	}
}