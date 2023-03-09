<?php

namespace SomeCompanyNamespace\Services\Phishing\Factories;

use SomeCompanyNamespace\Services\Phishing\Interfaces\QueueClientInterface;

interface QueueClientFactoryInterface
{
	/**
	 * @param int $type
	 * @return QueueClientInterface
	 */
	public function create(int $type): QueueClientInterface;
}