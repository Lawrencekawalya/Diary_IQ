<div class="report-page">
    <div class="report-header">
        <div>
            <div class="brand">DairyIQ Analytics</div>
            <div class="muted">Milk Quality Prediction Report</div>
        </div>
        <div class="muted">Generated: {{ now()->toDayDateTimeString() }}</div>
    </div>

    <div class="card summary">
        <h2>Executive Summary</h2>
        <p class="muted">Prediction Result According to US EAS 67:2023 - Raw Cow Milk Specification</p>
        <div class="prediction prediction-{{ strtolower($batch->prediction) }}">{{ $batch->prediction }}</div>
        <p>
            Raw Random Forest vote: <strong>{{ $batch->ml_prediction ?? 'N/A' }}</strong>.
            Confidence: <strong>{{ $batch->confidence ?? 'N/A' }}</strong>.
        </p>
        @if(($batch->standards_quality_gate['applied'] ?? false) === true)
            <p>
                Final decision was adjusted by the standards safety gate:
                {{ $batch->standards_quality_gate['reason'] ?? 'Standards checks required downgrade.' }}
            </p>
        @else
            <p>No standards gate downgrade was required.</p>
        @endif
    </div>

    <div class="grid">
        <div class="grid-cell">
            <div class="card">
                <h2>Company Details</h2>
                <table>
                    <tr><td>Company</td><td>{{ $company->name ?? 'N/A' }}</td></tr>
                    <tr><td>Contact Email</td><td>{{ $company->contact_email ?? 'N/A' }}</td></tr>
                    <tr><td>Phone</td><td>{{ $company->phone ?? 'N/A' }}</td></tr>
                    <tr><td>Address</td><td>{{ $company->address ?? 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
        <div class="grid-cell">
            <div class="card">
                <h2>Batch Details</h2>
                <table>
                    <tr><td>Batch Number</td><td>{{ $batch->batch_number }}</td></tr>
                    <tr><td>Collection Center</td><td>{{ $batch->collection_center ?? 'N/A' }}</td></tr>
                    <tr><td>District</td><td>{{ $batch->district ?? 'N/A' }}</td></tr>
                    <tr><td>Tested By</td><td>{{ $batch->tested_by ?? $batch->user?->name ?? 'N/A' }}</td></tr>
                    <tr><td>Collected At</td><td>{{ $batch->collected_at?->toDayDateTimeString() ?? 'N/A' }}</td></tr>
                    <tr><td>Liters Collected</td><td>{{ $batch->liters_collected ?? 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="section-divider"></div>

    <div class="report-header">
        <div class="brand">Measured Inputs and Standards Status</div>
        <div class="muted">{{ $batch->batch_number }}</div>
    </div>

    <div class="grid">
        <div class="grid-cell">
            <div class="card">
                <h2>Measured Inputs</h2>
                <table>
                    <thead>
                        <tr><th>Feature</th><th>Value</th></tr>
                    </thead>
                    <tbody>
                        @foreach($measurements as $feature => $value)
                            <tr><td>{{ $feature }}</td><td>{{ $value }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="grid-cell">
            <div class="card">
                <h2>Standards Status</h2>
                <table>
                    <thead>
                        <tr><th>Feature</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach($standardsRows as $row)
                            <tr>
                                <td>{{ $row['feature'] }}</td>
                                <td class="{{ $row['status'] === 'Normal' ? 'status-normal' : 'status-out' }}">{{ $row['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="grid">
        <div class="grid-cell">
            <div class="card">
                <h2>Class Probabilities</h2>
                @foreach(($batch->probabilities ?? []) as $label => $value)
                    <div class="chart-row">
                        <strong class="chart-label">{{ $label }}</strong>
                        <div class="chart-bar">
                            <div class="bar-track">
                                <div class="bar" style="width: {{ max(0, min(100, (float) $value * 100)) }}%;"></div>
                            </div>
                        </div>
                        <span class="chart-value">{{ number_format((float) $value * 100, 2) }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="grid-cell">
            <div class="card">
                <h2>Final Prediction Chart</h2>
                @foreach(['Low', 'Medium', 'High'] as $label)
                    <div class="chart-row">
                        <strong class="chart-label">{{ $label }}</strong>
                        <div class="chart-bar">
                            <div class="bar-track">
                                <div class="bar" style="width: {{ $batch->prediction === $label ? 100 : 0 }}%; background: {{ $label === 'High' ? '#16853a' : ($label === 'Medium' ? '#f59e0b' : '#b42318') }};"></div>
                            </div>
                        </div>
                        <span class="chart-value">{{ $batch->prediction === $label ? 'Selected' : '' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Standards Observations</h2>
        <ul>
            @foreach(($batch->standards_observations ?? []) as $observation)
                <li>{{ $observation }}</li>
            @endforeach
        </ul>
    </div>

    <div class="card">
        <h2>Model Metadata</h2>
        <table>
            <tr><td>Model Version</td><td>{{ $batch->model_metadata['model_version'] ?? 'N/A' }}</td></tr>
            <tr><td>Dataset Version</td><td>{{ $batch->model_metadata['dataset_version'] ?? 'N/A' }}</td></tr>
            <tr><td>Label Policy</td><td>{{ $batch->model_metadata['label_policy_version'] ?? 'N/A' }}</td></tr>
            <tr><td>Classes</td><td>{{ implode(', ', $batch->model_metadata['classes'] ?? []) }}</td></tr>
        </table>
    </div>
</div>
