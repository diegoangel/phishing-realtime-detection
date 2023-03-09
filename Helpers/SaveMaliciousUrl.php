<?php

namespace SomeCompanyNamespace\Services\Phishing\Helpers;

use DatabaseServer;
use SomeCompanyNamespace\Services\Phishing\Requests\Request;
use UsageMonitor;

class SaveMaliciousUrl
{
    private const INTERNAL_URL = 'https://internal.sgizmo.com';

    const STATUS = 'malicious';

    const TABLE_NAME = 'phishing_malicious_urls';

    /**
     * @param Request $request
     * @param int $phishingLogsId
     * @return bool
     */
    public static function save(Request $request, int $phishingLogsId): bool
    {
        try {
            $adapter = DatabaseServer::getPrimaryAdapter();
            $result = $adapter->insert(self::TABLE_NAME, [
                'iSurveyID' => $request->getSurveyId() ?? null,
                'sSurveyLink' => $request->getUrl(),
                'iPhishingLogID' => $phishingLogsId,
                'sInternalLink' => self::INTERNAL_URL . '/admin/customer/' . $request->getCustomerId() . '/surveys',
                'sStatus' => self::STATUS
            ]);

            return $result;
        } catch (\Exception $e) {
            UsageMonitor::Debug(
                [
                    "errorMsg" => $e->getMessage(),
                    "method" => __METHOD__,
                    'key' => 'saveMaliciousUrl',
                ],
                "Error with the insert on saveMaliciousUrl"
            );
            return false;
        }
    }
}
