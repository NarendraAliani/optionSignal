<?php
$pageTitle = 'Scanner';
$extraCss = '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">';
$extraJs = '<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Scanner Control</h5>
                    <form id="scanForm" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="timeframe" class="form-label">Timeframe</label>
                            <select class="form-select" id="timeframe">
                                <option value="1min">1 Minute</option>
                                <option value="3min">3 Minutes</option>
                                <option value="5min">5 Minutes</option>
                                <option value="15min">15 Minutes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="mode" class="form-label">Mode</label>
                            <select class="form-select" id="mode">
                                <option value="live">Live Market</option>
                                <option value="backtest">Backtest</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100" id="btnScan">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Run Scan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Signal Results</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="resultsTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Stock</th>
                                    <th>Contract</th>
                                    <th>Type</th>
                                    <th>Strike</th>
                                    <th>Close</th>
                                    <th>Prev Close</th>
                                    <th>Change %</th>
                                    <th>RSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
