<?php

namespace App\Console\Commands;

use App\Helpers\Helper;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\TeamPerson;
use App\Models\TeamRole;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class SendFollowupFirebaseReminders extends Command
{

    protected $signature = 'app:send-followup-firebase-reminders';
    protected $description = 'Send Firebase Push Notification reminders before follow-up time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        Log::info('Follow-up reminder started at: ' . $now);

        $intervals = [
            15 => 'notified_at_15',
            10 => 'notified_at_10',
            5  => 'notified_at_5',
        ];

        // $intervals = [];
        // for ($i = 1; $i <= 15; $i++) {
        //     $intervals[$i] = "notified_at_$i";
        // }

        foreach ($intervals as $minutes => $column) {
            $now = now()->setTimezone('Asia/Kolkata');
            $targetTime = $now->copy()->addMinutes($minutes);
            $from = $targetTime->copy()->subSeconds(30); // 30 sec before
            $to   = $targetTime->copy()->addSeconds(30); // 30 sec after

            Log::info("Checking followups with 'date_time' between {$from} and {$to} for {$minutes}-minute reminder");


            $followups = Followup::whereBetween('date_time', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
                ->where($column, false)
                //->where('platform', 'Web')
                ->where(function ($query) {
                    $query->whereNull('followup_action')
                        ->orWhere('followup_action', '!=', 'End Follow-up');
                })
                ->get();

            // $sql = $followups->toSql();
            // $bindings = $followups->getBindings();

            // echo "SQL Query: " . $sql . PHP_EOL;
            // echo "Bindings: " . json_encode($bindings) . PHP_EOL;
            // exit;

            Log::info("Found " . $followups->count() . " follow-ups for {$minutes}-minute reminder");

            foreach ($followups as $followup) {
                $teamPerson = TeamPerson::find($followup->created_by);

                if ($teamPerson) {
                    if ($followup->inquiry_id) {
                        $inquiryData = Inquiry::find($followup->inquiry_id);
                        if ($inquiryData && $inquiryData->inquiry_no) {
                            $ref = $inquiryData->inquiry_no;
                        }
                    } elseif ($followup->quotation_id) {
                        $quotationData = Quotation::find($followup->quotation_id);
                        if ($quotationData && $quotationData->quotation_no) {
                            $ref = $quotationData->quotation_no;
                        }
                    } elseif ($followup->customer_id) {
                        $customerData = Customer::find($followup->customer_id);
                        if ($customerData && $customerData->name) {
                            $ref = $customerData->name;
                        }
                    }

                    $title = 'Upcoming Follow-Up';
                    if ($ref) {
                        $body = "You have a follow-up for {$ref} scheduled in {$minutes} minutes.";
                    } else {
                        $body = "You have a follow-up scheduled in {$minutes} minutes.";
                    }

                    Log::info("Sending notification for Followup ID: {$followup->id} to TeamPerson ID: {$teamPerson->id}");

                    Helper::sendPushNotification([
                        "company_id"     => $followup->company_id,
                        "user_id"        => $teamPerson->id,
                        "user_type"      => 'Team',
                        "title"          => $title,
                        "body"           => $body,
                        "module_name"    => 'Follow Up',
                        "module_id"      => $followup->id,
                        "module_action"  => 'add',
                        "status"         => 'success'
                    ]);

                    $followup->update([$column => true]);
                    Log::info("Marked {$column} as true for Followup ID: {$followup->id}");
                } else {
                    Log::warning("Team person not found or missing device token for Followup ID: {$followup->id}");
                }
            }
        }

        Log::info('Follow-up reminder completed.');
    }
}
