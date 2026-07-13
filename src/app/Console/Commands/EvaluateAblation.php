<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Recipe;
use App\Models\Ingredients;
use Illuminate\Support\Facades\DB;

class EvaluateAblation extends Command
{
    protected $signature = 'app:evaluate-ablation
                            {--runs=5 : Jumlah iterasi random split untuk rata-rata}
                            {--seed=42 : Seed awal untuk reproduktibilitas}
                            {--k=10 : Jumlah rekomendasi Top-K}';

    protected $description = 'Ablation Study: Evaluasi 4 varian metode rekomendasi (Baseline → +IDF → +NoiseFilter → +DFA)';

    public function handle()
    {
        $runs = (int) $this->option('runs');
        $baseSeed = (int) $this->option('seed');
        $k = (int) $this->option('k');

        $this->info("╔══════════════════════════════════════════════════════════════╗");
        $this->info("║         ABLATION STUDY — AutoChef Recommendation           ║");
        $this->info("╚══════════════════════════════════════════════════════════════╝");
        $this->line("");

        // --- DATASET INFO ---
        $totalRecipes = Recipe::count();
        $totalIngredients = Ingredients::count();
        $totalVectors = DB::table('recipe_vectors')->count();
        $users = User::has('favorites', '>=', 4)->get();

        $this->info("📊 Dataset Summary:");
        $this->line("   Total Resep       : $totalRecipes");
        $this->line("   Total Bahan       : $totalIngredients");
        $this->line("   Total Vektor      : $totalVectors");
        $this->line("   User Eligible     : " . $users->count() . " (min 4 favorit)");
        $this->line("   Top-K             : $k");
        $this->line("   Runs              : $runs");
        $this->line("   Base Seed         : $baseSeed");
        $this->line("");

        if ($users->isEmpty()) {
            $this->error("Tidak ada user dengan minimal 4 favorit untuk dievaluasi.");
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

        // Definisi 4 metode ablation
        $methods = [
            'M1_Baseline'       => ['idf_reweight' => false, 'noise_filter' => false, 'dfa' => false],
            'M2_IDF_Reweight'   => ['idf_reweight' => true,  'noise_filter' => false, 'dfa' => false],
            'M3_Noise_Filter'   => ['idf_reweight' => true,  'noise_filter' => true,  'dfa' => false],
            'M4_Full_DFA'       => ['idf_reweight' => true,  'noise_filter' => true,  'dfa' => true],
        ];

        // Akumulator untuk semua runs
        $allRunResults = [];
        foreach (array_keys($methods) as $name) {
            $allRunResults[$name] = ['precision' => [], 'recall' => [], 'f1' => [], 'mrr' => []];
        }

        for ($run = 0; $run < $runs; $run++) {
            $seed = $baseSeed + $run;
            $this->line("🔄 Run " . ($run + 1) . "/$runs (seed=$seed)...");

            $runMetrics = [];
            foreach (array_keys($methods) as $name) {
                $runMetrics[$name] = ['precision' => 0, 'recall' => 0, 'f1' => 0, 'mrr' => 0];
            }

            $validUsersCount = 0;

            foreach ($users as $user) {
                $favorites = $user->favorites()->pluck('recipe.id')->toArray();

                // Reproducible shuffle
                srand($seed + $user->id);
                shuffle($favorites);

                $testSize = max(1, (int)(count($favorites) * 0.3));
                $testSet = array_slice($favorites, 0, $testSize);
                $trainSet = array_slice($favorites, $testSize);

                if (count($trainSet) == 0) continue;

                // Build base profile (rata-rata vektor)
                $baseProfile = $this->buildBaseProfile($trainSet, $allVectors);
                if (empty($baseProfile)) continue;

                $trainCount = count($trainSet);

                foreach ($methods as $methodName => $config) {
                    $profile = $this->applyMethod(
                        $baseProfile,
                        $trainCount,
                        $documentFrequency,
                        $totalDocs,
                        $config
                    );

                    $result = $this->evaluateProfile($profile, $allVectors, $trainSet, $testSet, $k);

                    $runMetrics[$methodName]['precision'] += $result['precision'];
                    $runMetrics[$methodName]['recall'] += $result['recall'];
                    $runMetrics[$methodName]['f1'] += $result['f1'];
                    $runMetrics[$methodName]['mrr'] += $result['mrr'];
                }

                $validUsersCount++;
            }

            if ($validUsersCount == 0) continue;

            // Simpan rata-rata per run
            foreach (array_keys($methods) as $name) {
                foreach (['precision', 'recall', 'f1', 'mrr'] as $metric) {
                    $allRunResults[$name][$metric][] = $runMetrics[$name][$metric] / $validUsersCount;
                }
            }
        }

        // Reset random seed
        srand();

        // --- CETAK HASIL ABLATION STUDY ---
        $this->line("");
        $this->info("══════════════════════════════════════════════════════════════");
        $this->info("           ABLATION STUDY RESULTS (TOP-$k, $runs runs)       ");
        $this->info("           Users evaluated per run: " . $users->count());
        $this->info("══════════════════════════════════════════════════════════════");

        $header = sprintf("%-22s | %-16s | %-16s | %-16s | %-16s",
            "Method", "Precision@$k", "Recall@$k", "F1-Score", "MRR");
        $this->line($header);
        $this->info(str_repeat("─", 95));

        foreach ($methods as $methodName => $config) {
            $label = str_replace('_', ' ', $methodName);
            $components = [];
            if ($config['idf_reweight']) $components[] = 'IDF';
            if ($config['noise_filter']) $components[] = 'NF';
            if ($config['dfa']) $components[] = 'DFA';

            $row = sprintf("%-22s", $label);

            foreach (['precision', 'recall', 'f1', 'mrr'] as $metric) {
                $values = $allRunResults[$methodName][$metric];
                $mean = array_sum($values) / count($values);
                $stddev = $this->calculateStdDev($values, $mean);

                if ($metric === 'mrr') {
                    $row .= sprintf(" | %6s ± %-6s",
                        number_format($mean, 4),
                        number_format($stddev, 4));
                } else {
                    $row .= sprintf(" | %5s%% ± %-5s%%",
                        number_format($mean * 100, 2),
                        number_format($stddev * 100, 2));
                }
            }

            $this->line($row);
        }

        $this->info(str_repeat("─", 95));
        $this->line("");

        // --- CETAK LEGEND ---
        $this->info("📝 Legend:");
        $this->line("   M1 Baseline     : Raw mean user profile (no enhancements)");
        $this->line("   M2 IDF Reweight : + IDF re-weighting on user profile");
        $this->line("   M3 Noise Filter : + Remove terms with weight < 0.01");
        $this->line("   M4 Full DFA     : + Dominant Feature Amplification (K=3, α=3.0, β=0.1)");
        $this->line("");
        $this->info("✅ Gunakan tabel ini sebagai TABLE ablation study di paper iSemantic 2026.");
    }

    /**
     * Build base profile: rata-rata vektor dari resep-resep train set
     */
    private function buildBaseProfile(array $trainSet, $allVectors): array
    {
        $profile = [];
        foreach ($trainSet as $trainId) {
            if (!isset($allVectors[$trainId])) continue;
            $vectorData = json_decode($allVectors[$trainId]->vector, true);
            foreach ($vectorData as $term => $weight) {
                if (!isset($profile[$term])) $profile[$term] = 0;
                $profile[$term] += $weight;
            }
        }
        return $profile;
    }

    /**
     * Apply method configuration to base profile
     */
    private function applyMethod(
        array $baseProfile,
        int $trainCount,
        array $documentFrequency,
        int $totalDocs,
        array $config
    ): array {
        $profile = $baseProfile;

        // Step 1: Mean normalization (selalu dilakukan)
        foreach ($profile as $term => $weight) {
            $profile[$term] = $weight / $trainCount;
        }

        // Step 2: IDF Re-weighting (opsional)
        if ($config['idf_reweight']) {
            foreach ($profile as $term => $weight) {
                $df = $documentFrequency[$term] ?? 1;
                $idf = log10($totalDocs / $df);
                $profile[$term] = $weight * $idf;
            }
        }

        // Step 3: Noise Filter (opsional)
        if ($config['noise_filter']) {
            $profile = array_filter($profile, function ($w) {
                return $w > 0.01;
            });
        }

        // Step 4: DFA (opsional)
        if ($config['dfa']) {
            arsort($profile);
            $count = 0;
            foreach ($profile as $term => $weight) {
                if ($count < 3) {
                    $profile[$term] = $weight * 3.0;
                } else {
                    $profile[$term] = $weight * 0.1;
                }
                $count++;
            }
        }

        return $profile;
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
