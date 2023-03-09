<?php

namespace SomeCompanyNamespace\Services\Phishing\Processors;

use DateTime;
use SomeCompanyNamespace\Services\Phishing\Factories\CacheClientFactory;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;
use SomeCompanyNamespace\Services\Phishing\PhishingState;

class CacheProcessor extends Processor
{
	public function __construct(?Processor $processor)
	{
		parent::__construct($processor);
	}

	/**
	 * @param ScanUrlRequest $request
	 */
	public function process(ScanUrlRequest $request): void
	{
		$cacheClient = (new CacheClientFactory())->create();
		$cacheKey = md5($request->getUrl());

		if ($request->getPriority() !== ScanUrlRequest::PRIORITY_HIGH) {
			if ($cacheClient->get($cacheKey)) {
				$cacheClient->delete($cacheKey);
				$request->setState(PhishingState::EXISTS);
			}else {
				$request->setState(PhishingState::CACHED);
			}
			$dateTime = new DateTime();
			$cacheClient->setTTL($request->getPriority());
			$cacheClient->add($cacheKey, $dateTime->getTimestamp());
			
		}

		parent::process($request);
	}
}
