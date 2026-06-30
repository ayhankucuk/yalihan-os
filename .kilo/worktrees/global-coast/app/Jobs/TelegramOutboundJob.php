namespace App\Jobs;

use App\Models\TelegramNotification;
use App\Services\Notification\TelegramOutboundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class TelegramOutboundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    protected $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(TelegramNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\Notification\TelegramOutboundService $service): void
    {
        // ✅ SAB Idempotency Patch: Prevent duplicate execution via Cache lock
        $idempotencyKey = 'telegram_job_idempotency_' . $this->notification->id;

        if (\Illuminate\Support\Facades\Cache::has($idempotencyKey)) {
            \Illuminate\Support\Facades\Log::warning('TelegramOutboundJob: Duplicate execution prevented by Idempotency Key.', [
                'notification_id' => $this->notification->id,
            ]);
            return;
        }

        // Lock for 1 hour to ensure long retries don't double trigger
        \Illuminate\Support\Facades\Cache::put($idempotencyKey, true, now()->addHour());

        try {
            // Actual API call logic will be implemented in future phases
            // E.g., Http::post(config('services.telegram.bot_url'), [...])

            // Simulating success
            $service->markAsSent($this->notification);

        } catch (Exception $e) {
            // Explicitly clearing lock ONLY IF it's an actionable failure (e.g. timeout),
            // but for SAB zero-tolerance we keep the lock and let the system decide
            // if a manual retry is needed via specialized commands.
            $service->markAsFailed($this->notification, $e->getMessage());
            throw $e;
        }
    }
}
