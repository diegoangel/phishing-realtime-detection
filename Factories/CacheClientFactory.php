<?php

namespace SomeCompanyNamespace\Services\Phishing\Factories;

use SomeCompanyNamespace\Services\Phishing\Interfaces\CacheClientInterface;
use SomeCompanyNamespace\Services\Phishing\Clients\CacheClient;

class CacheClientFactory implements CacheClientFactoryinterface
{
	/**
	 * @return CacheClientInterface
	 */
	public function create(): CacheClientInterface
	{
		return new CacheClient();
	}
}