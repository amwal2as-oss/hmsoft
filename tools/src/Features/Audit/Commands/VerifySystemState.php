<?php

namespace HMsoft\Tools\Features\Audit\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use HMsoft\Tools\Features\Audit\Models\AuditLog;
use Illuminate\Support\Facades\Schema;

class VerifySystemState extends Command
{
    protected $signature = 'audit:state-match';
    protected $description = 'Cross-reference current database records against the immutable audit ledger to detect unauthorized SQL tampering.';

    public function handle()
    {



        $this->info('🔍 Starting Zero-Trust State Verification across all domains...');

        // Dynamically fetch every model alias registered in AppServiceProvider
        $modelsToCheck = Relation::morphMap();

        if (empty($modelsToCheck)) {
            $this->warn('No models registered in the Morph Map. Nothing to verify.');
            return Command::SUCCESS;
        }

        $errorFound = false;
        $console = $this;

        foreach ($modelsToCheck as $alias => $modelClass) {
            $console->info("➡️  Scanning ledger for domain: [{$alias}]...");


            $modelInstance = new $modelClass;
            if (!Schema::hasTable($modelInstance->getTable())) {
                $console->warn("⚠️  SKIPPED: Table '{$modelInstance->getTable()}' for domain [{$alias}] does not exist in the database.");
                continue;
            }

            // Process records in chunks of 500 to protect server RAM
            $modelClass::chunkById(500, function ($records) use ($alias, &$errorFound, $console) {

                foreach ($records as $record) {
                    // 1. Get the absolute latest audit log for this specific record
                    $latestLog = AuditLog::where('auditable_type', $alias)
                        ->where('auditable_id', $record->getKey())
                        ->latest('id')
                        ->first();

                    // 2. Catch Unauthorized INSERTS (No log exists at all)
                    if (!$latestLog) {
                        $console->warn("⚠️  WARNING: No audit log exists for {$alias} ID: {$record->getKey()}. Was this created via raw SQL?");
                        continue;
                    }

                    // 3. Catch Unauthorized RESTORES (Ghost records)
                    if ($latestLog->event === 'deleted') {
                        $console->error("🚨 CRITICAL: {$alias} ID: {$record->getKey()} exists in the database, but the ledger says it was DELETED!");
                        $errorFound = true;
                        continue;
                    }

                    // 4. Catch Unauthorized UPDATES
                    $auditedState = $latestLog->new_values ?? [];

                    foreach ($auditedState as $column => $auditedValue) {

                        // DYNAMIC CASE: Handling Arrays (Relationships like translations, pricings, categories, OR JSON columns)
                        if (is_array($auditedValue)) {
                            $currentDatabaseValue = [];
                            $isRelationship = false;

                            // Check if the column name corresponds to a relationship method on the model
                            if (method_exists($record, $column)) {
                                try {
                                    $relation = $record->$column();
                                    if ($relation instanceof Relation) {
                                        $isRelationship = true;

                                        if ($relation instanceof BelongsToMany) {
                                            // Fetch MtM Pivot Data dynamically directly from DB to match the Trait's output
                                            $currentDatabaseValue = DB::table($relation->getTable())
                                                ->where($relation->getForeignPivotKeyName(), $record->getKey())
                                                ->get()
                                                ->map(fn($row) => (array) $row)
                                                ->toArray();
                                        } else {
                                            // Fetch HasMany or other relations
                                            $currentDatabaseValue = $relation->get()->toArray();
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $isRelationship = false;
                                }
                            }

                            if (!$isRelationship) {
                                // It's a standard Array/JSON casted column
                                $currentDatabaseValue = $record->getAttribute($column);
                                if (is_string($currentDatabaseValue)) {
                                    $currentDatabaseValue = json_decode($currentDatabaseValue, true);
                                }
                            }

                            // Helper Closure: Normalizes arrays for perfect comparison
                            // Strips custom keys (like keyBy), sorts inner keys, and sorts rows so order doesn't matter.
                            $normalizeArray = function ($array) {
                                if (!is_array($array)) return [];
                                $values = array_values($array);
                                $values = array_map(function ($item) {
                                    if (is_array($item)) ksort($item);
                                    return $item;
                                }, $values);
                                usort($values, fn($a, $b) => strcmp(json_encode($a), json_encode($b)));
                                return $values;
                            };

                            $normalizedAudited = $normalizeArray($auditedValue);
                            $normalizedCurrent = $normalizeArray($currentDatabaseValue);

                            // Compare the normalized states
                            if (json_encode($normalizedCurrent) !== json_encode($normalizedAudited)) {
                                $type = $isRelationship ? 'Relationship' : 'JSON Column';
                                $console->error("🚨 CRITICAL TAMPERING DETECTED on {$alias} ID: {$record->getKey()}!");
                                $console->line("   {$type} '{$column}' was altered bypassing the application.");
                                $errorFound = true;
                            }
                            continue;
                        }

                        // STANDARD CASE: Flat columns (price, status, etc.)
                        // $currentDatabaseValue = $record->getAttribute($column);

                        // if ($currentDatabaseValue instanceof \UnitEnum) {
                        //     $currentDatabaseValue = $currentDatabaseValue instanceof \BackedEnum
                        //         ? $currentDatabaseValue->value
                        //         : $currentDatabaseValue->name;
                        // }


                        $currentDatabaseValue = $record->getRawOriginal($column);

                        // 🛡️ حماية نهائية: في حال كان الكائن لا يزال Enum أو Carbon Date
                        if ($currentDatabaseValue instanceof \UnitEnum) {
                            $currentDatabaseValue = $currentDatabaseValue instanceof \BackedEnum
                                ? $currentDatabaseValue->value
                                : $currentDatabaseValue->name;
                        } elseif (is_object($currentDatabaseValue) && method_exists($currentDatabaseValue, '__toString')) {
                            $currentDatabaseValue = (string) $currentDatabaseValue;
                        }

                        if ((string) $currentDatabaseValue !== (string) $auditedValue) {
                            $console->error("🚨 CRITICAL TAMPERING DETECTED on {$alias} ID: {$record->getKey()}!");
                            $console->line("   Column '{$column}' was altered bypassing the application.");
                            $console->line("   Expected (from Ledger): " . print_r($auditedValue, true));
                            $console->line("   Actual (in Database):   " . print_r($currentDatabaseValue, true));
                            $console->newLine();

                            $errorFound = true;
                        }
                    }
                }
            });
        }

        if (!$errorFound) {
            $this->newLine();
            $this->info('✅ State-match verified. All database records perfectly match the cryptographic ledger.');
        } else {
            $this->newLine();
            $this->error('❌ Verification Failed: System integrity has been compromised. Check logs above.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
