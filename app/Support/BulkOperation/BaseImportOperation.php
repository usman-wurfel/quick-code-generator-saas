<?php

namespace App\Support\BulkOperation;

use App\Interfaces\FileManager;

use App\Models\BulkOperationInstance;
use App\Models\BulkOperationResult;
use App\Models\File;
use App\Models\QRCode;
use App\Support\BulkOperation\Export\BaseExportItem;
use App\Support\BulkOperation\Import\BaseImportItem;
use App\Support\BulkOperation\Jobs\ImportItemJob;
use App\Support\BulkOperation\Result\BaseResultItem;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Http\Request;
use Throwable;

abstract class BaseImportOperation extends BaseBulkOperation
{
    use WriteLogs;

    protected abstract function emptyResultItem(): BaseResultItem;

    protected abstract function emptyImportItem(): BaseImportItem;

    protected abstract function emptyExportItem(): BaseExportItem;

    protected function store(Request $request, BulkOperationInstance $instance)
    {
        $this->storeCsvFile($request, $instance);
    }

    /**
     * @return BaseImportItem[]
     */
    protected function items(BulkOperationInstance $instance): array
    {
        $rows = $this->readCsvFile(
            $instance->file
        );

        return $this->createImportItems(
            $instance,
            $rows
        );
    }

    public function run(BulkOperationInstance $instance): static
    {
        foreach ($this->items($instance) as $item) {
            dispatch(new ImportItemJob($item));
        }

        return $this;
    }

    protected function readCsvFile(File $file): ?array
    {
        $lines = [];

        $this->files->useTempLocalFile($file, function ($path) use (&$lines) {

            $file_to_read = fopen($path, 'r');

            while (!feof($file_to_read)) {
                $lines[] = fgetcsv($file_to_read, null, ',');
            }

            fclose($file_to_read);
        });

        return $lines;
    }

    protected function storeCsvFile(Request $request, BulkOperationInstance $instance)
    {
        $request->merge([
            'attachable_type' => $instance::class,
            'attachable_id' => $instance->id,
            'type' => FileManager::FILE_TYPE_GENERAL_USE_FILE
        ]);

        $this->files->store($request);
    }

    /**
     * @return BaseItem[]
     */
    protected function createImportItems(BulkOperationInstance $instance, array $rows)
    {
        // Remove header row.
        unset($rows[0]);

        return collect($rows)
            ->filter(function ($row) {

                return is_array($row) && !empty($row);
                //
            })->map(function ($row) use ($instance) {

                return $this->createImportItem($instance, $row);
                //
            })->filter(function ($item) {

                return !empty($item);
                //
            })->values()

            ->all();
    }

    protected function createImportItem(BulkOperationInstance $instance, array $row): ?BaseImportItem
    {
        try {
            return $this->emptyImportItem()->fromCsvRow($instance, $row);
        } catch (Throwable $th) {
            $this->logDebugf($th->getMessage());
            return null;
        }
    }

    public function addResult(QRCode $qrcode, BulkOperationInstance $instance)
    {
        $result = new BulkOperationResult();

        $result->bulk_operation_instance_id = $instance->id;

        $result->data = $this
            ->emptyResultItem()
            ->fromQRCode($qrcode)
            ->toArray();

        $result->save();
    }

    public function exportCsv(BulkOperationInstance $instance)
    {
        $rows = $this->generateExportRows($instance);

        $this->arrayToCsvDownload($rows, $this->name() . '.csv', ',');
    }

    protected function generateExportRows(BulkOperationInstance $instance)
    {
        $items = $this->results($instance)->map(function (BaseResultItem $result) {
            $qrcode = $result->toQRCode();

            return $this->emptyExportItem()->fromQRCode($qrcode);
        });

        return [
            $this->emptyExportItem()->getCsvColumnNames(),
            ...$items
        ];
    }

    protected function transformResults($results)
    {
        return collect($results)
            ->map(
                function (BulkOperationResult $result) {
                    return $this->emptyResultItem()->fromInstanceResult($result);
                }
            );
    }

    protected function progress(BulkOperationInstance $instance): string
    {
        $itemsCount = count($this->items($instance));

        $resultCount = $this->resultCount($instance);

        $progress = $resultCount > 0 ? round(100 * $resultCount / $itemsCount) : 0;

        return sprintf('%%%s (%s of %s)', $progress,  $resultCount, $itemsCount);
    }

    protected function resultCount($instance)
    {
        return  BulkOperationResult::where(
            'bulk_operation_instance_id',
            $instance->id
        )->count();
    }

    public function downloadCsvSample()
    {
        $columns = $this->emptyImportItem()->getCsvColumnNames();

        $row = array_map(function ($key) {
            return '';
        }, array_keys($columns));

        $rows = [
            $columns,
            $row
        ];

        return $this
            ->arrayToCsvDownload(
                $rows,
                $this->name() . ' Sample.csv',
                ','
            );
    }

    public function updateInstanceStatus(BulkOperationInstance $instance)
    {
        $resultCount = $this->resultCount($instance);

        $inputCount = count($this->items($instance));

        if ($resultCount < $inputCount) {

            if ($instance->status != $instance::STATUS_RUNNING) {
                $instance->status = $instance::STATUS_RUNNING;

                $instance->save();
            }
        }
    }

    public function isCompleted(BulkOperationInstance $instance): bool
    {
        $resultCount = $this->resultCount($instance);

        $inputCount = count($this->items($instance));

        return $resultCount >= $inputCount;
    }

    public function onOperationCompleted(BulkOperationInstance $instance)
    {
        $instance->status = $instance::STATUS_COMPLETED;

        $instance->save();

        parent::onOperationCompleted($instance);
    }
}
