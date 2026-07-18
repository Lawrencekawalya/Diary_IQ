# Updated Proposal Sections: Technical Tools and Software

The following text replaces Section 3.12 and its subsections in the report. It
describes the Flask-based DairyIQ prototype and the approved 11-feature machine
learning contract.

## 3.12 Technical Tools and Software

The development of DairyIQ will require a combination of data-processing,
machine-learning, web-development, database, visualization, deployment, and
testing technologies. Python will serve as the principal programming language
because it provides mature libraries for data analysis, machine learning, web
application development, and integration with external services.

The machine-learning component will be developed separately from the running
web application. The training pipeline will prepare and validate the approved
dataset, train and evaluate candidate classification models, and save the
selected Random Forest model as a versioned artifact. The Flask application
will then load the approved artifact and use it to classify milk samples from
the 11 submitted laboratory, sensory, and appearance parameters.

The prototype will use a client-server architecture. Flask will process user
requests and coordinate model inference, while HTML, CSS, JavaScript, and Jinja
templates will provide the browser-based interface. Firebase Authentication
will manage authorized access, and Firebase Firestore will store milk-batch
records, measurements, prediction results, and model metadata. This combination
of technologies will support prediction, historical analysis, visualization,
reporting, and future extension of the DairyIQ system.

## 3.12.1 Data Processing Tools

Pandas and NumPy will be used for data preparation and numerical processing.
Pandas will provide DataFrame structures for loading, inspecting, cleaning, and
transforming the milk-quality dataset. It will also support the selection and
ordering of the 11 model features: pH, temperature, taste, odor, fat content,
titratable acidity, protein content, lactose content, Total Plate Count (TPC),
Somatic Cell Count (SCC), and color.

Pandas will be used to identify missing values, duplicate records, invalid data
types, inconsistent category names, and values outside approved physical or
operational ranges. It will also support analysis of class distribution and
verification that the target contains the defined `Low`, `Medium`, and
`High` quality categories.

NumPy will support numerical operations used during preprocessing and
evaluation. These operations may include statistical summaries, deterministic
data generation where synthetic data is formally approved, and logarithmic
transformation of highly skewed TPC and SCC measurements. Any transformation
retained after experimentation will be included in the production
preprocessing pipeline so that training and web-based prediction apply exactly
the same processing rules.

Taste, odor, and color will be encoded according to a domain-approved mapping.
The representation will use `1` for a normal or acceptable condition and `0`
for an abnormal condition. The taste parameter will use a safe approved
assessment method and will not instruct operators to consume potentially
contaminated raw milk.

Matplotlib and Seaborn will be used during exploratory analysis and model
evaluation. Matplotlib will generate learning curves, confusion matrices, and
feature-importance plots. Seaborn will support statistical visualizations such
as class-distribution plots, correlation heatmaps, box plots, and
feature-distribution comparisons. These research visualizations will be
generated from reproducible evaluation results and included in the report.
Interactive charts displayed in the deployed web interface will be produced
separately using Chart.js.

## 3.12.2 Machine Learning Frameworks

Scikit-learn will provide the main machine-learning framework for
preprocessing, model training, hyperparameter tuning, cross-validation, and
performance evaluation. The principal classifier will be the
`RandomForestClassifier`, selected because it supports multiclass
classification, models nonlinear relationships, handles mixed laboratory and
encoded sensory features, and provides feature-importance estimates.

The Random Forest model will be trained using a stratified training and testing
strategy so that the `Low`, `Medium`, and `High` classes are represented
appropriately in each partition. Hyperparameters such as the number of trees,
maximum tree depth, minimum samples required for splitting and leaf nodes,
maximum features considered at each split, and class weighting will be
evaluated using cross-validation. The final configuration will be selected from
reproducible experimental results rather than assumed in advance.

Model performance will be assessed using accuracy, balanced accuracy,
macro-precision, macro-recall, macro F1-score, class-specific precision and
recall, confusion matrices, and five-fold cross-validation. Particular
attention will be given to recall for the `Low` class because incorrectly
classifying poor-quality milk as acceptable presents a greater operational and
food-safety risk.

Where required for comparative evaluation, Support Vector Machine, Artificial
Neural Network, and XGBoost classifiers will be trained using the same approved
dataset partitions and evaluation protocol. Scikit-learn will support the
Random Forest and Support Vector Machine implementations. XGBoost will be
implemented using its Python package if it remains part of the approved
comparison. PyTorch may be used for the comparative Artificial Neural Network.
Results for these models will only be reported after they have been reproduced
from the final implementation.

Scikit-learn pipelines will be used to keep preprocessing and classification
operations together. This will reduce the risk of applying different
transformations during model training and Flask inference. The existing
nine-feature candidate and the expanded 11-feature candidate will be evaluated
using identical data partitions to determine whether taste, odor, and color
improve generalization and class-specific performance.

Joblib will be used to save the selected preprocessing pipeline and Random
Forest classifier as a trusted, versioned model artifact. The artifact or its
associated metadata will contain the ordered feature list, supported classes,
sensory encoding definitions, model version, dataset version, label-policy
version, hyperparameters, dependency versions, evaluation metrics, training
time, and checksum. The Flask application will reject an artifact whose
feature contract or classes do not match the approved system specification.

## 3.12.3 Web Application Framework

Flask will be used to develop the DairyIQ backend and connect the trained
Random Forest model to the browser-based prototype. Flask will manage
application routes, authenticated sessions, form submission, server-side
validation, feature preparation, model inference, standards-based observations,
historical record retrieval, and result rendering.

The backend will validate all 11 required inputs before prediction. It will
convert the approved sensory selections into the same encoded values used
during training, arrange all features in the model's required order, and submit
the resulting DataFrame to the saved Random Forest pipeline. The prediction
service will return one of the supported classes together with class
probabilities, model version, dataset version, and label-policy version.

Jinja templates will connect Flask data with the HTML interface. HTML and CSS
will define the structure and responsive presentation of the login, batch
entry, testing, result, and history pages. JavaScript will support client-side
interaction and visualization, but Flask will remain responsible for
authoritative validation and prediction processing.

## 3.12.4 Database and Authentication Tools

Firebase Authentication will be used to verify authorized DairyIQ users.
Authentication credentials will remain under Firebase Authentication and will
not be stored as plain text in the application database. Flask sessions will
protect restricted routes after successful authentication.

Firebase Firestore will serve as the document-oriented database for the
prototype. It will store collection-center and transport information, all 11
model inputs, original sensory descriptions, encoded sensory values, predicted
class, class probabilities, observations, model metadata, user references, and
timestamps. Firestore will also support retrieval of historical records for
tables, charts, district analysis, and report export.

The database design will separate operational milk-batch records from user
profiles, model-version records, collection-center records, and audit events.
This separation will improve traceability and maintainability while allowing
each prediction to be linked to the exact model version that produced it.

## 3.12.5 Visualization and Reporting Tools

Chart.js will be used to display interactive visualizations in the DairyIQ web
interface. The result page will present entered parameters, the predicted milk
quality, and model probabilities where those probabilities have been properly
validated or calibrated. The history page will display milk-quality trends,
class distributions, collection-center statistics, district comparisons, and
milk-volume information.

DataTables and its export extensions will provide searchable and sortable
historical tables. Supported records may be exported to formats such as CSV,
Excel, or PDF according to the final implementation. Exported reports will
include the batch details, measurements, prediction, observations, model
version, and generation timestamp.

Standards-based observations will be presented separately from the Random
Forest classification. This distinction will prevent threshold-based warnings
from being incorrectly described as explanations generated by the machine
learning model.

## 3.12.6 Deployment and Testing Tools

Gunicorn will serve the Flask application in the deployment environment. The
application may be deployed to an approved hosted environment or to a server on
a dairy plant's local network. Because Firebase services are part of the
current architecture, the implemented prototype will require network access.
Fully offline operation will remain a future enhancement unless a local
database and authentication alternative is implemented.

Pytest will be used for automated testing. Tests will cover dataset validation,
sensory encoding, model training, artifact compatibility, prediction outputs,
Flask routes, authentication behavior, Firestore interactions, report
generation, and handling of invalid input. External Firebase operations will be
replaced with test doubles during automated tests so that tests do not depend
on production credentials or modify real operational records.

Git will support source-code version control, while environment variables will
provide secret and deployment configuration. Passwords, Firebase credentials,
and secret keys will not be hard-coded in source files. Versioned model
artifacts, evaluation reports, dataset metadata, and automated tests will
support reproducibility and controlled deployment of the final DairyIQ
prototype.
