<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EvaluateRecommendation extends Command
{
    protected $signature = 'app:evaluate';
    protected $description = 'Evaluates the recommendation engine and outputs Precision and Recall metrics';

    public function handle()
    {
        $this->info("Memulai evaluasi algoritma rekomendasi (TF-IDF + Cosine + DFA)...");

        // 1. Ambil semua user yang memiliki minimal 4 resep favorit
        $users = User::has('favorites', '>=', 4)->get();

        if ($users->isEmpty()) {
            $this->error("Tidak ada user dengan minimal 4 favorit untuk dievaluasi.");
            $this->error("TIPS: Silakan masuk ke aplikasi (atau DB), login dengan beberapa akun berbeda, lalu favoritkan minimal 4-5 resep secara acak di setiap akun agar skrip ini bisa berjalan.");
            return;
        }

        $totalPrecision = 0;
        $totalRecall = 0;
        $totalF1 = 0;
        $validUsersCount = 0;
        
        $k = 10; // Top-K recommendations to evaluate (Precision@10, Recall@10)

        // Pre-load semua vector untuk mempercepat proses
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

        foreach ($users as $user) {
            $favorites = $user->favorites()->pluck('recipe.id')->toArray();
            
            // Simulasikan pembagian data: 70% Train (diketahui), 30% Test (dihide untuk ditebak)
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

            // -- DFA (Dominant Feature Amplification) --
            arsort($userProfileVector);
            $amplificationFactor = 1.5;
            $count = 0;
            foreach ($userProfileVector as $term => $weight) {
                if ($count < 3) {
                    $userProfileVector[$term] = $weight * $amplificationFactor;
                    $count++;
                } else {
                    break;
                }
            }

            // --- HITUNG COSINE SIMILARITY DENGAN SEMUA RESEP (KECUALI TRAIN SET) ---
            $similarities = [];
            foreach ($allVectors as $recipeId => $rv) {
                if (in_array($recipeId, $trainSet)) continue; // Jangan rekomendasikan yang sudah di train set
                
                $vectorData = json_decode($rv->vector, true);
                $score = $this->calculateCosineSimilarity($userProfileVector, $vectorData);
                if ($score > 0) {
                    $similarities[$recipeId] = $score;
                }
            }

            // Ambil Top-K Rekomendasi
            arsort($similarities);
            $topK_Recommendations = array_slice(array_keys($similarities), 0, $k);

            // --- EVALUASI ---
            // True Positives (Hit) = Berapa banyak resep dari Test Set yang muncul di Top-K Rekomendasi?
            $hits = count(array_intersect($topK_Recommendations, $testSet));
            
            // Precision: Berapa persen dari rekomendasi yang benar (relevan)?
            $precision = (count($topK_Recommendations) > 0) ? ($hits / count($topK_Recommendations)) : 0;
            
            // Recall: Berapa persen dari Test Set yang berhasil ditebak?
            $recall = (count($testSet) > 0) ? ($hits / count($testSet)) : 0;
            
            // F1-Score
            $f1 = ($precision + $recall > 0) ? 2 * (($precision * $recall) / ($precision + $recall)) : 0;

            $totalPrecision += $precision;
            $totalRecall += $recall;
            $totalF1 += $f1;
            $validUsersCount++;
        }

        if ($validUsersCount == 0) {
            $this->error("Gagal melakukan evaluasi.");
            return;
        }

        // Tampilkan Hasil Rata-rata
        $avgPrecision = $totalPrecision / $validUsersCount;
        $avgRecall = $totalRecall / $validUsersCount;
        $avgF1 = $totalF1 / $validUsersCount;

        $this->info("====================================");
        $this->info(" HASIL EVALUASI METRIK REKOMENDASI  ");
        $this->info("====================================");
        $this->line("Total User Dievaluasi : " . $validUsersCount);
        $this->line("Rekomendasi Top-K     : " . $k);
        $this->info("------------------------------------");
        $this->info("Average Precision@$k : " . number_format($avgPrecision * 100, 2) . "%");
        $this->info("Average Recall@$k    : " . number_format($avgRecall * 100, 2) . "%");
        $this->info("Average F1-Score@$k  : " . number_format($avgF1 * 100, 2) . "%");
        $this->info("====================================");
        $this->line("Tips: Masukkan hasil (Precision, Recall, F1-Score) ini ke tabel di Paper Anda.");
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
