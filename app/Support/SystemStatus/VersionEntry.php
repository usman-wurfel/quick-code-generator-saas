<?php

namespace App\Support\SystemStatus;

use Carbon\Carbon;

class VersionEntry extends BaseEntry
{
    public function title()
    {
        return 'System Version';
    }

    protected function isSuccess()
    {
        return true;
    }

    public function text()
    {
        return $this->getVersion();
    }

    private function getVersion()
    {
        return json_decode(
            file_get_contents(
                base_path('composer.json')
            )
        )->version;
    }

    public function sortOrder()
    {
        return 0;
    }

    protected function informationText()
    {
        $time = (new Carbon(filemtime(base_path('composer.json'))));

        return 'Installed on ' . $time->format('Y-m-d');
    }


    protected function instructionsText()
    {
        return '';
    }
}
