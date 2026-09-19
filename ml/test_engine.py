import sys
from pathlib import Path

# Add project root to sys.path
BASE_DIR = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(BASE_DIR))

from ml.analytics_engine import AttendanceAnalyticsEngine, build_synthetic_training_pool

def test_engine():
    print("Running Python Scikit-Learn Engine Unit Tests...")
    
    # 1. Test synthetic pool generation
    df = build_synthetic_training_pool(n_samples=500)
    assert len(df) == 500, f"Expected 500 rows, got {len(df)}"
    assert "is_at_risk" in df.columns, "Missing target column 'is_at_risk'"
    print("[OK] Synthetic training pool generated successfully.")

    # 2. Test Model Training
    engine = AttendanceAnalyticsEngine()
    metrics = engine.train_models(df)
    assert metrics["accuracy"] > 70.0, f"Accuracy too low: {metrics['accuracy']}%"
    assert metrics["roc_auc"] > 0.75, f"ROC-AUC too low: {metrics['roc_auc']}"
    assert len(engine.feature_importances) > 0, "Missing feature importances"
    assert len(engine.cluster_profiles) == 4, f"Expected 4 cluster profiles, got {len(engine.cluster_profiles)}"
    print(f"[OK] Scikit-Learn models trained successfully (Accuracy: {metrics['accuracy']}%, ROC-AUC: {metrics['roc_auc']}).")

    # 3. Test Full Payload Generation
    payload = engine.generate_full_analytics_payload()
    assert payload["status"] == "success", "Payload status is not success"
    assert "overview" in payload, "Missing overview section in payload"
    assert "at_risk_students" in payload, "Missing at_risk_students in payload"
    assert len(payload["at_risk_students"]) > 0, "No at_risk_students found"
    print(f"[OK] Full JSON analytics payload generated ({len(payload['at_risk_students'])} at-risk students classified).")

    print("\nALL PYTHON SCIKIT-LEARN TESTS PASSED!")

if __name__ == "__main__":
    test_engine()
