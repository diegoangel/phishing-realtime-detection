<?php

namespace SomeCompanyNamespace\Services\Phishing\Factories;

use SomeCompanyNamespace\Services\Phishing\Interfaces\PhishingClientInterface;

interface PhishingClientFactoryInterface
{
	/**
	 * @return PhishingClientInterface
	 */
	public function create(): PhishingClientInterface;
}