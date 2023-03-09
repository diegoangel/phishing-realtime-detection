<?php

namespace SomeCompanyNamespace\Services\Phishing\Traits;

use Exception;
use SomeCompanyNamespace\Services\Phishing\Factories\LoggerFactory;
use Zend_Exception;
use Zend_Registry;

trait ConfigTrait
{
	/**
	 * @return mixed|void
	 * @throws Zend_Exception
	 */
	private function getConfig()
	{
		if (class_exists('Zend_Registry', false)) {
			try {
				return Zend_Registry::get('config');
			} catch (Zend_Exception $e) {
				$logger = (new LoggerFactory())->create();
				$logger->logException($e);
			}
		}
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function getConfigValue($value)
	{
        if ( empty($this->getConfig()->slashnext)) {
            throw new Exception('Missing Slashnext config values');
        }
        return $this->getConfig()->slashnext->{$value};
	}

	/**
	 * @return int
	 * @throws Zend_Exception
	 */
	public function getSecondsDelayBeforeSendingToScan() :int
	{
		return $this->getConfigValue('send_to_scan_delay');
	}

	/**
	 * @return int
	 * @throws Zend_Exception
	 */
	public function isDevelopmentEnvironment(): int
	{
		return $this->getConfigValue('is_dev_environment');
	}

	/**
	 * @return string
	 * @throws Zend_Exception
	 */
	public function getApiKey(): string
	{
		return $this->getConfigValue('apikey');
	}

	/**
	 * @return string
	 * @throws Zend_Exception
	 */
	public function getScanUrlEndpoint(): string
	{
		return $this->getConfigValue('url_scan_endpoint');
	}

	/**
	 * @return mixed
	 * @throws Zend_Exception
	 */
	public function getUrlsToScanQueue()
	{
		return $this->getConfigValue('queue_urls_to_scan');
	}

	/**
	 * @return mixed
	 * @throws Zend_Exception
	 */
	public function getScannedUrlsQueue()
	{
		return $this->getConfigValue('queue_scanned_urls');
	}
}
