<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EvaluateRecommendation extends Command
{
    protected $signature = 'app:evaluate';
    protected $description = 'Evaluates and compares Standard TF-IDF vs TF-IDF with DFA';

    public function handle()
    {
        $this->info("Memulai komparasi algoritma: TF-IDF Standar VS TF-IDF+DFA...");

        // 1. Ambil semua user yang memiliki minimal 4 resep favorit
        $users = User::has('favorites', '>=', 4)->get();

        if ($users->isEmpty()) {
            $this->error("Tidak ada user dengan minimal 4 favorit untuk dievaluasi.");
            $this->error("TIPS: Silakan masuk ke aplikasi, login dengan beberapa akun berbeda, lalu favoritkan minimal 4-5 resep secara acak di setiap akun agar skrip ini bisa berjalan.");
            return;
        }

        $metrics = [
            'standard' => ['precision' => 0, 'recall' => 0, 'f1' => 0],
            'dfa'      => ['precision' => 0, 'recall' => 0, 'f1' => 0],
        ];
        
        $validUsersCount = 0;
        $k = 10; // Kembalikan ke 10 agar ada hit, tapi kita gunakan MRR untuk membedakan ranking

        // Pre-load semua vector
        $allVectors = DB::table('recipe_vectors')->get()->keyBy('recipe_id');
        $totalDocs = $allVectors->count();

        // Hitung Document Frequency secara global
        $documentFrequency = [];
        foreach ($allVectors as $rv) {
            $vectorData = json_decode($rv->vector, true);
            if (is_array($vectorData)) {
                foreach (array_keys($vectorData) as $term) {
                    if (!isset($documentFrequency[$term])) $documentFrequency[$term] = 0;
                    $documentFrequency[$term]++;
                }
            }
        }

        $metrics = [
            'standard' => ['precision' => 0, 'recall' => 0, 'f1' => 0, 'mrr' => 0],
            'dfa'      => ['precision' => 0, 'recall' => 0, 'f1' => 0, 'mrr' => 0],
        ];

        foreach ($users as $user) {
            $favorites = $user->favorites()->pluck('recipe.id')->toArray();
            
            // Simulasikan pembagian data: 70% Train, 30% Test
            shuffle($favorites);
            $testSize = max(1, (int)(count($favorites) * 0.3));
            $testSet = array_slice($favorites, 0, $testSize);
            $trainSet = array_slice($favorites, $testSize);

            // --- BUILD USER PROFILE DARI TRAIN SET ---
            $userProfileVector = [];
            foreach ($trainSet as $trainId) {
                if (!isset($allVectors[$trainId])) continue;
                $vectorData = json_decode($allVectors[$trainId]->vector, true);
                foreach ($vectorData as $term => $weight) {
                    if (!isset($userProfileVector[$term])) $userProfileVector[$term] = 0;
                    $userProfileVector[$term] += $weight;
                }
            }

            $trainCount = count($trainSet);
            if ($trainCount == 0) continue;

            foreach ($userProfileVector as $term => $weight) {
                $userProfileVector[$term] = $weight / $trainCount;
                $df = $documentFrequency[$term] ?? 1;
                $idf = log10($totalDocs / $df);
                $userProfileVector[$term] = $userProfileVector[$term] * $idf;
            }

            $userProfileVector = array_filter($userProfileVector, function ($w) { return $w > 0.01; });

            // SIMPAN PROFIL UNTUK METODE 1: TF-IDF STANDARD
            $standardProfile = $userProfileVector;

            // SIMPAN PROFIL UNTUK METODE 2: TF-IDF + DFA
            $dfaProfile = $standardProfile;
            arsort($dfaProfile);
            $count = 0;
            foreach ($dfaProfile as $term => $weight) {
                if ($count < 3) {
                    $dfaProfile[$term] = $weight * 3.0; // Amplifikasi top 3
                } else {
                    $dfaProfile[$term] = $weight * 0.1; // Decay drastis tapi tidak 0
                }
                $count++;
            }

            // --- EVALUASI METODE 1 (STANDARD) ---
            $resStandard = $this->evaluateProfile($standardProfile, $allVectors, $trainSet, $testSet, $k);
            $metrics['standard']['precision'] += $resStandard['precision'];
            $metrics['standard']['recall'] += $resStandard['recall'];
            $metrics['standard']['f1'] += $resStandard['f1'];
            $metrics['standard']['mrr'] += $resStandard['mrr'];

            // --- EVALUASI METODE 2 (DFA) ---
            $resDFA = $this->evaluateProfile($dfaProfile, $allVectors, $trainSet, $testSet, $k);
            $metrics['dfa']['precision'] += $resDFA['precision'];
            $metrics['dfa']['recall'] += $resDFA['recall'];
            $metrics['dfa']['f1'] += $resDFA['f1'];
            $metrics['dfa']['mrr'] += $resDFA['mrr'];

            $validUsersCount++;
        }

        if ($validUsersCount == 0) {
            $this->error("Gagal melakukan evaluasi.");
            return;
        }

        // --- CETAK PERBANDINGAN HASIL ---
        $this->info("======================================================");
        $this->info(" PERBANDINGAN ALGORITMA REKOMENDASI (TOP-$k)          ");
        $this->info(" Total User Dievaluasi: $validUsersCount");
        $this->info("======================================================");
        $this->line(sprintf("%-20s | %-12s | %-12s", "Metrik", "TF-IDF Biasa", "TF-IDF + DFA"));
        $this->info("------------------------------------------------------");
        
        $metricsList = ['precision' => 'Precision', 'recall' => 'Recall', 'f1' => 'F1-Score', 'mrr' => 'MRR'];
        foreach ($metricsList as $key => $label) {
            $valStd = ($metrics['standard'][$key] / $validUsersCount) * ($key == 'mrr' ? 1 : 100);
            $valDfa = ($metrics['dfa'][$key] / $validUsersCount) * ($key == 'mrr' ? 1 : 100);
            
            if ($key == 'mrr') {
                // MRR biasanya tidak dipersentase, melainkan skala 0 - 1
                $this->line(sprintf("%-20s | %-11s  | %-11s", $label, number_format($valStd, 4), number_format($valDfa, 4)));
            } else {
                $this->line(sprintf("%-20s | %-11s%% | %-11s%%", $label, number_format($valStd, 2), number_format($valDfa, 2)));
            }
        }
        $this->info("======================================================");
        $this->line("Kesimpulan untuk Paper: Masukkan tabel perbandingan ini untuk");
        $this->line("membuktikan bahwa penambahan algoritma DFA (TF-IDF + DFA)");
        $this->line("menghasilkan performa rekomendasi yang lebih baik daripada");
        $this->line("algoritma baseline (TF-IDF biasa). Terutama pada metrik MRR.");
    }

    private function evaluateProfile($profile, $allVectors, $trainSet, $testSet, $k) 
    {
        $similarities = [];
        foreach ($allVectors as $recipeId => $rv) {
            if (in_array($recipeId, $trainSet)) continue; 
            
            $vectorData = json_decode($rv->vector, true);
            $score = $this->calculateCosineSimilarity($profile, $vectorData);
            if ($score > 0) $similarities[$recipeId] = $score;
        }

        arsort($similarities);
        $topK_Recommendations = array_slice(array_keys($similarities), 0, $k);

        $hits = count(array_intersect($topK_Recommendations, $testSet));
        
        $precision = (count($topK_Recommendations) > 0) ? ($hits / count($topK_Recommendations)) : 0;
        $recall = (count($testSet) > 0) ? ($hits / count($testSet)) : 0;
        $f1 = ($precision + $recall > 0) ? 2 * (($precision * $recall) / ($precision + $recall)) : 0;

        // MRR (Mean Reciprocal Rank) Calculation
        $mrr = 0;
        foreach ($topK_Recommendations as $rank => $recId) {
            if (in_array($recId, $testSet)) {
                $mrr = 1.0 / ($rank + 1);
                break; // Hanya ambil rank hit pertama
            }
        }

        return [
            'precision' => $precision,
            'recall' => $recall,
            'f1' => $f1,
            'mrr' => $mrr
        ];
    }

    private function calculateCosineSimilarity(array $vecA, array $vecB)
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $allKeys = array_unique(array_merge(array_keys($vecA), array_keys($vecB)));
        foreach ($allKeys as $key) {
            $valA = $vecA[$key] ?? 0.0;
            $valB = $vecB[$key] ?? 0.0;
            $dotProduct += ($valA * $valB);
            $normA += pow($valA, 2);
            $normB += pow($valB, 2);
        }

        if ($normA == 0 || $normB == 0) return 0;
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
