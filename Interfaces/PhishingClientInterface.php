<?php

namespace SomeCompanyNamespace\Services\Phishing\Interfaces;

interface PhishingClientInterface
{
	/**
	 * @return mixed
	 */
	public function scan(string $url): string;

	/**
	 * @return mixed
	 */
	public function getReport(string $scanId): string;
}