<?php

namespace SomeCompanyNamespace\Services\Phishing\Processors;

use SomeCompanyNamespace\Services\Phishing\Factories\PhishingClientFactory;
use SomeCompanyNamespace\Services\Phishing\Handlers\UrlScanRequestHandler;
use SomeCompanyNamespace\Services\Phishing\Requests\ScanUrlRequest;
use SomeCompanyNamespace\Services\Phishing\Helpers\SaveMaliciousUrl;
use SomeCompanyNamespace\Services\Phishing\PhishingState;

class HttpProcessor extends Processor
{
	/**
	 * @param Processor|null $processor
	 */
	public function __construct(?Processor $processor)
	{
		parent::__construct($processor);
	}

	/**
	 * @param ScanUrlRequest $request
	 */
	public function process(ScanUrlRequest $request): void
	{
		$phishingClient = (new PhishingClientFactory())->create();

		if ($request->getState() === PhishingState::RECEIVED && $request->getPriority() === ScanUrlRequest::PRIORITY_HIGH) {
			$response = json_decode($phishingClient->scan($request->getUrl()));
			$request->setPhishingClientResponse($response);
			$logResponseId = $this->logResponse($request);
			switch ($response->errorNo) {
				case 0:
					if (strtolower($this->decodeVerdict($response)) === SaveMaliciousUrl::STATUS) {
                        $request->setPhishingScanId($response->urlData->scanId);
                        $request->setState(PhishingState::MALICIOUS);
						SaveMaliciousUrl::save($request, $logResponseId);
					}
					break;
				default:
					$request->setPhishingScanId($response->urlData->scanId);
					$request->setState(PhishingState::DELAYED);
					break;
			}
		}

		if ($request->isComposite() && $request->getState() === PhishingState::DEQUEUED_FOR_GETTING_REPORTS) {
			$request->setState(PhishingState::PROCESSING);
			foreach ($request as $key => $urlScanRequest) {
				$response = json_decode($phishingClient->getReport($urlScanRequest->getPhishingScanId()));
				$urlScanRequest->setPhishingClientResponse($response);
				$urlScanRequest->setPhishingScanId($response->urlData->scanId);
				$logResponseId = $this->logResponse($urlScanRequest);
				switch ($response->errorNo) {
					case 0:
						$request->remove($key);
						if (strtolower($this->decodeVerdict($response)) === SaveMaliciousUrl::STATUS) {
							$urlScanRequest->setState(PhishingState::MALICIOUS);
							SaveMaliciousUrl::save($urlScanRequest, $logResponseId);
						}
						break;
					default:
						$urlScanRequest->setState(PhishingState::DELAYED);
						break;
				}
			}
		}

		if ($request->isComposite() && $request->getState() === PhishingState::DEQUEUED_FOR_FIRST_SCAN) {
			foreach ($request as $key => $urlScanRequest) {
				$response = json_decode($phishingClient->scan($urlScanRequest->getUrl()));
				$urlScanRequest->setPhishingClientResponse($response);
				$logResponseId = $this->logResponse($urlScanRequest);
				switch ($response->errorNo) {
					case 0:
						$request->remove($key);
						if (strtolower($this->decodeVerdict($response)) === SaveMaliciousUrl::STATUS) {
							$urlScanRequest->setState(PhishingState::MALICIOUS);
							SaveMaliciousUrl::save($urlScanRequest, $logResponseId);
						}
						break;
					default:
						$urlScanRequest->setPhishingScanId($response->urlData->scanId);
						$urlScanRequest->setState(PhishingState::DELAYED_FIRST_SCAN);
						break;
				}
			}
		}

		parent::process($request);
	}

	/**
	 * @param ScanUrlRequest $request
	 */
	private function logResponse(ScanUrlRequest $request)
	{
		 return $this->getLogger()->logToAll([
			'customer_id' => $request->getCustomerId(),
			'survey_id' => $request->getSurveyId(),
			'url' => $request->getUrl(),
			'vendor_response' => $request->getPhishingClientResponse()
		]);
	}

	/**
	 * @param $response
	 * @return string
	 */
	private function decodeVerdict($response): string
	{
		return ($response->urlData->threatData->verdict === 'Redirector') ?
			$response->urlData->landingURL->threatData->verdict : $response->urlData->threatData->verdict;
	}

}