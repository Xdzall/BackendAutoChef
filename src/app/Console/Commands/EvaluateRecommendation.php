<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Recipe;
use App\Models\Ingredients;
use Illuminate\Support\Facades\DB;

class EvaluateRecommendation extends Command
{
    protected $signature = 'app:evaluate
                            {--runs=5 : Jumlah iterasi random split untuk mean ± stddev}
                            {--seed=42 : Base seed untuk reproduktibilitas}
                            {--k=10 : Jumlah rekomendasi Top-K}
                            {--verbose-users : Tampilkan detail per-user}';

    protected $description = 'Evaluates and compares Standard TF-IDF vs TF-IDF with DFA (reproducible, multi-run)';

    public function handle()
    {
        $runs = (int) $this->option('runs');
        $baseSeed = (int) $this->option('seed');
        $k = (int) $this->option('k');
        $verboseUsers = $this->option('verbose-users');

        $this->info("╔══════════════════════════════════════════════════════════════╗");
        $this->info("║    ALGORITHM COMPARISON: Standard TF-IDF vs TF-IDF + DFA   ║");
        $this->info("╚══════════════════════════════════════════════════════════════╝");
        $this->line("");

        // --- DATASET INFO ---
        $totalRecipes = Recipe::count();
        $totalIngredients = Ingredients::count();
        $totalVectors = DB::table('recipe_vectors')->count();
        $users = User::has('favorites', '>=', 4)->get();

        $this->info("📊 Dataset Summary:");
        $this->line("   Total Resep           : $totalRecipes");
        $this->line("   Total Bahan (Unik)    : $totalIngredients");
        $this->line("   Total Vektor Resep    : $totalVectors");
        $this->line("   User Eligible (≥4 fav): " . $users->count());
        $this->line("   Evaluation Config     : Top-$k, $runs runs, base seed=$baseSeed");
        $this->line("   Train/Test Split      : 70% / 30%");
        $this->line("");

        if ($users->isEmpty()) {
            $this->error("Tidak ada user dengan minimal 4 favorit untuk dievaluasi.");
            $this->error("TIPS: Login dengan beberapa akun, lalu favoritkan minimal 4-5 resep per akun.");
            return;
        }

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

        // Akumulasi untuk semua runs
        $allRunResults = [
            'standard' => ['precision' => [], 'recall' => [], 'f1' => [], 'mrr' => []],
            'dfa'      => ['precision' => [], 'recall' => [], 'f1' => [], 'mrr' => []],
        ];

        for ($run = 0; $run < $runs; $run++) {
            $seed = $baseSeed + $run;
            $this->line("🔄 Run " . ($run + 1) . "/$runs (seed=$seed)...");

            $runMetrics = [
                'standard' => ['precision' => 0, 'recall' => 0, 'f1' => 0, 'mrr' => 0],
                'dfa'      => ['precision' => 0, 'recall' => 0, 'f1' => 0, 'mrr' => 0],
            ];

            $validUsersCount = 0;

            foreach ($users as $user) {
                $favorites = $user->favorites()->pluck('recipe.id')->toArray();

                // Reproducible shuffle with deterministic seed
                srand($seed + $user->id);
                shuffle($favorites);

                $testSize = max(1, (int)(count($favorites) * 0.3));
                $testSet = array_slice($favorites, 0, $testSize);
                $trainSet = array_slice($favorites, $testSize);

                if (count($trainSet) == 0) continue;

                // --- BUILD USER PROFILE ---
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
                foreach ($userProfileVector as $term => $weight) {
                    $userProfileVector[$term] = $weight / $trainCount;
                    $df = $documentFrequency[$term] ?? 1;
                    $idf = log10($totalDocs / $df);
                    $userProfileVector[$term] = $userProfileVector[$term] * $idf;
                }
                $userProfileVector = array_filter($userProfileVector, function ($w) { return $w > 0.01; });

                // METODE 1: TF-IDF STANDARD (after IDF re-weighting + noise filter)
                $standardProfile = $userProfileVector;

                // METODE 2: TF-IDF + DFA
                $dfaProfile = $standardProfile;
                arsort($dfaProfile);
                $count = 0;
                foreach ($dfaProfile as $term => $weight) {
                    if ($count < 7) {
                        $dfaProfile[$term] = $weight * 3.0;
                    } else {
                        $dfaProfile[$term] = $weight * 0.1;
                    }
                    $count++;
                }

                // --- EVALUATE STANDARD ---
                $resStd = $this->evaluateProfile($standardProfile, $allVectors, $trainSet, $testSet, $k);
                $runMetrics['standard']['precision'] += $resStd['precision'];
                $runMetrics['standard']['recall'] += $resStd['recall'];
                $runMetrics['standard']['f1'] += $resStd['f1'];
                $runMetrics['standard']['mrr'] += $resStd['mrr'];

                // --- EVALUATE DFA ---
                $resDFA = $this->evaluateProfile($dfaProfile, $allVectors, $trainSet, $testSet, $k);
                $runMetrics['dfa']['precision'] += $resDFA['precision'];
                $runMetrics['dfa']['recall'] += $resDFA['recall'];
                $runMetrics['dfa']['f1'] += $resDFA['f1'];
                $runMetrics['dfa']['mrr'] += $resDFA['mrr'];

                if ($verboseUsers && $run == 0) {
                    $this->line(sprintf("     User #%d: fav=%d, train=%d, test=%d | Std MRR=%.4f, DFA MRR=%.4f",
                        $user->id, count($favorites), count($trainSet), count($testSet),
                        $resStd['mrr'], $resDFA['mrr']));
                }

                $validUsersCount++;
            }

            if ($validUsersCount == 0) continue;

            // Simpan rata-rata per run
            foreach (['standard', 'dfa'] as $method) {
                foreach (['precision', 'recall', 'f1', 'mrr'] as $metric) {
                    $allRunResults[$method][$metric][] = $runMetrics[$method][$metric] / $validUsersCount;
                }
            }
        }

        // Reset random seed
        srand();

        // --- PRINT RESULTS ---
        $this->line("");
        $this->info("══════════════════════════════════════════════════════════════");
        $this->info("    COMPARISON RESULTS (TOP-$k, $runs runs, " . $users->count() . " users)");
        $this->info("══════════════════════════════════════════════════════════════");

        $header = sprintf("%-20s | %-20s | %-20s | %-10s", "Metric", "Standard TF-IDF", "TF-IDF + DFA", "Δ Change");
        $this->line($header);
        $this->info(str_repeat("─", 78));

        $metricLabels = ['precision' => 'Precision@'.$k, 'recall' => 'Recall@'.$k, 'f1' => 'F1-Score', 'mrr' => 'MRR'];

        foreach ($metricLabels as $key => $label) {
            $stdValues = $allRunResults['standard'][$key];
            $dfaValues = $allRunResults['dfa'][$key];

            $stdMean = array_sum($stdValues) / count($stdValues);
            $dfaMean = array_sum($dfaValues) / count($dfaValues);
            $stdStd = $this->calculateStdDev($stdValues, $stdMean);
            $dfaStd = $this->calculateStdDev($dfaValues, $dfaMean);

            $delta = ($stdMean > 0) ? (($dfaMean - $stdMean) / $stdMean) * 100 : 0;
            $deltaStr = ($delta >= 0 ? "+" : "") . number_format($delta, 1) . "%";

            if ($key === 'mrr') {
                $this->line(sprintf("%-20s | %s ± %-6s | %s ± %-6s | %s",
                    $label,
                    number_format($stdMean, 4), number_format($stdStd, 4),
                    number_format($dfaMean, 4), number_format($dfaStd, 4),
                    $deltaStr));
            } else {
                $this->line(sprintf("%-20s | %5s%% ± %-5s%% | %5s%% ± %-5s%% | %s",
                    $label,
                    number_format($stdMean * 100, 2), number_format($stdStd * 100, 2),
                    number_format($dfaMean * 100, 2), number_format($dfaStd * 100, 2),
                    $deltaStr));
            }
        }

        $this->info(str_repeat("─", 78));
        $this->line("");
        $this->info("✅ Gunakan tabel ini sebagai TABLE perbandingan algoritma di paper.");
        $this->info("   Hasil ini reproducible dengan seed=$baseSeed.");
    }

    private function evaluateProfile(array $profile, $allVectors, array $trainSet, array $testSet, int $k): array
    {
        $similarities = [];
        foreach ($allVectors as $recipeId => $rv) {
            if (in_array($recipeId, $trainSet)) continue;
            $vectorData = json_decode($rv->vector, true);
            $score = $this->calculateCosineSimilarity($profile, $vectorData);
            if ($score > 0) $similarities[$recipeId] = $score;
        }

        arsort($similarities);
        $topK = array_slice(array_keys($similarities), 0, $k);

        $hits = count(array_intersect($topK, $testSet));
        $precision = (count($topK) > 0) ? ($hits / count($topK)) : 0;
        $recall = (count($testSet) > 0) ? ($hits / count($testSet)) : 0;
        $f1 = ($precision + $recall > 0) ? 2 * (($precision * $recall) / ($precision + $recall)) : 0;

        $mrr = 0;
        foreach ($topK as $rank => $recId) {
            if (in_array($recId, $testSet)) {
                $mrr = 1.0 / ($rank + 1);
                break;
            }
        }

        return compact('precision', 'recall', 'f1', 'mrr');
    }

    private function calculateCosineSimilarity(array $vecA, array $vecB): float
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

    private function calculateStdDev(array $values, float $mean): float
    {
        if (count($values) <= 1) return 0;
        $sumSquares = 0;
        foreach ($values as $v) {
            $sumSquares += pow($v - $mean, 2);
        }
        return sqrt($sumSquares / (count($values) - 1));
    }
}
