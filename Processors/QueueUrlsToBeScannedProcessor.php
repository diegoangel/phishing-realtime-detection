<?php

namespace SomeCompanyNamespace\Services\Phishing\Processors;

use DateTime;
use SomeCompanyNamespace\Services\Phishing\Factories\CacheClientFactory;
use SomeCompanyNamespace\Services\Phishing\Factories\QueueClientFactory;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;
use SomeCompanyNamespace\Services\Phishing\PhishingState;

class QueueUrlsToBeScannedProcessor extends Processor
{
	public function __construct(?Processor $processor)
	{
		parent::__construct($processor);
	}

	public function process(ScanUrlRequest $request): void
	{
		$queueClient = (new QueueClientFactory())->create(QueueClientFactory::QUEUE_URL_TO_SCAN);

		if (empty($request->getState()) && !$request->isComposite()) {
			$numberOfMessagesInQueue = $queueClient->getNumberOfMessagesInQueue();
			$request->setState(PhishingState::EMPTY);
			if ($numberOfMessagesInQueue > 0) {
				$messages = $queueClient->consumeMessage();
				for ($i = 0; $i < count($messages); $i++) {
					$message = json_decode($messages[$i]['Body']);
					$scanUrlRequest = new ScanUrlRequest();
					$scanUrlRequest->setUrl($message->url);
					if (!$this->isReadyForScan($scanUrlRequest)) {
						continue;
					}
					$scanUrlRequest->setSurveyId($message->surveyId);
					$scanUrlRequest->setCustomerId($message->customerId);
					$request->setState(PhishingState::DEQUEUED_FOR_FIRST_SCAN);
					$request->add($scanUrlRequest);
					$queueClient->deleteMessage($messages[$i]['ReceiptHandle']);
				}
			}
		}

		if (!$request->isComposite() && $request->getState() === PhishingState::CACHED) {
			$request->setState(PhishingState::QUEUED);
			$queueClient->sendMessage($request->getUrl(), $request->getCustomerId(), $request->getSurveyId(), $request->getPhishingScanId(), $request->getAttempts());
		}

		parent::process($request);
	}

	/**
	 * @param ScanUrlRequest $request
	 * @return bool
	 */
	private function isReadyForScan(ScanUrlRequest $request): bool
	{
		$cacheClient = (new CacheClientFactory())->create();

		if (!$cacheClient->get(md5($request->getUrl()))) {
			return true;
		}

		$start = $cacheClient->get(md5($request->getUrl()));
		$end = new DateTime();

		return ($end->getTimestamp() - $start <= $request->getPriority()) ?  false : true;
	}
}
