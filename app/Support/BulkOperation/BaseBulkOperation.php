<?php

namespace App\Support\BulkOperation;

use App\Interfaces\FileManager;
use App\Models\BulkOperationInstance;
use App\Models\BulkOperationResult;
use App\Notifications\Dynamic\BulkOperationCompleted;
use Illuminate\Http\Request;
use Throwable;

abstract class BaseBulkOperation
{
    protected FileManager $files;

    public function __construct()
    {
        $this->files = app(FileManager::class);
    }

    public function createInstance(Request $request): BulkOperationInstance
    {
        $instance = $this->createInstanceModel($request);

        $this->store($request, $instance);

        return $instance;
    }

    protected abstract function store(Request $request, BulkOperationInstance $instance);

    public abstract function run(BulkOperationInstance $instance): static;

    protected function createInstanceModel(Request $request)
    {
        $operation = new BulkOperationInstance();

        $operation->type = $this->type();

        $operation->name = $this->name();

        $operation->user_id = $request->user()->id;

        $operation->status = $operation::STATUS_NEW;

        $operation->save();

        return $operation;
    }

    public abstract function name(): string;

    public abstract function type(): string;

    public function results(BulkOperationInstance $instance)
    {
        $results = BulkOperationResult::where(
            'bulk_operation_instance_id',
            $instance->id
        )
            ->orderBy('id', 'desc')
            ->get();

        return $this->transformResults($results);
    }

    protected function transformResults($results)
    {
        return $results;
    }

    public function instances()
    {
        $instances = BulkOperationInstance::whereType($this->type())
            ->orderBy('id', 'desc')
            ->get();

        return $this->transformInstances($instances);
    }

    protected function transformInstances($instances)
    {
        return $instances;
    }

    protected abstract function progress(BulkOperationInstance $instanace): string;

    public abstract function updateInstanceStatus(BulkOperationInstance $instance);

    public abstract function exportCsv(BulkOperationInstance $instance);

    public function toArray()
    {
        return [
            'name' => $this->name(),
            'type' => $this->type()
        ];
    }

    public abstract function isCompleted(BulkOperationInstance $instance): bool;

    public function onOperationCompleted(BulkOperationInstance $instance)
    {
        $instance->user->notify(
            BulkOperationCompleted::for($instance)
        );
    }

    public function url()
    {
        return url('/dashboard/bulk-operations?tab-id=' . $this->type());
    }

    /**
     * arrayToCsvDownload
     * 
     */
    function arrayToCsvDownload($array, $filename = "export.csv", $delimiter = ";")
    {
        // use keys as column titles
        $data = [];

        // working with items
        foreach ($array as $item) {
            $values = array_values((array) $item);
            array_push($data, implode($delimiter, $values));
        }

        try {
            // flush buffer
            ob_flush();
        } catch (Throwable $th) {
            //
        }

        // mixing items
        $csvData = join("\n", $data);
        //setup headers to download the file
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        //setup utf8 encoding
        header('Content-Type: application/csv; charset=UTF-8');
        // showing the results
        die($csvData);
    }
}
