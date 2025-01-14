<?php

namespace App\Exports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class AssessmentExport implements WithMultipleSheets
{
    use Exportable;

    protected $tahunAjaran;
    protected $namaProyek;

    public function __construct($tahunAjaran, $namaProyek)
    {
        $this->tahunAjaran = $tahunAjaran;
        $this->namaProyek = $namaProyek;
    }

    /**
     * Sheet yang akan di-ekspor
     */
    public function sheets(): array
    {
        // Cek apakah ada data assessment untuk tahun_ajaran dan nama_proyek
        $assessments = Assessment::where('tahun_ajaran', $this->tahunAjaran)
            ->where('nama_proyek', $this->namaProyek)
            ->get();

        // Jika tidak ada data, kirim template kosong tapi tetap dengan tahun_ajaran dan nama_proyek
        if ($assessments->isEmpty()) {
            return [
                // Sheet 1: Template kosong untuk Assessment, tetap menampilkan tahun_ajaran dan nama_proyek
                new AssessmentSheet($this->tahunAjaran, $this->namaProyek),

                // Sheet 2: Template kosong untuk TypeCriteriaSheet, tetap menampilkan tahun_ajaran dan nama_proyek
                new TypeCriteriaSheet($this->tahunAjaran, $this->namaProyek),
            ];
        } else {
            // Jika ada data, kirim template dengan data yang sudah ada
            return [
                // Sheet 1: Data Assessment yang sudah ada
                new AssessmentSheet($this->tahunAjaran, $this->namaProyek),

                // Sheet 2: TypeCriteriaSheet yang sudah ada
                new TypeCriteriaSheet($this->tahunAjaran, $this->namaProyek),
            ];
        }
    }
}
