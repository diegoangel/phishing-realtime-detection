<?php

namespace SomeCompanyNamespace\Services\Phishing\Processors;

use SomeCompanyNamespace\Services\Phishing\Factories\QueueClientFactory;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;
use SomeCompanyNamespace\Services\Phishing\PhishingState;

class QueueProcessor extends Processor
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
		$queueClient = (new QueueClientFactory())->create(QueueClientFactory::QUEUE_SCANNED_URLS);

		if (empty($request->getState()) && !$request->isComposite()) {
			$numberOfMessagesInQueue = $queueClient->getNumberOfMessagesInQueue();
			if ($numberOfMessagesInQueue > 0) {
				$messages = $queueClient->consumeMessage();
				for ($i = 0; $i < count($messages); $i++) {
					$message = json_decode($messages[$i]['Body']);
					$scanUrlRequest = new ScanUrlRequest();
					$scanUrlRequest->setUrl($message->url);
					$scanUrlRequest->setSurveyId($message->surveyId);
					$scanUrlRequest->setPhishingScanId($message->scanId);
					$scanUrlRequest->setCustomerId($message->customerId);
					$scanUrlRequest->setAttempts($message->attempt + 1);
					$request->setState(PhishingState::DEQUEUED_FOR_GETTING_REPORTS);
					$request->add($scanUrlRequest);
					$queueClient->deleteMessage($messages[$i]['ReceiptHandle']);
				}
			}
		}

		if (!$request->isComposite() && $request->getState() === PhishingState::DELAYED) {
			$delaySeconds = ($request->getPriority() === ScanUrlRequest::PRIORITY_HIGH) ? ScanUrlRequest::PRIORITY_HIGH : 0;
			$request->setState(PhishingState::QUEUED);
			$queueClient->sendMessage(
				$request->getUrl(),
				$request->getCustomerId(),
				$request->getSurveyId(),
				$request->getPhishingScanId(),
				$request->getAttempts(), [],
				$delaySeconds
			);
		}

		if ($request->isComposite() && $request->getState() === PhishingState::DEQUEUED_FOR_FIRST_SCAN) {
			foreach($request as $key => $scanUrlRequest) {
				$queueClient->sendMessage(
					$scanUrlRequest->getUrl(),
					$scanUrlRequest->getCustomerId(),
					$scanUrlRequest->getSurveyId(),
					$scanUrlRequest->getPhishingScanId(),
					$scanUrlRequest->getAttempts()
				);
			}
		}

		if ($request->isComposite() && $request->getState() === PhishingState::PROCESSING) {
			foreach($request as $key => $scanUrlRequest) {
				if ($scanUrlRequest->getAttempts() <= 5) {
					$queueClient->sendMessage(
						$scanUrlRequest->getUrl(),
						$scanUrlRequest->getCustomerId(),
						$scanUrlRequest->getSurveyId(),
						$scanUrlRequest->getPhishingScanId(),
						$scanUrlRequest->getAttempts()
					);
					$request->remove($key);
				}
			}
		}

		parent::process($request);
	}
}
