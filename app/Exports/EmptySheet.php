<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class EmptySheet implements FromCollection, WithHeadings, WithTitle
{
    protected $message;

    public function __construct($message = 'No data available')
    {
        $this->message = $message;
    }

    public function collection()
    {
        return collect([
            [$this->message]
        ]);
    }

    public function headings(): array
    {
        return [
            'Message'
        ];
    }

    public function title(): string
    {
        return 'Info';
    }
}
