<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperatorCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('operator_cards')->insert([
            [
                'name' => 'Telkomsel',
                'status' => 'Active',
                'thumbnail' => 'telkomsel.png',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'name' => 'Sigtel',
                'status' => 'Active',
                'thumbnail' => 'sigtel.png',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'name' => 'Indosat',
                'status' => 'Active',
                'thumbnail' => 'indoesat.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
