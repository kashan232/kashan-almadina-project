<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin_panel.reports.partials.report_global_zoom')
    <meta charset="UTF-8">
    <title>Stock Hold and Release Summary Report</title>
    <style>
        @page { size: A4 landscape; margin: 5mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 8mm;
            background: #fff;
        }
        .no-print {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            text-align: center;
            color: #000;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .report-header {
            text-align: center;
            position: relative;
            margin-bottom: 12px;
        }
        .report-title {
            color: #000;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 8px 0;
        }
        .date-range {
            font-size: 12px;
            font-weight: bold;
            text-align: left;
        }
        .date-range span { text-decoration: underline; }
        .generated-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 11px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 0;
        }
        th {
            background-color: #cfd8dc;
            border: 1px solid #000;
            padding: 6px 4px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 11px;
        }
        .party-title td {
            font-weight: bold;
            font-size: 13px;
            color: #0d47a1;
            border: none;
            padding: 10px 4px 4px;
            background: #fff;
        }
        .item-desc { text-align: left; }
        .num { text-align: center; font-weight: bold; }
        .sno { text-align: center; width: 40px; }
        .subtotal-row td,
        .grand-row td {
            font-weight: bold;
            background: #eceff1;
        }
        .grand-row td {
            background: #cfd8dc;
            border-top: 2px solid #000;
        }
        .empty-msg {
            text-align: center;
            padding: 30px;
            font-size: 14px;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:10px 25px;font-weight:bold;cursor:pointer;">Print Report</button>
        <button onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }" style="padding:10px 25px;margin-left:8px;cursor:pointer;">Close</button>
    </div>

    @php
        $fmt = fn($v) => abs((float)$v) < 0.0001 ? '' : number_format((float)$v, 0);
    @endphp

    <div class="company-name">AL-MADINA TRADERS</div>
    <div class="report-header">
        <div class="generated-date">{{ $generated_at->format('l, F j, Y') }}</div>
        <div class="report-title">
            Stock Hold and Release Summary Report
            @if(($report_type ?? 'party') === 'item')
                <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">Hold and Summary Report</div>
            @else
                <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">Customer / Party Wise</div>
            @endif
        </div>
        <div class="date-range">
            From: <span>{{ $from_date ? \Carbon\Carbon::parse($from_date)->format('d-m-y') : '' }}</span>
            To: <span>{{ $to_date ? \Carbon\Carbon::parse($to_date)->format('d-m-y') : '' }}</span>
        </div>
    </div>

    @forelse($groups as $group)
    <table>
        <thead>
            <tr>
                <th class="sno">S#</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 10%;">Opn</th>
                <th style="width: 10%;">Hold</th>
                <th style="width: 10%;">Rel</th>
                <th style="width: 10%;">Payable</th>
            </tr>
        </thead>
        <tbody>
            <tr class="party-title">
                <td colspan="6">{{ $group['party_name'] }}</td>
            </tr>
            @foreach($group['rows'] as $i => $row)
            @php
                $detailsB64 = base64_encode(json_encode([
                    'party_name' => $group['party_name'],
                    'product_name' => $row['product_name'],
                    'opening' => $row['opening'],
                    'entries' => array_values($row['item_details'] ?? []),
                ], JSON_UNESCAPED_UNICODE));
            @endphp
            <tr>
                <td class="sno">{{ $i + 1 }}</td>
                <td class="item-desc">{{ $row['product_name'] }}</td>
                <td class="num">{{ $fmt($row['opening']) }}</td>
                <td class="num">
                    @if(abs($row['hold']) > 0.0001)
                        <a href="javascript:void(0)" class="qty-detail-link" data-details="{{ $detailsB64 }}" style="color: #0d47a1; text-decoration: underline; font-weight: bold;">
                            {{ $fmt($row['hold']) }}
                        </a>
                    @else
                        {{ $fmt($row['hold']) }}
                    @endif
                </td>
                <td class="num">
                    @if(abs($row['rel']) > 0.0001)
                        <a href="javascript:void(0)" class="qty-detail-link" data-details="{{ $detailsB64 }}" style="color: #0d47a1; text-decoration: underline; font-weight: bold;">
                            {{ $fmt($row['rel']) }}
                        </a>
                    @else
                        {{ $fmt($row['rel']) }}
                    @endif
                </td>
                <td class="num">
                    @if(abs($row['payable']) > 0.0001)
                        <a href="javascript:void(0)" class="qty-detail-link" data-details="{{ $detailsB64 }}" style="color: #0d47a1; text-decoration: underline; font-weight: bold;">
                            {{ $fmt($row['payable']) }}
                        </a>
                    @else
                        {{ $fmt($row['payable']) }}
                    @endif
                </td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td colspan="2" style="text-align:right;">Sub Total.</td>
                <td class="num">{{ $fmt($group['totals']['opening']) }}</td>
                <td class="num">{{ $fmt($group['totals']['hold']) }}</td>
                <td class="num">{{ $fmt($group['totals']['rel']) }}</td>
                <td class="num">{{ $fmt($group['totals']['payable']) }}</td>
            </tr>
        </tbody>
    </table>
    @empty
    <p class="empty-msg">No hold / release data found for selected filters.</p>
    @endforelse

    @if(!empty($groups))
    <table>
        <tbody>
            <tr class="grand-row">
                <td class="sno"></td>
                <td style="text-align:right;">Grand Total</td>
                <td class="num">{{ $fmt($grand['opening']) }}</td>
                <td class="num">{{ $fmt($grand['hold']) }}</td>
                <td class="num">{{ $fmt($grand['rel']) }}</td>
                <td class="num">{{ $fmt($grand['payable']) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Detail Modal -->
    <div id="detailModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
        <div style="background:#fff; width:75%; max-width:800px; margin:50px auto; padding:20px; border-radius:6px; box-shadow:0 5px 15px rgba(0,0,0,0.3); max-height:85vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #0d47a1; padding-bottom:10px; margin-bottom:15px;">
                <h3 id="modalTitle" style="margin:0; color:#0d47a1; font-size:16px;">Item Transaction Details</h3>
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
                    const rawData = this.getAttribute('data-details');
                    if (!rawData) return;

                    try {
                        const jsonStr = decodeURIComponent(escape(atob(rawData)));
                        const data = JSON.parse(jsonStr);
                        modalTitle.innerHTML = `<span style="color:#800080;">${data.party_name}</span> &mdash; <span style="color:#0d47a1;">${data.product_name}</span>`;

                        let html = `<table style="width:100%; border-collapse:collapse; border:1px solid #000; margin-top:10px;">
                            <thead>
                                <tr style="background:#e0e0e0;">
                                    <th style="border:1px solid #000; padding:6px; text-align:center;">Date</th>
                                    <th style="border:1px solid #000; padding:6px; text-align:center;">Ref Type</th>
                                    <th style="border:1px solid #000; padding:6px; text-align:center;">Ref #</th>
                                    <th style="border:1px solid #000; padding:6px; text-align:right;">Hold</th>
                                    <th style="border:1px solid #000; padding:6px; text-align:right;">Release</th>
                                    <th style="border:1px solid #000; padding:6px; text-align:right;">Running Balance</th>
                                </tr>
                            </thead>
                            <tbody>`;

                        let runningBalance = parseFloat(data.opening || 0);

                        if (Math.abs(runningBalance) > 0.0001) {
                            html += `<tr style="background:#f9f9f9; font-weight:bold;">
                                <td colspan="3" style="border:1px solid #000; padding:6px; text-align:right;">Opening Balance:</td>
                                <td style="border:1px solid #000; padding:6px; text-align:right;">-</td>
                                <td style="border:1px solid #000; padding:6px; text-align:right;">-</td>
                                <td style="border:1px solid #000; padding:6px; text-align:right;">${Math.round(runningBalance)}</td>
                            </tr>`;
                        }

                        if (data.entries && data.entries.length > 0) {
                            let totalHold = 0;
                            let totalRel = 0;

                            data.entries.forEach(function(entry) {
                                const isHold = entry.kind === 'hold';
                                const holdQty = isHold ? parseFloat(entry.qty || 0) : 0;
                                const relQty = !isHold ? parseFloat(entry.qty || 0) : 0;

                                totalHold += holdQty;
                                totalRel += relQty;
                                runningBalance += (holdQty - relQty);

                                html += `<tr>
                                    <td style="border:1px solid #000; padding:6px; text-align:center;">${entry.date}</td>
                                    <td style="border:1px solid #000; padding:6px; text-align:center;">${entry.ref_type}</td>
                                    <td style="border:1px solid #000; padding:6px; text-align:center;">${entry.ref_no}</td>
                                    <td style="border:1px solid #000; padding:6px; text-align:right;">${holdQty > 0 ? Math.round(holdQty) : ''}</td>
                                    <td style="border:1px solid #000; padding:6px; text-align:right;">${relQty > 0 ? Math.round(relQty) : ''}</td>
                                    <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">${Math.round(runningBalance)}</td>
                                </tr>`;
                            });

                            html += `<tr style="background:#e0e0e0; font-weight:bold;">
                                <td colspan="3" style="border:1px solid #000; padding:6px; text-align:right;">Total:</td>
                                <td style="border:1px solid #000; padding:6px; text-align:right;">${totalHold > 0 ? Math.round(totalHold) : ''}</td>
                                <td style="border:1px solid #000; padding:6px; text-align:right;">${totalRel > 0 ? Math.round(totalRel) : ''}</td>
                                <td style="border:1px solid #000; padding:6px; text-align:right;">${Math.round(runningBalance)}</td>
                            </tr>`;
                        } else {
                            html += `<tr>
                                <td colspan="6" style="border:1px solid #000; padding:15px; text-align:center; color:#666;">No transaction entries recorded in this period.</td>
                            </tr>`;
                        }

                        html += `</tbody></table>`;
                        modalContent.innerHTML = html;
                        modal.style.display = 'block';
                    } catch (err) {
                        console.error('Error parsing details:', err);
                    }
                });
            });
        });
    </script>
</body>
</html>
