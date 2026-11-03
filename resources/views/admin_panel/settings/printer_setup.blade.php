@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <!-- Breadcrumbs / Title -->
            <div class="d-flex justify-content-between align-items-center mb-4 pt-2">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">
                        <i class="fa fa-print text-primary me-2"></i> 1-Click Silent Printer Setup
                    </h3>
                    <p class="text-muted small mb-0">
                        Map each module (Invoices, Challans, Vouchers, Ledgers) to its dedicated printer for 1-click instant printing without Chrome dialogs.
                    </p>
                </div>
                <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <!-- QZ Tray Status Banner -->
            <div class="card shadow-sm border-0 mb-4" id="qzStatusCard" style="border-left: 5px solid #6c757d !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div id="qzStatusIcon" style="font-size: 28px; color: #6c757d;">
                                <i class="fa fa-circle-o-notch fa-spin"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold" id="qzStatusTitle">Checking QZ Tray Status...</h6>
                                <p class="mb-0 small text-muted" id="qzStatusDesc">Connecting to local print service on this computer...</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefreshPrinters" onclick="loadPrintersFromQZ()">
                                <i class="fa fa-refresh me-1"></i> Detect Printers
                            </button>
                            <a href="https://qz.io/download/" target="_blank" class="btn btn-sm btn-outline-dark" id="btnDownloadQZ">
                                <i class="fa fa-download me-1"></i> Download QZ Tray (Free)
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div id="autoDetectedAlert" class="alert alert-info d-none shadow-sm mb-4">
                <i class="fa fa-check-circle me-2 text-success fs-5 align-middle"></i>
                <span id="autoDetectedText">Printers detected from QZ Tray! We have pre-selected recommended printers below. Click <strong>"Save Printer Settings"</strong> to activate 1-Click Silent Printing.</span>
            </div>

            <!-- Setup Form -->
            <form id="printerSetupForm" onsubmit="savePrinterSetup(event)">
                @csrf
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">Module-Wise Printer Mapping</h5>
                            <span class="small text-muted">Select which physical printer prints which document.</span>
                        </div>
                        <div class="form-check form-switch d-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox" id="silent_enabled" name="silent_enabled" value="1" 
                                {{ !empty($savedSettings['silent_enabled']) && $savedSettings['silent_enabled'] == '1' ? 'checked' : '' }}
                                style="width: 44px; height: 22px; cursor: pointer;">
                            <label class="form-check-label fw-bold text-success fs-6" for="silent_enabled" style="cursor: pointer;">
                                Enable 1-Click Silent Printing
                            </label>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%;">Module / Document</th>
                                        <th style="width: 20%;">Target Paper Format</th>
                                        <th style="width: 35%;">Assigned Printer</th>
                                        <th style="width: 20%; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($defaultModules as $modKey => $mod)
                                    @php
                                        $savedPrinter = $savedSettings['modules'][$modKey]['printer'] ?? '';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $mod['label'] }}</div>
                                            <small class="text-muted">{{ $mod['description'] }}</small>
                                        </td>
                                        <td>
                                            @if($mod['default_paper'] === 'thermal_80')
                                                <span class="badge bg-dark px-2 py-1"><i class="fa fa-ticket me-1"></i> 80mm Thermal Roll</span>
                                            @elseif($mod['default_paper'] === 'a5')
                                                <span class="badge bg-primary px-2 py-1"><i class="fa fa-file-text-o me-1"></i> A5 Portrait</span>
                                            @elseif($mod['default_orientation'] === 'landscape')
                                                <span class="badge bg-success px-2 py-1"><i class="fa fa-file-o me-1"></i> A4 Landscape</span>
                                            @else
                                                <span class="badge bg-info px-2 py-1"><i class="fa fa-file-o me-1"></i> A4 Portrait</span>
                                            @endif
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm printer-dropdown" 
                                                    id="printer_{{ $modKey }}" 
                                                    name="settings[{{ $modKey }}][printer]" 
                                                    data-saved="{{ $savedPrinter }}"
                                                    data-paper="{{ $mod['default_paper'] }}">
                                                <option value="">-- Use Browser Default / Window Print --</option>
                                                @if($savedPrinter)
                                                    <option value="{{ $savedPrinter }}" selected>{{ $savedPrinter }} (Saved)</option>
                                                @endif
                                            </select>
                                            <input type="hidden" name="settings[{{ $modKey }}][paper]" value="{{ $mod['default_paper'] }}">
                                            <input type="hidden" name="settings[{{ $modKey }}][orientation]" value="{{ $mod['default_orientation'] }}">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    onclick="triggerTestPrint('{{ $modKey }}', '{{ $mod['default_paper'] }}')">
                                                <i class="fa fa-print me-1"></i> Test Print
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fa fa-info-circle me-1 text-primary"></i> 
                            Settings are saved per workstation browser and synced with the database.
                        </span>
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnSaveSettings">
                            <i class="fa fa-save me-1"></i> Save Printer Settings
                        </button>
                    </div>
                </div>
            </form>

            <!-- Guide Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa fa-question-circle text-info me-2"></i> How 1-Click Silent Printing Works</h5>
                    <ol class="mb-0 text-muted ps-3 lh-lg" style="font-size: 13.5px;">
                        <li><strong>Step 1:</strong> Keep <strong>QZ Tray</strong> running in your Windows system tray (bottom-right next to the clock).</li>
                        <li><strong>Step 2:</strong> In the table above, select your <strong>A5 Printer (e.g. Black Copper / HP)</strong> for Invoices and Vouchers.</li>
                        <li><strong>Step 3:</strong> Select your <strong>POS-80 Thermal Printer</strong> for Delivery Challans.</li>
                        <li><strong>Step 4:</strong> Make sure the switch <strong>"Enable 1-Click Silent Printing"</strong> is turned <strong>ON</strong>.</li>
                        <li><strong>Step 5:</strong> Click <strong>"Save Printer Settings"</strong>.</li>
                        <li><strong>Done!</strong> Now whenever you click Print on an Invoice or DC, it prints silently without any dialogs!</li>
                    </ol>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- QZ Tray JavaScript Library & Service -->
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.min.js"></script>
<script src="{{ asset('js/qz-print-service.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        checkQzConnection();
    });

    function checkQzConnection() {
        var card = document.getElementById("qzStatusCard");
        var icon = document.getElementById("qzStatusIcon");
        var title = document.getElementById("qzStatusTitle");
        var desc = document.getElementById("qzStatusDesc");

        QzPrintService.connect().then(function() {
            card.style.borderLeft = "5px solid #2e7d32 !important";
            icon.style.color = "#2e7d32";
            icon.innerHTML = '<i class="fa fa-check-circle"></i>';
            title.innerText = "QZ Tray Connected & Ready!";
            title.style.color = "#2e7d32";
            desc.innerText = "Local print service is active. Printers can be mapped for 1-click silent direct printing.";
            loadPrintersFromQZ();
        }).catch(function(err) {
            card.style.borderLeft = "5px solid #d32f2f !important";
            icon.style.color = "#d32f2f";
            icon.innerHTML = '<i class="fa fa-exclamation-triangle"></i>';
            title.innerText = "QZ Tray Not Detected on this PC";
            title.style.color = "#d32f2f";
            desc.innerText = "Please make sure QZ Tray is running in your Windows taskbar. (Click Detect Printers once running).";
        });
    }

    function loadPrintersFromQZ() {
        var btn = document.getElementById("btnRefreshPrinters");
        if (btn) btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Detecting...';

        QzPrintService.getPrinters().then(function(printers) {
            if (btn) btn.innerHTML = '<i class="fa fa-refresh me-1"></i> Detect Printers';

            var laserPrinter = '';
            var thermalPrinter = '';

            printers.forEach(function(p) {
                var pLow = p.toLowerCase();
                if (!laserPrinter && (pLow.includes('copper') || pLow.includes('hp') || pLow.includes('laser') || pLow.includes('m23') || pLow.includes('canon') || pLow.includes('epson') || pLow.includes('brother'))) {
                    laserPrinter = p;
                }
                if (!thermalPrinter && (pLow.includes('pos') || pLow.includes('thermal') || pLow.includes('xp') || pLow.includes('rp') || pLow.includes('receipt') || pLow.includes('80') || pLow.includes('58'))) {
                    thermalPrinter = p;
                }
            });

            if (!laserPrinter && printers.length > 0) laserPrinter = printers[0];
            if (!thermalPrinter) thermalPrinter = laserPrinter;

            var hasAnySaved = false;

            document.querySelectorAll(".printer-dropdown").forEach(function(select) {
                var savedVal = select.getAttribute("data-saved") || select.value;
                var paperType = select.getAttribute("data-paper");
                select.innerHTML = '<option value="">-- Use Browser Default / Window Print --</option>';

                var foundSaved = false;
                printers.forEach(function(pName) {
                    var opt = document.createElement("option");
                    opt.value = pName;
                    opt.innerText = pName;
                    if (pName === savedVal) {
                        opt.selected = true;
                        foundSaved = true;
                        hasAnySaved = true;
                    }
                    select.appendChild(opt);
                });

                // Auto-select smart default if no saved value
                if (!foundSaved && !savedVal) {
                    if (paperType === 'thermal_80' && thermalPrinter) {
                        select.value = thermalPrinter;
                    } else if (laserPrinter) {
                        select.value = laserPrinter;
                    }
                }
            });

            // If no settings were saved yet, auto-check the silent switch and show banner
            var silentSwitch = document.getElementById("silent_enabled");
            if (!hasAnySaved && !silentSwitch.checked) {
                silentSwitch.checked = true;
                var alertBox = document.getElementById("autoDetectedAlert");
                if (alertBox) {
                    alertBox.classList.remove("d-none");
                    document.getElementById("autoDetectedText").innerHTML = 
                        `Printers detected! We pre-selected <strong>${escapeHtml(laserPrinter)}</strong> for invoices and <strong>${escapeHtml(thermalPrinter)}</strong> for thermal slips. Please click <strong>"Save Printer Settings"</strong> below to activate!`;
                }
            }

        }).catch(function(err) {
            if (btn) btn.innerHTML = '<i class="fa fa-refresh me-1"></i> Detect Printers';
            console.error(err);
            alert("Could not detect printers: " + (err.message || err) + "\n\nPlease ensure QZ Tray is running in the Windows taskbar and click Detect Printers.");
        });
    }

    function triggerTestPrint(modKey, paperType) {
        var select = document.getElementById("printer_" + modKey);
        var printerName = select ? select.value : "";
        if (!printerName) {
            alert("Please select a printer for this module first.");
            return;
        }

        QzPrintService.testPrint(printerName, paperType).then(function() {
            alert("Test slip sent successfully to " + printerName + "! Check your printer.");
        }).catch(function(err) {
            alert("Test print failed: " + (err.message || err));
        });
    }

    function savePrinterSetup(e) {
        e.preventDefault();
        var form = document.getElementById("printerSetupForm");

        var dataObj = {
            silent_enabled: document.getElementById("silent_enabled").checked ? '1' : '0',
            modules: {}
        };

        document.querySelectorAll(".printer-dropdown").forEach(function(select) {
            var mKey = select.id.replace("printer_", "");
            var printer = select.value;
            var paper = form.querySelector('input[name="settings[' + mKey + '][paper]"]')?.value || 'a5';
            var orient = form.querySelector('input[name="settings[' + mKey + '][orientation]"]')?.value || 'portrait';

            dataObj.modules[mKey] = {
                printer: printer,
                paper: paper,
                orientation: orient
            };
        });

        var btn = document.getElementById("btnSaveSettings");
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Saving...';

        QzPrintService.saveSettings(dataObj).then(function(res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-save me-1"></i> Save Printer Settings';
            
            var alertBox = document.getElementById("autoDetectedAlert");
            if (alertBox) alertBox.classList.add("d-none");

            document.querySelectorAll(".printer-dropdown").forEach(function(select) {
                select.setAttribute("data-saved", select.value);
            });

            alert("✅ Printer settings saved successfully!\n\n1-Click Silent Printing is now ACTIVE. Open any Invoice or Delivery Challan to print directly without dialogs!");
        }).catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-save me-1"></i> Save Printer Settings';
            alert("Saved locally in this browser. (" + (err.message || err) + ")");
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }
</script>
@endsection
