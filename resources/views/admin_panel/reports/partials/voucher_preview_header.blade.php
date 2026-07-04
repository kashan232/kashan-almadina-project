<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 25px; background: #0d47a1; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">Print Report</button>
</div>

<div style="text-align:center; margin-bottom: 8px;">
    <x-amt-logo width="120px" style="margin: 0 auto;" />
</div>

<div class="company-name">Al-Madina Traders</div>

<div class="report-header">
    <div class="generated-date">{{ now()->format('l, F d, Y') }}</div>
    <h1 class="report-title">{{ $reportTitle ?? 'Voucher Report' }}</h1>
    @if(!empty($showReceiptDates))
    <div class="date-range">
        Receipt From: <span>{{ ($receipt_from ?? null) ? \Carbon\Carbon::parse($receipt_from)->format('d-m-y') : '-' }}</span>
        To: <span>{{ ($receipt_to ?? null) ? \Carbon\Carbon::parse($receipt_to)->format('d-m-y') : '-' }}</span>
    </div>
    @endif
    <div class="date-range">
        Entry From: <span>{{ ($entry_from ?? null) ? \Carbon\Carbon::parse($entry_from)->format('d-m-y') : '-' }}</span>
        To: <span>{{ ($entry_to ?? null) ? \Carbon\Carbon::parse($entry_to)->format('d-m-y') : '-' }}</span>
    </div>
</div>
