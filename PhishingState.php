<?php

namespace SomeCompanyNamespace\Services\Phishing;

class PhishingState
{
    const EMPTY = 'EMPTY';
    const CACHED = 'CACHED';
    const QUEUED = 'QUEUED';
    const RECEIVED = 'RECEIVED';
    const MALICIOUS = 'MALICIOUS';
    const DELAYED = 'DELAYED';
    const DELAYED_FIRST_SCAN = 'DELAYED_FIRST_SCAN';
    const DEQUEUED_FOR_GETTING_REPORTS = 'DEQUEUED_FOR_GETTING_REPORTS';
    const PROCESSING = 'PROCESSING';
    const DEQUEUED_FOR_FIRST_SCAN = 'DEQUEUED_FOR_FIRST_SCAN';
    CONST EXISTS = 'EXISTS';

}