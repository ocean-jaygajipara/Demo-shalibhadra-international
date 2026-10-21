<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleExecutiveTracking extends Model
{
    protected $table = 'sale_executive_tracking';

    protected $fillable = [
        'company_id',
        'type_reference_id',
        'type',
        'latitude',
        'longitude',
        'address',
        'current_datetime',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    const LOGIN = 1;
    const LOGOUT = 2;

    const ATTENDANCE_IN = 3;
    const ATTENDANCE_OUT = 4;
    const ATTENDANCE_AUTO_OUT = 5;

    // const LEAVE_ADD = 6;
    // const LEAVE_EDIT = 7;
    // const LEAVE_DELETE = 8;

    // const EXPENSE_ADD = 9;
    // const EXPENSE_EDIT = 10;
    // const EXPENSE_DELETE = 11;

    const VISIT_START = 12;
    const VISIT_END = 13;
    const VISIT_DELETE = 14;

    const INQUIRY_ADD = 15;
    const INQUIRY_EDIT = 16;
    const INQUIRY_DELETE = 17;

    const CUSTOMER_ADD = 18;
    const CUSTOMER_EDIT = 19;
    const CUSTOMER_DELETE = 20;

    const FOLLOW_UP_ADD = 21;
    const FOLLOW_UP_EDIT = 22;
    const FOLLOW_UP_DELETE = 23;

    const TRACKING_SYNC = 24;

    const VISIT_IN_PROCESS = 25;

    const LIVE_TRACKING = 26;

    public static function SaleExecutiveTrackingTypes(){
        $types = [];
        $types[0] = '';
        $types[self::LOGIN] = 'Login';
        $types[self::LOGOUT] = 'LogOut';

        $types[self::ATTENDANCE_IN] = 'Attendance In';
        $types[self::ATTENDANCE_OUT] = 'Attendance Out';
        $types[self::ATTENDANCE_AUTO_OUT] = 'Attendance Auto Out';

        // $types[self::ATTENDANCE_AUTO_OUT] = 'Tracking Sync';

        $types[self::VISIT_START] = 'Visit Start';
        $types[self::VISIT_END] = 'Visit End';
        $types[self::VISIT_DELETE] = 'Visit Delete';

        $types[self::INQUIRY_ADD] = 'Inquiry Add';
        $types[self::INQUIRY_EDIT] = 'Inquiry Edit';
        $types[self::INQUIRY_DELETE] = 'Inquiry Delete';

        $types[self::CUSTOMER_ADD] = 'Customer Add';
        $types[self::CUSTOMER_EDIT] = 'Customer Edit';
        $types[self::CUSTOMER_DELETE] = 'Customer Delete';

        $types[self::FOLLOW_UP_ADD] = 'Follow up Add';
        $types[self::FOLLOW_UP_EDIT] = 'Follow up Edit';
        $types[self::FOLLOW_UP_DELETE] = 'Follow up Delete';

        $types[self::TRACKING_SYNC] = 'Tracking Sync';

        $types[self::VISIT_IN_PROCESS] = 'Visit In Process';

        $types[self::LIVE_TRACKING] = 'Live Tracking';

        return $types;
    }
}
