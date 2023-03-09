<?php

namespace SomeCompanyNamespace\Services\Phishing\Factories;

use SomeCompanyNamespace\Services\Phishing\Logger;

interface LoggerFactoryInterface
{
	/**
	 * @return Logger
	 */
	public function create(): Logger;
}