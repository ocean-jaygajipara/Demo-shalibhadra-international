<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Invoice;
use App\Models\CompanySocial;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EInvoiceService
{
    protected Client $client;
    protected ?string $authToken = null;
    protected ?string $sek = null; // Session Encryption Key (raw binary)
    protected string $environment;
    protected array $credentials;

    protected string $baseUrl;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60,
            'verify' => false, // Change to true in production for proper SSL verification
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Authenticate with Whitebooks E-Invoice API
     */
    public function authenticate($credential): void
    {
        $this->credentials = [
            'email' => $credential->email,
            'username' => $credential->username,
            'password' => $credential->password,
            'gstin' => $credential->gstin,
            'client_id' => $credential->api_client_id,
            'client_secret' => $credential->api_client_secret,
            'ip_address' => $this->getIpAddress(),
        ];

        $this->environment = $credential->environment ?? 'sandbox';

        $cacheKey = "einvoice_token_{$this->credentials['gstin']}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $this->authToken = $cached['token'];
            $this->sek = base64_decode($cached['sek_base64']);
            return;
        }

        try {
            $response = $this->client->get(config("einvoice.{$this->environment}.auth"), [
                'query' => [
                    'email' => $this->credentials['email'],
                ],
                'headers' => [
                    'username' => $this->credentials['username'],
                    'password' => $this->credentials['password'],
                    'ip_address' => $this->credentials['ip_address'],
                    'client_id' => $this->credentials['client_id'],
                    'client_secret' => $this->credentials['client_secret'],
                    'gstin' => $this->credentials['gstin'],
                ],
            ]);

            // Get the JSON response body
            $body = $response->getBody()->getContents();

            if (empty($body)) {
                throw new Exception('Empty response body from authentication');
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in auth response: ' . $body);
            }

            // Check status (note: response has "status_cd" => "Sucess" – likely typo for "Success")
            if (!isset($data['status_cd']) || $data['status_cd'] !== 'Sucess') {
                $errorMsg = $data['status_desc'] ?? $data['error'] ?? 'Unknown error';
                throw new Exception('Authentication failed: ' . $errorMsg);
            }

            // Extract AuthToken and Sek from the "data" object
            if (!isset($data['data']['AuthToken']) || !isset($data['data']['Sek'])) {
                throw new Exception('Missing AuthToken or Sek in response data');
            }

            $this->authToken = $data['data']['AuthToken'];
            $sekBase64 = $data['data']['Sek'];

            $this->sek = base64_decode($sekBase64);

            if (!$this->authToken || !$this->sek) {
                throw new Exception('Failed to decode AuthToken or SEK');
            }

            // Cache for ~6 hours (based on TokenExpiry in response)
            Cache::put($cacheKey, [
                'token' => $this->authToken,
                'sek_base64' => $sekBase64,
            ], now()->addHours(6));

        } catch (RequestException $e) {
            $error = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new Exception('Authentication failed: ' . $error);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getIpAddress(): string
    {
        if (app()->environment('local')) {

            $localIp = getHostByName(getHostName());
            if (
                empty($localIp) ||
                $localIp === '127.0.0.1' ||
                str_starts_with($localIp, '127.')
            ) {
                return gethostbyname(gethostname());
            }

            return $localIp;
        }
        return '208.109.29.159';
    }

    /**
     * Encrypt payload using SEK (AES-256-ECB)
     */
    protected function encryptPayload(string $jsonPayload): string
    {
        if (!$this->sek) {
            throw new Exception('SEK not available. Authenticate first.');
        }

        return base64_encode(openssl_encrypt($jsonPayload, 'AES-256-ECB', $this->sek, OPENSSL_RAW_DATA));
    }

    /**
     * Generate IRN using Whitebooks API
     */
    public function generateIRN(Invoice $invoice): array
    {
        if (!$this->authToken) {
            throw new Exception('Authenticate first');
        }
        $payload = $this->mapInvoiceToEInvoice($invoice);

        $this->environment = $credential->environment ?? 'sandbox';
        try {

            $response = $this->client->post(config("einvoice.{$this->environment}.generate_irn"), [
                'query' => [
                    'email' => $this->credentials['email'],
                ],
                'headers' => [
                    'ip_address' => $this->credentials['ip_address'],
                    'client_id' => $this->credentials['client_id'],
                    'client_secret' => $this->credentials['client_secret'],
                    'username' => $this->credentials['username'],
                    'auth-token' => $this->authToken,
                    'gstin' => $this->credentials['gstin'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $resp = json_decode($response->getBody()->getContents(), true);
            //dd($payload,$resp);
            // Success response
            if (isset($resp['status_cd']) && $resp['status_cd'] === '1') {
                $irnData = $resp['data'] ?? [];

                // Save to database
                $invoice->update([
                    'irn' => $irnData['Irn'] ?? null,
                    'ack_no' => $irnData['AckNo'] ?? null,
                    'ack_date' => $irnData['AckDt'] ?? null,
                    'signed_invoice' => $irnData['SignedInvoice'] ?? null,
                    'signed_qr_code' => $irnData['SignedQRCode'] ?? null,
                    'irn_status' => $irnData['Status'] ?? 'ACT',
                    'e_invoice_status' => 'GENERATED',
                    'e_invoice_response' => json_encode($irnData),
                    'irn_generated_at' => now(),
                ]);

                return $resp;
            }


            // Error response
            if (isset($resp['status_cd']) && $resp['status_cd'] === '0') {

                $errorDesc = $resp['status_desc'] ?? 'Unknown error';
                if (str_contains($errorDesc, 'Invalid Token') || str_contains($errorDesc, '1005')) {

                    Cache::forget("einvoice_token_{$this->credentials['gstin']}");

                    $credential = CompanySocial::where('company_id', $invoice->company_id)
                        ->where('platform', 'einvoice')
                        ->whereNotNull('platform')
                        ->firstOrFail();

                    $this->authenticate($credential);
                    // Retry generating IRN once
                    return $this->generateIRN($invoice);
                }

                // Duplicate IRN
                if (
                    str_contains($errorDesc, 'Duplicate') ||
                    str_contains($errorDesc, 'duplicate') ||
                    str_contains($errorDesc, '2278') ||
                    str_contains($errorDesc, 'already generated') ||
                    str_contains($errorDesc, 'cancelled for this Document number') ||
                    str_contains($errorDesc, 'cannot be generated for the same')
                ) {
                    throw new Exception('Duplicate IRN: Invoice already submitted to e-invoice system.');
                }
                $errorArray = json_decode($errorDesc, true);
                $message = $errorArray[0]['ErrorMessage'] ?? 'Unknown E-Invoice error';
                throw new Exception($message);
            }

            // Unexpected response
            throw new Exception('Unexpected API response format');

        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            $errorData = json_decode($errorBody, true);

            throw new Exception($errorData['error'] ?? $errorData['status_desc'] ?? 'IRN generation failed');
        }
    }



    /**
     * Map Invoice to E-Invoice JSON Schema (adjust version as needed)
     */

    protected function mapInvoiceToEInvoice(Invoice $invoice): array
    {
        $items = $invoice->invoice_items;

        $totalAssVal = 0;
        $totalCgstVal = 0;
        $totalSgstVal = 0;
        $totalIgstVal = 0;

        $itemList = [];

        foreach ($items as $index => $item) {
            $taxable = round($item->taxable_amount ?? $item->amount, 2);
            $totalAssVal += $taxable;

            $cgstAmt = round($item->cgst_amount ?? 0, 2);
            $sgstAmt = round($item->sgst_amount ?? 0, 2);
            $igstAmt = round($item->gst_amount ?? 0, 2);

            $totalCgstVal += $cgstAmt;
            $totalSgstVal += $sgstAmt;
            $totalIgstVal += $igstAmt;

            // Calculate GST rate
            $gstRate = 0;
            if ($igstAmt > 0 && $taxable > 0) {
                $gstRate = round(($igstAmt / $taxable) * 100, 2);
            } elseif (($cgstAmt + $sgstAmt) > 0 && $taxable > 0) {
                $gstRate = round((($cgstAmt + $sgstAmt) / $taxable) * 100, 2);
            }

            $validRates = [0, 0.25, 3, 5, 12, 18, 28];
            $gstRate = $this->roundToNearestValidGSTRate($gstRate, $validRates);

            // Recalculate tax amounts using clean rate to avoid fractional discrepancies
            if ($igstAmt > 0) {
                $igstAmt = round(($taxable * $gstRate) / 100, 2);
                $cgstAmt = $sgstAmt = 0;
            } else {
                $halfRate = $gstRate / 2;
                $cgstAmt = $sgstAmt = round(($taxable * $halfRate) / 100, 2);
                $igstAmt = 0;
            }

            $totItemVal = round($taxable + $cgstAmt + $sgstAmt + $igstAmt, 2);

            // E-Invoice API validation: TotAmt - Discount = AssAmt
            // In our system: both 'amount' and 'taxable_amount' are stored AFTER discount
            // So we need to calculate TotAmt (before discount) = AssAmt + Discount
            $discountAmt = round($item->discount_flat ?? 0, 2);
            $totAmt = round($taxable + $discountAmt, 2);

            $itemList[] = [
                "SlNo" => (string) ($index + 1),
                "PrdDesc" => substr($item->product_name ?? 'Item', 0, 300),
                "IsServc" => "N",
                "HsnCd" => (string) ($item->hsn_code ?? '999999'),
                "Qty" => (float) ($item->quantity ?? 1),
                "Unit" => $this->mapUnit($item->unit ?? 'nos'),
                "UnitPrice" => round($item->price_in_amount ?? $item->rate_in_amount ?? 0, 2),
                "TotAmt" => $totAmt,
                "Discount" => $discountAmt,
                "AssAmt" => $taxable,
                "GstRt" => $gstRate,
                "IgstAmt" => $igstAmt,
                "CgstAmt" => $cgstAmt,
                "SgstAmt" => $sgstAmt,
                "CesRt" => 0.00,
                "CesAmt" => 0.00,
                "CesNonAdvlAmt" => 0.00,
                "StateCesRt" => 0.00,
                "StateCesAmt" => 0.00,
                "StateCesNonAdvlAmt" => 0.00,
                "OthChrg" => 0.00,
                "TotItemVal" => $totItemVal,
            ];
        }

        // Recalculate totals from cleaned item list
        $totalAssVal = round(array_sum(array_column($itemList, 'AssAmt')), 2);
        $totalCgstVal = round(array_sum(array_column($itemList, 'CgstAmt')), 2);
        $totalSgstVal = round(array_sum(array_column($itemList, 'SgstAmt')), 2);
        $totalIgstVal = round(array_sum(array_column($itemList, 'IgstAmt')), 2);

        $itemsTotal = round($totalAssVal + $totalCgstVal + $totalSgstVal + $totalIgstVal, 2);
        $grandTotal = round($invoice->grand_total ?? 0, 2);

        // === NEW LOGIC: Handle difference properly ===
        $difference = round($grandTotal - $itemsTotal, 2);

        $othChrg = 0.00;
        $discount = 0.00;
        $rndOffAmt = 0.00;

        // If difference is large, assume it's additional charges or discount
        if (abs($difference) > 99.99) {
            if ($difference > 0) {
                $othChrg = $difference; // Freight, packing, etc.
            } else {
                $discount = abs($difference); // Invoice-level discount
            }
        } else {
            // Small difference → legitimate rounding
            $rndOffAmt = $difference;
        }

        // Final invoice value must match
        $totInvVal = round($itemsTotal + $othChrg - $discount + $rndOffAmt, 2);



        // GSTIN and state code fixes (your existing code is good)
        $buyerGstin = $this->validateGSTIN($invoice->gst_no);

        $sellerStateCode = substr($invoice->company->gst_no, 0, 2);
        $buyerStateCode = $buyerGstin !== 'URP' ? substr($buyerGstin, 0, 2) : $sellerStateCode;

        return [
            "Version" => "1.1",
            "TranDtls" => [
                "TaxSch" => "GST",
                "SupTyp" => "B2B",
                "RegRev" => "N",
                "EcmTrn" => "N",
                "IgstOnIntra" => "N"
            ],
            "DocDtls" => [
                "Typ" => "INV",
                "No" => $invoice->invoice_order_no,
                "Dt" => date('d/m/Y', strtotime($invoice->invoice_order_date))
            ],
            "SellerDtls" => [
                "Gstin" => $invoice->company->gst_no,
                "LglNm" => substr($invoice->company->company_name, 0, 100),
                "TrdNm" => substr($invoice->company->company_name, 0, 100),
                "Addr1" => $this->cleanAddress($invoice->company->address),
                "Loc" => $invoice->company->city->name ?? 'Unknown',
                "Pin" => (int) ($invoice->company->pincode ?? 999999),
                "Stcd" => $sellerStateCode,
                ...(!empty($invoice->company->mobile_number) ? ["Ph" => substr($invoice->company->mobile_number, 0, 12)] : []),
                ...(!empty($invoice->company->email) && strlen($invoice->company->email) >= 6 ? ["Em" => $invoice->company->email] : []),
            ],
            "BuyerDtls" => [
                "Gstin" => $buyerGstin,
                "LglNm" => substr($invoice->customer->customer_name, 0, 100),
                "TrdNm" => substr($invoice->customer->customer_name, 0, 100),
                "Pos" => $buyerStateCode,
                "Addr1" => $this->cleanAddress($invoice->customer->address),
                "Loc" => $invoice->customer->city->name ?? 'Unknown',
                "Pin" => (int) ($invoice->customer->pincode ?? 999999),
                "Stcd" => $buyerStateCode,
                ...(!empty($invoice->customer->mobile_number) ? ["Ph" => substr($invoice->customer->mobile_number, 0, 12)] : []),
                ...(!empty($invoice->customer->email) && strlen($invoice->customer->email) >= 6 ? ["Em" => $invoice->customer->email] : []),
            ],
            "ValDtls" => [
                "AssVal" => $totalAssVal,
                "CgstVal" => $totalCgstVal,
                "SgstVal" => $totalSgstVal,
                "IgstVal" => $totalIgstVal,
                "CesVal" => 0.00,
                "StCesVal" => 0.00,
                "Discount" => $discount,
                "OthChrg" => $othChrg,
                "RndOffAmt" => $rndOffAmt, // Now guaranteed to be small
                "TotInvVal" => $grandTotal,
                "TotInvValFc" => 0.00
            ],
            "ItemList" => $itemList,
        ];
    }

    /**
     * Validate and clean GSTIN format
     */
    protected function validateGSTIN(?string $gstin): string
    {
        if (empty($gstin)) {
            return 'URP'; // Unregistered Person
        }

        // Clean GSTIN
        $gstin = strtoupper(trim($gstin));

        // Basic GSTIN format: 2 digits (state) + 10 chars (PAN) + 1 char (entity) + 1 char (Z) + 1 char (checksum)
        if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
            return 'URP';
        }

        return $gstin;
    }

    /**
     * Round GST rate to nearest valid rate
     */
    protected function roundToNearestValidGSTRate(float $rate, array $validRates): float
    {
        if (in_array($rate, $validRates)) {
            return $rate;
        }

        // Find nearest valid rate
        $nearest = $validRates[0];
        $minDiff = abs($rate - $nearest);

        foreach ($validRates as $validRate) {
            $diff = abs($rate - $validRate);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $validRate;
            }
        }

        return $nearest;
    }

    public function cleanAddress(?string $address): string
    {
        if (!$address)
            return '';
        $cleaned = strip_tags($address);
        $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = preg_replace('/[^\w\s,.\-\/()]/', '', $cleaned);
        return substr(trim($cleaned), 0, 100);
    }

    // Add other methods (cancel IRN, get GSTIN details, etc.) as needed
    protected function mapUnit(?string $unit): string
    {
        if (empty($unit)) {
            return 'OTH';
        }

        $unitMap = [
            'kg' => 'KGS',
            'kgs' => 'KGS',
            'gross' => 'GRS',
            'pcs' => 'PCS',
            'nos' => 'NOS',
            'box' => 'BOX',
            'metre' => 'MTR',
            'meter' => 'MTR',
            'ltr' => 'LTR',
            'litre' => 'LTR',
        ];

        return $unitMap[strtolower($unit)] ?? 'OTH';
    }

    /**
     * Cancel IRN using Whitebooks API
     * Can only cancel within 24 hours of IRN generation
     */

    public function cancelIRN(Invoice $invoice, string $cancelReason = '1', string $cancelRemarks = 'Wrong entry'): array
    {
        if (!$this->authToken) {
            throw new Exception('Authenticate first');
        }

        if (empty($invoice->irn)) {
            throw new Exception('No IRN found for this invoice');
        }

        // Check if already cancelled
        if ($invoice->irn_status === 'CNL' || $invoice->e_invoice_status === 'CANCELLED') {
            throw new Exception('IRN is already cancelled');
        }

        // Check if invoice was generated within last 24 hours
        $irnGeneratedAt = $invoice->irn_generated_at;
        if ($irnGeneratedAt && now()->diffInHours($irnGeneratedAt) > 24) {
            throw new Exception('IRN can only be cancelled within 24 hours of generation');
        }

        $payload = [
            "Irn" => $invoice->irn,
            "CnlRsn" => $cancelReason,
            "CnlRem" => substr($cancelRemarks, 0, 100)
        ];

        try {
            $response = $this->client->post(config("einvoice.{$this->environment}.cancel_irn"), [
                'query' => [
                    'email' => $this->credentials['email'],
                ],
                'headers' => [
                    'ip_address' => $this->credentials['ip_address'],
                    'client_id' => $this->credentials['client_id'],
                    'client_secret' => $this->credentials['client_secret'],
                    'username' => $this->credentials['username'],
                    'auth-token' => $this->authToken,
                    'gstin' => $this->credentials['gstin'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $resp = json_decode($response->getBody()->getContents(), true);

            if (isset($resp['status_cd']) && $resp['status_cd'] === '1') {
                $cancelData = $resp['data'] ?? [];

                // Update invoice with cancelled status
                $invoice->update([
                    'irn_status' => 'CNL',
                    'e_invoice_status' => 'CANCELLED',
                    'cancel_date' => $cancelData['CancelDate'] ?? now(),
                    'cancel_reason' => $cancelReason,
                    'cancel_remarks' => $cancelRemarks,
                    'e_invoice_response' => json_encode([
                        'cancellation' => $cancelData,
                        'original' => json_decode($invoice->e_invoice_response, true)
                    ]),
                ]);

                return $resp;
            }

            // Error response
            if (isset($resp['status_cd']) && $resp['status_cd'] === '0') {
                $errorDesc = $resp['status_desc'] ?? 'Unknown error';

                // Token expired
                if (str_contains($errorDesc, 'Invalid Token')) {
                    Cache::forget("einvoice_token_{$this->credentials['gstin']}");
                    throw new Exception('Token expired. Please re-authenticate and retry.');
                }

                // Already cancelled
                if (
                    str_contains(strtolower($errorDesc), 'already cancelled') ||
                    str_contains(strtolower($errorDesc), 'cnl')
                ) {
                    // Update local status to match
                    $invoice->update([
                        'irn_status' => 'CNL',
                        'e_invoice_status' => 'CANCELLED',
                        'cancel_date' => now(),
                    ]);
                    throw new Exception('IRN is already cancelled in the system');
                }

                throw new Exception('IRN cancellation failed: ' . $errorDesc);
            }

            throw new Exception('Unexpected API response format');

        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            $errorData = json_decode($errorBody, true);

            throw new Exception($errorData['error'] ?? $errorData['status_desc'] ?? 'IRN cancellation failed');
        }
    }
}
