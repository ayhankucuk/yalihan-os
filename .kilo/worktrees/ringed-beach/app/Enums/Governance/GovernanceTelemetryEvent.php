<?php

namespace App\Enums\Governance;

enum GovernanceTelemetryEvent: string
{
    case DIFF_VIEWED = 'governance.diff_viewed';
    case PUBLISH_ATTEMPTED = 'governance.publish_attempted';
    case PUBLISH_REJECTED = 'governance.publish_rejected';
    case PUBLISH_SUCCEEDED = 'governance.publish_succeeded';
    case SHADOW_EVALUATED = 'governance.shadow_evaluated';
}
