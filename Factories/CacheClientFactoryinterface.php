<?php

namespace SomeCompanyNamespace\Services\Phishing\Factories;

use SomeCompanyNamespace\Services\Phishing\Interfaces\CacheClientInterface;

interface CacheClientFactoryinterface
{
	/**
	 * @return CacheClientInterface
	 */
	public function create(): CacheClientInterface;
}