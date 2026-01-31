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
                        <div class="col-md-2">
                            <label for="timeframe" class="form-label">Timeframe</label>
                            <select class="form-select" id="timeframe">
                                <option value="1min">1 Minute</option>
                                <option value="3min">3 Minutes</option>
                                <option value="5min">5 Minutes</option>
                                <option value="15min" selected>15 Minutes</option>
                                <option value="30min">30 Minutes</option>
                                <option value="1hour">1 Hour</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="mode" class="form-label">Mode</label>
                            <select class="form-select" id="mode" onchange="toggleBacktestInputs()">
                                <option value="live">Live Market</option>
                                <option value="backtest">Backtest</option>
                            </select>
                        </div>
                        
                        <!-- Backtest Inputs (Hidden by default) -->
                        <div class="col-md-2 backtest-field d-none">
                            <label for="btDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="btDate" value="<?= date('Y-m-d', strtotime('-1 day')) ?>">
                        </div>
                        <div class="col-md-2 backtest-field d-none">
                            <label for="btFromTime" class="form-label">From Time</label>
                            <input type="time" class="form-control" id="btFromTime" value="09:15">
                        </div>
                        <div class="col-md-2 backtest-field d-none">
                            <label for="btToTime" class="form-label">To Time</label>
                            <input type="time" class="form-control" id="btToTime" value="15:30">
                        </div>
                        
                        <!-- Threshold Input -->
                        <div class="col-md-2">
                            <label for="threshold" class="form-label">Threshold (x)</label>
                            <input type="number" class="form-control" id="threshold" value="2.0" min="1.0" max="10.0" step="0.1" title="Price multiplier (2.0 = 100% gain)">
                            <div class="form-text" style="font-size: 0.7rem;">2.0 = 100% gain</div>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100" id="btnScan">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Scan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Debug Info Card -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow-sm border-info" id="debugCard" style="display: none;">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">📊 Scan Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h5 class="text-primary mb-0" id="statsStocks">-</h5>
                            <small class="text-muted">Stocks Scanned</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-primary mb-0" id="statsContracts">-</h5>
                            <small class="text-muted">Contracts Checked</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-primary mb-0" id="statsCandles">-</h5>
                            <small class="text-muted">Candles Analyzed</small>
                        </div>
                        <div class="col-md-3">
                            <h5 class="text-success mb-0" id="statsMatches">-</h5>
                            <small class="text-muted">Signals Found</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted" id="debugMessage"></small>
                    </div>
                    <!-- Detailed Debug Log -->
                    <div class="mt-3" id="debugDetails" style="display: none;">
                        <hr>
                        <h6 class="text-muted">Detailed Log:</h6>
                        <div id="debugDetailsContent" style="max-height: 200px; overflow-y: auto; font-size: 0.85rem;">
                        </div>
                    </div>
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
                        <table id="resultsTable" class="table table-striped table-hover table-sm text-center" style="font-size: 0.9rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th>Script</th>
                                    <th>PC LP</th>
                                    <th>CC LP</th>
                                    <th>% diff</th>
                                    
                                    <!-- OHLC -->
                                    <th>Open</th>
                                    <th>High</th>
                                    <th>Low</th>
                                    
                                    <!-- Indicators -->
                                    <th>Vol</th>
                                    <th>RSI</th>
                                    <th>EMA10</th>
                                    <th>EMA20</th>
                                    <th>EMA50</th>
                                    <th>EMA200</th>
                                    
                                    <!-- Greeks -->
                                    <th>Delta</th>
                                    <th>IV</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    function toggleBacktestInputs() {
        const mode = document.getElementById('mode').value;
        const fields = document.querySelectorAll('.backtest-field');
        fields.forEach(f => {
            if (mode === 'backtest') f.classList.remove('d-none');
            else f.classList.add('d-none');
        });
    }

    // Status Updater (Keep existing)
    // ... (omitted brevity, assume kept or just call updateSystemStatus which is global if defined previously, 
    // actually I am rewriting the script block)

    // Scan Logic
    const form = document.getElementById('scanForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnScan');
            const spinner = btn.querySelector('.spinner-border');
            const tbody = document.querySelector('#resultsTable tbody');
            
            btn.disabled = true;
            spinner.classList.remove('d-none');
            tbody.innerHTML = '<tr><td colspan="15" class="text-center">Scanning... This may take a few seconds.</td></tr>';

            const timeframe = document.getElementById('timeframe').value;
            const mode = document.getElementById('mode').value;
            const threshold = document.getElementById('threshold').value;
            
            // Build Params
            const params = new URLSearchParams({
                timeframe: timeframe,
                mode: mode,
                threshold: threshold
            });
            
            if (mode === 'backtest') {
                params.append('date', document.getElementById('btDate').value);
                params.append('fromTime', document.getElementById('btFromTime').value);
                params.append('toTime', document.getElementById('btToTime').value);
            }

            fetch('api/scan.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = '';
                    
                    // Show debug card
                    const debugCard = document.getElementById('debugCard');
                    debugCard.style.display = 'block';
                    
                    // Update stats
                    if (data.debug) {
                        document.getElementById('statsStocks').textContent = data.debug.stocks_scanned || 0;
                        document.getElementById('statsContracts').textContent = data.debug.contracts_checked || 0;
                        document.getElementById('statsCandles').textContent = data.debug.candles_analyzed || 0;
                        document.getElementById('statsMatches').textContent = data.debug.matches_found || 0;
                        
                        const threshold = data.debug.threshold || 2.0;
                        const pctGain = ((threshold - 1) * 100).toFixed(0);
                        document.getElementById('debugMessage').textContent = 
                            `Scanned with ${threshold}x threshold (${pctGain}% gain required). ` +
                            `Execution time: ${data.debug.execution_time || 'N/A'}`;
                        
                        // Display detailed debug info
                        if (data.debug.details && data.debug.details.length > 0) {
                            const debugDetails = document.getElementById('debugDetails');
                            const debugContent = document.getElementById('debugDetailsContent');
                            debugDetails.style.display = 'block';
                            
                            let html = '<ul class="list-unstyled mb-0">';
                            data.debug.details.forEach(stock => {
                                html += `<li class="mb-2">`;
                                html += `<strong>${stock.name}</strong> (${stock.symbol}, Token: ${stock.token})<br>`;
                                
                                if (stock.cmp) {
                                    html += `<span class="text-success">✓ CMP: ₹${stock.cmp} (from ${stock.cmp_source})</span><br>`;
                                } else {
                                    html += `<span class="text-danger">✗ CMP: Failed to fetch</span><br>`;
                                }
                                
                                if (stock.strikes_found > 0) {
                                    html += `<span class="text-success">✓ Found ${stock.strikes_found} option contracts</span><br>`;
                                } else {
                                    html += `<span class="text-warning">⚠ No option contracts found</span><br>`;
                                    if (stock.strike_range) {
                                        html += `<small class="text-muted">Searched range: ₹${stock.strike_range.min} - ₹${stock.strike_range.max}</small><br>`;
                                    }
                                }
                                
                                if (stock.error) {
                                    html += `<span class="text-danger">Error: ${stock.error}</span><br>`;
                                }
                                
                                html += `</li>`;
                            });
                            html += '</ul>';
                            debugContent.innerHTML = html;
                        }
                    }
                    
                    if (data.status === 'success' && data.data && data.data.length > 0) {
                        data.data.forEach(signal => {
                            const row = `
                                <tr>
                                    <td class="fw-bold text-start">${signal.stock}</td>
                                    <td>${signal.pclp}</td>
                                    <td class="fw-bold text-success">${signal.cclp}</td>
                                    <td><span class="badge bg-success">${signal.change_pct}%</span></td>
                                    
                                    <td>${signal.open}</td>
                                    <td>${signal.high}</td>
                                    <td>${signal.low}</td>
                                    
                                    <td>${signal.volume}</td>
                                    <td>${signal.rsi}</td>
                                    <td>${signal.ema10}</td>
                                    <td>${signal.ema20}</td>
                                    <td>${signal.ema50}</td>
                                    <td>${signal.ema200}</td>
                                    
                                    <td>${signal.delta}</td>
                                    <td>${signal.iv}</td>
                                    <td>${signal.timestamp}</td>
                                </tr>
                            `;
                            tbody.innerHTML += row;
                        });
                        
                        // Re-init DataTable if needed, or just append rows?
                        // For simplicity, just HTML append. If Sort needed, destroy and re-init.
                        // $('#resultsTable').DataTable().destroy(); 
                        // $('#resultsTable').DataTable({ ... options ... });
                        
                    } else {
                        tbody.innerHTML = '<tr><td colspan="16" class="text-center text-muted">No signals found matching criteria.</td></tr>';
                    }
                    
                    if (data.status === 'error') {
                        tbody.innerHTML = `<tr><td colspan="16" class="text-center text-danger">Error: ${data.message}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    tbody.innerHTML = '<tr><td colspan="16" class="text-center text-danger">Network Error occurred.</td></tr>';
                })
                .finally(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });
        });
    }
</script>
