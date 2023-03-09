<?php

use SomeCompanyNamespace\Services\Phishing\Clients\SlashNextClient;

class SlashNextClientTest extends PHPUnit_Framework_TestCase
{
	private $client;

	protected function setUp() {
		$this->client = new SlashNextClient;
	}

	public function testIsValidResponse() {
		$response = $this->client->scan('www.SomeCompanyNamespace.com/s3/399128/test-survey');
		$json_response = json_decode($response);

		$this->assertTrue(isset($json_response->errorNo));
	}
}
