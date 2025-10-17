<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class MongoSheetExport implements FromArray, WithTitle
{
    protected $title;
    protected $rows;

    public function __construct(string $collection, array $rows)
    {
        $this->title = mb_substr($collection, 0, 31); // límite Excel
        $this->rows  = $rows;
    }

    public function title(): string
    {
        return $this->title ?: 'sheet';
    }

    public function array(): array
    {
        if (empty($this->rows)) return [];

        // Normaliza headers
        $headers = [];
        foreach ($this->rows as $r) {
            foreach (array_keys($r) as $k) $headers[$k] = true;
        }
        $headers = array_values(array_keys($headers));

        $out = [];
        $out[] = $headers;

        foreach ($this->rows as $r) {
            $line = [];
            foreach ($headers as $h) {
                $val = $r[$h] ?? null;
                if (is_array($val) || is_object($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                $line[] = $val;
            }
            $out[] = $line;
        }
        return $out;
    }
}
