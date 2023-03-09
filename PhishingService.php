<?php

namespace SomeCompanyNamespace\Services\Phishing;

use SomeCompanyNamespace\Services\Phishing\Handlers\GetScanUrlReportHandler;
use SomeCompanyNamespace\Services\Phishing\Handlers\UrlScanRequestHandler;
use SomeCompanyNamespace\Services\Phishing\Handlers\ScanUrlHandler;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;

final class PhishingService
{
	/**
	 * This is the entry point  for scanning URLs
	 *
	 * @param string $url
	 * @param int $customerId
	 * @param int|null $surveyId
	 */
	public static function scan(string $url, int $customerId, ?int $surveyId = null, int $priority = ScanUrlRequest::PRIORITY_HIGH): void
	{
		$urlScanRequestHandler = new UrlScanRequestHandler();

		$scanUrlRequest = new ScanUrlRequest();
		$scanUrlRequest->setUrl($url);
		$scanUrlRequest->setCustomerId($customerId);
		$scanUrlRequest->setSurveyId($surveyId);
		$scanUrlRequest->setPriority($priority);
		$scanUrlRequest->setState(PhishingState::RECEIVED);

		$urlScanRequestHandler->process($scanUrlRequest);
	}

	/**
	 * This is the entry point for getting the scan report
	 *
	 * @return void
	 */
	public static function getReport(): void
	{
		$getScanUrlReportHandler = new GetScanUrlReportHandler();

		$getScanUrlReportHandler->process(new ScanUrlRequest());
	}

	/**
	 * This is the entry point for sending urls to vendor for beign scanned
	 *
	 * @return void
	 */
	public static function scanUrl(): void
	{
		$scanUrlHandler = new ScanUrlHandler();

		$scanUrlHandler->process(new ScanUrlRequest());
	}
}
