<?php

namespace App\Repositories;

use App\Interfaces\FileManager as FileManagerInterface;
use App\Models\Config;
use App\Models\File;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use Throwable;

use Illuminate\Support\Str;

class FileManager implements FileManagerInterface
{
    use WriteLogs;

    private $rejectedExtensions = 'bash|sh|php';

    private $fileValidator;

    private static $_fs = null;

    private static function isLocal()
    {
        return static::getDriverName() === 'local';
    }

    private static function getDriverName()
    {
        return Config::get('app.storage_type') ?? 'local';
    }

    public static function fs(): FilesystemAdapter
    {
        if (!empty(static::$_fs)) {
            return static::$_fs;
        }

        $driver = static::getDriverName();

        if ($driver === 'local') {
            static::$_fs = Storage::disk('local');

            return static::$_fs;
        }

        $keys = [
            'key',
            'secret',
            'region',
            'bucket',
            'url',
            'endpoint',
        ];

        $configs = [
            'driver' => 's3',
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true)
        ];

        foreach ($keys as $key) {
            $configs[$key] = Config::get("filesystems.s3.$key");
        }

        static::$_fs = Storage::build($configs);

        return static::$_fs;
    }

    public function store(Request $request): File
    {
        $this->validate($request);

        $file = $this->save(
            name: $request->file('file')->getClientOriginalName(),
            type: $request->type,
            mime_type: $request->file('file')->getMimeType(),
            attachable_type: $request->attachable_type,
            attachable_id: $request->attachable_id,
            user_id: $request->user()->id,
        );

        $requestFile = $request->file('file');

        $path = $this->fs()->putFileAs(
            static::UPLOAD_DIR,
            $requestFile,
            $this->makeFileName($file, $requestFile->extension())
        );

        $file->path = $path;

        $file->save();

        $this->verifyFile($file);

        return $file;
    }

    private function verifyFile($file)
    {
        if (is_callable($this->fileValidator)) {

            try {
                call_user_func($this->fileValidator, $file);
            } catch (Throwable $th) {

                $validator = ValidatorFacade::make(compact('file'), [
                    'file' => 'required'
                ]);

                $validator->after(function ($validator) use ($th) {

                    $validator->errors()->add(
                        'file',
                        $th->getMessage()
                    );
                });

                $this->delete($file);

                $validator->validate();
            }
        }
    }

    private function makeFilePath(File $file, string $extension)
    {
        return $this::UPLOAD_DIR . '/' . $this->makeFileName($file, $extension);
    }

    private function makeFileName(File $file, $extension)
    {
        return $file->id . '.' . $extension;
    }

    public function save(
        $name,
        $type,
        $mime_type,
        $attachable_type,
        $attachable_id,
        $user_id,
        $extension = null,
        $data = null,
    ) {
        $file = new File(
            compact(
                'name',
                'type',
                'mime_type',
                'attachable_type',
                'attachable_id',
                'user_id'
            )
        );

        $file->save();

        if (!empty($data)) {
            $path = $this->makeFilePath($file, $extension);

            Storage::put($path, $data);

            $file->path = $path;

            $file->save();
        }

        return $file;
    }

    public function resource(Request $request, $file)
    {
        $fs = $this->fs();

        if ($this->isLocal())
            $stream = $fs->readStream($file->path);

        else
            $stream = fopen(
                $this->fs()->temporaryUrl(
                    $this->path($file),
                    Carbon::now()->addHour()
                ),
                'r'
            );

        return response()->stream(
            function () use ($stream) {
                while (ob_get_level() > 0) ob_end_flush();

                fpassthru($stream);

                fclose($stream);
            },
            200,
            [
                'Content-Type' => $request->mode === 'download' ? 'application/oct-stream' : $file->mime_type,

                'Content-disposition' => sprintf('inline; filename="%s"', $file->name),

                'Content-Length' => $this->fs()->size($file->path)
            ]
        );
    }

    public function validate(Request $request)
    {
        $data = array_merge($request->all(), ['file' => $request->file('file')]);

        $validator = ValidatorFacade::make($data, [
            'file' => 'file|required',
            'attachable_type' => 'required'
        ]);

        $this->validateExtension($request, $validator);

        $this->validateAttachableType($request, $validator);

        $validator->validate();
    }

    public function setFileValidator(callable $callback)
    {
        $this->fileValidator = $callback;
    }

    private function validateExtension(Request $request, Validator $validator)
    {
        if (empty($request->file('file'))) {
            return;
        }

        $extension = $request->file('file')->clientExtension();

        $rejected = preg_match('/' . $this->rejectedExtensions . '/', $extension);

        $validator->after(function ($validator) use ($rejected) {

            if ($rejected) {
                $validator->errors()->add(
                    'file',
                    'File extension is not supported!'
                );
            }
        });
    }

    private function validateAttachableType(Request $request, Validator $validator)
    {
        $validator->after(function ($validator) use ($request) {

            if (!class_exists($request->attachable_type)) {
                $validator->errors()->add(
                    'file',
                    'Invalid attachable type'
                );
            }
        });
    }

    public function delete(File $file)
    {
        if ($this->exists($file))
            $this->fs()->delete($file->path);

        $file->delete();
    }

    public function path(File $file)
    {
        try {
            return $this->fs()->path($file->path);
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function url(File $file)
    {
        $base = config('app.url');

        if (@$base[strlen($base) - 1] != '/') {
            $base = "$base/";
        }

        return $base . sprintf('api/files/%s/resource', $file->slug);
    }

    public function extension(File $file)
    {
        return pathinfo($this->path($file), PATHINFO_EXTENSION);
    }

    public function duplicate(File $file): File
    {
        $copy = $this->save(
            $file->name,
            $file->type,
            $file->mime_type,
            $file->attachable_type,
            $file->attachable_id,
            $file->user_id,
            $this->extension($file)
        );

        $copy->path = $this->makeFilePath($copy, $this->extension($file));

        $this->fs()->put(
            $copy->path,
            $this->raw($file)
        );

        $copy->save();

        return $copy;
    }

    public function exists($file): bool
    {
        $path = $file?->path;

        if (!$path) return false;

        return $this->fs()->exists($path);
    }

    public function write(File $file, $data)
    {
        return $this->fs()->put($file->path, $data);
    }

    public function raw(File $file)
    {
        if (!$this->exists($file)) return;

        return $this->fs()->get($file->path);
    }

    public function base64(File $file)
    {
        if (!$this->exists($file)) return '';

        $content = $this->raw($file);

        return base64_encode($content);
    }

    private function waitForTestFile($path)
    {
        $backoff = 0;
        while (!$this->fs()->get($path)) {
            if (5 < $backoff++) {
                return false;
            }
            sleep(1);
        }
    }

    public function testReadWrite()
    {
        $path = Str::random(5) . '.txt';

        $content = Str::random(50);

        $this->fs()->put($path, $content);

        try {
            if (!$this->fs()->exists($path)) {
                return false;
            }

            $this->waitForTestFile($path);

            $fileContent = $this->fs()->get($path);

            return $content === $fileContent;
        } catch (Throwable $th) {
            return false;
        } finally {
            try {
                $this->fs()->delete($path);
            } catch (Throwable $th) {
                //
            }
        }
    }

    public function useTempLocalFile(File $file, callable $callback)
    {
        $content = $this->raw($file);

        $name = uniqid(time(), true);

        $path = storage_path($name);

        file_put_contents($path, $content);

        $callback($path);

        unlink($path);
    }
}
