<?php

namespace SomeCompanyNamespace\Services\Phishing;

use DatabaseServer;
use DateTime;
use Exception;
use SGException;
use UsageMonitor;

class Logger
{

	/**
	 * logToAll method
	 *
	 * @param array $data
	 * @return int
	 */
	public function logToAll(array $data): int
	{
		$this->log($data);
		$last_log_id = $this->saveToDB($data);

		return $last_log_id;
	}

	/**
	 * log method
	 *
	 * @param array $data
	 * @return void
	 */
	public function log(array $data): void
	{
		$vendor_response = $data['vendor_response'];

		UsageMonitor::Debug(
			[
				'key' => 'phishingServiceLog',
				'cid' => $data['customer_id'] ?? '',
				'url' => $data['url'] ?? '',
				'responseCode' => $vendor_response->errorNo ?? '',
				'responseBody' => $vendor_response->urlData->threatData->verdict ?? ''
			],
			"Log API call phishing_scan"
		);
	}

	/**
	 * saveToDB method
	 *
	 * @param array $data
	 * @return int|SGException
	 */
	public function saveToDB(array $data): int
	{
		$now = new DateTime();

		$data = [
			'iTimestamp' => $now->format(\DateTime::ISO8601),
			'iCustomerID' => $data['customer_id'] ?? '',
			'iSurveyID' => $data['survey_id'] ?? '',
			'sIdentifier' => $data['url'] ?? '',
			'sResponse' => json_encode($data['vendor_response']) ?? ''
		];

		$adapter = DatabaseServer::getPrimaryAdapter();
		$result = $adapter->insert('phishing_logs', $data);

		if ($result === false) {
			throw new SGException("Unable to insert row in " . get_class($this) . '::' . __FUNCTION__ . ' (triggered in ' . __METHOD__ . ')');
		}

		return $adapter->lastInsertId();
	}

	/**
	 * @param Exception $e
	 */
	public function logException(Exception $e): void
	{
		APM::captureThrowable($e);

		UsageMonitor::Debug([
			'key' => 'phishingServiceLogException',
			'errorMsg' => $e->getMessage(),
			'observed_location' => $e->getFile() . ':' . $e->getLine(),
		], 'Exception: ' . get_class($e) . ' - ' . $e->getMessage());
	}
}