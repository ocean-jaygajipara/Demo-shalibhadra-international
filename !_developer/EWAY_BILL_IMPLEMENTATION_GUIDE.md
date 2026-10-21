# E-Way Bill Implementation Guide

## Overview
This document outlines the complete implementation plan for integrating e-way billing functionality into the Shalibhadra International Private Limited CRM system.

## Prerequisites & Requirements

### 1. GSTN E-Way Bill API Credentials
**Timeline: 2-4 weeks (External dependency)**

You need to obtain the following from GSTN (Goods and Services Tax Network):
- **GSTIN Number** (15-digit GST Identification Number)
- **Username** (GSTN portal username)
- **Password** (GSTN portal password)
- **API Client ID** (provided by GSTN after registration)
- **API Client Secret** (provided by GSTN after registration)
- **API Environment** (Sandbox for testing, Production for live)

**Steps to get credentials:**
1. Register on GSTN portal (https://www.gst.gov.in/)
2. Apply for E-Way Bill API access
3. Complete KYC and verification process
4. Receive API credentials (usually takes 2-4 weeks)
5. Test with Sandbox environment first

### 2. Technical Requirements
- PHP 8.2+ (Already available)
- Laravel 12.0 (Already available)
- Guzzle HTTP Client (Already available in Laravel)
- SSL Certificate for API communication
- Valid GSTIN for your company

---

## Implementation Breakdown

### Phase 1: Database Schema (Estimated: 2-3 days)

#### 1.1 Create `eway_bills` Table
Stores all e-way bill transactions and details.

**Fields:**
- `id` (Primary Key)
- `company_id` (Foreign Key)
- `invoice_id` (Foreign Key to invoice table)
- `eway_bill_no` (Unique e-way bill number from GSTN)
- `eway_bill_date` (Date when e-way bill was generated)
- `valid_upto` (E-way bill validity date)
- `distance` (Distance in kilometers)
- `vehicle_no` (Vehicle registration number)
- `vehicle_type` (R/RW/O)
- `transporter_id` (Transporter ID if registered)
- `transporter_name` (Transporter name)
- `transporter_gstin` (Transporter GSTIN if available)
- `status` (Generated/Cancelled/Expired/Updated)
- `generated_at` (Timestamp)
- `cancelled_at` (Timestamp if cancelled)
- `api_response` (JSON - Full API response)
- `error_message` (Error details if generation failed)
- `created_at`, `updated_at`, `deleted_at`

#### 1.2 Add Fields to `invoice` Table
Add e-way bill reference fields to existing invoice table.

**New Fields:**
- `eway_bill_id` (Foreign Key to eway_bills)
- `eway_bill_no` (For quick reference)
- `eway_bill_status` (Generated/Pending/Cancelled)
- `distance_km` (Distance for e-way bill)
- `vehicle_number` (Vehicle number)
- `vehicle_type` (R/RW/O)
- `transporter_id` (Transporter ID)
- `transporter_name` (Transporter name)
- `transporter_gstin` (Transporter GSTIN)

#### 1.3 Create `company_eway_credentials` Table
Store GSTN API credentials securely per company.

**Fields:**
- `id` (Primary Key)
- `company_id` (Foreign Key)
- `gstin` (15-digit GSTIN)
- `username` (GSTN username - encrypted)
- `password` (GSTN password - encrypted)
- `api_client_id` (API Client ID - encrypted)
- `api_client_secret` (API Client Secret - encrypted)
- `environment` (sandbox/production)
- `status` (active/inactive)
- `last_successful_call` (Timestamp)
- `created_at`, `updated_at`

---

### Phase 2: Models & Relationships (Estimated: 1 day)

#### 2.1 Create EwayBill Model
```php
- Relationships: belongsTo(Invoice), belongsTo(Company)
- Fillable fields
- Status constants
- Helper methods for status checks
```

#### 2.2 Create CompanyEwayCredential Model
```php
- Relationships: belongsTo(Company)
- Encryption for sensitive fields
- Helper methods for credential validation
```

#### 2.3 Update Invoice Model
```php
- Add eway_bill relationship
- Add fillable fields for e-way bill
- Helper methods for e-way bill status
```

---

### Phase 3: Service Layer - API Integration (Estimated: 5-7 days)

#### 3.1 Create EwayBillService Class
Main service class for all e-way bill operations.

**Methods:**
- `authenticate()` - Get OAuth token from GSTN
- `generateEwayBill($invoiceId)` - Generate new e-way bill
- `cancelEwayBill($ewayBillNo, $reason)` - Cancel e-way bill
- `updateEwayBill($ewayBillNo, $vehicleNo)` - Update vehicle number
- `getEwayBillStatus($ewayBillNo)` - Check status
- `validateInvoiceData($invoice)` - Validate invoice data before generation

**API Endpoints to Integrate:**
- Authentication: `https://ewaybillgst.gov.in/ewaybillapi/authenticate`
- Generate: `https://ewaybillgst.gov.in/ewaybillapi/ewb/ewaybill`
- Cancel: `https://ewaybillgst.gov.in/ewaybillapi/ewb/cancel`
- Update Vehicle: `https://ewaybillgst.gov.in/ewaybillapi/ewb/updatevehicleno`

#### 3.2 Data Mapping
Map invoice data to GSTN e-way bill format:

**Required Data:**
- Supplier GSTIN (from company)
- Recipient GSTIN (from customer)
- Invoice/Document number
- Invoice/Document date
- HSN codes (from invoice items)
- Taxable value
- GST amount
- Total invoice value
- Transport details
- Distance
- Vehicle number
- Transporter details

---

### Phase 4: Controllers & Routes (Estimated: 3-4 days)

#### 4.1 Create EwayBillController
**Methods:**
- `index()` - List all e-way bills
- `generate($invoiceId)` - Generate e-way bill for invoice
- `cancel($ewayBillId)` - Cancel e-way bill
- `update($ewayBillId)` - Update vehicle number
- `status($ewayBillId)` - Get e-way bill status
- `show($ewayBillId)` - View e-way bill details

#### 4.2 Update InvoiceController
- Add e-way bill generation action
- Add e-way bill status in invoice view
- Add e-way bill details in invoice print

#### 4.3 Create CompanyEwayCredentialController
- `create()` - Show credentials form
- `store()` - Save credentials (encrypted)
- `edit()` - Edit credentials
- `update()` - Update credentials
- `test()` - Test API connection

#### 4.4 Routes
```php
// E-way Bill Routes
Route::prefix('eway-bill')->group(function() {
    Route::get('/', [EwayBillController::class, 'index']);
    Route::post('/generate/{invoice}', [EwayBillController::class, 'generate']);
    Route::post('/cancel/{ewayBill}', [EwayBillController::class, 'cancel']);
    Route::post('/update/{ewayBill}', [EwayBillController::class, 'update']);
    Route::get('/status/{ewayBill}', [EwayBillController::class, 'status']);
});

// Company E-way Credentials
Route::prefix('company')->group(function() {
    Route::get('/eway-credentials', [CompanyEwayCredentialController::class, 'create']);
    Route::post('/eway-credentials', [CompanyEwayCredentialController::class, 'store']);
});
```

---

### Phase 5: User Interface (Estimated: 5-6 days)

#### 5.1 Company Settings Page
- Form to enter GSTN credentials
- Test connection button
- Environment selector (Sandbox/Production)
- Status indicator

#### 5.2 Invoice Form Updates
- Add e-way bill section with fields:
  - Distance (km)
  - Vehicle Number
  - Vehicle Type (dropdown: R/RW/O)
  - Transporter Name
  - Transporter GSTIN
  - Transporter ID

#### 5.3 Invoice View Page
- Display e-way bill number (if generated)
- Display e-way bill status badge
- Generate E-way Bill button
- Cancel E-way Bill button (if generated)
- Update Vehicle button (if generated)
- QR Code display for e-way bill number

#### 5.4 Invoice Print Template
- Add e-way bill section showing:
  - E-way bill number
  - E-way bill date
  - Valid upto date
  - Vehicle number
  - Distance
  - QR Code

#### 5.5 E-way Bill List Page
- DataTable with columns:
  - E-way bill number
  - Invoice number
  - Date
  - Status
  - Vehicle number
  - Distance
  - Actions (View, Cancel, Update)
- Filters:
  - Date range
  - Status
  - Invoice number
  - E-way bill number

---

### Phase 6: Validation & Request Classes (Estimated: 1-2 days)

#### 6.1 EwayBillGenerateRequest
Validation rules:
- Invoice must exist
- Invoice must have valid GST details
- Distance must be provided
- Vehicle number format validation
- All required invoice items must have HSN codes

#### 6.2 EwayBillCancelRequest
Validation rules:
- E-way bill must exist
- E-way bill must be in Generated status
- Cancellation reason required

---

### Phase 7: Helper Functions (Estimated: 1 day)

#### 7.1 E-way Bill Helpers
- `validateEwayBillNumber($number)` - Validate e-way bill number format
- `formatEwayBillNumber($number)` - Format display
- `calculateDistance($fromAddress, $toAddress)` - Calculate distance (optional - can use Google Maps API)
- `generateEwayBillQRCode($ewayBillNo)` - Generate QR code

---

### Phase 8: Background Jobs (Optional - Estimated: 1-2 days)

#### 8.1 Create GenerateEwayBillJob
- Queue e-way bill generation for better UX
- Retry mechanism for failed API calls
- Email notification on success/failure

---

### Phase 9: Testing (Estimated: 3-5 days)

#### 9.1 Unit Tests
- Test data mapping
- Test validation rules
- Test helper functions

#### 9.2 Integration Tests
- Test API authentication
- Test e-way bill generation (Sandbox)
- Test e-way bill cancellation
- Test vehicle number update
- Test error handling

#### 9.3 User Acceptance Testing
- Test complete flow from invoice to e-way bill generation
- Test with real invoice data
- Test print templates
- Test cancellation flow

---

## Timeline Summary

| Phase | Task | Estimated Time | Dependencies |
|-------|------|----------------|--------------|
| **Pre-requisite** | Get GSTN API Credentials | 2-4 weeks | External (GSTN) |
| **Phase 1** | Database Schema | 2-3 days | None |
| **Phase 2** | Models & Relationships | 1 day | Phase 1 |
| **Phase 3** | Service Layer - API Integration | 5-7 days | Phase 2, API Credentials |
| **Phase 4** | Controllers & Routes | 3-4 days | Phase 3 |
| **Phase 5** | User Interface | 5-6 days | Phase 4 |
| **Phase 6** | Validation & Requests | 1-2 days | Phase 4 |
| **Phase 7** | Helper Functions | 1 day | Phase 2 |
| **Phase 8** | Background Jobs (Optional) | 1-2 days | Phase 3 |
| **Phase 9** | Testing | 3-5 days | All phases |

**Total Development Time: 22-31 days (excluding API credential procurement)**

---

## Key Changes Summary

### Database Changes
1. **New Tables:** 2 tables (eway_bills, company_eway_credentials)
2. **Modified Tables:** 1 table (invoice - add 9 new fields)
3. **Total Migrations:** 3 new migration files

### Code Changes
1. **New Models:** 2 models (EwayBill, CompanyEwayCredential)
2. **Modified Models:** 1 model (Invoice)
3. **New Controllers:** 2 controllers (EwayBillController, CompanyEwayCredentialController)
4. **Modified Controllers:** 1 controller (InvoiceController)
5. **New Services:** 1 service class (EwayBillService)
6. **New Request Classes:** 2 request classes
7. **New Views:** 4-5 new view files
8. **Modified Views:** 2-3 existing view files
9. **New Routes:** ~10 new routes

### Configuration
1. **Environment Variables:** Add GSTN API base URLs
2. **Config File:** Create eway_bill.php config file

---

## Important Notes

### 1. E-way Bill Validity
- E-way bills are valid for a specific period based on distance:
  - Upto 100 km: 1 day
  - 101-300 km: 3 days
  - 301-500 km: 5 days
  - 501-1000 km: 10 days
  - Above 1000 km: 15 days

### 2. Mandatory Requirements
- Invoice value >= ₹50,000 requires e-way bill
- Must have valid GSTIN for supplier and recipient
- All items must have HSN codes
- Transport details are mandatory

### 3. Security Considerations
- Encrypt API credentials in database
- Use environment variables for sensitive data
- Implement rate limiting for API calls
- Log all API requests/responses for audit

### 4. Error Handling
- Handle API timeouts
- Handle invalid credentials
- Handle network failures
- Provide user-friendly error messages
- Implement retry mechanism

### 5. Compliance
- Ensure data accuracy before generation
- Maintain audit trail
- Store API responses for reference
- Handle cancellation properly (can't cancel after vehicle dispatch in some cases)

---

## Next Steps

1. **Immediate Action Required:**
   - Apply for GSTN E-way Bill API access
   - Gather all required company GST details
   - Prepare test invoices for testing

2. **Development Start:**
   - Begin with Phase 1 (Database Schema)
   - Set up sandbox environment for testing
   - Create development branch for e-way bill feature

3. **Testing Strategy:**
   - Start with sandbox API credentials
   - Test with sample invoices
   - Validate all data mappings
   - Test error scenarios

---

## Support & Resources

- **GSTN E-way Bill Portal:** https://ewaybillgst.gov.in/
- **API Documentation:** Available after API registration
- **GSTN Support:** Contact through GSTN portal

---

## Questions to Clarify

1. Do you have GSTN API credentials already, or do we need to apply?
2. What is your company GSTIN?
3. Do you want to integrate distance calculation automatically (using Google Maps API) or manual entry?
4. Do you need e-way bill generation for existing invoices or only new ones?
5. Should e-way bill generation be mandatory for invoices above ₹50,000?
6. Do you need email notifications for e-way bill generation/cancellation?

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-27  
**Prepared By:** AI Assistant

