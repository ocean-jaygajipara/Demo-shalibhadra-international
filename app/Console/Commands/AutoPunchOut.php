<?php

namespace App\Console\Commands;

use App\Helpers\Helper;
use App\Models\TeamAttendance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoPunchOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-punch-out';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto punch out team person if punchout is pending based on working_end_time time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {

            $now = Carbon::now();

            $teamAttendance_PunchIn = TeamAttendance::query();
            $teamAttendance_PunchIn = $teamAttendance_PunchIn->with(['team_person']);
            // $teamAttendance_PunchIn = $teamAttendance_PunchIn->whereDate('punch_in_time', $now->format("Y-m-d"));
            // $teamAttendance_PunchIn = $teamAttendance_PunchIn->whereDate('punch_in_time', "2025-05-24");
            $teamAttendance_PunchIn = $teamAttendance_PunchIn->whereNull('punch_out_time');

            Log::info("Punch Out Records   =>    " . Helper::interpolateQuery($teamAttendance_PunchIn?->toSql(), $teamAttendance_PunchIn->getBindings()));

            $teamAttendance_PunchIn = $teamAttendance_PunchIn->get();

            foreach ($teamAttendance_PunchIn as $record) {
                Log::info("L-47   =>   ". json_encode($record));
                $working_end_time = Carbon::parse($record?->team_person?->working_end_time)->format("His");
                Log::info("Auto Punch Out  46 =>    " . $now?->format("His") . ' ' . $working_end_time);

                if ($now?->format("His") >= $working_end_time) {
                    Log::info("Auto Punch Out   =>   " . $now?->format("His") . '  ' . $working_end_time);

                    $punchOut = TeamAttendance::where('id', $record?->id)->first();
                    if ($punchOut) {
                        $update = [];
                        $update['punch_out_time'] = Carbon::parse($now->format("Y-m-d") . ' ' . $record?->team_person?->working_end_time)->format("Y-m-d H:i:s");;
                        $update['punch_out_flag'] = 'auto';
                        $update['working_time'] =  Helper::getTimeDifference($punchOut->punch_in_time, $update['punch_out_time']);
                        $punchOut->update($update);
                    }
                } else {
                    Log::info("Auto Punch Out  58 =>    " . $now?->format("His") . ' ' . $working_end_time);
                }
            }
        } catch (\Exception $e) {
            Log::error('Auto Punch Out command failed: ' . $e->getMessage());
        }
    }
}
