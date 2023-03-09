<?php

namespace SomeCompanyNamespace\Services\Phishing\Factories;

use SomeCompanyNamespace\Services\Phishing\Clients\SlashNextClient;
use SomeCompanyNamespace\Services\Phishing\Interfaces\PhishingClientInterface;

class PhishingClientFactory implements PhishingClientFactoryInterface
{
	/**
	 * @return PhishingClientInterface
	 */
	public function create(): PhishingClientInterface
	{
		return new SlashNextClient();
	}
}