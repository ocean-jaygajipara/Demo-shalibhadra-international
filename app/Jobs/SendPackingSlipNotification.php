<?php

namespace App\Jobs;

use App\Helpers\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPackingSlipNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    protected $notificationData;

    public function __construct(array $notificationData)
    {
        Log::info('🔷 Job Constructor Called', [
            'user_id' => $notificationData['user_id'] ?? 'N/A',
            'time' => now()->toDateTimeString(),
            'data_keys' => array_keys($notificationData)
        ]);

        $this->notificationData = $notificationData;
    }

    public function handle()
    {
        Log::info('🔵 ===== JOB HANDLE STARTED =====', [
            'user_id' => $this->notificationData['user_id'] ?? 'N/A',
            'time' => now()->toDateTimeString(),
            'job_id' => $this->job->getJobId() ?? 'N/A',
            'attempts' => $this->attempts()
        ]);

        try {
            Log::info('Notification data received in handle', [
                'data' => $this->notificationData
            ]);

            Log::info('About to call Helper::sendPushNotification');
            Helper::sendPushNotificationForPacking($this->notificationData);

            Log::info('✅ Helper::sendPushNotification completed successfully', [
                'user_id' => $this->notificationData['user_id']
            ]);

        } catch (\Exception $e) {
            throw $e; // Re-throw to trigger retry
        }

        Log::info('🔵 ===== JOB HANDLE COMPLETED =====', [
            'user_id' => $this->notificationData['user_id'] ?? 'N/A'
        ]);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('💀 Job FAILED after all retries', [
            'user_id' => $this->notificationData['user_id'] ?? null,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }
}
