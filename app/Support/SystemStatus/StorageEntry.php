<?php

namespace App\Support\SystemStatus;

class StorageEntry extends BaseEntry
{
    public function title()
    {
        return 'Storage';
    }

    public function text()
    {
        return $this->isSuccess() ? 'Linked' : 'Unlinked';
    }

    public function instructionsText()
    {
        return sprintf(
            'Execute the following commands to link the storage:
            <code>
                ln -s -f %s %s
            </code>',
            storage_path('app/public'),
            public_path('storage'),
        );
    }

    protected function informationText()
    {
        return 'Links exist in public folder';
    }

    protected function isSuccess()
    {
        return file_exists(
            public_path('storage')
        );
    }

    public function sortOrder()
    {
        return 30;
    }
}
