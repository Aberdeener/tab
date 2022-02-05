<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Rotation;
use Illuminate\Database\Seeder;

class RotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Rotation::factory()->create([
                'name' => "Week #{$i}",
                'start' => Carbon::now()->addWeeks($i - 1)->subDay(),
                'end' => Carbon::now()->addWeeks($i)->subDay()
            ]);
        }

        $users = User::all();

        foreach ($users as $user) {
            if (random_int(0, 3) == 3) {
                $user->rotations()->attach(Rotation::all()->random(1));
            }

            if (random_int(0, 6) == 6) {
                $user->rotations()->attach(Rotation::all()->random(1));
            }

            $user->rotations()->attach(Rotation::all()->random(1));
        }
    }
}