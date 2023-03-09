<?php

namespace SomeCompanyNamespace\Services\Phishing\Handlers;

use SomeCompanyNamespace\Services\Phishing\Processors\CacheProcessor;
use SomeCompanyNamespace\Services\Phishing\Processors\HttpProcessor;
use SomeCompanyNamespace\Services\Phishing\Processors\QueueProcessor;
use SomeCompanyNamespace\Services\Phishing\Processors\QueueUrlsToBeScannedProcessor;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;

class UrlScanRequestHandler
{
	public $handler;

	public function __construct()
	{
		$this->buildHandler();
	}

	/**
	 * Chain processing objects who will handle the request in sequence
	 * A reminiscence of the Chain of Responsability design pattern can be seen
	 */
	private function buildHandler()
	{
		$this->handler = (
			new HttpProcessor(
				new CacheProcessor(
					new QueueUrlsToBeScannedProcessor(
						new QueueProcessor(null)
					)
				)
			)
		);
	}

	public function process(ScanUrlRequest $request)
	{
		$this->handler->process($request);
	}
}