<?php

namespace App\Http\Controllers;

use App\Models\QRCodeRedirect;

use Illuminate\Http\Request;

use App\Interfaces\DeviceInfo;

use App\Interfaces\SubscriptionManager;
use App\Models\QRCode;
use App\Models\User;
use App\Support\QRCodeScanManager;
use App\Support\QRCodeTypes\Interfaces\PincodeProtectedType;
use App\Support\QRCodeTypes\Interfaces\ShouldImmediatlyRedirectToDestination;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Throwable;

class QRCodeRedirectController extends Controller
{
    private SubscriptionManager $subscriptionManager;

    private QRCodeScanManager $scanManager;

    public const SCAN_PREFIX = ['scan', 's'];

    public static function bindRoutes()
    {
        Route::get('/scan/{slug}', [static::class, 'index']);

        Route::get('/s/{slug}', [static::class, 'index']);

        Route::post('/s/{slug}', [static::class, 'index']);
    }

    public static function serveQRCode(QRCode $qrcode)
    {
        $redirect = $qrcode->redirect;

        return static::serveSlug($redirect->slug);
    }

    public static function serveSlug(string $slug)
    {
        return app()->call(static::class . '@index', [
            'slug' => $slug,
            'request' => request(),
        ]);
    }

    public function __construct()
    {
        $this->subscriptionManager = app(SubscriptionManager::class);
        $this->scanManager = app(QRCodeScanManager::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(
        Request $request,
        DeviceInfo $info,
        $slug,
    ) {
        if ($info->isBot()) {
            abort(403);
        }

        $redirect = QRCodeRedirect::whereSlug($slug)->first();

        if (!$redirect) {
            abort(404);
        }

        $user = $redirect->qrcode->user;

        $this->enforceSubscriptionRules($user);

        if ($redirect->qrcode->archived) {
            abort(403, t('QR code is archived.'));
        }

        $this->collectScanDetails($redirect, $request);

        return $this->renderRedirect($redirect);
    }

    private function collectScanDetails(QRCodeRedirect $redirect, Request $request)
    {
        if ($request->boolean('preview')) return;

        try {
            $scan = $this->scanManager->collectScanDetails(
                $redirect->qrcode,
                $request
            );

            $scan->qrcode_redirect_id = $redirect->id;

            $scan->save();
        } catch (Throwable $th) {
            Log::error(sprintf(
                'Could not save scan details for user agent %s',
                @$_SERVER['HTTP_USER_AGENT']
            ));
        }
    }

    private function renderRedirect(QRCodeRedirect $redirect)
    {
        $type = $redirect->qrcode->resolveType();

        if ($type instanceof PincodeProtectedType) {
            if ($type->shouldProtectByPincode($redirect->qrcode)) {
                return $type->renderPincodeProtectionPage($redirect->qrcode);
            }
        }

        if ($type instanceof ShouldImmediatlyRedirectToDestination) {
            return redirect($redirect->destination);
        }

        try {
            return view('qrcode.types.' . $type::slug());
        } catch (Throwable $th) {
            return t('QR code has changed to a static type. (' . $type::slug() . ')');
        }
    }

    private function enforceSubscriptionRules(User $user)
    {
        if (
            !$this
                ->subscriptionManager
                ->shouldEnforceSubscriptionRules($user)
        ) {
            return;
        }

        if (!$this->subscriptionManager->userHasActiveSubscription($user)) {
            abort(403, t('Subscription is expired.'));
        }

        if ($this->subscriptionManager->userScanLimitReached($user)) {
            abort(403, t('Scan limit reached.'));
        }
    }
}
