<?php

namespace SomeCompanyNamespace\Services\Phishing\Processors;

use SomeCompanyNamespace\Services\Phishing\Factories\LoggerFactory;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;
use SomeCompanyNamespace\Services\Phishing\Traits\ConfigTrait;
use SomeCompanyNamespace\Services\Phishing\Logger;

abstract class Processor
{
	use ConfigTrait;

	private $processor;
	private $logger;

	/**
	 * @param Processor|null $processor
	 */
	public function __construct(?Processor $processor)
	{
		$this->processor = $processor;
	}

	/**
	 * @param ScanUrlRequest $request
	 * @return void
	 */
	public function process(ScanUrlRequest $request): void
	{
		if ($this->processor != null)
			$this->processor->process($request);
	}

	/**
	 * @return \SomeCompanyNamespace\Services\Phishing\Logger
	 */
	public function getLogger(): Logger
	{
		if ($this->logger === null) {
			$this->logger = (new LoggerFactory())->create();
		}
		return $this->logger;
	}
}
