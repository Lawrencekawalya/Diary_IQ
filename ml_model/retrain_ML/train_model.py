import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report, confusion_matrix, accuracy_score
import joblib

# 1) Load dataset
data = pd.read_csv("milk_quality_dataset.csv")

# Features and target
X = data.drop(columns=["Label", "All_Normal"])
y = data["Label"]

# 2) Split data
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42, stratify=y
)

# 3) Train Random Forest
rf = RandomForestClassifier(
    n_estimators=200,   # number of trees
    max_depth=None,     # let it expand fully
    random_state=42,
    class_weight="balanced"   # handle any imbalance
)
rf.fit(X_train, y_train)

# 4) Evaluate
y_pred = rf.predict(X_test)
print("Accuracy:", accuracy_score(y_test, y_pred))
print("\nClassification Report:\n", classification_report(y_test, y_pred))
print("\nConfusion Matrix:\n", confusion_matrix(y_test, y_pred))

# 5) Save model
joblib.dump(rf, "dairy_model_4class.pkl")
print("✅ Model saved as dairy_model_3class.pkl")

# 6) (Optional) If you want XGBoost later
"""
from xgboost import XGBClassifier

xgb = XGBClassifier(
    n_estimators=300,
    learning_rate=0.05,
    max_depth=6,
    random_state=42,
    subsample=0.8,
    colsample_bytree=0.8,
    eval_metric="mlogloss"
)
xgb.fit(X_train, y_train)
joblib.dump(xgb, "dairy_model_4class_xgb.pkl")
"""
