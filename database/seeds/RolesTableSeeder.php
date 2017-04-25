<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            [
                'name' => 'uppgiftslamnare',
                'label' => 'Uppgiftslämnare',
            ],
            [
                'name' => 'personal',
                'label' => 'Personal',
            ],
            [
                'name' => 'admin',
                'label' => 'Administratör',
            ],
        ]);
    }
}
