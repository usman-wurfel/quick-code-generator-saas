<?php

namespace App\Http\Controllers;

use App\Support\QRCodeScanManager;
use Illuminate\Http\Request;

class QRCodeScanController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function count(Request $request, QRCodeScanManager $scans)
    {
        $count = $scans->countScansOfUserQRCodes($request->user());

        return compact('count');
    }
}
