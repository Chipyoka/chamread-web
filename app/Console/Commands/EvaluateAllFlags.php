<?php
// app/Console/Commands/EvaluateAllFlags.php

namespace App\Console\Commands;

use App\Models\CustomerAccount;
use App\Models\Reading;
use App\Models\User;
use App\Services\FlagService;
use Illuminate\Console\Command;

class EvaluateAllFlags extends Command
{
    protected $signature = 'flags:evaluate-all 
                            {type : Entity type (reading, account, meter_reader)}
                            {--chunk=100 : Records per chunk for memory efficiency}
                            {--id= : Process a single record ID (for testing)}
                            {--dry-run : Show what would be flagged without saving}';

    protected $description = 'Evaluate all records of a type against active flag rules';

    public function handle(FlagService $flagService): int
    {
        $type = $this->argument('type');
        $chunkSize = (int) $this->option('chunk');

        // Validate type
        if (!in_array($type, ['reading', 'account', 'meter_reader'])) {
            $this->error("Invalid type. Use: reading, account, or meter_reader");
            return self::FAILURE;
        }

        // Get the model class and query
        [$modelClass, $query] = $this->getQuery($type);

        // Single record mode (for debugging)
        if ($id = $this->option('id')) {
            return $this->processSingle($modelClass, $id, $flagService);
        }

        // Check if there are any active rules for this type
        $rulesCount = \App\Models\FlagRule::active()
            ->whereHas('flag', fn($q) => $q->where('applies_to', $type)->where('active', true))
            ->count();

        if ($rulesCount === 0) {
            $this->warn("No active rules found for type: {$type}");
            return self::SUCCESS;
        }

        $this->info("Found {$rulesCount} active rule(s) for {$type}");
        $this->info("Processing records in chunks of {$chunkSize}...");

        $totalProcessed = 0;
        $totalFlagged = 0;
        $flagCounts = [];

        $query->chunk($chunkSize, function ($records) use ($flagService, &$totalProcessed, &$totalFlagged, &$flagCounts) {
            foreach ($records as $record) {
                $totalProcessed++;

                if ($this->option('dry-run')) {
                    // Dry run: just show what would happen
                    $matched = app(\App\Services\RuleEvaluator::class)->evaluate($record);
                    if ($matched->isNotEmpty()) {
                        $totalFlagged++;
                        foreach ($matched as $rule) {
                            $code = $rule->flag->code;
                            $flagCounts[$code] = ($flagCounts[$code] ?? 0) + 1;
                        }
                    }
                } else {
                    // Actual evaluation and flagging
                    $applied = $flagService->evaluate($record);
                    if (!empty($applied)) {
                        $totalFlagged++;
                        foreach ($applied as $code) {
                            $flagCounts[$code] = ($flagCounts[$code] ?? 0) + 1;
                        }
                    }
                }
            }

            $this->output->write('.');
        });

        $this->newLine(2);
        $this->info("Evaluation complete.");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', $totalProcessed],
                ['Total Flagged', $totalFlagged],
                ['Dry Run', $this->option('dry-run') ? 'Yes' : 'No'],
            ]
        );

        if (!empty($flagCounts)) {
            $this->info("\nFlag breakdown:");
            $this->table(
                ['Flag Code', 'Count'],
                collect($flagCounts)->map(fn($count, $code) => [$code, $count])->toArray()
            );
        }

        return self::SUCCESS;
    }

    /**
     * Process a single record by ID.
     */
    private function processSingle(string $modelClass, string $id, FlagService $flagService): int
    {
        $record = $modelClass::find($id);

        if (!$record) {
            $this->error("{$modelClass} with ID {$id} not found.");
            return self::FAILURE;
        }

        $this->info("Evaluating {$modelClass} #{$id}...");

        if ($this->option('dry-run')) {
            $matched = app(\App\Services\RuleEvaluator::class)->evaluate($record);
            if ($matched->isEmpty()) {
                $this->info("No rules matched.");
            } else {
                foreach ($matched as $rule) {
                    $this->line("  ✓ {$rule->flag->code}: {$rule->field} {$rule->operator} {$rule->value}");
                }
            }
            return self::SUCCESS;
        }

        $applied = $flagService->evaluate($record);

        if (empty($applied)) {
            $this->info("No flags applied.");
        } else {
            $this->info("Applied flags: " . implode(', ', $applied));
        }

        return self::SUCCESS;
    }

    /**
     * Get the model class and base query for a given type.
     */
    private function getQuery(string $type): array
    {
        return match ($type) {
            'reading' => [
                Reading::class,
                Reading::query()->with('account') // Eager load for nested rules
            ],
            'account' => [
                CustomerAccount::class,
                CustomerAccount::query()
            ],
            'meter_reader' => [
                User::class,
                User::meterReaders() // Scope for meter readers only
            ],
        };
    }
}