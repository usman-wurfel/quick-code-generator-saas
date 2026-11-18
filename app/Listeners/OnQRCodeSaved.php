<?php

namespace App\Listeners;

use App\Events\QRCodeSaved;
use App\Events\ShouldSaveQRCodeVariants;
use App\Support\QRCodeRedirectManager;
use Illuminate\Support\Facades\Log;

class OnQRCodeSaved
{
    private QRCodeSaved $event;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\QRCodeSaved  $event
     * @return void
     */
    public function handle(QRCodeSaved $event)
    {
        $this->event = $event;

        /** @var QRCodeRedirectManager */
        $qrcodeRedirectManager = app(QRCodeRedirectManager::class);

        $qrcodeRedirectManager->updateDestinationIfNeeded($event->qrcode);

        if (!$this->shouldRun()) return;

        event(new ShouldSaveQRCodeVariants($event->qrcode));
    }

    private function shouldRun()
    {
        if (app()->runningInConsole() && app()->environment() === 'local') {
            return env('DEV_SAVE_QRCODE_VARIANTS');
        }

        $qr = $this->event->qrcode;

        $wasRecentlyCreated = $qr->wasRecentlyCreated;

        $wasDataOrDesignChanged = $qr->wasChanged(['data', 'design']);

        $dataIsEmpty = empty(array_values((array)$qr->data));

        return $wasRecentlyCreated || $wasDataOrDesignChanged || $dataIsEmpty;
    }
}
