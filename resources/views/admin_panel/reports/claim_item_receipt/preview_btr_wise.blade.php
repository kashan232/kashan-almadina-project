<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Claim BTR Wise Report</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 10mm;
            background-color: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 15px;
        }
        .report-title {
            color: #0d47a1;
            font-size: 22px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            display: inline-block;
        }
        .date-range {
            position: absolute;
            right: 0;
            top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            left: 0;
            top: 5px;
            font-size: 11px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        th {
            background-color: #fff59d;
            border: 1px solid #000;
            padding: 6px 4px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            color: #000;
        }
        td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
            font-size: 11px;
        }
        .btr-header-row td {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .total-row td {
            font-weight: bold;
            background-color: #fffde7;
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .btn-print {
            background-color: #0d47a1;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .qty-link {
            color: #0d47a1;
            text-decoration: underline;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">Print Report</button>
    </div>

    <div class="report-header">
        <div class="generated-date">Date: {{ date('d-m-Y') }}</div>
        <h1 class="report-title">Al Madina Traders</h1>
        <div class="date-range">
            From: <span>{{ $from_date ? date('d-m-Y', strtotime($from_date)) : 'Start' }}</span> 
            To: <span>{{ $to_date ? date('d-m-Y', strtotime($to_date)) : 'End' }}</span>
        </div>
        <div style="font-weight: bold; margin-top: 5px; font-size: 13px; text-decoration: underline;">
            Claim BTR Wise Report
        </div>
    </div>

    @forelse($btrGroups as $btrNo => $items)
        @php
            $totClmAcp = 0;
            $totCir = 0;
            $totBal = 0;
        @endphp
        <table>
            <thead>
                <tr>
                    <th style="width: 18%; text-align: left;">Brand</th>
                    <th style="width: 40%; text-align: left;">Item</th>
                    <th style="width: 14%; text-align: right;">Clm Acp</th>
                    <th style="width: 14%; text-align: right;">CIR</th>
                    <th style="width: 14%; text-align: right;">Balance</th>
                </tr>
                <tr class="btr-header-row">
                    <td colspan="5">BTR # {{ $btrNo }}</td>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    @php
                        $bal = $item['clm_acp'] - $item['cir'];
                        $totClmAcp += $item['clm_acp'];
                        $totCir += $item['cir'];
                        $totBal += $bal;

                        $detailsB64 = base64_encode(json_encode([
                            'btr_no' => $btrNo,
                            'brand_name' => $item['brand_name'],
                            'product_name' => $item['product_name'],
                            'entries' => array_values($item['entries'] ?? []),
                        ], JSON_UNESCAPED_UNICODE));
                    @endphp
                    <tr>
                        <td>{{ $item['brand_name'] }}</td>
                        <td>{{ $item['product_name'] }}</td>
                        <td style="text-align: right;">
                            @if($item['clm_acp'] > 0)
                                <a href="javascript:void(0)" class="qty-link qty-detail-link" data-details="{{ $detailsB64 }}">
                                    {{ number_format($item['clm_acp'], 0) }}
                                </a>
                            @else
                                0
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($item['cir'] > 0)
                                <a href="javascript:void(0)" class="qty-link qty-detail-link" data-details="{{ $detailsB64 }}">
                                    {{ number_format($item['cir'], 0) }}
                                </a>
                            @else
                                0
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if(abs($bal) > 0)
                                <a href="javascript:void(0)" class="qty-link qty-detail-link" data-details="{{ $detailsB64 }}">
                                    {{ number_format($bal, 0) }}
                                </a>
                            @else
                                0
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" style="text-align: right;">Total Bal.</td>
                    <td style="text-align: right;">{{ number_format($totClmAcp, 0) }}</td>
                    <td style="text-align: right;">{{ number_format($totCir, 0) }}</td>
                    <td style="text-align: right;">{{ number_format($totBal, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <table style="text-align: center;">
            <tr>
                <td colspan="5" style="padding: 20px; color: #777;">No records found for the selected criteria.</td>
            </tr>
        </table>
    @endforelse

    <!-- Detail Modal -->
    <div id="detailModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
        <div style="background:#fff; width:80%; max-width:850px; margin:50px auto; padding:20px; border-radius:6px; box-shadow:0 5px 15px rgba(0,0,0,0.3); max-height:85vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #0d47a1; padding-bottom:10px; margin-bottom:15px;">
                <h3 id="modalTitle" style="margin:0; color:#0d47a1; font-size:16px;">BTR Voucher Details</h3>
                <span id="closeModal" style="font-size:24px; font-weight:bold; cursor:pointer; color:#888;">&times;</span>
            </div>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('detailModal');
            const closeModalBtn = document.getElementById('closeModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');

            closeModalBtn.onclick = function() {
                modal.style.display = 'none';
            };

            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            };

            document.querySelectorAll('.qty-detail-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const rawB64 = this.getAttribute('data-details');
                    if (!rawB64) return;

                    try {
                        const binaryStr = atob(rawB64);
                        const bytes = Uint8Array.from(binaryStr, c => c.charCodeAt(0));
                        const decodedStr = new TextDecoder('utf-8').decode(bytes);
                        const data = JSON.parse(decodedStr);

                        modalTitle.textContent = 'BTR Details - BTR #' + data.btr_no + ' (' + data.brand_name + ' - ' + data.product_name + ')';

                        let html = '<table style="width:100%; border-collapse:collapse; font-size:11px; margin-top:10px;">';
                        html += '<thead><tr style="background:#0d47a1; color:#fff;">';
                        html += '<th style="border:1px solid #ccc; padding:6px; text-align:center;">#</th>';
                        html += '<th style="border:1px solid #ccc; padding:6px; text-align:left;">Voucher Type</th>';
                        html += '<th style="border:1px solid #ccc; padding:6px; text-align:center;">Voucher #</th>';
                        html += '<th style="border:1px solid #ccc; padding:6px; text-align:center;">Date</th>';
                        html += '<th style="border:1px solid #ccc; padding:6px; text-align:left;">Party Name</th>';
                        html += '<th style="border:1px solid #ccc; padding:6px; text-align:right;">Clm Acp</th>';
                        html += '<th style="border:1px solid #ccc; padding:6px; text-align:right;">CIR</th>';
                        html += '</tr></thead><tbody>';

                        let sumClmAcp = 0;
                        let sumCir = 0;

                        if (data.entries && data.entries.length > 0) {
                            data.entries.forEach(function(entry, idx) {
                                const clmAcp = parseFloat(entry.clm_acp || 0);
                                const cir = parseFloat(entry.cir || 0);
                                sumClmAcp += clmAcp;
                                sumCir += cir;

                                html += '<tr>';
                                html += '<td style="border:1px solid #ccc; padding:6px; text-align:center;">' + (idx + 1) + '</td>';
                                html += '<td style="border:1px solid #ccc; padding:6px;">' + (entry.type || 'N/A') + '</td>';
                                html += '<td style="border:1px solid #ccc; padding:6px; text-align:center; font-weight:bold;">' + (entry.voucher_no || 'N/A') + '</td>';
                                html += '<td style="border:1px solid #ccc; padding:6px; text-align:center;">' + (entry.date || 'N/A') + '</td>';
                                html += '<td style="border:1px solid #ccc; padding:6px;">' + (entry.party_name || 'N/A') + '</td>';
                                html += '<td style="border:1px solid #ccc; padding:6px; text-align:right;">' + clmAcp + '</td>';
                                html += '<td style="border:1px solid #ccc; padding:6px; text-align:right;">' + cir + '</td>';
                                html += '</tr>';
                            });

                            html += '<tr style="font-weight:bold; background:#e3f2fd;">';
                            html += '<td colspan="5" style="border:1px solid #ccc; padding:6px; text-align:right;">Total:</td>';
                            html += '<td style="border:1px solid #ccc; padding:6px; text-align:right;">' + sumClmAcp + '</td>';
                            html += '<td style="border:1px solid #ccc; padding:6px; text-align:right;">' + sumCir + '</td>';
                            html += '</tr>';
                        } else {
                            html += '<tr><td colspan="7" style="border:1px solid #ccc; padding:15px; text-align:center; color:#777;">No voucher entries available.</td></tr>';
                        }

                        html += '</tbody></table>';
                        modalContent.innerHTML = html;
                        modal.style.display = 'block';
                    } catch(err) {
                        console.error('Error parsing BTR details:', err);
                        alert('Unable to load details.');
                    }
                });
            });
        });
    </script>
</body>
</html>
