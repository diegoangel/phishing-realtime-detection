<?php

namespace SomeCompanyNamespace\Services\Phishing\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SomeCompanyNamespace\Services\Phishing\Factories\LoggerFactory;
use SomeCompanyNamespace\Services\Phishing\Interfaces\PhishingClientInterface;
use SomeCompanyNamespace\Services\Phishing\Traits\ConfigTrait;

class SlashNextClient implements PhishingClientInterface
{
	use ConfigTrait;

	private $httpClient;
	private $logger;

	public function __construct()
	{
		$this->httpClient = new Client(['base_uri' => $this->getScanUrlEndpoint()]);
		$this->logger = (new LoggerFactory())->create();
	}

	/**
	 * @param string $scanId
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getReport(string $scanId): string
	{
		try {
			$response = $this->httpClient->request('POST', '', ['json' => [
				'authkey' => $this->getApiKey(),
				'scanid' => $scanId
			]]);
			return $response->getBody()->getContents();
		} catch (GuzzleException $e) {
			$this->logger->logException($e);
		}
	}

	/**
	 * @param string $url
	 * @return string
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function scan(string $url): string
	{
		try {
			$response = $this->httpClient->request('POST', '', ['json' => [
				'authkey' => $this->getApiKey(),
				'url' => $url
			]]);
			return $response->getBody()->getContents();
		} catch (GuzzleException $e) {
			$this->logger->logException($e);
		}
	}
}
