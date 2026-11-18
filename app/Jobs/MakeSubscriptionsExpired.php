<?php

namespace App\Jobs;

use App\Interfaces\SubscriptionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeSubscriptionsExpired implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $subscriptions;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SubscriptionManager $subscriptions)
    {
        $this->subscriptions = $subscriptions;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->subscriptions->setExpiredSubscriptions();
    }
}
