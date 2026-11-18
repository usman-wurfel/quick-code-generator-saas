<?php

namespace App\Http\Controllers;

use App\Models\BulkOperationInstance;
use App\Support\BulkOperation\BaseImportOperation;
use App\Support\BulkOperation\BulkOperationManager;
use App\Support\DropletManager;
use Illuminate\Http\Request;

class BulkOperationsController extends Controller
{
    private BulkOperationManager $operations;

    private DropletManager $droplet;

    public function __construct()
    {
        $this->operations = app(BulkOperationManager::class);
        $this->droplet = new DropletManager();

        if ($this->droplet->isSmall() || app()->environment('demo')) {
            abort(401);
        }
    }

    public function listOperationTypes()
    {
        return $this->operations->listTypes();
    }

    public function storeOperation($type, Request $request)
    {
        $operation = $this->operations->find($type);

        if (!$operation) {
            abort(404);
        }

        $instance = $operation->createInstance($request);

        $operation->run($instance);

        return $instance;
    }

    public function listInstances($type)
    {
        return $this->operations->find($type)?->instances();
    }

    public function listInstanceResults(BulkOperationInstance $instance)
    {
        return $this
            ->operations
            ->ofInstance($instance)
            ->results($instance);
    }

    public function exportCsv(BulkOperationInstance $instance)
    {
        $this->operations
            ->ofInstance($instance)
            ->exportCsv($instance);
    }

    public function csvSample($type)
    {
        $operation = $this->operations->find($type);

        if (!$operation instanceof BaseImportOperation) {
            abort(404);
        }

        $operation->downloadCsvSample();
    }

    public function editInstanceName(BulkOperationInstance $instance)
    {
        $name = request()->input('name');

        if (empty($name)) {
            abort(422, t('Name is required'));
        }

        $instance->name = $name;

        $instance->save();

        return $instance;
    }
}
