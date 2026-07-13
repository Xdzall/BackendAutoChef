<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Recipe;
use App\Models\Ingredients;
use Illuminate\Support\Facades\DB;

class EvaluateSensitivity extends Command
{
    protected $signature = 'app:evaluate-sensitivity
                            {--runs=5 : Jumlah iterasi random split}
                            {--seed=42 : Base seed}
                            {--k=10 : Top-K recommendations}
                            {--mode=all : Mode analisis: K, alpha, beta, atau all}';

    protected $description = 'Sensitivity Analysis: Evaluasi dampak variasi hyperparameter DFA (K, α, β) terhadap MRR';

    public function handle()
    {
        $runs = (int) $this->option('runs');
        $baseSeed = (int) $this->option('seed');
        $k = (int) $this->option('k');
        $mode = $this->option('mode');

        $this->info("╔══════════════════════════════════════════════════════════════╗");
        $this->info("║       SENSITIVITY ANALYSIS — DFA Hyperparameters           ║");
        $this->info("╚══════════════════════════════════════════════════════════════╝");
        $this->line("");

        // --- DATASET INFO ---
        $totalRecipes = Recipe::count();
        $totalIngredients = Ingredients::count();
        $users = User::has('favorites', '>=', 4)->get();

        $this->info("📊 Dataset: $totalRecipes resep, $totalIngredients bahan, " . $users->count() . " eligible users");
        $this->line("   Config: Top-$k, $runs runs, seed=$baseSeed");
        $this->line("");

        if ($users->isEmpty()) {
            $this->error("Tidak ada user dengan minimal 4 favorit.");
            return;
        }

        // Pre-load
        $allVectors = DB::table('recipe_vectors')->get()->keyBy('recipe_id');
        $totalDocs = $allVectors->count();

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

        // ═══════════════════════════════════════════════════════
        // ANALYSIS 1: Variasi K (dengan α=3.0, β=0.1 tetap)
        // ═══════════════════════════════════════════════════════
        if ($mode === 'all' || $mode === 'K') {
            $this->info("━━━ Analysis 1: Variasi K (α=3.0, β=0.1 fixed) ━━━");
            $kValues = [1, 2, 3, 5, 7, 10];
            $results = [];

            foreach ($kValues as $topK) {
                $mrrValues = $this->runEvaluation(
                    $users, $allVectors, $documentFrequency, $totalDocs,
                    $topK, 3.0, 0.1, $k, $runs, $baseSeed
                );
                $mean = array_sum($mrrValues) / count($mrrValues);
                $std = $this->calculateStdDev($mrrValues, $mean);
                $results[] = ['K' => $topK, 'mean' => $mean, 'std' => $std];
                $this->line("   K=$topK → MRR = " . number_format($mean, 4) . " ± " . number_format($std, 4));
            }

            $this->line("");
            $this->printTable("Variasi K (α=3.0, β=0.1)", "K", $results);
        }

        // ═══════════════════════════════════════════════════════
        // ANALYSIS 2: Variasi α (dengan K=3, β=0.1 tetap)
        // ═══════════════════════════════════════════════════════
        if ($mode === 'all' || $mode === 'alpha') {
            $this->info("━━━ Analysis 2: Variasi α (K=3, β=0.1 fixed) ━━━");
            $alphaValues = [1.0, 1.5, 2.0, 3.0, 4.0, 5.0];
            $results = [];

            foreach ($alphaValues as $alpha) {
                $mrrValues = $this->runEvaluation(
                    $users, $allVectors, $documentFrequency, $totalDocs,
                    3, $alpha, 0.1, $k, $runs, $baseSeed
                );
                $mean = array_sum($mrrValues) / count($mrrValues);
                $std = $this->calculateStdDev($mrrValues, $mean);
                $results[] = ['K' => $alpha, 'mean' => $mean, 'std' => $std];
                $this->line("   α=$alpha → MRR = " . number_format($mean, 4) . " ± " . number_format($std, 4));
            }

            $this->line("");
            $this->printTable("Variasi α (K=3, β=0.1)", "α", $results);
        }

        // ═══════════════════════════════════════════════════════
        // ANALYSIS 3: Variasi β (dengan K=3, α=3.0 tetap)
        // ═══════════════════════════════════════════════════════
        if ($mode === 'all' || $mode === 'beta') {
            $this->info("━━━ Analysis 3: Variasi β (K=3, α=3.0 fixed) ━━━");
            $betaValues = [0.01, 0.05, 0.1, 0.2, 0.3, 0.5];
            $results = [];

            foreach ($betaValues as $beta) {
                $mrrValues = $this->runEvaluation(
                    $users, $allVectors, $documentFrequency, $totalDocs,
                    3, 3.0, $beta, $k, $runs, $baseSeed
                );
                $mean = array_sum($mrrValues) / count($mrrValues);
                $std = $this->calculateStdDev($mrrValues, $mean);
                $results[] = ['K' => $beta, 'mean' => $mean, 'std' => $std];
                $this->line("   β=$beta → MRR = " . number_format($mean, 4) . " ± " . number_format($std, 4));
            }

            $this->line("");
            $this->printTable("Variasi β (K=3, α=3.0)", "β", $results);
        }

        $this->line("");
        $this->info("✅ Gunakan tabel-tabel di atas untuk sensitivity analysis di paper iSemantic 2026.");
        $this->info("   Ini menjustifikasi pemilihan hyperparameter K=3, α=3.0, β=0.1 secara empiris.");
    }

    /**
     * Run evaluation for a specific DFA configuration across multiple runs
     */
    private function runEvaluation(
        $users, $allVectors, array $documentFrequency, int $totalDocs,
        int $topK, float $alpha, float $beta,
        int $k, int $runs, int $baseSeed
    ): array {
        $mrrPerRun = [];

        for ($run = 0; $run < $runs; $run++) {
            $seed = $baseSeed + $run;
            $totalMrr = 0;
            $validUsers = 0;

            foreach ($users as $user) {
                $favorites = $user->favorites()->pluck('recipe.id')->toArray();

                srand($seed + $user->id);
                shuffle($favorites);

                $testSize = max(1, (int)(count($favorites) * 0.3));
                $testSet = array_slice($favorites, 0, $testSize);
                $trainSet = array_slice($favorites, $testSize);

                if (count($trainSet) == 0) continue;

                // Build profile with DFA
                $profile = $this->buildProfileWithDFA(
                    $trainSet, $allVectors, $documentFrequency, $totalDocs,
                    $topK, $alpha, $beta
                );

                if (empty($profile)) continue;

                // Evaluate
                $mrr = $this->evaluateMRR($profile, $allVectors, $trainSet, $testSet, $k);
                $totalMrr += $mrr;
                $validUsers++;
            }

            if ($validUsers > 0) {
                $mrrPerRun[] = $totalMrr / $validUsers;
            }
        }

        srand();
        return $mrrPerRun;
    }

    /**
     * Build user profile with full pipeline including DFA
     */
    private function buildProfileWithDFA(
        array $trainSet, $allVectors, array $documentFrequency, int $totalDocs,
        int $topK, float $alpha, float $beta
    ): array {
        $profile = [];
        foreach ($trainSet as $trainId) {
            if (!isset($allVectors[$trainId])) continue;
            $vectorData = json_decode($allVectors[$trainId]->vector, true);
            foreach ($vectorData as $term => $weight) {
                if (!isset($profile[$term])) $profile[$term] = 0;
                $profile[$term] += $weight;
            }
        }

        $trainCount = count($trainSet);
        if ($trainCount == 0) return [];

        // Mean normalization
        foreach ($profile as $term => $weight) {
            $profile[$term] = $weight / $trainCount;
        }

        // IDF Re-weighting
        foreach ($profile as $term => $weight) {
            $df = $documentFrequency[$term] ?? 1;
            $idf = log10($totalDocs / $df);
            $profile[$term] = $weight * $idf;
        }

        // Noise filter
        $profile = array_filter($profile, function ($w) { return $w > 0.01; });

        // DFA
        arsort($profile);
        $count = 0;
        foreach ($profile as $term => $weight) {
            if ($count < $topK) {
                $profile[$term] = $weight * $alpha;
            } else {
                $profile[$term] = $weight * $beta;
            }
            $count++;
        }

        return $profile;
    }

    private function evaluateMRR(array $profile, $allVectors, array $trainSet, array $testSet, int $k): float
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

        foreach ($topK as $rank => $recId) {
            if (in_array($recId, $testSet)) {
                return 1.0 / ($rank + 1);
            }
        }
        return 0;
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

    private function printTable(string $title, string $paramLabel, array $results): void
    {
        $this->info("┌──────────────────────────────────────────┐");
        $this->info("│ $title");
        $this->info("├────────┬───────────────────────────────────┤");
        $this->line(sprintf("│ %-6s │ %-18s │", $paramLabel, "MRR (mean ± std)"));
        $this->info("├────────┼───────────────────────────────────┤");

        $bestMrr = 0;
        $bestIdx = 0;
        foreach ($results as $i => $r) {
            if ($r['mean'] > $bestMrr) {
                $bestMrr = $r['mean'];
                $bestIdx = $i;
            }
        }

        foreach ($results as $i => $r) {
            $marker = ($i === $bestIdx) ? " ◀ best" : "";
            $this->line(sprintf("│ %-6s │ %s ± %-6s%s",
                $r['K'],
                number_format($r['mean'], 4),
                number_format($r['std'], 4),
                $marker
            ));
        }

        $this->info("└────────┴───────────────────────────────────┘");
        $this->line("");
    }
}
