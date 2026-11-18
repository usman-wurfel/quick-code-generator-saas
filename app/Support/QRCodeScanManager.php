<?php

namespace App\Support;

use App\Interfaces\UserManager;
use App\Models\QRCode;
use App\Models\QRCodeRedirect;
use App\Models\User;

use App\Models\QRCodeScan as QRCodeScanModel;
use App\Repositories\DeviceInfo;
use App\Support\MaxMind\MaxMindResolver;
use Illuminate\Http\Request;

class QRCodeScanManager
{
    private UserManager $users;

    public function __construct()
    {
        $this->users = app(UserManager::class);
    }

    public function countScansOfUserQRCodes(User $user)
    {
        $userIds = $this->users->getUserIdsOnTheSameSubscription($user);

        $qrcodesIds = QRCode::whereIn('user_id', $userIds)
            ->select('id')
            ->get()
            ->pluck('id');

        $redirectIds = QRCodeRedirect::whereIn('qrcode_id', $qrcodesIds)
            ->select('id')
            ->get()
            ->pluck('id');

        $scanCount = QRCodeScanModel::whereIn(
            'qrcode_redirect_id',
            $redirectIds
        )->count();

        return $scanCount;
    }

    public function saveScanDetails($qrcodeId, $ip, $overrides = [])
    {
        $info = new DeviceInfo();

        $scan = new QRCodeScanModel;

        $scan->user_agent = $info->getUserAgent();

        $scan->qrcode_id = $qrcodeId;

        $scan->ip_address = $ip;

        $scan->device_name = $info->getDeviceName();

        $scan->device_brand = $info->getDeviceBrand();

        $scan->device_model = $info->getDeviceModel();

        $scan->os_name = $info->getOSName();

        $scan->os_version = $info->getOSVersion();

        $scan->client_type = $info->getClientType();

        $scan->client_name = $info->getClientName();

        $scan->client_version = $info->getClientVersion();

        $resolver = new MaxMindResolver();

        $location = $resolver->resolve($scan->ip_address);

        if ($location) {
            $scan->fillLocationData($location);
        }

        $scan->calculateHour();

        if (!empty($overrides)) {
            $scan->forceFill($overrides);
        }

        $scan->save();

        return $scan;
    }

    public function collectScanDetails(QRCode $qrcode, Request $request)
    {
        $scan = $this->saveScanDetails($qrcode->id, $request->ip());

        $this->increaseScans($this->users->getClientUser($qrcode));

        return $scan;
    }

    public function getScansByUser(User $user)
    {
        $user = $this->users->getParentUser($user);

        $count = $user->getMeta($this->userScanMetaKey());

        if (empty($count)) {
            $count = $this->syncUserScanCount($user);
        }

        return $count;
    }

    private function syncUserScanCount(User $user)
    {
        $user = $this->users->getParentUser($user);

        $count = $this->countScansOfUserQRCodes($user);

        $user->setMeta($this->userScanMetaKey(), $count);

        return $count;
    }

    public function increaseScans(User $user)
    {
        $user = $this->users->getParentUser($user);

        $number = $this->getScansByUser($user);

        if (empty($number)) {
            $number = 0;
        }

        $user->setMeta($this->userScanMetaKey(), ++$number);

        return $number;
    }

    private function userScanMetaKey()
    {
        return 'number_of_scans';
    }
}
