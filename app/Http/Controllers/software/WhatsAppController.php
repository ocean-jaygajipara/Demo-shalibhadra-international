<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\Quotation;
use App\Models\PurchaseOrder;
use App\Models\JobWorkOrder;
use App\Models\Invoice;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class WhatsAppController extends Controller
{
    /**
     * Send WhatsApp message via Helper (Liliya API) or Web WhatsApp fallback
     *
     * @param int $id
     * @param string $type
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send($id, $type)
    {
        try {
            $customerPhone = null;
            $customerName = '';
            $docNo = '';
            $docDate = '';
            $total = '0';
            $companyId = null;
            $filepath = null;
            $fileUrl = null;
            $message = '';

            if ($type === 'quotation') {
                $quotation = Quotation::with(['company', 'customer.city', 'customer.state', 'customer.country', 'team_person', 'quotation_items', 'term_condition', 'transport_by', 'transport_detail'])->find($id);
                if (!$quotation) {
                    return back()->withErrors(['Quotation not found.']);
                }

                $customerPhone = preg_replace('/[^0-9]/', '', $quotation->phone ?: ($quotation->customer->mobile_number ?? ''));
                if (!$customerPhone) {
                    return back()->withErrors(['Customer mobile number not found.']);
                }

                $customerName = $quotation->customer->customer_name ?? 'Customer';
                $docNo = $quotation->quotation_no;
                $docDate = Carbon::parse($quotation->quotation_date)->format('d-m-Y');
                $total = (string)$quotation->grand_total;
                $companyId = $quotation->company_id;

                $message = "Dear *{$customerName}*,\n\n" .
                    "Thank you for your inquiry with Shalibhadra International Private Limited.\n" .
                    "Please find attached your Quotation details below:\n\n" .
                    "📄 *Quotation No*: {$docNo}\n" .
                    "📅 *Date*: {$docDate}\n" .
                    "💰 *Total Amount*: ₹{$total}\n\n" .
                    "Please review the attached PDF quotation and feel free to contact us for any query.";

                // --- Generate PDF ---
                $isWithoutPrice = request()->get('without_price') == '1';
                $sidebarMainLogo = public_path('software/img/logo.png');
                $sidebarheaderLogo = '';

                if ($quotation->company_id) {
                    $companyDetail = Helper::getCompanyDetailById($quotation->company_id);
                    if ($companyDetail && $companyDetail->id) {
                        $sidebarMainLogo = $companyDetail->company_logo ?? $sidebarMainLogo;
                        $sidebarheaderLogo = $companyDetail->order_header_logo ?? '';
                    }
                }

                $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
                $sidebarheaderLogo = Helper::webpToBase64($sidebarheaderLogo);
                $sidebarHeaderLogo = $sidebarheaderLogo;

                $folderPath = 'software.modules.sales_management.quotation';
                $viewPath = $folderPath . '.single_pdf';
                $modules = [
                    'folder_path' => $folderPath,
                    'route' => 'quotation',
                    'currentGuard' => $this->currentGuard ?? null,
                    'authLoginUserDetail' => $this->authenticateLoginUserDetails ?? null,
                    'parent_type_id' => $this->authenticateLoginUserDetails?->parent_type_id ?? 0
                ];

                $html = View::make($viewPath, compact('quotation', 'modules', 'sidebarMainLogo', 'sidebarheaderLogo', 'sidebarHeaderLogo', 'isWithoutPrice'))
                    ->render();

                $year = now()->format('Y');
                $month = now()->format('m');
                $company_slug = Str::slug($quotation->company->company_name ?? 'company');
                $folder = "uploads/{$quotation->company_id}-{$company_slug}/quotation/{$year}-{$month}/";
                $uploadedPath = public_path($folder);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }

                $quotationNoClean = str_replace(['/', '\\', ' '], '_', $quotation->quotation_no);
                $filename = 'Quotation_' . $quotationNoClean . '.pdf';
                $filepath = $uploadedPath . $filename;
                $fileUrl = asset($folder . $filename);

                Pdf::loadHTML($html)
                    ->setPaper('A4', 'portrait')
                    ->save($filepath);
                // --- End Generate PDF ---

            } elseif ($type === 'sales_order') {
                $salesOrder = SalesOrder::with(['company', 'customer.city', 'customer.state', 'customer.country', 'team_person', 'sales_order_items', 'term_condition', 'transport_by', 'transport_detail'])->find($id);
                if (!$salesOrder) {
                    return back()->withErrors(['Sales Order not found.']);
                }

                $customerPhone = preg_replace('/[^0-9]/', '', $salesOrder->phone ?: ($salesOrder->customer->mobile_number ?? ''));
                if (!$customerPhone) {
                    return back()->withErrors(['Customer mobile number not found.']);
                }

                $customerName = $salesOrder->customer->customer_name ?? 'Customer';
                $docNo = $salesOrder->sales_order_no;
                $docDate = Carbon::parse($salesOrder->sales_order_date)->format('d-m-Y');
                $total = (string)$salesOrder->grand_total;
                $companyId = $salesOrder->company_id;

                $message = "Dear *{$customerName}*,\n\n" .
                    "Thank you for your order with Shalibhadra International Private Limited.\n" .
                    "Please find attached your Sales Order details below:\n\n" .
                    "📄 *Sales Order No*: {$docNo}\n" .
                    "📅 *Order Date*: {$docDate}\n" .
                    "💰 *Total Amount*: ₹{$total}\n\n";

                // --- Generate PDF ---
                $isWithoutPrice = request()->get('without_price') == '1';
                $sidebarMainLogo = public_path('software/img/logo.png');
                $sidebarheaderLogo = '';

                if ($salesOrder->company_id) {
                    $companyDetail = Helper::getCompanyDetailById($salesOrder->company_id);
                    if ($companyDetail && $companyDetail->id) {
                        $sidebarMainLogo = $companyDetail->company_logo ?? $sidebarMainLogo;
                        $sidebarheaderLogo = $companyDetail->order_header_logo ?? '';
                    }
                }

                $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
                $sidebarheaderLogo = Helper::webpToBase64($sidebarheaderLogo);

                $folderPath = 'software.modules.sales_management.sales_order';
                $viewPath = $folderPath . '.single_pdf';
                $modules = ['folder_path' => $folderPath, 'route' => 'sales_order'];

                $html = View::make($viewPath, compact('salesOrder', 'modules', 'sidebarMainLogo', 'sidebarheaderLogo', 'isWithoutPrice'))
                    ->render();

                $year = now()->format('Y');
                $month = now()->format('m');
                $company_slug = Str::slug($salesOrder->company->company_name ?? 'company');
                $folder = "uploads/{$salesOrder->company_id}-{$company_slug}/salesorder/{$year}-{$month}/";
                $uploadedPath = public_path($folder);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }

                $orderNoClean = str_replace(['/', '\\', ' '], '_', $salesOrder->sales_order_no);
                $filename = 'SalesOrder_' . $orderNoClean . '.pdf';
                $filepath = $uploadedPath . $filename;
                $fileUrl = asset($folder . $filename);

                Pdf::loadHTML($html)
                    ->setPaper('A4', 'portrait')
                    ->save($filepath);
                // --- End Generate PDF ---

            } elseif ($type === 'invoice') {
                $invoice = Invoice::with(['company', 'customer', 'team_person', 'invoice_items', 'term_condition', 'transport_by', 'transport_detail', 'sales_order'])->find($id);
                if (!$invoice) {
                    return back()->withErrors(['Invoice not found.']);
                }

                $customerPhone = preg_replace('/[^0-9]/', '', $invoice?->customer?->mobile_number ?? '');
                if (!$customerPhone) {
                    return back()->withErrors(['Customer mobile number not found.']);
                }

                $customerName = $invoice->customer_name ?: ($invoice->customer->customer_name ?? 'Customer');
                $docNo = $invoice->invoice_order_no;
                $docDate = Carbon::parse($invoice->invoice_order_date)->format('d-m-Y');
                $total = (string)$invoice->grand_total;
                $companyId = $invoice->company_id;

                $message = "Dear *{$customerName}*,\n\n" .
                    "Please find attached your Tax Invoice details from Shalibhadra International Private Limited.\n\n" .
                    "📄 *Invoice No*: {$docNo}\n" .
                    "📅 *Date*: {$docDate}\n" .
                    "💰 *Total Amount*: ₹{$total}\n\n" .
                    "Please find the invoice PDF attached for your reference and records. Feel free to contact us if you have any queries.\n\n" .
                    "Regards,\n" .
                    "Shalibhadra International Private Limited";

                // --- Generate PDF ---
                $isWithoutPrice = request()->get('without_price') == '1';
                $sidebarMainLogo = public_path('software/img/logo.png');
                $sidebarheaderLogo = '';
                $authorisedSignature = '';

                if ($invoice->company_id) {
                    $companyDetail = Helper::getCompanyDetailById($invoice->company_id);
                    if ($companyDetail && $companyDetail->id) {
                        $sidebarMainLogo = $companyDetail->company_logo ?? $sidebarMainLogo;
                        $sidebarheaderLogo = $companyDetail->order_header_logo ?? '';
                        $authorisedSignature = $companyDetail->authorised_signature ?? '';
                    }
                }

                $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
                $sidebarheaderLogo = Helper::webpToBase64($sidebarheaderLogo);
                $sidebarHeaderLogo = $sidebarheaderLogo;
                $authorisedSignature = Helper::webpToBase64($authorisedSignature);

                $folderPath = 'software.modules.sales_management.invoice';
                $viewPath = $folderPath . '.single_pdf';
                $modules = ['folder_path' => $folderPath, 'route' => 'invoice'];
                $Invoice = $invoice;
                $html = View::make($viewPath, compact('invoice', 'Invoice', 'modules', 'sidebarMainLogo', 'sidebarheaderLogo', 'sidebarHeaderLogo', 'authorisedSignature', 'isWithoutPrice'))
                    ->render();

                $year = now()->format('Y');
                $month = now()->format('m');
                $company_slug = Str::slug($invoice->company->company_name ?? 'company');
                $folder = "uploads/{$invoice->company_id}-{$company_slug}/Invoice/{$year}-{$month}/";
                $uploadedPath = public_path($folder);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }

                $invoiceNoClean = str_replace(['/', '\\', ' '], '_', $invoice->invoice_order_no);
                $filename = 'Invoice_' . $invoiceNoClean . '.pdf';
                $filepath = $uploadedPath . $filename;
                $fileUrl = asset($folder . $filename);

                Pdf::loadHTML($html)
                    ->setPaper('A4', 'portrait')
                    ->save($filepath);
                // --- End Generate PDF ---

            } elseif ($type === 'purchase_order') {
                $purchaseOrder = PurchaseOrder::with(['company', 'vendor', 'purchase_order_items', 'term_condition', 'team_person', 'admin_person'])->find($id);
                if (!$purchaseOrder) {
                    return back()->withErrors(['Purchase Order not found.']);
                }

                $customerPhone = preg_replace('/[^0-9]/', '', $purchaseOrder->phone ?: ($purchaseOrder->vendor->mobile_number ?? ($purchaseOrder->vendor->whatsapp_number ?? '')));
                if (!$customerPhone) {
                    return back()->withErrors(['Vendor mobile number not found.']);
                }

                $customerName = $purchaseOrder->vendor_name ?: ($purchaseOrder->vendor->vendor_name ?? 'Vendor');
                $docNo = $purchaseOrder->purchase_order_no;
                $docDate = Carbon::parse($purchaseOrder->purchase_order_date)->format('d-m-Y');

                $unitsSummary = [];
                foreach ($purchaseOrder->purchase_order_items as $item) {
                    $u = strtolower(trim($item->unit ?: 'pcs'));
                    $unitsSummary[$u] = ($unitsSummary[$u] ?? 0) + (float)$item->quantity;
                }
                $qtyParts = [];
                foreach ($unitsSummary as $u => $q) {
                    $formattedVal = (fmod($q, 1) !== 0.0) ? number_format($q, 2) : number_format($q, 0);
                    $unitLabel = match ($u) {
                        'kg' => 'Kg',
                        'pcs' => 'Pcs',
                        'gross' => 'Gross',
                        default => ucfirst($u),
                    };
                    $qtyParts[] = "{$formattedVal} {$unitLabel}";
                }
                $total = !empty($qtyParts) ? implode(', ', $qtyParts) : ((float)($purchaseOrder->master_quantity ?? 0) . ' Pcs');
                $companyId = $purchaseOrder->company_id;

                $companyName = $purchaseOrder->company->company_name ?? 'Shalibhadra International Private Limited';
                $message = "Dear *{$customerName}*,\n\n" .
                    "Please find attached your Purchase Order details from {$companyName}.\n\n" .
                    "📄 *Purchase Order No*: {$docNo}\n" .
                    "📅 *Date*: {$docDate}\n" .
                    "📦 *Total Quantity*: {$total}\n\n" .
                    "Please find the Purchase Order PDF attached for your reference and records. Feel free to contact us if you have any queries.\n\n" .
                    "Regards,\n" .
                    "{$companyName}";

                // --- Generate PDF ---
                $isWithoutPrice = request()->get('without_price') == '1';
                $sidebarMainLogo = public_path('software/img/logo.png');
                $sidebarheaderLogo = '';

                if ($purchaseOrder->company_id) {
                    $companyDetail = Helper::getCompanyDetailById($purchaseOrder->company_id);
                    if ($companyDetail && $companyDetail->id) {
                        $sidebarMainLogo = $companyDetail->company_logo ?? $sidebarMainLogo;
                        $sidebarheaderLogo = $companyDetail->order_header_logo ?? '';
                    }
                }

                $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
                $sidebarheaderLogo = Helper::webpToBase64($sidebarheaderLogo);

                $folderPath = 'software.modules.purchase_management.purchase_order';
                $viewPath = $folderPath . '.single_pdf';
                $modules = ['folder_path' => $folderPath, 'route' => 'purchase_order'];

                $sidebarHeaderLogo = $sidebarheaderLogo;
                $html = View::make($viewPath, compact('purchaseOrder', 'modules', 'sidebarMainLogo', 'sidebarheaderLogo', 'sidebarHeaderLogo', 'isWithoutPrice'))
                    ->render();

                $year = now()->format('Y');
                $month = now()->format('m');
                $company_slug = Str::slug($purchaseOrder->company->company_name ?? 'company');
                $folder = "uploads/{$purchaseOrder->company_id}-{$company_slug}/purchaseorder/{$year}-{$month}/";
                $uploadedPath = public_path($folder);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }

                $purchaseOrderNoClean = str_replace(['/', '\\', ' '], '_', $purchaseOrder->purchase_order_no);
                $filename = 'PurchaseOrder_' . $purchaseOrderNoClean . '.pdf';
                $filepath = $uploadedPath . $filename;
                $fileUrl = asset($folder . $filename);

                Pdf::loadHTML($html)
                    ->setPaper('A4', 'portrait')
                    ->save($filepath);
                // --- End Generate PDF ---

            } elseif ($type === 'job_work_order') {
                $jobOrder = JobWorkOrder::with(['company', 'vendor', 'job_work_order_items', 'term_condition', 'team_person', 'admin_person'])->find($id);
                if (!$jobOrder) {
                    return back()->withErrors(['Job Work Order not found.']);
                }

                $customerPhone = preg_replace('/[^0-9]/', '', $jobOrder?->vendor?->mobile_number ?? '');
                if (!$customerPhone) {
                    return back()->withErrors(['Vendor mobile number not found.']);
                }

                $customerName = $jobOrder->vendor->vendor_name ?? 'Vendor';
                $docNo = $jobOrder->job_work_order_no;
                $docDate = Carbon::parse($jobOrder->job_work_order_date)->format('d-m-Y');
                $total = (string)$jobOrder->grand_total;
                $companyId = $jobOrder->company_id;

                $message = "Dear *{$customerName}*,\n\n" .
                    "Please find attached your Job Work Order details from Shalibhadra International Private Limited.\n\n" .
                    "📄 *Job Work Order No*: {$docNo}\n" .
                    "📅 *Date*: {$docDate}\n" .
                    "💰 *Total Amount*: ₹{$total}\n\n" .
                    "Please review the attached PDF document. Feel free to contact us if you have any questions.\n\n" .
                    "Regards,\n" .
                    "Shalibhadra International Private Limited";

                // --- Generate PDF ---
                $isWithoutPrice = request()->get('without_price') == '1';
                $sidebarMainLogo = public_path('software/img/logo.png');
                $sidebarheaderLogo = '';

                if ($jobOrder->company_id) {
                    $companyDetail = Helper::getCompanyDetailById($jobOrder->company_id);
                    if ($companyDetail && $companyDetail->id) {
                        $sidebarMainLogo = $companyDetail->company_logo ?? $sidebarMainLogo;
                        $sidebarheaderLogo = $companyDetail->order_header_logo ?? '';
                    }
                }

                $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
                $sidebarheaderLogo = Helper::webpToBase64($sidebarheaderLogo);

                $folderPath = 'software.modules.job_work_management.job_work_order';
                $viewPath = $folderPath . '.single_pdf';
                $modules = ['folder_path' => $folderPath, 'route' => 'job_work_order'];

                $sidebarHeaderLogo = $sidebarheaderLogo;
                $html = View::make($viewPath, compact('jobOrder', 'modules', 'sidebarMainLogo', 'sidebarheaderLogo', 'sidebarHeaderLogo', 'isWithoutPrice'))
                    ->render();

                $year = now()->format('Y');
                $month = now()->format('m');
                $company_slug = Str::slug($jobOrder->company->company_name ?? 'company');
                $folder = "uploads/{$jobOrder->company_id}-{$company_slug}/joborders/{$year}-{$month}/";
                $uploadedPath = public_path($folder);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }

                $jobOrderNoClean = str_replace(['/', '\\', ' '], '_', $jobOrder->job_work_order_no);
                $filename = 'JobWorkOrder_' . $jobOrderNoClean . '.pdf';
                $filepath = $uploadedPath . $filename;
                $fileUrl = asset($folder . $filename);

                Pdf::loadHTML($html)
                    ->setPaper('A4', 'portrait')
                    ->save($filepath);
                // --- End Generate PDF ---

            } else {
                return back()->withErrors(['Unsupported type.']);
            }

            // --- Send Message via Helper (Liliya WhatsApp API) ---
            $templateName = '';
            $clientId = null;
            $authEmail = null;
            $authPassword = null;

            if ($type === 'sales_order') {
                $templateName = 'shalibadra_sales';
            } elseif ($type === 'purchase_order') {
                $templateName = config('services.liliya.purchase_order_template', env('LILIYA_PURCHASE_ORDER_TEMPLATE', 'shalibhadra_purchase_order'));
                $authEmail = config('services.liliya.purchase_order_email', env('LILIYA_PURCHASE_ORDER_EMAIL', env('LILIYA_EMAIL', 'ocean@user.com')));
                $authPassword = config('services.liliya.purchase_order_password', env('LILIYA_PURCHASE_ORDER_PASSWORD', env('LILIYA_PASSWORD', 'Ocean@123')));
                $clientId = config('services.liliya.purchase_order_client_id', env('LILIYA_PURCHASE_ORDER_CLIENT_ID', env('LILIYA_CLIENT_ID', 'SHALIB467')));
            } elseif ($type === 'job_work_order') {
                $templateName = config('services.liliya.job_work_template', env('LILIYA_JOB_WORK_TEMPLATE', 'shalibadra_job_work'));
                $authEmail = config('services.liliya.job_work_email', env('LILIYA_JOB_WORK_EMAIL', env('LILIYA_EMAIL', 'ocean@user.com')));
                $authPassword = config('services.liliya.job_work_password', env('LILIYA_JOB_WORK_PASSWORD', env('LILIYA_PASSWORD', 'Ocean@123')));
                $clientId = config('services.liliya.job_work_client_id', env('LILIYA_JOB_WORK_CLIENT_ID', env('LILIYA_CLIENT_ID', 'SHALIB467')));
            } elseif ($type === 'invoice') {
                $templateName = config('services.liliya.invoice_template', env('LILIYA_INVOICE_TEMPLATE', 'shalibadra_invoice'));
                $authEmail = config('services.liliya.invoice_email', env('LILIYA_INVOICE_EMAIL', env('LILIYA_EMAIL', 'ocean@user.com')));
                $authPassword = config('services.liliya.invoice_password', env('LILIYA_INVOICE_PASSWORD', env('LILIYA_PASSWORD', 'Ocean@123')));
                $clientId = config('services.liliya.invoice_client_id', env('LILIYA_INVOICE_CLIENT_ID', env('LILIYA_CLIENT_ID', 'SHALIB467')));
            }

            $parameters = [
                (string)$customerName,
                (string)$docNo,
                (string)$docDate,
                (string)$total
            ];

            // Normalize phone number
            $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
            if (strlen($cleanPhone) === 10) {
                $cleanPhone = '91' . $cleanPhone;
            }

            $documentName = $filepath ? basename($filepath) : null;
            $liliyaResult = Helper::sendWhatsAppMessage($cleanPhone, $templateName, $parameters, $filepath, $clientId, $documentName, $authEmail, $authPassword);

            if ($liliyaResult['success']) {
                $title = ucfirst(str_replace('_', ' ', $type));
                return back()->with('success', "{$title} sent successfully via WhatsApp!");
            } else {
                Log::warning("Liliya WhatsApp Send Failed for {$type} #{$id}: " . ($liliyaResult['message'] ?? 'Unknown error'));

                // Fallback to Web WhatsApp link
                $whatsappUrl = "https://web.whatsapp.com/send?phone={$cleanPhone}&text=" . urlencode($message . "\n" . $fileUrl);
                return redirect()->away($whatsappUrl);
            }
        } catch (\Exception $e) {
            Log::error("WhatsApp Send Exception: " . $e->getMessage());
            return back()->withErrors([$e->getMessage()]);
        }
    }

    /**
     * AJAX endpoint to test WhatsApp / Liliya connection
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testWhatsAppConnection(Request $request)
    {
        try {
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone'));
            $templateName = $request->input('template_name') ?: 'shalibadra_enquiry';

            if (empty($phone)) {
                return response()->json(['status' => false, 'success' => false, 'message' => 'Phone number is required.']);
            }

            if (strlen($phone) === 10) {
                $phone = '91' . $phone;
            }

            // Test positional parameters for template
            $testParams = ["Test Customer", "TEST-001", date('d-m-Y'), "1000"];

            $result = Helper::sendWhatsAppMessage($phone, $templateName, $testParams);

            if ($result['success']) {
                return response()->json([
                    'status' => true,
                    'success' => true,
                    'message' => 'Test message sent successfully via Liliya WhatsApp API!'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send test message.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
