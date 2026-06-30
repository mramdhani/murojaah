<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class CleanupGuestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-guests {--days=30 : Hapus guest yang lebih lama dari X hari}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pembersihan akun guest lama yang sudah tidak aktif';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $date = Carbon::now()->subDays($days);

        $this->info("Mencari akun guest yang dibuat sebelum {$date->toDateString()}...");

        // Cari user guest yang dibuat lebih dari $days lalu,
        // DAN tidak punya aktivitas (session/review) di $days terakhir.
        $query = User::where('email', 'like', 'guest_%')
                     ->where('created_at', '<', $date)
                     ->whereNotExists(function ($q) use ($date) {
                         $q->select(\Illuminate\Support\Facades\DB::raw(1))
                           ->from('memorization_sessions')
                           ->whereColumn('memorization_sessions.user_id', 'users.id')
                           ->where('started_at', '>=', $date);
                     })
                     ->whereNotExists(function ($q) use ($date) {
                         $q->select(\Illuminate\Support\Facades\DB::raw(1))
                           ->from('review_logs')
                           ->whereColumn('review_logs.user_id', 'users.id')
                           ->where('reviewed_at', '>=', $date);
                     });

        $count = $query->count();

        if ($count === 0) {
            $this->info("Tidak ada akun guest lama yang ditemukan.");
            return;
        }

        // Jika script dipanggil otomatis tanpa opsi interaktif, kita tidak ingin command stuck di konfirmasi
        // Tapi artisan command ini juga bisa dijalankan manual
        if ($this->option('no-interaction') || $this->confirm("Ditemukan {$count} akun guest lama. Yakin ingin menghapusnya? Data hafalan terkait juga akan terhapus.", true)) {
            $query->delete(); // Karena menggunakan onDelete('cascade') di DB, relasi juga akan terhapus otomatis di DB
            $this->info("Berhasil menghapus {$count} akun guest lama.");
        } else {
            $this->info("Proses dibatalkan.");
        }
    }
}
