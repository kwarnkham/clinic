<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $now = now();
            DB::table('products')->where('item_id', '!=', 1)->orderBy('id')->lazy()->each(function ($product) use ($now) {
                DB::table('product_stock')->insert([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'stock' => $product->stock,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            });
        })
            ->dailyAt('02:30');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
