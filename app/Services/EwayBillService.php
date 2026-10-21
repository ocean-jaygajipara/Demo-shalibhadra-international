<?php

namespace App\Services;

use App\Helpers\Helper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Invoice;
use App\Models\EwayBill;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EwayBillService
{
    protected Client $client;
    protected ?string $token = null;
    protected string $environment;
    protected array $credentials;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // Set to true in production with valid SSL
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Authenticate with E-Way Bill API
     */
    public function authenticate($credential): string
    {
        $this->credentials = [
            'username' => $credential->username,
            'password' => $credential->password,
            'gstin' => $credential->gstin,
            'email' => $credential->email,
            'client_id' => $credential->api_client_id,
            'client_secret' => $credential->api_client_secret,
        ];

        $this->environment = $credential->environment ?? 'sandbox';
        $cacheKey = "eway_token_{$credential->gstin}";
        if (Cache::has($cacheKey)) {
            $this->token = Cache::get($cacheKey);
            return $this->token;
        }

        try {

            $response = $this->client->get(
                config("eway.{$this->environment}.auth"),
                [
                    'query' => [
                        'username' => $this->credentials['username'],
                        'password' => $this->credentials['password'],
                        'email' => $this->credentials['email'],
                    ],
                    'headers' => [
                        'client_id' => $this->credentials['client_id'],
                        'client_secret' => $this->credentials['client_secret'],
                        'gstin' => $this->credentials['gstin'],
                        'ip_address' => $this->getEwayIpAddress(),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            if (($data['status_cd'] ?? '0') != '1') {
                throw new Exception($data['status_desc'] ?? 'Authentication failed');
            }
            Log::info('WhiteBooks authentication successful', [
                'gstin' => $this->credentials['gstin'],
            ]);

            return true;

        } catch (RequestException $e) {
            $error = $e->hasResponse()
                ? $e->getResponse()->getBody()->getContents()
                : $e->getMessage();
            Log::error('WhiteBooks authentication failed', [
                'error' => $error,
            ]);
            throw new Exception('WhiteBooks auth failed: ' . $error);
        }
    }

    public function cleanAddress(?string $address): string
    {
        if (!$address) {
            return '';
        }
        $cleaned = strip_tags($address);
        $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = preg_replace('/[^\w\s,.\-\/()]/', '', $cleaned);
        return substr(trim($cleaned), 0, 100);
    }

    public function getEwayIpAddress(): string
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
     * Generate E-Way Bill
     */
    public function generate(Invoice $invoice, array $transportData = []): array
    {
        // Remove token check completely
        if (empty($this->credentials)) {
            throw new Exception('Authentication credentials not initialized');
        }

        try {
            $payload = $this->mapInvoice($invoice, $transportData);

            $response = $this->client->post(
                config("eway.{$this->environment}.generate"),
                [
                    'query' => [
                        'email' => $this->credentials['email'],
                    ],
                    'headers' => [
                        'client_id' => $this->credentials['client_id'],
                        'client_secret' => $this->credentials['client_secret'],
                        'gstin' => $this->credentials['gstin'],
                        'ip_address' => $this->getEwayIpAddress(),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            //dd($payload, $data);

            if (($data['status_cd'] ?? '0') == '1') {
                if (isset($data['ewayBillNo'])) {
                    Log::info('E-Way Bill generated successfully', [
                        'invoice_id' => $invoice->id,
                        'eway_bill_no' => $data['ewayBillNo'] ?? $data['ewbNo']
                    ]);
                }
                return $data;
            }

            $errorRawMessage =
                $data['error']['message'] ??
                $data['message'] ??
                'E-Way Bill generation failed';

            if (str_contains(strtolower($errorRawMessage), 'limit')) {
                throw new Exception('E-Way Bill limit exceeded for this sub user. Please extend limit in WhiteBooks.');
            }

            if (str_contains($errorRawMessage, 'errorCodes')) {
                $displayMessage = $this->parseErrorCodes($errorRawMessage);
            } else {
                $displayMessage = $errorRawMessage;
            }

            throw new Exception('E-Way Bill generation failed: ' . $displayMessage);
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            $errorData = json_decode($errorBody, true);

            throw new Exception(
                $errorData['error']['message'] ??
                $errorData['message'] ??
                'E-Way Bill generation failed: ' . $e->getMessage()
            );
        }
    }


    public function getEwayErrorDescription(string $code): string
    {
        $errorMap = [
            '212' => 'Invalid Vehicle Number',
            '213' => 'Invalid Consignor Address',
            '214' => 'Invalid Consignee PIN Code',
            '215' => 'Invalid Consignee State Code',
            '216' => 'Invalid HSN Code',
            '220' => 'Invalid Trans mode',
            '221' => 'Invalid Approximate Distance',
            '303' => 'Vehicle number is required',
            '304' => 'Invalid vehicle format',
            '355' => 'Invalid Vehicle Number Format',
            '359' => 'E-Way Bill generation failed due to document validation. This invoice number may have been used earlier or the invoice date may be invalid.',
            '702' => 'Distance between From and To pincodes is incorrect',
            '724' => 'Transport distance does not match NIC distance slab',
            '704' => 'Invalid distance for given transaction type',

            '709' => 'Pin to pin distance is not available for the given pin codes',
            '710' => 'Invalid state code for the given pincode',
            '100' => 'Invalid Json',
            '227' => 'User Gstin cannot be blank',
            '316' => 'Eway bill is already verified, you cannot cancel it',
            '341' => 'This e-way bill is generated by you and hence you cannot reject it',
            '345' => 'Validity period lapsed, you cannot reject the e-way bill',
            '604' => 'E-Way Bill already generated for this document number. You cannot generate again on the same invoice number.',
        ];
        return $errorMap[$code] ?? "Unknown error (Code: {$code})";
    }

    public function parseErrorCodes(string $errorMessageString): string
    {
        preg_match('/"errorCodes":"(.*?)"/', $errorMessageString, $matches);
        $codesString = $matches[1] ?? '';
        $codes = array_filter(explode(',', $codesString));
        $messages = [];
        foreach ($codes as $code) {
            $messages[] = $this->getEwayErrorDescription($code);
        }
        return empty($messages) ? $errorMessageString : implode(' | ', $messages);
    }

    /**
     * Cancel E-Way Bill (updated to work without token)
     */
    public function cancel($ewayBillNo, $cancelReason, $cancelRemarks): array
    {
        if (empty($this->credentials)) {
            throw new Exception('Authentication credentials not initialized');
        }

        try {
            $response = $this->client->post(
                config("eway.{$this->environment}.cancel"),
                [
                    'query' => [
                        'email' => $this->credentials['email'],
                    ],
                    'headers' => [
                        'client_id' => $this->credentials['client_id'],
                        'client_secret' => $this->credentials['client_secret'],
                        'gstin' => $this->credentials['gstin'],
                        'ip_address' => $this->getEwayIpAddress(),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'ewbNo' => (int) $ewayBillNo,
                        'cancelRsnCode' => (int) $cancelReason,
                        'cancelRmrk' => $cancelRemarks,
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            if (($data['status_cd'] ?? '0') != '1') {
                throw new Exception($data['status_desc'] ?? 'E-Way Bill cancellation failed');
            }

            Log::info('E-Way Bill cancelled successfully', [
                'eway_bill_no' => (int) $ewayBillNo,
                'cancel_date' => $data['cancelDate'] ?? now()
            ]);
            return $data;
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            $errorData = json_decode($errorBody, true);

            throw new Exception(
                $errorData['error']['message'] ??
                $errorData['message'] ??
                'Cancellation failed: ' . $e->getMessage()
            );
        }
    }


    /**
     * Update Vehicle Number in E-Way Bill
     */
    public function updateVehicle($ewayBillNo, $vehicleNo, $transporterName = '', $vehicleType = 'R', $reasonCode = '1', $fromPlace, $lr_no, $lr_date, $reasonRem = ''): array
    {
        if (empty($this->credentials)) {
            throw new Exception('Authentication credentials not initialized');
        }

        try {
            $response = $this->client->post(
                config("eway.{$this->environment}.update_vehicle"),
                [
                    'query' => [
                        'email' => $this->credentials['email'],
                    ],
                    'headers' => [
                        'client_id' => $this->credentials['client_id'],
                        'client_secret' => $this->credentials['client_secret'],
                        'gstin' => $this->credentials['gstin'],
                        'ip_address' => $this->getEwayIpAddress(),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'ewbNo' => (int) $ewayBillNo,
                        'vehicleNo' => $vehicleNo,
                        'fromPlace' => $fromPlace, // Optional
                        'fromState' => $this->getStateCodeFromGSTIN($this->credentials['gstin']),
                        'reasonCode' => $reasonCode, // 1=Break down, 2=Transhipment, 3=Others, 4=First Time
                        'reasonRem' => $reasonRem ?: 'Vehicle number updated',
                        'transDocNo' => $lr_no,
                        'transDocDate' => !empty($lr_date) ? date('d/m/Y', strtotime($lr_date)) : '',
                        'transMode' => '1', // 1=Road
                        'vehicleType' => $vehicleType, // R=Regular, O=Over Dimensional Cargo
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            //dd($todata, $data);
            // Use loose comparison
            if (($data['status_cd'] ?? '0') != '1') {
                throw new Exception($data['status_desc'] ?? 'Vehicle update failed');
            }

            return $data;

        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            $errorData = json_decode($errorBody, true);

            throw new Exception(
                $errorData['error']['message'] ??
                $errorData['message'] ??
                'Vehicle update failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Map Invoice data to E-Way Bill API payload
     */
    protected function mapInvoice(Invoice $invoice, array $transportData = []): array
    {
        // Calculate valid up to based on distance (1 day per 100 km)
        $is_igst = 0;
        $row_gst_percentage = 0;
        foreach ($invoice->invoice_items as $item) {
            $cgst_amount = floatval($item->cgst_amount ?? 0);
            $sgst_amount = floatval($item->sgst_amount ?? 0);
            $gst_amount = floatval($item->gst_amount ?? 0);

            $cgst_per = floatval($item->cgst_in_percentage ?? 0);
            $sgst_per = floatval($item->sgst_in_percentage ?? 0);
            $gst_per = floatval($item->gst_in_percentage ?? 0);

            if ($cgst_amount > 0 && $sgst_amount > 0) {
                $row_gst_percentage = $cgst_per + $sgst_per;
            } else {
                $is_igst = 1;
                $row_gst_percentage = $gst_per;
            }
        }


        $data = [
            'supplyType' => 'O', // O=Outward, I=Inward
            'subSupplyType' => '1', // 1=Supply, 2=Import, 3=Export, etc.
            'subSupplyDesc' => '',
            'docType' => 'INV', // INV, BIL, BOE, CHL
            'docNo' => $invoice->invoice_order_no,
            'docDate' => date('d/m/Y', strtotime($invoice->invoice_order_date)),

            // Supplier Details
            'fromGstin' => $invoice->company->gst_no,
            'fromTrdName' => substr($invoice->company->company_name, 0, 100),
            'fromAddr1' => $this->cleanAddress(substr($invoice->company->address, 0, 120)),
            'fromAddr2' => '',
            'fromPlace' => substr($invoice->company->city->name ?? '', 0, 50),
            'fromPincode' => (int) $invoice->company->pincode ?? 100000,
            'fromStateCode' => $this->getStateCodeFromGSTIN($this->credentials['gstin']),
            'actFromStateCode' => $this->getStateCodeFromGSTIN($this->credentials['gstin']),

            // Recipient Details
            'toGstin' => $invoice->gst_no ?? 'URP',
            'toTrdName' => substr($invoice->customer->customer_name, 0, 100),
            'toAddr1' => $this->cleanAddress(substr($invoice->customer->address, 0, 120)),
            'toAddr2' => '',
            'toPlace' => substr($invoice->customer->city->name ?? '', 0, 50),
            'toPincode' => (int) ($invoice->customer->pincode ?? 0),
            'toStateCode' => $this->getStateCodeFromGSTIN($invoice->gst_no),
            'actToStateCode' => $this->getStateCodeFromGSTIN($invoice->gst_no),

            'transactionType' => 1,

            // Value Details
            'totalValue' => round((float) $invoice->total_taxable, 2),
            'cgstValue' => $is_igst == 0 ? round((float) ($invoice->gst_amount / 2 ?? 0), 2) : 0,
            'sgstValue' => $is_igst == 0 ? round((float) ($invoice->gst_amount / 2 ?? 0), 2) : 0,
            'igstValue' => $is_igst == 1 ? round((float) ($invoice->gst_amount ?? 0), 2) : 0,
            'cessValue' => 0,
            'totInvValue' => round((float) $invoice->grand_total, 2),

            // Transport Details
            'transMode' => '1', // 1=Road, 2=Rail, 3=Air, 4=Ship
            //'transDistance' => (string) ($transportData['distance']),
            'transDistance' => (string) 0,
            'transporterName' => $invoice->transport_detail->name ?? 'Self / Transporter',
            'transDocNo' => $transportData['lr_no'],
            'transDocDate' => date('d/m/Y', strtotime($transportData['lr_date'])),
            'vehicleNo' => $transportData['vehicle_no'] ?? '',
            'vehicleType' => $transportData['vehicle_type'] ?? 'R',

            // Item List
            'itemList' => $invoice->invoice_items->map(function ($item) use ($is_igst, $row_gst_percentage) {
                return [
                    'productName' => substr($item->product_name, 0, 100),
                    'productDesc' => substr($item->product_code, 0, 100),
                    'hsnCode' => (int) ($item->hsn_code ?? 0),
                    'quantity' => (float) $item->quantity,
                    'qtyUnit' => $this->mapUnit($item->unit),
                    'taxableAmount' => round((float) $item->taxable_amount, 2),
                    'cgstRate' => $is_igst == 0 ? round((float) ($row_gst_percentage / 2 ?? 0), 2) : 0,
                    'sgstRate' => $is_igst == 0 ? round((float) ($row_gst_percentage / 2 ?? 0), 2) : 0,
                    'igstRate' => $is_igst == 1 ? round((float) ($row_gst_percentage ?? 0), 2) : 0,
                    'cessRate' => 0,
                ];
            })->toArray(),
        ];

        $transporterGstin = $invoice->transport_detail->transport_gst_id ?? null;
        if (!empty($transporterGstin) && preg_match('/^[0-9]{2}[0-9A-Z]{13}$/', $transporterGstin)) {
            $data['transporterId'] = $transporterGstin;
        }
        unset($data['transDocNo'], $data['transDocDate']);
        unset($data['transporterName']);

        return $data;
    }


    function getStateCodeFromGSTIN(?string $gstin): ?int
    {
        if (!$gstin || strlen($gstin) < 2) {
            return null;
        }
        return (int) substr($gstin, 0, 2);
    }

    /**
     * Map unit to E-Way Bill format
     */
    protected function mapUnit(string $unit): string
    {
        $unitMap = [
            'kg' => 'KGS',
            'gross' => 'NOS',
            'pcs' => 'NOS',
            'nos' => 'NOS',
            'box' => 'BOX',
            'metre' => 'MTR',
        ];

        return $unitMap[strtolower($unit)] ?? 'OTH';
    }
}
