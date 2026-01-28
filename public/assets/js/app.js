$(document).ready(function() {
    // Initialize DataTable
    var table = $('#resultsTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 25,
        "language": {
            "emptyTable": "No signals found in this scan."
        }
    });

    $('#scanForm').on('submit', function(e) {
        e.preventDefault();
        
        var timeframe = $('#timeframe').val();
        var mode = $('#mode').val();
        var btn = $('#btnScan');
        var spinner = btn.find('.spinner-border');

        // UI Loading State
        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        table.clear().draw();

        $.ajax({
            url: 'api/scan.php',
            method: 'GET',
            data: {
                timeframe: timeframe,
                mode: mode
            },
            success: function(response) {
                if (response.status === 'success') {
                    var data = response.data;
                    if (data.length > 0) {
                        data.forEach(function(row) {
                            table.row.add([
                                row.timestamp,
                                row.stock,
                                row.contract,
                                `<span class="badge ${row.type === 'CE' ? 'bg-success' : 'bg-danger'}">${row.type}</span>`,
                                row.strike,
                                row.price,
                                row.prev_close,
                                `<span class="text-success fw-bold">+${row.change_pct}%</span>`,
                                row.rsi
                            ]);
                        });
                        table.draw();
                    } else {
                        // Empty already handled by DataTable default
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while scanning: ' + error);
            },
            complete: function() {
                // Reset UI State
                btn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });
});
