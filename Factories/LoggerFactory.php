<?php

namespace SomeCompanyNamespace\Services\Phishing\Factories;

use SomeCompanyNamespace\Services\Phishing\Logger;

class LoggerFactory implements LoggerFactoryInterface
{
	/**
	 * @return Logger
	 */
	public function create(): Logger
	{
		return new Logger();
	}
}