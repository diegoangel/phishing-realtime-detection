<?php

namespace SomeCompanyNamespace\Services\Phishing\Handlers;

use SomeCompanyNamespace\Services\Phishing\Processors\HttpProcessor;
use SomeCompanyNamespace\Services\Phishing\Processors\QueueProcessor;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;

class GetScanUrlReportHandler
{
	public $handler;

	public function __construct()
	{
		$this->buildHandler();
	}

	/**
	 * Chain processing objects to handle the request in sequence
	 * A reminiscence of the Chain of Responsability design pattern it can be seen
	 */
	private function buildHandler()
	{
		$this->handler = new QueueProcessor(
			new HttpProcessor(
				new QueueProcessor(null)
			)
		);
	}

	/**
	 * @param ScanUrlRequest $request
	 */
	public function process(ScanUrlRequest $request)
	{
		$this->handler->process($request);
	}
}