<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MongoCollectionsExport implements WithMultipleSheets
{
    protected $dataByCollection;

    public function __construct(array $dataByCollection)
    {
        $this->dataByCollection = $dataByCollection;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->dataByCollection as $collection => $rows) {
            $sheets[] = new MongoSheetExport($collection, $rows);
        }
        return $sheets;
    }
}
