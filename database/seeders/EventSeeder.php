<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Stadium;
use App\Models\User;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get stadiums with their owners
        $stadiums = Stadium::with('user')->get();
        
        if ($stadiums->isEmpty()) {
            $this->command->info('No stadiums found. Please run StadiumSeeder first.');
            return;
        }
        
        $events = [
            [
                'name' => 'بطولة الصيف للكرة الخماسية',
                'description' => 'بطولة صيفية مفتوحة للفرق الخماسية مع جوائز قيمة للفائزين',
                'date' => now()->addDays(30)->setHour(18)->setMinute(0),
                'status' => 'active',
            ],
            [
                'name' => 'دوري الأحياء',
                'description' => 'دوري محلي للأحياء المجاورة، التسجيل مفتوح للجميع',
                'date' => now()->addDays(45)->setHour(20)->setMinute(0),
                'status' => 'active',
            ],
            [
                'name' => 'مباراة ودية - الشباب ضد الكبار',
                'description' => 'مباراة ودية بين فريق الشباب وفريق الكبار في الحي',
                'date' => now()->addDays(7)->setHour(17)->setMinute(30),
                'status' => 'active',
            ],
            [
                'name' => 'تدريب مفتوح للناشئين',
                'description' => 'جلسة تدريبية مفتوحة للناشئين تحت 16 سنة مع مدربين مختصين',
                'date' => now()->addDays(5)->setHour(16)->setMinute(0),
                'status' => 'active',
            ],
            [
                'name' => 'بطولة رمضان',
                'description' => 'بطولة رمضانية خاصة تقام بعد صلاة التراويح',
                'date' => now()->addDays(60)->setHour(22)->setMinute(0),
                'status' => 'active',
            ],
            [
                'name' => 'كأس الشركات',
                'description' => 'بطولة خاصة لفرق الشركات والمؤسسات',
                'date' => now()->addDays(20)->setHour(19)->setMinute(0),
                'status' => 'active',
            ],
            [
                'name' => 'دورة تدريبية للحكام',
                'description' => 'دورة تدريبية مكثفة للحكام الجدد مع شهادات معتمدة',
                'date' => now()->addDays(10)->setHour(10)->setMinute(0),
                'status' => 'active',
            ],
            [
                'name' => 'مهرجان كرة القدم للأطفال',
                'description' => 'مهرجان ترفيهي للأطفال دون 12 سنة مع أنشطة متنوعة',
                'date' => now()->addDays(15)->setHour(9)->setMinute(0),
                'status' => 'active',
            ],
            [
                'name' => 'البطولة الختامية',
                'description' => 'البطولة الختامية للموسم مع حفل توزيع الجوائز',
                'date' => now()->addDays(90)->setHour(18)->setMinute(0),
                'status' => 'active',
            ],
            [
                'name' => 'ليلة الأبطال',
                'description' => 'مباراة استعراضية مع نجوم كرة القدم المحليين',
                'date' => now()->addDays(25)->setHour(21)->setMinute(0),
                'status' => 'active',
            ],
        ];

        foreach ($events as $index => $eventData) {
            // Select a random stadium
            $stadium = $stadiums->random();
            
            // Create event with stadium owner as the event owner
            $eventData['stadium_id'] = $stadium->id;
            $eventData['user_id'] = $stadium->user_id;
            
            Event::create($eventData);
        }

        // Create one inactive event
        $stadium = $stadiums->random();
        Event::create([
            'name' => 'بطولة ملغية',
            'description' => 'تم إلغاء هذه البطولة لظروف خاصة',
            'date' => now()->addDays(14)->setHour(18)->setMinute(0),
            'status' => 'inactive',
            'stadium_id' => $stadium->id,
            'user_id' => $stadium->user_id,
        ]);
    }
}