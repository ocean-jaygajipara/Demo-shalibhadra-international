<?php

namespace Database\Seeders;

use App\Models\ManageEmail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ManageEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $ManageEmail = [
            [
                'company_id' => '4',
                'module' => 'Quotation',
                'ntype' => 'quotation_approve',
                'name' => 'Quotation approve',
                'subject' => 'Quotation  approve - [quotation_no] on [quotation_date]',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that your quotation <strong>([quotation_no]) </strong>dated<strong> [quotation_date]</strong> has been approved by<strong> [company_name]</strong></p><p>Below are your details for reference:</p><p>Quotation &nbsp;Order No: [<strong>quotation_no</strong>]&nbsp;</p><p>Quotation &nbsp;Order Date: [<strong>quotation_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Quotation',
                'ntype' => 'quotation_send_pdf',
                'name' => 'Quotation Send PDF',
                'subject' => 'Quotation from [company_name]',
                'body' => '<p>Dear<strong> [customer_name]</strong>,</p><p>Thank you for considering<strong> [company_name]</strong> for your requirements.</p><p>Please find the attached quotation for your reference. If you have any questions or need further clarification, feel free to contact us.</p><p>We look forward to the opportunity to serve you.</p><p>Best regards, &nbsp;<br><strong>[company_name]</strong></p>',
                'status' => 'active',
                'created_by' => 1,
            ],
             [
                'company_id' => '4',
                'module' => 'Quotation',
                'ntype' => 'quotation_update',
                'name' => 'Quotation Update',
                'subject' => 'Quotation Update - [quotation_no] on [quotation_date]',
                'body' => '<p>Dear [customer_name],</p><p>We are pleased to inform you that a new quotation ([quotation_no]) has been successfully created on [quotation_date] by [company_name].Please find your quotation details below for your reference:</p><p>&nbsp;</p><p><strong>Quotation Details</strong></p><p>- Quotation No: [quotation_no]</p><p>- Quotation Date: [quotation_date]</p><p>- Company: [company_name]</p><p>- Customer Name: [customer_name]</p><p>- Address: [customer_address]</p><p>- Phone Number: [customer_phone]</p><p>- State: [customer_state]</p><p>- Transport Mode: [transport_by]</p><p>- Transporter Detail: [transporter_detail]</p><p>- Terms &amp; Conditions: [terms_conditions]</p><p>- Shipping Address: [shipping_address]</p><p>- Billing Address: [billing_address]</p><p><br>&nbsp;</p><p><strong>Total Summary</strong></p><p>- Total Taxable: ₹[number_format(total_taxable, 2)]</p><p>- GST: ₹[number_format(gst_amount, 2)]</p><p>- TCS: ₹[number_format(tcs_amount, 2)]</p><p>- Round Off: ₹[number_format(round_off, 2)]</p><p>- <strong>**Grand Total: ₹[number_format(grand_total, 2)]**</strong></p><p><br>&nbsp;</p><p>If you have any questions or require changes to your quotation, feel free to reach out to us.</p><p>Best regards, &nbsp;</p><p>[company_name]</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Quotation',
                'ntype' => 'quotation_create',
                'name' => 'Quotation Create',
                'subject' => 'New Quotation [quotation_no] Created by [company_name]',
                'body' => '<p>&nbsp;</p><p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that a new quotation ([quotation_no]) has been successfully created on [quotation_date] by [company_name].Please find your quotation details below for your reference:</p><p>&nbsp;</p><p><strong>Quotation Details</strong></p><p>- Quotation No: [quotation_no]</p><p>- Quotation Date: [quotation_date]</p><p>- Company: [company_name]</p><p>- Customer Name: [customer_name]</p><p>- Address: [customer_address]</p><p>- Phone Number: [customer_phone]</p><p>- State: [customer_state]</p><p>- Transport Mode: [transport_by]</p><p>- Transporter Detail: [transporter_detail]</p><p>- Terms &amp; Conditions: [terms_conditions]</p><p>- Shipping Address: [shipping_address]</p><p>- Billing Address: [billing_address]</p><p><strong>Quotation Items</strong></p><p>[quotation_items_table]</p><p>&nbsp;</p><p>&nbsp;</p><p><strong>Total Summary</strong></p><p>- Total Taxable: ₹[total_taxable]</p><p>- GST: ₹[gst_amount]</p><p>- TCS: ₹[tcs_amount]</p><p>- Round Off: ₹[round_off]</p><p>- <strong>**Grand Total: ₹[grand_total]**</strong></p><p><br>&nbsp;</p><p>If you have any questions or require changes to your quotation, feel free to reach out to us.</p><p>Best regards, &nbsp;</p><p>[company_name]</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Quotation',
                'ntype' => 'quotation_order_generated',
                'name' => 'Quotation Order Generated',
                'subject' => 'Quotation  Order Generated - [quotation_no] on [quotation_date]',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that your quotation <strong>([quotation_no]) </strong>dated<strong> [quotation_date]</strong> has been Order Generated by<strong> [company_name]</strong></p><p>Below are your details for reference:</p><p><br>Quotation &nbsp;Order No: [<strong>quotation_no</strong>]&nbsp;</p><p>Quotation &nbsp;Order Date: [<strong>quotation_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Quotation',
                'ntype' => 'quotation_deleted',
                'name' => 'Quotation deleted',
                'subject' => 'Quotation Deleted - [quotation_no] on [quotation_date]',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We would like to inform you that your quotation <strong>([quotation_no])</strong> dated<strong> [quotation_date]</strong> has been &lt;strong&gt;deleted&lt;/strong&gt; by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p><br>Quotation &nbsp;Order No: [<strong>quotation_no</strong>]&nbsp;</p><p>Quotation &nbsp;Order Date: [<strong>quotation_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Sales Orders',
                'ntype' => 'sales_order_create',
                'name' => 'SalesOrder  Create',
                'subject' => 'New SalesOrder [sales_order_no] Created by [company_name]',
                'body' => '<p>&nbsp;</p><p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that a new SalesOrder (<strong>[sales_order_no]</strong>) has been successfully created on<strong> [sales_order_date]</strong> by<strong> [company_name]</strong>.Please find your quotation details below for your reference:</p><p>&nbsp;</p><p><strong>SalesOrder&nbsp;Details</strong></p><p>- SalesOrder No: [sales_order_no]</p><p>- SalesOrder Date: [sales_order_date]</p><p>- Company: [company_name]</p><p>- Customer Name: [customer_name]</p><p>- Address: [customer_address]</p><p>- Phone Number: [customer_phone]</p><p>- State: [customer_state]</p><p>- Transport Mode: [transport_by]</p><p>- Transporter Detail: [transporter_detail]</p><p>- Terms &amp; Conditions: [terms_conditions]</p><p>- Shipping Address: [shipping_address]</p><p>- Billing Address: [billing_address]</p><p>&nbsp;</p><p><strong>SalesOrder Items</strong></p><p><strong>[sales_order_items_table]</strong></p><p>&nbsp;</p><p>&nbsp;</p><p><strong>Total Summary</strong></p><p>- Total Taxable: ₹[total_taxable]</p><p>- GST: ₹[gst_amount]</p><p>- TCS: ₹[tcs_amount]</p><p>- Round Off: ₹[round_off]</p><p>- <strong>**Grand Total: ₹[grand_total]**</strong></p><p><br>&nbsp;</p><p>If you have any questions or require changes to your quotation, feel free to reach out to us.</p><p>Best regards, &nbsp;</p><p>[company_name]</p>',
                'status' => 'active',
                'created_by' => 1,

            ],
            [
                'company_id' => '4',
                'module' => 'Sales Orders',
                'ntype' => 'sales_order_approve',
                'name' => 'SalesOrder approve',
                'subject' => 'SalesOrder approve - [sales_order_no] on [sales_order_date]',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that your SalesOrder <strong>([sales_order_no]) </strong>dated<strong> [sales_order_date]</strong> has been approved by<strong> [company_name]</strong></p><p>Below are your details for reference:</p><p>Sales Order No: [<strong>sales_order_no</strong>]&nbsp;</p><p>Sales Order Date: [<strong>sales_order_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Sales Orders',
                'ntype' => 'sales_order_partially_dispatch',
                'name' => 'SalesOrder Partially Dispatched',
                'subject' => 'Partial Dispatch Notification for Your Sales Order [sales_order_no]',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We would like to inform you that your Sales Order <strong>([sales_order_no])</strong> dated <strong>[sales_order_date]</strong> has been <strong>partially dispatched</strong> by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p>Dispatch &nbsp;No: <strong>[dispatch_no]&nbsp;</strong></p><p>Dispatch Date : <strong>[dispatch_date]&nbsp;</strong></p><p>Sales Order No: [<strong>sales_order_no</strong>]&nbsp;</p><p>Sales Order Date: [<strong>sales_order_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p><strong>SalesOrder Items</strong></p><p>[dispatch_items_table]</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Sales Orders',
                'ntype' => 'sales_order_fully_dispatch',
                'name' => 'SalesOrder Fully Dispatched',
                'subject' => 'Your Sales Order [sales_order_no] Has Been Fully Dispatched',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that your Sales Order <strong>([sales_order_no])</strong> dated <strong>[sales_order_date]</strong> has been <strong>fully dispatched</strong> by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p>Dispatch &nbsp;No: <strong>[dispatch_no]&nbsp;</strong></p><p>Dispatch Date : <strong>[dispatch_date]&nbsp;</strong></p><p>Sales Order No: [<strong>sales_order_no</strong>]&nbsp;</p><p>Sales Order Date: [<strong>sales_order_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p><strong>Dispatched Items</strong></p><p>[dispatch_items_table]</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Sales Orders',
                'ntype' => 'sales_order_deleted',
                'name' => 'SalesOrder deleted',
                'subject' => 'Sales Order [sales_order_no] Has Been Deleted',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We would like to inform you that your Sales Order <strong>([sales_order_no])</strong> dated <strong>[sales_order_date]</strong> has been <strong>deleted</strong> by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p><br>Sales Order No: [<strong>sales_order_no</strong>]&nbsp;</p><p>Sales Order Date: [<strong>sales_order_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Dispatch',
                'ntype' => 'dispatch_deleted',
                'name' => 'Dispatch deleted',
                'subject' => 'Dispatch [dispatch_no] Has Been Deleted',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We would like to inform you that your Dispatch <strong>([dispatch_no])</strong> dated <strong>[dispatch_date]</strong> has been <strong>deleted</strong> by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p>Dispatch &nbsp;No: <strong>[dispatch_no]&nbsp;</strong></p><p>Dispatch Date : <strong>[dispatch_date]&nbsp;</strong></p><p>Sales Order No: [<strong>sales_order_no</strong>]&nbsp;</p><p>Sales Order Date: [<strong>sales_order_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Purchase Order',
                'ntype' => 'purchase_order_create',
                'name' => 'Purchase Order Create',
                'subject' => 'New Purchase Order [purchase_order_no] Created by [company_name]',
                'body' => '<p>&nbsp;</p><p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that a new Purchase Order (<strong>[purchase_order_no]</strong>) has been successfully created on<strong> [purchase_order_date]</strong> by<strong> [company_name]</strong>.Please find your quotation details below for your reference:</p><p>&nbsp;</p><p><strong>Purchase Order&nbsp;Details</strong></p><p>- Purchase Order &nbsp;No: [purchase_order_no]</p><p>- Purchase Order Date: [purchase_order_date]</p><p>- Company: [company_name]</p><p>- Customer Name: [customer_name]</p><p>- Address: [customer_address]</p><p>- Phone Number: [customer_phone]</p><p>- State: [customer_state]</p><p>- Transport Mode: [transport_by]</p><p>- Transporter Detail: [transporter_detail]</p><p>- Terms &amp; Conditions: [terms_conditions]</p><p>- Shipping Address: [shipping_address]</p><p>- Billing Address: [billing_address]</p><p>&nbsp;</p><p><strong>Purchase Order &nbsp;Items</strong></p><p><strong>[purchase_order_items_table]</strong></p><p>&nbsp;</p><p>&nbsp;</p><p><strong>Total Summary</strong></p><p>- Total Taxable: ₹[total_taxable]</p><p>- GST: ₹[gst_amount]</p><p>- TCS: ₹[tcs_amount]</p><p>- Round Off: ₹[round_off]</p><p>- <strong>**Grand Total: ₹[grand_total]**</strong></p><p><br>&nbsp;</p><p>If you have any questions or require changes to your quotation, feel free to reach out to us.</p><p>Best regards, &nbsp;</p><p>[company_name]</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Purchase Order',
                'ntype' => 'purchase_order_approve',
                'name' => 'Purchase Order approve',
                'subject' => 'Purchase Order approve - [purchase_order_no] on [purchase_order_date]',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that your Purchase Order <strong>([purchase_order_no]) </strong>dated<strong> [purchase_order_date]</strong> has been approved by<strong> [company_name]</strong></p><p>Below are your details for reference:</p><p>- Purchase Order &nbsp;No: <strong>[purchase_order_no]</strong></p><p>- Purchase Order Date: <strong>[purchase_order_date]</strong></p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Purchase Order',
                'ntype' => 'purchase_order_deleted',
                'name' => 'Purchase Order deleted',
                'subject' => 'Purchase Order [purchase_order_no] Has Been Deleted',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We would like to inform you that your Purchase Order <strong>([purchase_order_no])</strong> dated <strong>[purchase_order_date]</strong> has been <strong>deleted</strong> by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p>&nbsp;</p><p>- Purchase Order &nbsp;No: <strong>[purchase_order_no]</strong></p><p>- Purchase Order Date: <strong>[purchase_order_date]</strong></p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Purchase Order',
                'ntype' => 'purchase_order_partially_inward',
                'name' => 'Purchase Order Partially Inward',
                'subject' => 'Partial Inward Notification for Your Purchase Order [purchase_order_no]',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We would like to inform you that your Purchase Order <strong>([purchase_order_no])</strong> dated <strong>[purchase_order_date]</strong> has been <strong>partially Inward </strong>by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p>Inward No: <strong>[inward_no]&nbsp;</strong></p><p>Inward Date : <strong>[inward_date]&nbsp;</strong></p><p>Purchase Order No: [purchase_order_no]&nbsp;</p><p>Purchase Order Date: [<strong>purchase_order_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p><strong>Inward Items</strong></p><p>[inward_items_table]</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Purchase Order',
                'ntype' => 'purchase_order_fully_inward',
                'name' => 'Purchase Order Fully Inward',
                'subject' => 'Your Purchase Order [purchase_order_no] Has Been Fully Inward',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We are pleased to inform you that your Sales Order <strong>([purchase_order_no])</strong> dated <strong>[purchase_order_date]</strong> has been <strong>fully Inward </strong>by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p>Inward No: <strong>[inward_no]&nbsp;</strong></p><p>Inward Date : <strong>[inward_date]&nbsp;</strong></p><p>Purchase Order No: [<strong>purchase_order_no</strong>]&nbsp;</p><p>Purchase Order Date: [<strong>purchase_order_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p><strong>Inward Items</strong></p><p>[inward_items_table]</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'company_id' => '4',
                'module' => 'Inward',
                'ntype' => 'inward_deleted',
                'name' => 'Inward deleted',
                'subject' => 'Inward [inward_no] Has Been Deleted',
                'body' => '<p><strong>Dear [customer_name],</strong></p><p>We would like to inform you that your Inward <strong>([inward_no])</strong> dated <strong>[inward_date]</strong> has been <strong>deleted</strong> by <strong>[company_name]</strong>.</p><p>Below are your details for reference:</p><p>Inward No: <strong>[inward_no]&nbsp;</strong></p><p>Inward Date : <strong>[inward_date]&nbsp;</strong></p><p>Purchase Order No: [<strong>purchase_order_no</strong>]&nbsp;</p><p>Purchase Order Date: [<strong>purchase_order_date</strong>]&nbsp;</p><p>Company Name: [<strong>company_name</strong>]&nbsp;</p><p>&nbsp;</p><p>Our team will reach out to you shortly for the next steps.</p><p>If you have any questions or require further assistance, feel free to contact us.</p><p><br><strong>Best regards,</strong></p><p>[<strong>company_name</strong>]&nbsp;</p>',
                'status' => 'active',
                'created_by' => 1,
            ],

        ];

        foreach ($ManageEmail as $subEmail) {
            ManageEmail::create($subEmail);
        }
    }
}
