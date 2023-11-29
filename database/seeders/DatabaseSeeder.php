<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('units')->insert([
            'name' => 'APTO: 100',
            'id_owner' => '1'
        ]);
        DB::table('units')->insert([
            'name' => 'APTO: 101',
            'id_owner' => '1'
        ]);
        DB::table('units')->insert([
            'name' => 'APTO: 200',
            'id_owner' => '0'
        ]);
        DB::table('units')->insert([
            'name' => 'APTO: 201',
            'id_owner' => '0'
        ]);
        DB::table('areas')->insert([
            'allowed' => '1',
            'title' => 'Academia',
            'cover' => 'gym.jpg',
            'days' => '1,2,3,5,6',
            'start_time' => '06:00:00',
            'end_time' => '23:00:00'
        ]);
        DB::table('areas')->insert([
            'allowed' => '1',
            'title' => 'Piscina',
            'cover' => 'pool.jpg',
            'days' => '1,2,3,4,5,6',
            'start_time' => '09:00:00',
            'end_time' => '22:00:00'
        ]);
        DB::table('areas')->insert([
            'allowed' => '1',
            'title' => 'Churrasqueira',
            'cover' => 'barbecue.jpg',
            'days' => '4,5,6',
            'start_time' => '10:00:00',
            'end_time' => '23:00:00'
        ]);
        DB::table('walls')->insert([
            'title' => 'Aviso de teste importante',
            'body' => 'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Sed aliquet est ante, ultricies suscipit urna pharetra in. ',
            'datecreated' => '2023-11-15 13:00:00'
        ]);
        DB::table('walls')->insert([
            'title' => 'Alerta a todos os moradores',
            'body' => 'Cras ac tortor sollicitudin neque mattis rhoncus non nec odio. Pellentesque vitae volutpat nulla. Mauris eu odio dignissim, sollicitudin lectus quis, aliquam arcu.',
            'datecreated' => '2023-11-19 15:32:00'
        ]);
    }
}