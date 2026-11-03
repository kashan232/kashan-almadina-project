<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

class PrinterSettingController extends Controller
{
    private $keyPath;
    private $certPath;

    public function __construct()
    {
        $this->keyPath = storage_path('app/qz/private.key');
        $this->certPath = storage_path('app/qz/digital-certificate.txt');
        $this->ensureKeysExist();
    }

    /**
     * Display Printer Setup Page
     */
    public function index()
    {
        $savedSettings = json_decode(SystemSetting::get('printer_settings', '{}'), true) ?: [];

        $defaultModules = [
            'sale_invoice' => [
                'label' => 'Sale Invoice',
                'default_paper' => 'a5',
                'default_orientation' => 'portrait',
                'description' => 'Prints customer sale invoices'
            ],
            'delivery_challan' => [
                'label' => 'Delivery Challan (DC)',
                'default_paper' => 'thermal_80',
                'default_orientation' => 'portrait',
                'description' => 'Prints thermal dispatch receipt'
            ],
            'stock_release' => [
                'label' => 'Stock Release Slip',
                'default_paper' => 'thermal_80',
                'default_orientation' => 'portrait',
                'description' => 'Prints stock release dispatch'
            ],
            'stock_transfer' => [
                'label' => 'Stock Transfer (A5)',
                'default_paper' => 'a5',
                'default_orientation' => 'portrait',
                'description' => 'Prints transfer orders in A5'
            ],
            'stock_transfer_thermal' => [
                'label' => 'Stock Transfer (Thermal)',
                'default_paper' => 'thermal_80',
                'default_orientation' => 'portrait',
                'description' => 'Prints transfer orders in 80mm'
            ],
            'voucher' => [
                'label' => 'Receipt / Payment Vouchers',
                'default_paper' => 'a5',
                'default_orientation' => 'portrait',
                'description' => 'Prints cash/bank vouchers'
            ],
            'claim_acceptance' => [
                'label' => 'Claim Acceptance Voucher',
                'default_paper' => 'a5',
                'default_orientation' => 'portrait',
                'description' => 'Prints battery warranty claim vouchers'
            ],
            'customer_outstanding_summary' => [
                'label' => 'Customer Outstanding (Summary)',
                'default_paper' => 'a5',
                'default_orientation' => 'portrait',
                'description' => 'Prints party outstanding balances'
            ],
            'customer_outstanding_detail' => [
                'label' => 'Customer Outstanding (Detailed)',
                'default_paper' => 'a4',
                'default_orientation' => 'landscape',
                'description' => 'Prints complete multi-column statement'
            ],
            'general_ledger' => [
                'label' => 'General Ledger & Reports',
                'default_paper' => 'a4',
                'default_orientation' => 'portrait',
                'description' => 'Prints party & account ledgers'
            ],
            'stock_reports' => [
                'label' => 'Stock & Hold/Release Reports',
                'default_paper' => 'a5',
                'default_orientation' => 'portrait',
                'description' => 'Prints stock reports in A5'
            ],
        ];

        return view('admin_panel.settings.printer_setup', compact('savedSettings', 'defaultModules'));
    }

    /**
     * Save Printer Setup Settings
     */
    public function save(Request $request)
    {
        $settings = $request->input('modules', $request->input('settings', []));
        $silentRaw = $request->input('silent_enabled');
        $silentEnabled = in_array($silentRaw, ['1', 1, true, 'true', 'on'], true) ? '1' : '0';

        $data = [
            'silent_enabled' => $silentEnabled,
            'modules' => $settings,
            'updated_at' => now()->toDateTimeString()
        ];

        SystemSetting::set('printer_settings', json_encode($data));

        return response()->json([
            'status' => 'success',
            'message' => 'Printer settings saved successfully!',
            'data' => $data
        ]);
    }

    /**
     * Get Saved Printer Settings for Client-Side JS
     */
    public function getSettings()
    {
        $raw = SystemSetting::get('printer_settings', '{}');
        $data = json_decode($raw, true) ?: [];
        return response()->json($data);
    }

    /**
     * Serve QZ Tray Digital Certificate
     */
    public function certificate()
    {
        $this->ensureKeysExist();
        return response(file_get_contents($this->certPath), 200, [
            'Content-Type' => 'text/plain'
        ]);
    }

    /**
     * Sign QZ Tray Print Request using Private Key
     */
    public function sign(Request $request)
    {
        $toSign = $request->input('request');
        if (!$toSign) {
            return response('No data to sign', 400);
        }

        $this->ensureKeysExist();
        $privateKey = openssl_pkey_get_private(file_get_contents($this->keyPath));

        $signature = '';
        // QZ Tray uses SHA512 by default
        openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA512);

        return response(base64_encode($signature), 200, [
            'Content-Type' => 'text/plain'
        ]);
    }

    /**
     * Generate self-signed RSA cert and private key if not present
     */
    private function ensureKeysExist()
    {
        $dir = dirname($this->keyPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->keyPath) || !file_exists($this->certPath)) {
            $dn = [
                "countryName" => "PK",
                "stateOrProvinceName" => "Sindh",
                "localityName" => "Hyderabad",
                "organizationName" => "Al-Madina Traders",
                "organizationalUnitName" => "POS Systems",
                "commonName" => "amthyd.com",
                "emailAddress" => "support@amthyd.com"
            ];

            $configArgs = [
                'digest_alg' => 'sha512',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];

            if (file_exists('C:/xampp/apache/conf/openssl.cnf')) {
                $configArgs['config'] = 'C:/xampp/apache/conf/openssl.cnf';
            } elseif (file_exists('C:/xampp/php/extras/ssl/openssl.cnf')) {
                $configArgs['config'] = 'C:/xampp/php/extras/ssl/openssl.cnf';
            }

            $privkey = openssl_pkey_new($configArgs);
            $csr = openssl_csr_new($dn, $privkey, $configArgs);
            $cert = openssl_csr_sign($csr, null, $privkey, 3650, $configArgs);

            openssl_x509_export($cert, $certout);
            openssl_pkey_export($privkey, $pkeyout, null, $configArgs);

            file_put_contents($this->certPath, $certout);
            file_put_contents($this->keyPath, $pkeyout);
        }
    }
}
