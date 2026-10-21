<?php

namespace Database\Seeders;

use App\Models\MainMenu;
use App\Models\SubMenu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use function PHPSTORM_META\map;

class MainMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MainMenu::truncate();
        SubMenu::truncate();
        $data = [
            [
                'name' => 'Dashboard',
                'menu_icon' => 'tf-icons ti ti-smart-home',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Dashboard',
                        'route_name' => 'software.dashboard',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Tracking Dashboard',
                        'route_name' => 'tracking-dashboard.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Reports',
                        'route_name' => 'reports.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'SalesOrder Report',
                        'route_name' => 'sales-order-report.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Customer Report',
                        'route_name' => 'customer-report.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ]
                ]
            ],
            [
                'name' => 'Marketing',
                'menu_icon' => 'tf-icons ti ti-speakerphone',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Inquiry',
                        'route_name' => 'inquiry.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Follow Up',
                        'route_name' => 'followup.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Source Of Inquiry',
                        'route_name' => 'marketing-source-of-inquiry.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Follow Up Reason',
                        'route_name' => 'marketing-follow-up-reason.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Marketing Status',
                        'route_name' => 'marketing-status.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ]
                ]
            ],
            [
                'name' => 'Customer',
                'menu_icon' => 'icon-base ti ti-user-cog',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [

                    [
                        'name' => 'Customer Type',
                        'route_name' => 'customer-type.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Customer',
                        'route_name' => 'customer.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Visit',
                        'route_name' => 'visit.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Complain Type',
                        'route_name' => 'complaintype.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Complain',
                        'route_name' => 'complain.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ]
                ]
            ],
            [
                'name' => 'Sales Management',
                'menu_icon' => 'icon-base ti ti-user-cog',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Quotation',
                        'route_name' => 'quotation.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Sales Orders',
                        'route_name' => 'sales-order.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Dispatch',
                        'route_name' => 'dispatch_orders.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Price List',
                        'route_name' => 'price-list.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Terms and Condition',
                        'route_name' => 'terms-and-condition.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Sales Status',
                        'route_name' => 'sales-status.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Packing Slip',
                        'route_name' => 'packing_slip.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Packing Type',
                        'route_name' => 'packing_type.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ]
                ]
            ],
            [
                'name' => 'Purchase Management',
                'menu_icon' => 'icon-base ti ti-shopping-cart',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Vendor',
                        'route_name' => 'vendors.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Purchase Order',
                        'route_name' => 'purchase-order.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Inward',
                        'route_name' => 'inwards.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ]
                ]

            ],
            [
                'name' => 'Inventory',
                'menu_icon' => 'icon-base ti ti-building-warehouse',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Product Stock',
                        'route_name' => 'product-stock.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Add Product Stock',
                        'route_name' => 'product-stock-history.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ]
                ]

            ],
            [
                'name' => 'HR/Team Person',
                'menu_icon' => 'tf-icons ti ti-user',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Team Person',
                        'route_name' => 'team-person.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Team Attedance',
                        'route_name' => 'team-attendance.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Expence Category',
                        'route_name' => 'expense-category.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Expence Sub Category',
                        'route_name' => 'expense-subcategory.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Expense',
                        'route_name' => 'expense.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Leave Type',
                        'route_name' => 'leavetype.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Leave Application',
                        'route_name' => 'leave-application.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],
            [
                'name' => 'Product',
                'menu_icon' => 'tf-icons ti ti-brand-producthunt',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Product Category',
                        'route_name' => 'product-category.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Product Sub Category',
                        'route_name' => 'product-subcategory.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Product Variant',
                        'route_name' => 'product-variant.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Product',
                        'route_name' => 'products.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Product Combo',
                        'route_name' => 'product-combo.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Tax',
                        'route_name' => 'product-tax.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Unit',
                        'route_name' => 'master-unit.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Order Unit',
                        'route_name' => 'orderunit.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],
            [
                'name' => 'Master',
                'menu_icon' => 'tf-icons ti ti-wallet',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [

                    [
                        'name' => 'Warehouse',
                        'route_name' => 'master-warehouse.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Transport By',
                        'route_name' => 'master-transport-by.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Transport Details',
                        'route_name' => 'master-transport-detail.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Document Type',
                        'route_name' => 'document-type.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Document Details',
                        'route_name' => 'document-list.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Designation',
                        'route_name' => 'designation.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ]

                ]
            ],
            [
                'name' => 'Visitor Book',
                'menu_icon' => 'icon-base ti ti-book',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Expo Category',
                        'route_name' => 'expo-category.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Expo Management',
                        'route_name' => 'expo-management.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Expo Visitor',
                        'route_name' => 'expo-visitor.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],
            [
                'name' => 'QR Code Redemption',
                'menu_icon' => 'tf-icons ti ti-qrcode',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Set Point = Rupees',
                        'route_name' => 'set-point-to-rupees.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Quick QR Generator',
                        'route_name' => 'quick-qr-generator.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'QR withdrawal Management',
                        'route_name' => 'withdraw-request.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],
            [
                'name' => 'Import Settings',
                'menu_icon' => 'tf-icons ti ti-database-import',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Import Inquiry',
                        'route_name' => 'import-inquiry.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Import Product Stock',
                        'route_name' => 'import-product-stock.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Import Product',
                        'route_name' => 'import-product.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],
            [
                'name' => 'Third Party Integration',
                'menu_icon' => 'tf-icons ti ti-plug-connected',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Indiamart',
                        'url' => 'software/company/thirdparty-integration/indiamart',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Tradeindia',
                        'route_name' => 'software/company/thirdparty-integration/tradeindia',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Facebook',
                        'route_name' => 'software/company/thirdparty-integration/facebook',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Website',
                        'route_name' => 'software/company/thirdparty-integration/website',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],
            [
                'name' => "Setting's",
                'menu_icon' => 'ti-icon ti ti-settings',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Team Role',
                        'url' => 'team-role.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Notification',
                        'route_name' => 'notification.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],

            [
                'name' => 'Region',
                'menu_icon' => 'tf-icons ti ti-map-pin',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Country',
                        'route_name' => 'master-country.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'State',
                        'route_name' => 'master-state.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'City',
                        'route_name' => 'master-city.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Area',
                        'route_name' => 'master-area.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ]
                ]
            ],
            /*
            [
                'name' => 'Register Company',
                'menu_icon' => 'icon-base ti ti-building-factory-2',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Register Company',
                        'route_name' => 'company.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]

            ],
            [
                'name' => 'Plan',
                'menu_icon' => 'icon-base ti ti-list-details',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Plan Master',
                        'route_name' => 'plan-master.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Team Role',
                        'route_name' => 'team-role.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],
            [
                'name' => 'Admin Settings',
                'menu_icon' => 'icon-base ti ti-settings-star',
                'created_by' => '1',
                'updated_by' => '1',
                'status' => 'active',
                'sub_menu' => [
                    [
                        'name' => 'Customer Type',
                        'route_name' => 'customer-type.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                    [
                        'name' => 'Sales Status',
                        'route_name' => 'sales-status.index',
                        'created_by' => '1',
                        'updated_by' => '1',
                    ],
                ]
            ],
            */
        ];

        $main_menu_order_by = 1;
        $sub_menu_order_by = 1;

        foreach ($data as $item) {

            $menu_item['name'] = $item['name'];
            $menu_item['menu_icon'] = isset($item['menu_icon']) ? $item['menu_icon'] : null;
            $menu_item['order_by'] = $main_menu_order_by;
            $menu_item['created_by'] = $item['created_by'];
            $menu_item['updated_by'] = $item['updated_by'];
            $menu_item['status'] = $item['status'];

            $menu = MainMenu::create($menu_item);
            $main_menu_order_by++;

            $sub_menu_order_by = 1;
            foreach ($item['sub_menu'] as $sub_item) {

                $sub_menu_item['name'] = $sub_item['name'];
                $sub_menu_item['route_name'] = isset($sub_item['route_name']) ? $sub_item['route_name'] : null;
                $sub_menu_item['order_by'] = $sub_menu_order_by;
                $sub_menu_item['created_by'] = $sub_item['created_by'];
                $sub_menu_item['updated_by'] = $sub_item['updated_by'];
                $sub_menu_item['main_menu_id'] = $menu->id;
                SubMenu::create($sub_menu_item);
                $sub_menu_order_by++;
            }
        }
    }
}
