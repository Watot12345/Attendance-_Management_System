<?php
require_once __DIR__ . '/../includes/controllers/AnalyticsController.php';
require_once __DIR__ . '/../includes/core/Router.php';

echo "=====================================================\n";
echo "   INTEGRATION TEST SUITE: SCIKIT-LEARN ANALYTICS    \n";
echo "=====================================================\n\n";

function assertTest(string $title, bool $condition) {
    echo $condition ? "[PASS] " : "[FAIL] ";
    echo "$title\n";
    if (!$condition) {
        exit(1);
    }
}

// 1. Test Router Mapping for all Analytics endpoints
$routerReflection = new ReflectionClass('Router');
$routesProp = $routerReflection->getProperty('routes');
$routesProp->setAccessible(true);
$routes = $routesProp->getValue();

assertTest("Route /dashboard/analytics is registered", isset($routes['/dashboard/analytics']));
assertTest("Route /api/analytics/overview is registered", isset($routes['/api/analytics/overview']));
assertTest("Route /api/analytics/patterns is registered", isset($routes['/api/analytics/patterns']));
assertTest("Route /api/analytics/at-risk is registered", isset($routes['/api/analytics/at-risk']));
assertTest("Route /api/analytics/retrain is registered", isset($routes['/api/analytics/retrain']));
assertTest("Route /api/analytics/intervene is registered", isset($routes['/api/analytics/intervene']));

// 2. Test getMlPayload
$payload = AnalyticsController::getMlPayload(false);
assertTest("ML payload returned success status", ($payload['status'] ?? '') === 'success');
assertTest("ML model specifications present", !empty($payload['model_specs']));
assertTest("RandomForest algorithm identified", stripos($payload['model_specs']['algorithm'], 'RandomForest') !== false);
assertTest("ROC-AUC metric is calculated and valid", is_numeric($payload['model_specs']['roc_auc']));
assertTest("Feature importances array present", count($payload['feature_importances']) >= 4);
assertTest("At-risk students list generated", count($payload['at_risk_students']) > 0);
assertTest("Detected patterns list generated", count($payload['patterns']) >= 3);
assertTest("K-Means cluster profiles generated", count($payload['cluster_profiles']) === 4);

// 3. Test Retraining capability
$retrainPayload = AnalyticsController::getMlPayload(true);
assertTest("Live Retrain generates valid updated model specs", !empty($retrainPayload['model_specs']['last_retrained']));

echo "\n=====================================================\n";
echo "   ALL INTEGRATION & UNIT TESTS PASSED SUCCESSFULLY! \n";
echo "=====================================================\n";
