<?php

namespace App\Repositories;

use App\Events\TranslationActivated;
use App\Interfaces\FileManager;
use App\Interfaces\ModelSearchBuilder;
use App\Interfaces\TranslationManager as TranslationManagerInterface;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TranslationManager implements TranslationManagerInterface
{
    private ModelSearchBuilder $search;

    private FileManager $files;

    private static $activeTranslation;

    private static $currentTranslation = null;

    private static $triedToGetCurrentTranslation = false;

    private static $multilingualEnabled = null;

    public function __construct(ModelSearchBuilder $search, FileManager $files)
    {
        $this->search = $search;

        $this->files = $files;
    }

    public function setCurrentTranslation($locale)
    {
        $translation = Translation::whereLocale($locale)
            ->where('is_active', true)->first();

        if (!$translation) abort(404);

        // 10 years expires
        Cookie::queue('locale', $locale, time() + (10 * 365 * 24 * 60 * 60));
    }

    private static function loadCurrentTranslation()
    {
        if (static::$triedToGetCurrentTranslation) return;

        if (static::$activeTranslation) return;

        try {
            $data = static::loadCurrentTranslationFile();

            static::$activeTranslation = json_decode($data, true);
        } catch (\Throwable $th) {
        }

        static::$triedToGetCurrentTranslation = true;
    }

    public static function t(string $text)
    {
        static::loadCurrentTranslation();

        if (!empty(static::$activeTranslation[$text])) {
            return static::$activeTranslation[$text];
        }

        return $text;
    }

    public function search(Request $request)
    {
        $paginate = true;

        if ($request->paginate === 'false') {
            $paginate = false;
        }

        $search = $this->search->init(
            Translation::class,
            $request
        )
            ->inColumn('name')
            ->search();

        if ($request->boolean('is_active')) {
            $search->query()->where('is_active', true);
        }

        if ($paginate) {
            return $search->paginate()
                ->through(function ($row) {
                    $row->completeness = $this->completeness($row);
                    return $row;
                });
        }

        return $search->query()->get();
    }

    public function load(Translation $translation)
    {
        $text = $this->files->raw($translation->file);

        return empty($text) ? '[]' : $text;
    }

    public function loadTranslationArray(Translation $translation): array
    {
        return json_decode($this->load($translation), true);
    }

    /**
     * Updates the translation file with $data
     */
    public function write(string $data, Translation $translation)
    {
        $this->files->write($translation->file, $data);

        $translation->touch();
    }

    public function writeTranslationArray(array $data, Translation $translation)
    {
        return $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $translation);
    }

    public function save($data, ?Translation $translation = null)
    {
        if ($translation?->is_default) return $translation;

        if (!$translation) {
            $translation = new Translation();
        }

        $translation->fill($data);

        $translation->save();

        return $translation;
    }

    public function delete(Translation $translation)
    {
        if ($translation->is_default) return $translation;

        return $translation->delete();
    }

    public function completeness(Translation $translation)
    {
        if (!$translation->file) return 0;

        if ($translation->is_default) return 100;

        $json = $this->load($translation);

        $data = json_decode($json, true);

        if (empty($data)) {
            return 0;
        }

        $completed = array_reduce(
            $data,
            fn ($count, $value) => empty($value) ? $count : $count + 1,
            0
        );

        return floor($completed / count($data) * 100);
    }

    public function verifyTranslationFile(Translation $translation)
    {
        $json = $this->load($translation);

        $data = json_decode($json, true);

        if (empty($data)) {
            throw new InvalidArgumentException("Translation file has invalid json data.");
        }

        $defaultTranslationJson = $this->loadDefaultTranslation();

        $defaultData = json_decode($defaultTranslationJson, true);

        $notFoundKeys = [];

        foreach (array_keys($defaultData) as $key) {
            $found = array_filter(array_keys($data), fn ($dataKey) => $key === $dataKey);

            if (!$found) {
                $notFoundKeys[] = $key;
            }
        }

        if (!empty($notFoundKeys)) {
            throw new InvalidArgumentException(
                "The following translation keys was not found, please download the default English file and copy missing keys.\n- " . implode("\n- ", $notFoundKeys)
            );
        }
    }

    private function loadDefaultTranslation()
    {
        $defaultTranslation = Translation::where('is_default', true)->first();

        return $this->load($defaultTranslation);
    }

    public function getCurrentTranslation(): Translation
    {
        if ($this::$currentTranslation) {
            return $this::$currentTranslation;
        }

        $translation = null;

        if ($locale = Cookie::get('locale')) {
            $translation = Translation::whereLocale($locale)
                ->where('is_active', true)->first();
        }

        if (!$translation) {
            try {
                $translation = Translation::where('is_main', true)->first();
            } catch (\Throwable $th) {
                //
            }
        }

        if (!$translation) {
            $translation = Translation::where('is_active', true)->first();
        }

        if (!$translation) {
            $translation = Translation::where('is_default', true)->first();
        }

        $this::$currentTranslation = $translation;

        return $this::$currentTranslation;
    }

    public static function loadCurrentTranslationFile()
    {
        try {
            $manager = app(static::class);

            $translation = $manager->getCurrentTranslation();

            return $manager->load($translation);
        } catch (\Throwable $th) {

            if (config('app.installed'))
                Log::error($th->getMessage());

            return '{}';
        }

        return '{}';
    }

    public function upload(Request $request, Translation $translation)
    {
        $request->merge([
            'attachable_type' => Translation::class,
            'attachable_id' => $translation->id,
            'type' => FileManager::FILE_TYPE_TRANSLATION
        ]);

        $this->files->setFileValidator(function () use ($translation) {
            $translation->refresh();
            $this->verifyTranslationFile($translation);
        });

        $result = $this->files->store($request);

        return $result;
    }

    public function deActivate(Translation $translation)
    {
        if ($translation->is_active) {

            return $this->toggleActivate($translation);
        }
    }

    public function activate(Translation $translation)
    {
        if (!$translation->is_active) {

            return $this->toggleActivate($translation);
        }
    }

    public function toggleActivate(Translation $translation)
    {
        if (app()->environment() === 'demo') {
            return [
                'error' => 'Cannot activate translation in demo'
            ];
        }

        $translation->is_active = !$translation->is_active;

        if (!$translation->is_active) {
            $translation->is_main = false;
        }

        $translation->save();

        if ($translation->is_active) {
            event(new TranslationActivated($translation));
        }

        return $translation;
    }

    public function setMain(Translation $translation)
    {
        if (app()->environment() === 'demo') {
            return [
                'error' => 'Disabled in demo'
            ];
        }

        if (!$translation->is_active) {
            return [
                'error' => 'Translation must be activated first'
            ];
        }

        Translation::get()->each(function ($t) {
            $t->is_main = false;
            $t->save();
        });

        $translation->is_main = true;

        $translation->save();

        return $translation;
    }

    public function getDefaultTranslation(): Translation
    {
        return Translation::where('is_default', true)->first();
    }

    public function multilingualEnabled(): bool
    {
        if ($this::$multilingualEnabled != null) {
            return $this::$multilingualEnabled;
        }

        $this::$multilingualEnabled = Translation::where('is_active', true)->count() > 1;

        return $this::$multilingualEnabled;
    }
}
