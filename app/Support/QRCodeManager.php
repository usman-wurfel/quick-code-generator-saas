<?php

namespace App\Support;

use App\Events\ShouldSaveQRCodeVariants;
use App\Interfaces\FileManager;
use App\Interfaces\UserManager;
use App\Models\File;
use App\Models\LeadForm;
use App\Models\QRCode;
use App\Models\QRCodeRedirect;
use App\Models\QRCodeScan;
use App\Models\QRCodeWebPageDesign;
use App\Models\User;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class QRCodeManager
{
    use WriteLogs;

    private UserManager $users;

    private FileManager $files;

    private QRCodeWebPageDesignManager $webpageDesignManager;

    private FolderManager $folders;

    public function __construct()
    {
        $this->users = app(UserManager::class);

        $this->files = app(FileManager::class);

        $this->webpageDesignManager = app(QRCodeWebPageDesignManager::class);

        $this->folders = app(FolderManager::class);
    }

    public function createdBySubUser(QRCode $qrcode, User $user)
    {
        $found = $user->sub_users->pluck('id')
            ->first(fn ($id) => $id == $qrcode->user_id);

        return !empty($found);
    }

    public function copy(QRCode $qrcode): QRCode
    {
        $copier = new QRCodeCopier();

        return $copier->copy($qrcode);
    }

    public function deleteMany(array $qrcodeIds)
    {
        $redirectIds = QRCodeRedirect::whereIn('qrcode_id', $qrcodeIds)->pluck('id');

        $scanIds = QRCodeScan::whereIn('qrcode_id', $qrcodeIds)
            ->orWhereIn('qrcode_redirect_id', $redirectIds)
            ->pluck('id');

        DB::table('qrcode_scans')->whereIn('id', $scanIds)->delete();

        DB::table('qrcode_redirects')->whereIn('id', $redirectIds)->delete();

        DB::table('qrcodes')->whereIn('id', $qrcodeIds)->delete();
    }

    public function delete(QRCode $qrcode)
    {
        return $this->deleteMany([$qrcode->id]);
    }

    /** 
     * @param User actor the user who is requesting the qr code  
     * @param User qrcodeMaker count qr codes which is created by spcified qrcodeMaker, can be null if the actor is permitted to qrcode.list-all.
     * @param string qrcodeType qrcode type to search for, null means all qrcodes would be counted.
     **/
    public function getQRCodeCount(User $actor, User $qrcodeMaker = null, $qrcodeType = null)
    {
        $query = $this->buildUserSearchQuery($actor, $qrcodeMaker);

        if (!empty($qrcodeType) && is_string($qrcodeType)) {
            $query->where('type', $qrcodeType);
        }

        if (!empty($qrcodeType) && is_array($qrcodeType)) {
            $query->whereIn('type', $qrcodeType);
        }

        return $query->count();
    }

    protected function buildUserSearchQuery(User $actor, User $qrcodeMaker = null)
    {
        $query = QRCode::query();

        $this->applyAdminSearchQuery($query, $actor, $qrcodeMaker);

        $this->applyOwnerSearchQuery($query, $actor, $qrcodeMaker);

        return $query;
    }

    private function applyOwnerSearchQuery(Builder $query, User $actor, User $qrcodeMaker = null)
    {
        if ($actor->permitted('qrcode.list-all')) return;

        if (!$qrcodeMaker) {
            $qrcodeMaker = $actor;
        }

        if ($qrcodeMaker->id != $actor->id) {
            throw new InvalidArgumentException('User does not have the ability to qrcode.list-all and is trying to count QR Codes that are made by another user.');
        }

        $ids = $this->users->getUserIdsOnTheSameSubscription($qrcodeMaker);

        $query->whereIn('user_id', $ids);
    }

    private function applyAdminSearchQuery(Builder $query, User $actor, User $qrcodeMaker = null)
    {
        if (!$actor->permitted('qrcode.list-all')) return;

        if (!$qrcodeMaker) return;

        $ids = $this->users->getUserIdsOnTheSameSubscription($qrcodeMaker);

        $query->whereIn('user_id', $ids);
    }

    public function archive(QRCode $qrcode, $archived)
    {
        $qrcode->archived = $archived;

        if ($archived) {
            $qrcode->folder_id = null;
        }

        $qrcode->save();

        return $qrcode;
    }

    public function changeUser(QRCode $qrcode, $userId)
    {
        $qrcode->user_id = $userId;

        $qrcode->save();

        $files = $this->getQRCodeFiles($qrcode);

        $files->each(function ($file) use ($userId) {
            $file->user_id = $userId;
            $file->save();
        });

        $design = $this->webpageDesignManager->getDesign($qrcode);

        $leadFormId = @$design->design['lead_form_id'];

        if ($leadFormId) {

            $leadForm = LeadForm::find($leadFormId);

            $leadForm->user_id = $userId;

            $leadForm->save();
            //
        }

        $this->folders->resetQRCodeFolder($qrcode);

        return $qrcode;
    }

    private function getQRCodeFiles(QRCode $qrcode)
    {
        $design = QRCodeWebPageDesign::where('qrcode_id', $qrcode->id)->first();

        return File::where(function ($query) use ($qrcode) {
            $query->where('attachable_id', $qrcode->id);

            $query->where('attachable_type', $qrcode::class);
        })->orWhere(function ($query) use ($design) {

            if (!$design) return;

            $query->where('attachable_id', $design->id);

            $query->where('attachable_type', $design::class);
        })->get();
    }

    private function changePinCode(QRCode $qrcode, $pincode = null)
    {
        $qrcode->pincode = $pincode;

        $qrcode->save();

        $this->regenerateQRCodeSVGFile($qrcode);

        return [
            'success' => true
        ];
    }

    private function resetPinCode(QRCode $qrcode)
    {
        return $this->changePinCode($qrcode);
    }

    private function regenerateQRCodeSVGFile(QRCode $qrcode)
    {
        event(new ShouldSaveQRCodeVariants($qrcode));
    }

    public function setPincode(QRCode $qrcode, $pincode)
    {
        if (empty($pincode)) {
            return $this->resetPinCode($qrcode);
        }

        if (!preg_match('/^\d{5}$/', $pincode)) {
            return [
                'success' => false,
                'message' => t('PIN Code must be 5 digits')
            ];
        }

        return $this->changePinCode($qrcode, $pincode);
    }
}
