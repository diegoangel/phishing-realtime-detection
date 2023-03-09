<?php

namespace SomeCompanyNamespace\Services\Phishing\Clients;

use SomeCompanyNamespace\Classes\AwsServiceHelpers\AwsSqsHelper;
use SomeCompanyNamespace\Services\Phishing\Factories\LoggerFactory;
use SomeCompanyNamespace\Services\Phishing\Interfaces\QueueClientInterface;
use Aws\Exception\AwsException;

class QueueClient implements QueueClientInterface
{
	const MAX_NUMBER_OF_MESSAGES_TO_DEQUEUE = 10;

	private $queueUrl;
	private $queueClient;
	private $logger;

	public function __construct(AwsSqsHelper $queueClient, string $queueUrl)
	{
		$this->queueClient = $queueClient;
		$this->queueUrl = $queueUrl;
		$this->logger = (new LoggerFactory())->create();
	}

	/**
	 * @param string $url
	 * @param int $customerId
	 * @param int|null $surveyId
	 * @param string $scanId
	 * @param int $attempt
	 */
	public function sendMessage(string $url, int $customerId, ?int $surveyId, ?string $scanId, int $attempt, array $messageAttributes = [], int $delaySeconds = 0)
	{
		try {
			$params = $this->buildMessage($url, $customerId, $surveyId, $scanId, $attempt, $messageAttributes, $delaySeconds);
			$result = $this->queueClient->sendMessage($params['QueueUrl'], $params['MessageBody'], $params['MessageAttributes'], $params['DelaySeconds']);
		} catch (AwsException $e) {
			$this->logger->logException($e);
		}
	}

	/**
	 * @param string $url
	 * @param int $customerId
	 * @param int|null $surveyId
	 * @param string $scanId
	 * @param int $attempt
	 * @return array
	 */
	private function buildMessage(string $url, int $customerId, ?int $surveyId, ?string $scanId, int $attempt, array $messageAttributes = [], int $delaySeconds = 0)
	{
		return [
			'QueueUrl' => $this->queueUrl,
			'MessageBody' => json_encode([
				'url' => $url,
				'customerId' => $customerId,
				'surveyId' => $surveyId,
				'scanId' => $scanId,
				'attempt' => $attempt
			]),
			'MessageAttributes' => $messageAttributes,
			'DelaySeconds' => $delaySeconds,
		];
	}

	/**
	 * @return array|mixed
	 */
	public function consumeMessage()
	{
		return $this->queueClient->getMessages($this->queueUrl, self::MAX_NUMBER_OF_MESSAGES_TO_DEQUEUE);
	}

	/**
	 * @param string $receiptHandle
	 */
	public function deleteMessage(string $receiptHandle)
	{
		return $this->queueClient->deleteMessage($this->queueUrl, $receiptHandle);
	}

	/**
	 * @return string
	 */
	public function getNumberOfMessagesInQueue(): string
	{
		return $this->queueClient->getNumberOfMessagesInQueue($this->queueUrl);
	}
}
