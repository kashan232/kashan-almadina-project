/**
 * QZ Tray Silent Printing Service for Al-Madina Traders
 * Communicates with local QZ Tray application for 1-click silent direct printing.
 */

const QzPrintService = (function() {
    let isConnected = false;
    let connectPromise = null;
    let localSettings = null;

    // Default module paper configurations
    const MODULE_CONFIGS = {
        'sale_invoice': { paper: 'a5', orientation: 'portrait', width: 148, height: 210 },
        'delivery_challan': { paper: 'thermal_80', orientation: 'portrait', width: 80, height: 0 },
        'stock_release': { paper: 'thermal_80', orientation: 'portrait', width: 80, height: 0 },
        'stock_transfer': { paper: 'a5', orientation: 'portrait', width: 148, height: 210 },
        'stock_transfer_thermal': { paper: 'thermal_80', orientation: 'portrait', width: 80, height: 0 },
        'voucher': { paper: 'a5', orientation: 'portrait', width: 148, height: 210 },
        'claim_acceptance': { paper: 'a5', orientation: 'portrait', width: 148, height: 210 },
        'customer_outstanding_summary': { paper: 'a5', orientation: 'portrait', width: 148, height: 210 },
        'customer_outstanding_detail': { paper: 'a4', orientation: 'landscape', width: 297, height: 210 },
        'general_ledger': { paper: 'a4', orientation: 'portrait', width: 210, height: 297 },
        'stock_reports': { paper: 'a5', orientation: 'portrait', width: 148, height: 210 }
    };

    /**
     * Load settings from localStorage with backend fallback
     */
    function loadSettings(forceRefresh = false) {
        if (!forceRefresh && localSettings) {
            return Promise.resolve(localSettings);
        }

        try {
            const saved = localStorage.getItem('amt_printer_settings');
            if (saved && !forceRefresh) {
                localSettings = JSON.parse(saved);
                return Promise.resolve(localSettings);
            }
        } catch (e) {}

        return fetch('/api/printer-settings/get')
            .then(res => res.json())
            .then(data => {
                localSettings = (data && typeof data === 'object') ? data : { silent_enabled: '0', modules: {} };
                try {
                    localStorage.setItem('amt_printer_settings', JSON.stringify(localSettings));
                } catch (e) {}
                return localSettings;
            })
            .catch(() => {
                localSettings = { silent_enabled: '0', modules: {} };
                return localSettings;
            });
    }

    /**
     * Save settings to localStorage and backend
     */
    function saveSettings(settingsData) {
        localSettings = settingsData;
        try {
            localStorage.setItem('amt_printer_settings', JSON.stringify(settingsData));
        } catch (e) {}

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        return fetch('/settings/printer-setup/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || ''
            },
            body: JSON.stringify(settingsData)
        }).then(res => res.json());
    }

    /**
     * Setup QZ Security Certificates and Signatures
     */
    function setupSecurity() {
        if (typeof qz === 'undefined') return;

        qz.security.setCertificatePromise(function(resolve, reject) {
            fetch('/api/qz/certificate')
                .then(r => r.text())
                .then(resolve)
                .catch(reject);
        });

        qz.security.setSignatureAlgorithm("SHA512");
        qz.security.setSignaturePromise(function(toSign) {
            return function(resolve, reject) {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('/api/qz/sign', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    },
                    body: JSON.stringify({ request: toSign })
                })
                .then(r => r.text())
                .then(resolve)
                .catch(reject);
            };
        });
    }

    /**
     * Connect to QZ Tray WebSocket (Safe Singleton)
     */
    function connect() {
        if (typeof qz === 'undefined') {
            return Promise.reject(new Error("QZ Tray script not loaded on page."));
        }

        if (qz.websocket.isActive()) {
            isConnected = true;
            return Promise.resolve();
        }

        if (connectPromise) {
            return connectPromise;
        }

        setupSecurity();

        connectPromise = qz.websocket.connect({
            retries: 3,
            delay: 1
        }).then(() => {
            isConnected = true;
            connectPromise = null;
            return true;
        }).catch(err => {
            isConnected = false;
            connectPromise = null;
            throw err;
        });

        return connectPromise;
    }

    /**
     * Get all installed printers on the client PC safely
     */
    function getPrinters() {
        return connect().then(() => {
            return new Promise((resolve, reject) => {
                // Ensure socket handshake is completely settled
                setTimeout(() => {
                    if (typeof qz === 'undefined' || !qz.websocket.isActive()) {
                        return reject(new Error("QZ Tray connection is not active."));
                    }
                    qz.printers.find()
                        .then(printers => resolve(printers || []))
                        .catch(reject);
                }, 150);
            });
        });
    }

    /**
     * Check if silent printing is enabled in settings
     */
    function isSilentEnabled() {
        if (!localSettings) return false;
        return localSettings.silent_enabled === '1' || localSettings.silent_enabled === true;
    }

    /**
     * Get assigned printer for a specific module
     */
    function getModulePrinter(moduleKey) {
        if (!localSettings || !localSettings.modules) return null;
        return localSettings.modules[moduleKey]?.printer || null;
    }

    /**
     * Core silent print function for any module
     */
    function printModule(moduleKey, elementOrSelector, fallbackFn) {
        return loadSettings().then(() => {
            // If silent printing is not enabled, fallback to standard window.print()
            if (!isSilentEnabled()) {
                console.log('[QZ Service] 1-Click Silent printing is OFF. Opening browser print dialog.');
                if (typeof fallbackFn === 'function') fallbackFn();
                return;
            }

            const printerName = getModulePrinter(moduleKey);
            if (!printerName) {
                console.log('[QZ Service] No printer mapped for module "' + moduleKey + '". Opening browser print dialog.');
                showPrintToast(`No printer mapped for this module. Opening print dialog...`, 'warning');
                if (typeof fallbackFn === 'function') fallbackFn();
                return;
            }

            // Get target element
            let element = null;
            if (typeof elementOrSelector === 'string') {
                element = document.querySelector(elementOrSelector);
            } else if (elementOrSelector instanceof HTMLElement) {
                element = elementOrSelector;
            }

            if (!element) {
                if (typeof fallbackFn === 'function') fallbackFn();
                return;
            }

            // Show printing indicator
            showPrintToast(`Printing silently to <b>${escapeHtml(printerName)}</b>...`, 'info');

            return connect().then(() => {
                const modCfg = MODULE_CONFIGS[moduleKey] || { paper: 'a5', orientation: 'portrait', width: 148, height: 210 };
                
                let printConfigOpts = {
                    units: 'mm',
                    orientation: modCfg.orientation || 'portrait',
                    margins: 0,
                    scaleContent: true
                };

                if (modCfg.paper === 'thermal_80' || modCfg.width === 80) {
                    printConfigOpts.size = { width: 80 }; // continuous roll
                } else if (modCfg.width && modCfg.height) {
                    printConfigOpts.size = { width: modCfg.width, height: modCfg.height };
                }

                const config = qz.configs.create(printerName, printConfigOpts);

                // Collect page styles
                let stylesHtml = '';
                document.querySelectorAll('style, link[rel="stylesheet"]').forEach(el => {
                    stylesHtml += el.outerHTML;
                });

                const printData = [{
                    type: 'pixel',
                    format: 'html',
                    flavor: 'plain',
                    data: `<!DOCTYPE html><html><head><meta charset="UTF-8"><base href="${window.location.origin}/">${stylesHtml}<style>body{background:#fff!important;margin:0!important;padding:0!important;}.invoice-sheet,.dc-sheet{margin:0!important;box-shadow:none!important;border:none!important;}.no-print-toolbar,.no-print{display:none!important;}</style></head><body>${element.outerHTML}</body></html>`
                }];

                return qz.print(config, printData).then(() => {
                    showPrintToast(`Document printed successfully to <b>${escapeHtml(printerName)}</b>!`, 'success');
                });
            }).catch(err => {
                console.warn("QZ Tray Print failed, falling back to browser print dialog:", err);
                showPrintToast(`Silent print: ${err.message || err}. Opening print dialog...`, 'warning');
                if (typeof fallbackFn === 'function') {
                    fallbackFn();
                }
            });
        });
    }

    /**
     * Test print slip on a specific printer
     */
    function testPrint(printerName, paperType) {
        return connect().then(() => {
            const isThermal = (paperType === 'thermal_80');
            const configOpts = isThermal 
                ? { size: { width: 80 }, units: 'mm', margins: 0 }
                : { size: { width: 148, height: 210 }, units: 'mm', orientation: 'portrait', margins: 5 };

            const config = qz.configs.create(printerName, configOpts);

            const testHtml = `
                <div style="font-family:Arial,sans-serif; text-align:center; padding:10px; width:${isThermal ? '72mm' : '135mm'};">
                    <h2 style="margin:0; font-size:16px;">AL-MADINA TRADERS</h2>
                    <p style="margin:2px 0; font-size:10px;">Printer Connection Test</p>
                    <hr style="border:1px dashed #000; margin:8px 0;">
                    <div style="text-align:left; font-size:10px; line-height:1.5;">
                        <b>Printer:</b> ${escapeHtml(printerName)}<br>
                        <b>Paper Format:</b> ${isThermal ? '80mm Thermal Roll' : 'A5 Sheet'}<br>
                        <b>Date & Time:</b> ${new Date().toLocaleString()}<br>
                        <b>Status:</b> 1-Click Silent Printing OK!
                    </div>
                    <hr style="border:1px dashed #000; margin:8px 0;">
                    <p style="margin:4px 0; font-size:9px; font-weight:bold;">Test Print Successful &bull; QZ Tray Ready</p>
                </div>
            `;

            const printData = [{
                type: 'pixel',
                format: 'html',
                flavor: 'plain',
                data: `<!DOCTYPE html><html><body>${testHtml}</body></html>`
            }];

            return qz.print(config, printData);
        });
    }

    /**
     * Update status badge in print toolbars
     */
    function updateToolbarBadge(moduleKey) {
        const badge = document.getElementById('qzPrintBadge');
        if (!badge) return;

        loadSettings().then(() => {
            const silent = isSilentEnabled();
            const printer = getModulePrinter(moduleKey);

            if (silent && printer) {
                badge.className = 'badge bg-success';
                badge.innerHTML = `<i class="fa fa-bolt me-1"></i> Silent Print: <b>${escapeHtml(printer)}</b>`;
                badge.title = `1-Click Direct Print is active for this document. Target: ${printer}`;
            } else if (silent && !printer) {
                badge.className = 'badge bg-warning text-dark';
                badge.innerHTML = `<a href="/settings/printer-setup" target="_blank" style="color:inherit; text-decoration:none;"><i class="fa fa-exclamation-triangle me-1"></i> Select Printer in Setup</a>`;
            } else {
                badge.className = 'badge bg-secondary text-white';
                badge.innerHTML = `<a href="/settings/printer-setup" target="_blank" style="color:#fff; text-decoration:none;"><i class="fa fa-cog me-1"></i> Silent Print: Off (Setup)</a>`;
                badge.title = 'Click to configure 1-Click Silent Printing in Printer Settings';
            }
        });
    }

    function showPrintToast(msg, type = 'info') {
        let toast = document.getElementById('qzPrintToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'qzPrintToast';
            toast.style.cssText = 'position:fixed; bottom:20px; right:20px; z-index:999999; padding:12px 20px; border-radius:6px; font-size:13px; font-family:Arial,sans-serif; box-shadow:0 4px 15px rgba(0,0,0,0.3); transition:opacity 0.3s ease; display:flex; align-items:center; gap:8px;';
            document.body.appendChild(toast);
        }

        const bg = (type === 'success') ? '#2e7d32' : (type === 'warning' ? '#ed6c02' : '#0288d1');
        toast.style.background = bg;
        toast.style.color = '#fff';
        toast.innerHTML = `<i class="fa ${type === 'success' ? 'fa-check-circle' : (type === 'warning' ? 'fa-exclamation-triangle' : 'fa-print')}"></i> <span>${msg}</span>`;
        toast.style.opacity = '1';

        setTimeout(() => {
            if (toast) toast.style.opacity = '0';
        }, 4000);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    // Auto-init settings on load
    loadSettings();

    return {
        connect,
        getPrinters,
        loadSettings,
        saveSettings,
        isSilentEnabled,
        getModulePrinter,
        printModule,
        testPrint,
        updateToolbarBadge
    };
})();

// Global helper for simple one-line calls in template onclicks
function printWithQzOrBrowser(moduleKey, selector, fallbackFn) {
    if (typeof QzPrintService !== 'undefined') {
        QzPrintService.printModule(moduleKey, selector, fallbackFn || function() { window.print(); });
    } else if (typeof fallbackFn === 'function') {
        fallbackFn();
    } else {
        window.print();
    }
}
