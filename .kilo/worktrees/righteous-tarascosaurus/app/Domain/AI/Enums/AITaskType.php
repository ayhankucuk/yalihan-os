<?php

namespace App\Domain\AI\Enums;

enum AITaskType: string
{
    case ANALYZE_PROPERTY = 'analyze_property';
    case EXTRACT_PROPERTY_FEATURES = 'extract_property_features';
    case SUGGEST_PROPERTY_TEMPLATE = 'suggest_property_template';
    case GENERATE_PROPERTY_TEMPLATE = 'generate_property_template';
    case RECOMMEND_NEXT_ACTIONS = 'recommend_next_actions';
    case AUDIT_PORTFOLIO_HEALTH = 'audit_portfolio_health';
}
