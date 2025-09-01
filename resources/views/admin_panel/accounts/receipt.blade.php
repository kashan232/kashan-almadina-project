<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt Voucher #{{ $voucher->id }}</title>
<style>
body { font-family: Arial, sans-serif; font-size: 10pt; margin:20px; color:#000; }
.challan-container { border:1px solid #000; padding:10px 15px; max-width:800px; margin:auto; }
.header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
.company-info h1 { font-size:20pt; font-weight:bold; margin:0; }
.company-info .contact-details { font-size:8pt; margin-top:2px; }
.logo-section { text-align:right; }
.logo { width:120px; height:auto; margin-bottom:5px; }
.challan-box { border:1px solid #000; padding:4px 15px; text-align:center; font-weight:bold; font-size:10pt; position:relative; }
.challan-box .estimate { font-size:8pt; font-weight:normal; display:block; margin-top:2px; }
.info-row { display:flex; justify-content:space-between; margin-bottom:5px; font-size:9pt; }
.customer-info { line-height:1.5; }
.invoice-info { border-left:1px solid #000; padding-left:15px; }
.line { border-top:1px solid #000; margin:10px 0; }
table { width:100%; border-collapse:collapse; margin-bottom:5px; }
th, td { border:1px solid #000; padding:5px; font-size:9pt; text-align:left; }
th { background:#f2f2f2; font-weight:bold; }
.text-right { text-align:right; }
.summary-section { display:flex; justify-content:space-between; align-items:flex-start; }
.quantity-total { font-weight:bold; font-size:9pt; margin-top:5px; }
.summary-totals { width:50%; margin-left:auto; line-height:1.5; font-size:9pt; padding-top:10px; }
.summary-line { display:flex; justify-content:space-between; }
.signature-area { margin-top:30px; position:relative; width:250px; }
.signature-line { border-top:1px solid #000; margin-bottom:5px; }
.amount-in-words { margin-top:10px; font-size:9pt; line-height:1.4; }
</style>
</head>
<body>

<div class="challan-container">
  <div class="header">
    <div class="company-info">
      <h1>Al-Madina Traders</h1>
      <div class="contact-details">
        Shop# 2, United Hotel, Qazi Qayoom Road, Hyderabad.<br>
        Mob / Whatsapp: 0312-0252899, Tel: 022-2780942
      </div>
    </div>
    <div class="logo-section">
      <img src="https://i.imgur.com/BL8PyRT.png" alt="AMT Logo" class="logo">
      <div class="challan-box">
        Receipt Voucher
      </div>
    </div>
  </div>

  <div class="line"></div>

  <div class="info-row">
   <div class="customer-info">
<span style="font-weight:bold;">Customer:</span> {{ $customerName }}<br>
<span style="font-weight:bold;">Type:</span> {{ $voucher->type }}<br>
<span style="font-weight:bold;">Address:</span> {{ $customerAddress }}

</div>

<div class="summary-totals">
    <div class="summary-line">
        <div>AMOUNT TOTAL:</div>
        <div class="text-right">{{ number_format($voucher->amount, 2) }}</div>
    </div>
    <div class="summary-line">
        <div>Previous Balance:</div>
        <div class="text-right">{{ number_format($closingBalance, 2) }}</div>
    </div>
    <div class="summary-line" style="margin-top:10px; font-weight:bold;">
        <div>AMOUNT PAYABLE:</div>
        <div class="text-right">{{ number_format($voucher->amount + $closingBalance, 2) }}</div>
    </div>
</div>

  </div>

  <table>
    <thead>
      <tr>
        <th style="width:5%;">S No.</th>
        <th style="width:70%;">Narration</th>
        <th style="width:25%;" class="text-right">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="text-align:center;">1</td>
        <td>{{ $voucher->narration }}</td>
        <td class="text-right">{{ number_format($voucher->amount, 2) }}</td>
      </tr>
    </tbody>
  </table>

  <div class="summary-section">
    <div class="quantity-total">
      Total Items: 1
    </div>
    <div class="summary-totals">
      <div class="summary-line">
        <div>AMOUNT TOTAL:</div>
        <div class="text-right">{{ number_format($voucher->amount, 2) }}</div>
      </div>
      <div class="summary-line">
        <div>Previous Balance:</div>
        <div class="text-right">{{ number_format($voucher->customer->closing_balance ?? 0, 2) }}</div>
      </div>
      <div class="summary-line" style="margin-top:10px; font-weight:bold;">
        <div>AMOUNT PAYABLE:</div>
        <div class="text-right">{{ number_format($voucher->amount + ($voucher->customer->closing_balance ?? 0), 2) }}</div>
      </div>
    </div>
  </div>

  <div class="signature-area">
    <div class="signature-line"></div>
    Authorized Signature
  </div>


</div>

</body>
</html>
