<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Stadium;
use App\Models\Event;
use App\Models\Favorite;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users, stadiums, and events
        $users = User::all();
        $stadiums = Stadium::all();
        $events = Event::all();
        
        if ($users->isEmpty() || $stadiums->isEmpty() || $events->isEmpty()) {
            $this->command->info('Please run UserSeeder, StadiumSeeder, and EventSeeder first.');
            return;
        }
        
        // Create random favorites for each user
        foreach ($users as $user) {
            // Each user favorites 2-5 random stadiums
            $randomStadiums = $stadiums->random(rand(2, min(5, $stadiums->count())));
            foreach ($randomStadiums as $stadium) {
                $stadium->favoriteBy($user);
            }
            
            // Each user favorites 1-3 random events
            $randomEvents = $events->random(rand(1, min(3, $events->count())));
            foreach ($randomEvents as $event) {
                $event->favoriteBy($user);
            }
        }
        
    }
}