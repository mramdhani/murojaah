<?php
require __DIR__ . '/api/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'murojaah',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'timezone' => '+07:00',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$userId = 1444;
$startDate = Carbon\Carbon::now('Asia/Jakarta')->subWeeks(22)->startOfWeek();

$heatmapData = App\Models\ReviewLog::where('user_id', $userId)
    ->where('reviewed_at', '>=', $startDate)
    ->selectRaw('DATE(reviewed_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->orderBy('date', 'asc')
    ->get()
    ->pluck('count', 'date')
    ->toArray();

echo "Start Date: " . $startDate->toDateTimeString() . "\n";
echo "Heatmap Data Keys & Values:\n";
print_r($heatmapData);
