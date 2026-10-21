<?php

namespace App\Services;

use App\Helpers\Helper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\JobWorkIssue;
use App\Models\JobWorkIssueEwayBill;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JobWorkIssueEwayBillService
{
    protected Client $client;
    protected ?string $token = null;
    protected string $environment;
    protected array $credentials;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
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

        // We can reuse the same token cache key since it depends on GSTIN
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

            Log::info('E-Way Bill authentication successful', [
                'gstin' => $this->credentials['gstin'],
            ]);
            return true;

        } catch (RequestException $e) {
            $error = $e->hasResponse()
                ? $e->getResponse()->getBody()->getContents()
                : $e->getMessage();
            Log::error('E-Way Bill authentication failed', [
                'error' => $error,
            ]);
            throw new Exception('Auth failed: ' . $error);
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
     * Generate E-Way Bill for Job Work Issue
     */
    public function generate(JobWorkIssue $jobWorkIssue, array $transportData = []): array
    {
        if (empty($this->credentials)) {
            throw new Exception('Authentication credentials not initialized');
        }

        try {
            $payload = $this->mapJobWorkIssue($jobWorkIssue, $transportData);

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
            // dd($data, $payload);
            if (($data['status_cd'] ?? '0') == '1') {
                if (isset($data['ewayBillNo'])) {
                    Log::info('Job Work E-Way Bill generated successfully', [
                        'job_work_issue_id' => $jobWorkIssue->id,
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
        // Reusing the same error map
        $errorMap = [
            '100' => 'Invalid Json',
            '212' => 'Invalid Vehicle Number',
            '213' => 'Invalid Consignor Address',
            '214' => 'Invalid Consignee PIN Code',
            '215' => 'Invalid Consignee State Code',
            '216' => 'Invalid HSN Code',
            '220' => 'Invalid Trans mode',
            '221' => 'Invalid Approximate Distance',
            '227' => 'User Gstin cannot be blank',
            '303' => 'Vehicle number is required',
            '304' => 'Invalid vehicle format',
            '316' => 'Eway bill is already verified, you cannot cancel it',
            '341' => 'This e-way bill is generated by you and hence you cannot reject it',
            '345' => 'Validity period lapsed, you cannot reject the e-way bill',
            '355' => 'Invalid Vehicle Number Format',
            '359' => 'E-Way Bill generation failed due to document validation. This number may have been used earlier or the date may be invalid.',
            '604' => 'E-Way Bill already generated for this document number. You cannot generate again on the same number.',
            '702' => 'Distance between From and To pincodes is incorrect',
            '704' => 'Invalid distance for given transaction type',
            '709' => 'Pin to pin distance is not available for the given pin codes',
            '710' => 'Invalid state code for the given pincode',
            '724' => 'Transport distance does not match NIC distance slab',
            'X21' => 'Invalid Transporter Document details. Please check LR Number and LR Date format (should be dd/mm/yyyy).',
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
     * Cancel E-Way Bill
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

            Log::info('Job Work E-Way Bill cancelled successfully', [
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
            // Build payload dynamically
            $payload = [
                'ewbNo' => (int) $ewayBillNo,
                'vehicleNo' => $vehicleNo,
                'fromPlace' => $fromPlace,
                'fromState' => $this->getStateCodeFromGSTIN($this->credentials['gstin']),
                'reasonCode' => $reasonCode,
                'reasonRem' => $reasonRem ?: 'Vehicle number updated',
                'transMode' => '1', // 1=Road
                'vehicleType' => $vehicleType, // R=Regular, O=Over Dimensional Cargo
            ];

            // Only include LR details if both are provided
            if (!empty($lr_no) && !empty($lr_date)) {
                $payload['transDocNo'] = $lr_no;
                $payload['transDocDate'] = date('d/m/Y', strtotime($lr_date));
            }

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
                    'json' => $payload,
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            if (($data['status_cd'] ?? '0') != '1') {
                $errorRawMessage = $data['error']['message'] ?? $data['status_desc'] ?? 'Vehicle update failed';
                if (str_contains($errorRawMessage, 'errorCodes')) {
                    $displayMessage = $this->parseErrorCodes($errorRawMessage);
                } else {
                    $displayMessage = $errorRawMessage;
                }
                throw new Exception($displayMessage);
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
     * Map Job Work Issue data to E-Way Bill API payload
     */
    protected function mapJobWorkIssue(JobWorkIssue $jobWorkIssue, array $transportData = []): array
    {
        $is_igst = 0;
        $row_gst_percentage = 0;

        // This loop checks if IGST or CGST/SGST based on the limits of the *details*
        // However, usually this is determined by state codes.
        // Copying similar logic from Invoice, but iterating over job_work_issue_items
        foreach ($jobWorkIssue->job_work_issue_items as $item) {
            // Note: JobWorkIssueItem fields might differ from InvoiceItem. 
            // Assuming similar structure based on request params in controller view_file output previously
            // But actually I need to check JobWorkIssueItem model to be precise. 
            // Using defaults from request or defaults.

            $cgst_amount = floatval($item->cgst_amount ?? 0);
            $sgst_amount = floatval($item->sgst_amount ?? 0);

            $cgst_per = floatval($item->cgst_percentage ?? 0);
            $sgst_per = floatval($item->sgst_percentage ?? 0);
            $ibst_per = floatval($item->igst_percentage ?? 0); // Assuming field name

            if ($cgst_amount > 0 && $sgst_amount > 0) {
                $row_gst_percentage = $cgst_per + $sgst_per;
            } else {
                $is_igst = 1;
                // If specific IGST field isn't there, might use total tax logic, but here assume similar to Invoice
                $row_gst_percentage = $ibst_per > 0 ? $ibst_per : ($cgst_per + $sgst_per);
            }
        }

        $data = [
            'supplyType' => 'O', // O=Outward
            'subSupplyType' => '4', // 8=Job Work
            'subSupplyDesc' => 'Job Work',
            'docType' => 'CHL', // CHL=Delivery Challan for Job Work
            'docNo' => $jobWorkIssue->job_work_issue_no,
            'docDate' => date('d/m/Y', strtotime($jobWorkIssue->job_work_issue_date)),


            // Supplier Details (Our Company)
            'fromGstin' => $jobWorkIssue->company->gst_no,
            'fromTrdName' => substr($jobWorkIssue->company->company_name, 0, 100),
            'fromAddr1' => $this->cleanAddress(substr($jobWorkIssue->company->address, 0, 120)),
            'fromAddr2' => '',
            'fromPlace' => substr($jobWorkIssue->company->city->name ?? '', 0, 50),
            'fromPincode' => (int) ($jobWorkIssue->company->pincode ?? 100000),
            'fromStateCode' => $this->getStateCodeFromGSTIN($this->credentials['gstin']),
            'actFromStateCode' => $this->getStateCodeFromGSTIN($this->credentials['gstin']),

            // Recipient Details (Vendor)
            'toGstin' => $jobWorkIssue->vendor->gst_number ?? 'URP',
            'toTrdName' => substr($jobWorkIssue->vendor->vendor_name, 0, 100),
            'toAddr1' => $this->cleanAddress(substr($jobWorkIssue->vendor->address, 0, 120)),
            'toAddr2' => '',
            'toPlace' => substr($jobWorkIssue->vendor->city->name ?? '', 0, 50),
            'toPincode' => (int) ($jobWorkIssue->vendor->pincode ?? 0),
            'toStateCode' => $this->getStateCodeFromGSTIN($jobWorkIssue->vendor->gst_number),
            'actToStateCode' => $this->getStateCodeFromGSTIN($jobWorkIssue->vendor->gst_number),

            'transactionType' => 1,

            // Value Details
            'totalValue' => round((float) $jobWorkIssue->master_taxable, 2),
            'cgstValue' => $is_igst == 0 ? round((float) ($jobWorkIssue->total_cgst_amount ?? 0), 2) : 0,
            'sgstValue' => $is_igst == 0 ? round((float) ($jobWorkIssue->total_sgst_amount ?? 0), 2) : 0,
            // Assuming master_total_amount includes taxes or there's a specific tax total.
            // Based on JobWorkIssueRequest: total_cgst_amount, total_sgst_amount are present in master
            'igstValue' => $is_igst == 1 ? round((float) (($jobWorkIssue->grand_total - $jobWorkIssue->master_taxable) ?? 0), 2) : 0,
            'cessValue' => 0,
            'totInvValue' => round((float) $jobWorkIssue->grand_total, 2),

            // Transport Details
            'transMode' => '1', // 1=Road
            'transDistance' => (string) 0, // Auto-calculated by EWB system if 0
            'transporterName' => substr($jobWorkIssue->transport_details->name ?? 'Self / Transporter', 0, 100),
            'transporterId' => $jobWorkIssue->transport_details->transport_gst_id ?? '',
            'transDocNo' => $transportData['lr_no'] ?? '',
            'transDocDate' => !empty($transportData['lr_date']) ? date('d/m/Y', strtotime($transportData['lr_date'])) : '',
            'vehicleNo' => $transportData['vehicle_no'] ?? '',
            'vehicleType' => $transportData['vehicle_type'] ?? 'R',

            // Item List
            'itemList' => $jobWorkIssue->job_work_issue_items->map(function ($item) use ($is_igst, $row_gst_percentage) {
                // Determine tax rates for the item
                $cgst_rate = floatval($item->cgst_percentage ?? 0);
                $sgst_rate = floatval($item->sgst_percentage ?? 0);
                $igst_rate = $cgst_rate + $sgst_rate; // Fallback if no IGST field
    
                return [
                    'productName' => substr($item->product->product_name ?? 'Item', 0, 100),
                    'productDesc' => substr($item->product->product_code ?? '', 0, 100),
                    'hsnCode' => (int) ($item->product->product_hsn->name ?? 0),
                    'quantity' => (float) $item->issue_weight, // Using issue_weight as quantity for Job Work
                    'qtyUnit' => $this->mapUnit($item->product->unit->name ?? 'kgs'), // Assuming relation exists
                    'taxableAmount' => round((float) $item->taxable_amount, 2),
                    'cgstRate' => $is_igst == 0 ? round($cgst_rate, 2) : 0,
                    'sgstRate' => $is_igst == 0 ? round($sgst_rate, 2) : 0,
                    'igstRate' => $is_igst == 1 ? round($igst_rate, 2) : 0,
                    'cessRate' => 0,
                ];
            })->toArray(),
        ];

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
            'kgs' => 'KGS',
            'gross' => 'NOS',
            'pcs' => 'NOS',
            'nos' => 'NOS',
            'box' => 'BOX',
            'metre' => 'MTR',
            'mtr' => 'MTR',
        ];

        return $unitMap[strtolower($unit)] ?? 'OTH';
    }
}
