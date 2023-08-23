<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tips')->insert([
            [
                'title' => 'Cara menyimpan uang yang baik',
                'thumbnail' => 'nabung.jpg',
                'url' => 'https://blockchainmedia.id/cara-menabung-kripto-usdt-dan-usdc-di-pintu/',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'title' => 'Cara berinvestasi emas',
                'thumbnail' => 'emas.jpg',
                'url' => 'https://pintu.co.id/blog/cara-investasi-emas-bagi-pemula-agar-untung',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'title' => 'Cara bermain saham',
                'thumbnail' => 'saham.jpg',
                'url' => 'https://www.mncsekuritas.id/pages/3-tips-investasi-saham-untuk-pemula',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
