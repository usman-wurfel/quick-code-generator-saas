<?php

namespace App\Http\Controllers;

use App\Events\FileDeleted;
use App\Interfaces\FileManager;
use App\Models\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FilesController extends Controller
{
    public function store(Request $request, FileManager $files)
    {
        return $files->store($request);
    }

    public function resource(Request $request, File $file, FileManager $files)
    {
        try {
            return $files->resource($request, $file);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            abort(404, 'File not found');
        }
    }

    public function show(File $file)
    {
        return $file;
    }

    public function destroy(File $file, FileManager $files)
    {
        $files->delete($file);

        event(new FileDeleted($file));

        return $file;
    }
}
