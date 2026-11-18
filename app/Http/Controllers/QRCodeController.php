<?php

namespace App\Http\Controllers;

use App\Events\ShouldSaveQRCodeVariants;
use App\Http\Requests\QRCodeRequest;

use App\Http\Requests\ArchiveQRCodeRequest;
use App\Interfaces\FileManager;
use App\Models\QRCode;

use Illuminate\Http\Request;

use App\Interfaces\QRCodeGenerator;

use App\Models\User;
use App\Support\Billing\AccountCreditBillingManager;
use App\Support\Billing\BillingManager;
use App\Support\CompatibleSVG\CompatibleSVGManager;
use App\Support\FolderManager;
use App\Support\QRCodeManager;
use App\Support\QRCodeRedirectManager;
use App\Support\QRCodeReports\ReportsManager;
use App\Support\QRCodeScanManager;
use App\Support\QRCodeSearchBuilder;
use App\Support\QRCodeStorage;
use App\Support\QRCodeTypes\QRCodeTypeManager;
use App\Support\QRCodeTypes\ViewComposers\Components\QRCodeFavicon\FileServer;
use App\Support\QRCodeWebPageDesignManager;
use App\Support\System\Traits\WriteLogs;
use Throwable;

class QRCodeController extends Controller
{
    use WriteLogs;

    private QRCodeGenerator $generator;
    private QRCodeManager $qrcodeManager;
    private QRCodeWebPageDesignManager $webpageDesignManager;
    private QRCodeTypeManager $typeManager;
    private FolderManager $folders;
    private QRCodeScanManager $scans;

    private BillingManager $billingManager;

    private AccountCreditBillingManager $accountCredit;

    public function __construct(
        QRCodeGenerator $generator,
        QRCodeManager $qrcodeManager,
        QRCodeWebPageDesignManager $webpageDesignManager,
        QRCodeTypeManager $typeManager,
        FolderManager $folders,
        QRCodeScanManager $scans,
        BillingManager $billingManager,
        AccountCreditBillingManager $accountCredit,
    ) {
        $this->generator = $generator;
        $this->qrcodeManager = $qrcodeManager;
        $this->webpageDesignManager = $webpageDesignManager;
        $this->typeManager = $typeManager;
        $this->folders = $folders;
        $this->scans = $scans;
        $this->billingManager = $billingManager;
        $this->accountCredit = $accountCredit;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->boolean('list_all')) {
            if (!$request->user()->isSuperAdmin()) {
                return [];
            }

            return QRCode::orderBy('id', 'desc')->select('id', 'name')->get();
        }

        return (new QRCodeSearchBuilder)
            ->byUser($request->user())
            ->with('redirect')
            ->forQrCodesCreatedBy($request->user_id)
            ->archived($request->boolean('search_archived'))
            ->name($request->search_name)
            ->type($request->search_type)
            ->folder($request->folder_id)
            ->applyClientRestrictions()
            ->applySubUserRestrictions()
            ->sort()
            ->paginationPath($request->input('path'))
            ->paginate();
    }

    public function show(QRCode $qrcode)
    {
        return $qrcode;
    }

    public function showRedirect(QRCode $qrcode)
    {
        return $qrcode->redirect;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\QRCodeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(QRCodeRequest $request)
    {
        $this->typeManager->find(
            $request->input('type')
        )->validate($request);

        $qrcode = new QRCode($request->all());

        $qrcode->user_id = $request->user()->id;

        if ($request->user()->is_sub) {
            $qrcode->folder_id = $this->folders->getSubuserFolders($request->user())->get(0)->id;
        }

        $qrcode->save();

        if ($this->billingManager->isAccountCreditBilling()) {
            $this
                ->accountCredit
                ->forUser(request()->user())
                ->deductQRCodePrice($qrcode);
        }

        return $qrcode;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\QRCodeRequest  $request
     * @param  \App\Models\QRCode  $qrcode
     * @return \Illuminate\Http\Response
     */
    public function update(QRCodeRequest $request, QRCode $qrcode)
    {
        $this->typeManager->find(
            $request->input('type')
        )->validate($request);

        $qrcode->fill($request->all())->save();

        return $qrcode;
    }

    public function updateRedirect(Request $request, QRCode $qrcode)
    {
        $redirect = $qrcode->redirect;

        if (!$redirect) {
            abort(404);
        }

        $qrcodeRedirectManager = new QRCodeRedirectManager();

        $result = $qrcodeRedirectManager->updateRedirect($redirect, $request->all());

        event(new ShouldSaveQRCodeVariants($qrcode));

        $qrcode->touch();

        return $result;
    }

    public function archive(ArchiveQRCodeRequest $request, QRCode $qrcode)
    {
        return $this->qrcodeManager->archive($qrcode, $request->boolean('archived'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\QRCode  $qrcode
     * @return \Illuminate\Http\Response
     */
    public function destroy(QRCode $qrcode, QRCodeManager $qrcodeManager)
    {
        $qrcodeManager->delete($qrcode);

        return $qrcode;
    }

    public function storeLogo(Request $request, QRCode $qrcode, FileManager $files)
    {
        $request->merge([
            'attachable_type' => QRCode::class,
            'attachable_id' => $qrcode->id,
            'type' => FileManager::FILE_TYPE_QRCODE_LOGO
        ]);

        $result = $files->store($request);

        $qrcode->refresh();

        event(new ShouldSaveQRCodeVariants($qrcode));

        $qrcode->touch();

        return $result;
    }

    public function uploadDesignFile(Request $request, QRCode $qrcode, FileManager $files)
    {
        $request->merge([
            'attachable_type' => QRCode::class,
            'attachable_id' => $qrcode->id,
            'type' => FileManager::FILE_TYPE_QRCODE_DESIGN_FILE
        ]);

        $name = $request->name && $request->name != 'undefined' ? $request->name : null;

        $result = $files->store($request);

        if ($name) {
            $merged = array_merge(
                (array) $qrcode->design,
                [
                    $request->name => $result->id
                ]
            );

            $qrcode->design = $merged;

            $qrcode->save();
        }

        return $result;
    }

    public function storeForegroundImage(Request $request, QRCode $qrcode, FileManager $files)
    {
        $request->merge([
            'attachable_type' => QRCode::class,
            'attachable_id' => $qrcode->id,
            'type' => FileManager::FILE_TYPE_QRCODE_FOREGROUND_IMAGE
        ]);

        $result = $files->store($request);

        event(new ShouldSaveQRCodeVariants($qrcode));

        $qrcode->touch();

        return $result;
    }

    public function preview(Request $request)
    {
        try {
            $this->generator->initFromRequest($request);

            return $this->generator->respondInline();
        } catch (Throwable $th) {

            $this->logDebugf($th->getMessage());

            return 'Invalid parameters';
        }
    }

    public function report(Request $request, QRCode $qrcode, $slug)
    {
        return ReportsManager::report($slug)
            ->of($qrcode)
            ->from($request->from)
            ->to($request->to)
            ->generate();
    }

    public function copy(QRCode $qrcode)
    {
        return $this->qrcodeManager->copy($qrcode);
    }

    public function getQRCodeScanCount(Request $request)
    {
        return [
            'count' => $this->scans->getScansByUser($request->user())
        ];
    }

    public function getQRCodeCount(Request $request)
    {
        $types = [];

        if (!empty($request->qrcode_type))
            $types = explode(',', $request->qrcode_type);

        return [
            'count' => $this->qrcodeManager->getQRCodeCount(
                actor: $request->user(),
                qrcodeMaker: User::find($request->user_id),
                qrcodeType: $types
            )
        ];
    }

    public function getWebPageDesign(QRCode $qrcode)
    {
        return $this->webpageDesignManager->getDesignOrCreateNewDesignIfNeeded($qrcode);
    }

    public function saveWebPageDesign(QRCode $qrcode, Request $request)
    {
        return $this->webpageDesignManager->saveDesign(
            $qrcode,
            $request->all()
        );
    }

    public function storeWebPageDesignFile(Request $request, QRCode $qrcode, FileManager $files)
    {
        $design = $this->webpageDesignManager->saveDesign($qrcode, []);

        $request->merge([
            'attachable_type' => $design::class,
            'attachable_id' => $design->id,
            'type' => FileManager::FILE_TYPE_QRCODE_WEBPAGEDESIGN
        ]);

        $result = $files->store($request);

        return $result;
    }

    public function changeQRCodeUser(QRCode $qrcode, Request $request)
    {
        return $this->qrcodeManager->changeUser(
            $qrcode,
            $request->input('user_id')
        );
    }

    public function setPincode(QRCode $qrcode, Request $request)
    {
        return $this->qrcodeManager->setPincode($qrcode, $request->input('pincode'));
    }

    public function compatibleSVG(QRCode $qrcode)
    {
        $manager = new CompatibleSVGManager($qrcode);

        return [
            'svg' => $manager->render()
        ];
    }

    public function serveSvgFile(QRCode $qrcode)
    {
        return QRCodeStorage::ofQRCode($qrcode)->serveSvgFile();
    }

    public function serveFavicon(QRCode $qrcode, $fileName)
    {
        $favicon = new FileServer($qrcode);

        return $favicon->serve($fileName);
    }
}
