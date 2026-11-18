<?php

namespace App\Support\BulkOperation\Operations;

use App\Support\BulkOperation\BaseImportOperation;
use App\Support\BulkOperation\Export\BaseExportItem;
use App\Support\BulkOperation\Export\ImportUrlQRCodesOperationExportItem;
use App\Support\BulkOperation\Import\BaseImportItem;
use App\Support\BulkOperation\Import\ImportUrlQRCodeOperationImportItem;
use App\Support\BulkOperation\Result\BaseResultItem;
use App\Support\BulkOperation\Result\ImportUrlQRCodesOperationResult;

class ImportUrlQRCodes extends BaseImportOperation
{
    public function type(): string
    {
        return 'import-url-qrcodes';
    }

    public function name(): string
    {
        return t('Import QR Codes');
    }

    protected function transformInstances($instances)
    {
        return $instances->map(function ($instance) {
            $instance->progress = $this->progress($instance);

            return $instance;
        });
    }

    protected function emptyImportItem(): BaseImportItem
    {
        return new ImportUrlQRCodeOperationImportItem;
    }

    protected function emptyResultItem(): BaseResultItem
    {
        return new ImportUrlQRCodesOperationResult;
    }

    protected function emptyExportItem(): BaseExportItem
    {
        return new ImportUrlQRCodesOperationExportItem;
    }
}
