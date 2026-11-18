<?php

use App\Http\Controllers\PaymentProcessorController;
use App\Support\AI\QuickQrArtWebhookHandler;
use App\Support\PaymentProcessors\PaymentProcessorManager;
use Illuminate\Support\Facades\Route;;

Route::post('/{slug}', [PaymentProcessorController::class, 'handleWebhook'])
    ->where('slug', sprintf(
        '^(%s).*',
        PaymentProcessorManager::getSlugs()->join('|')
    ));



Route::get('/{slug}', [PaymentProcessorController::class, 'getWebhook'])
    ->where('slug', sprintf(
        '^(%s).*',
        PaymentProcessorManager::getSlugs()->join('|')
    ));


Route::post('quickqrart', [QuickQrArtWebhookHandler::class, 'handle']);
