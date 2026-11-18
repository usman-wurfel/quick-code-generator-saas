<?php

namespace App\Notifications;

use App\Interfaces\CurrencyManager;
use App\Interfaces\FileManager;
use App\Models\Transaction;
use Illuminate\Notifications\Messages\MailMessage;

use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TransactionNotification extends Notification
{
    protected Transaction $transaction;

    protected FileManager $files;

    protected CurrencyManager $currency;

    public function __construct()
    {
        $this->files = app(FileManager::class);

        $this->currency = app(CurrencyManager::class);
    }



    protected function renderTransactionDetails(MailMessage $message)
    {
        $lines = [
            sprintf(t('Transaction ID: %s.'), $this->transaction->id),
            sprintf(t('Sent by: %s.'), $this->transaction->subscription->user->name),
            sprintf(
                t('Amount: %s%s.'),
                $this->currency->enabledCurrency()->currency_code,
                $this->transaction->amount
            ),
            sprintf(t('Date: %s.'), $this->transaction->created_at),
            sprintf(
                t('Payment Proof: <a href="%s" target="_blank">click here to view</a>.'),
                $this->files->url($this->transaction->payment_proof)
            )
        ];

        foreach ($lines as $line) {
            $message->line(new HtmlString($line));
        }

        return $message;
    }
}
