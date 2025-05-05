<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnswersPeer;
use App\Jobs\ProcessFlaskPeerAssessment;

class ProcessAllPeerAnswers extends Command
{
    protected $signature = 'peer:process-all';
    protected $description = 'Proses semua jawaban peer yang belum punya score_SLA atau similarity';

    public function handle()
    {
        $this->info('Memulai pemrosesan jawaban peer...');

        $peers = AnswersPeer::whereNull('score_SLA')
            ->orWhereNull('similarity')
            ->get();

        $this->info("Ditemukan {$peers->count()} data untuk diproses");

        foreach ($peers as $peer) {
            $answerData = [
                'answer' => $peer->answer,
                'score' => $peer->score
            ];

            ProcessFlaskPeerAssessment::dispatch($answerData, $peer->id);
        }

        $this->info('Semua job berhasil dikirim ke queue.');
    }
}
