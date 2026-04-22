<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function teamPrefix(string $teamName): string
{
    $normalized = Str::of($teamName)->trim()->lower()->replaceMatches('/[^a-z0-9\s]+/', '')->value();

    $mapped = [
        'development' => 'Dev',
        'developer' => 'Dev',
        'engineering' => 'Eng',
        'engineer' => 'Eng',
        'human resources' => 'HR',
        'hr' => 'HR',
        'finance' => 'Fin',
        'marketing' => 'Mkt',
        'product' => 'Prd',
        'operations' => 'Ops',
        'design' => 'Dsg',
        'support' => 'Sup',
        'sales' => 'Sal',
        'quality assurance' => 'QA',
        'qa' => 'QA',
    ][$normalized] ?? null;

    if ($mapped) {
        return $mapped;
    }

    $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($words) > 1) {
        $initials = strtoupper(implode('', array_map(
            static fn (string $word): string => substr($word, 0, 1),
            array_slice($words, 0, 3)
        )));

        return $initials ?: 'Tem';
    }

    $base = ucfirst(substr($normalized ?: 'team', 0, 3));

    return strlen($base) >= 2 ? $base : 'Tem';
}

$rows = DB::table('users')
    ->join('employee_profiles', 'employee_profiles.user_id', '=', 'users.id')
    ->join('departments', 'departments.id', '=', 'employee_profiles.department_id')
    ->select([
        'users.id',
        'users.name',
        'users.employee_code',
        'departments.name as team',
    ])
    ->orderBy('departments.name')
    ->orderBy('users.id')
    ->get();

if ($rows->isEmpty()) {
    fwrite(STDERR, "No employee records found.\n");
    exit(1);
}

$nextByPrefix = [];
$usedCodes = [];
$updated = [];

foreach ($rows as $row) {
    $team = (string) $row->team;
    $prefix = teamPrefix($team);

    if (!array_key_exists($prefix, $nextByPrefix)) {
        $maxExisting = DB::table('users')
            ->where('employee_code', 'like', $prefix . '-emp%')
            ->where('id', '!=', $row->id)
            ->pluck('employee_code')
            ->reduce(function (int $carry, string $code) use ($prefix): int {
                if (preg_match('/^' . preg_quote($prefix, '/') . '\-emp(\d+)$/i', $code, $matches)) {
                    return max($carry, (int) $matches[1]);
                }

                return $carry;
            }, 0);

        $nextByPrefix[$prefix] = $maxExisting + 1;
    }

    do {
        $candidate = sprintf('%s-emp%03d', $prefix, $nextByPrefix[$prefix]);
        $nextByPrefix[$prefix]++;
    } while (isset($usedCodes[$candidate]));

    $usedCodes[$candidate] = true;

    if ((string) $row->employee_code !== $candidate) {
        DB::table('users')
            ->where('id', $row->id)
            ->update([
                'employee_code' => $candidate,
                'updated_at' => now(),
            ]);
    }

    $updated[] = sprintf(
        '%s | %s | %s -> %s',
        $row->id,
        $row->name,
        $row->employee_code ?: '-',
        $candidate
    );
}

echo 'SYNCED ' . count($updated) . " EMPLOYEE CODE(S)\n";
foreach ($updated as $line) {
    echo $line . PHP_EOL;
}
