<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stadium;
use App\Models\User;

class StadiumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users with 'owner' role to assign stadiums to
        $owners = User::role('owner')->get();
        
        // If no owners exist, create one for seeding purposes
        if ($owners->isEmpty()) {
            $owner = User::create([
                'name' => 'Stadium Owner',
                'phone_number' => '218912345681',
                'password' => bcrypt('aaaa5555'),
                'type' => 'owner',
                'status' => 'active',
            ]);
            $owner->assignRole('owner');
            
            $owners = collect([$owner]);
        }
        
        $stadiums = [
            [
                'name' => 'ملعب الصداقة',
                'location' => 'طريق طرابلس، طرابلس',
                'latitude' => 32.8709,
                'longitude' => 13.1773,
                'price_per_hour' => '150',
                'capacity' => '22',
                'description' => 'ملعب قانوني مع إضاءة ليلية وخدمات كاملة',
                'rating' => 4.7,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب النصر',
                'location' => 'شارع الجزائر، بنغازي',
                'latitude' => 32.1149,
                'longitude' => 20.0686,
                'price_per_hour' => '130',
                'capacity' => '18',
                'description' => 'ملعب مناسب للمباريات التنافسية والتدريب',
                'rating' => 4.5,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الفتح',
                'location' => 'شارع جمال عبد الناصر، طرابلس',
                'latitude' => 32.8870,
                'longitude' => 13.1912,
                'price_per_hour' => '180',
                'capacity' => '24',
                'description' => 'ملعب ممتاز مع مرافق حديثة وإضاءة عالية الجودة',
                'rating' => 4.8,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الحرية',
                'location' => 'شارع الفاتح، مصراتة',
                'latitude' => 32.3754,
                'longitude' => 15.0925,
                'price_per_hour' => '120',
                'capacity' => '16',
                'description' => 'ملعب صغير مناسب للمباريات الودية',
                'rating' => 4.0,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الزاوية',
                'location' => 'شارع الاستقلال، الزاوية',
                'latitude' => 32.7635,
                'longitude' => 12.7244,
                'price_per_hour' => '140',
                'capacity' => '20',
                'description' => 'ملعب جيد مع خدمات متكاملة',
                'rating' => 4.2,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الوحدة',
                'location' => 'طريق الساحلي، سرت',
                'latitude' => 31.2055,
                'longitude' => 16.5895,
                'price_per_hour' => '110',
                'capacity' => '14',
                'description' => 'ملعب مثالي للتدريب والمباريات الودية',
                'rating' => 3.9,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الأهلي',
                'location' => 'شارع الاستقلال، البيضاء',
                'latitude' => 32.7635,
                'longitude' => 21.7553,
                'price_per_hour' => '160',
                'capacity' => '22',
                'description' => 'ملعب عشب صناعي عالي الجودة مع مرافق ممتازة',
                'rating' => 4.6,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب النجوم',
                'location' => 'شارع الملك، طبرق',
                'latitude' => 32.0841,
                'longitude' => 23.9535,
                'price_per_hour' => '130',
                'capacity' => '16',
                'description' => 'ملعب مجهز بأحدث المعدات وإضاءة عالية الجودة',
                'rating' => 4.3,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الأمل',
                'location' => 'طريق السكة، الخمس',
                'latitude' => 32.6500,
                'longitude' => 14.2667,
                'price_per_hour' => '120',
                'capacity' => '18',
                'description' => 'ملعب مناسب للبطولات المحلية والتدريب',
                'rating' => 4.1,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الرياضة',
                'location' => 'شارع طرابلس، زليتن',
                'latitude' => 32.4674,
                'longitude' => 14.5685,
                'price_per_hour' => '135',
                'capacity' => '20',
                'description' => 'ملعب عشب صناعي مع مرافق وخدمات كاملة',
                'rating' => 4.4,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الشباب',
                'location' => 'شارع السلام، الزنتان',
                'latitude' => 31.9310,
                'longitude' => 12.5823,
                'price_per_hour' => '115',
                'capacity' => '14',
                'description' => 'ملعب مثالي للشباب والأطفال',
                'rating' => 3.8,
                'status' => 'open',
            ],
            [
                'name' => 'ملعب الجزيرة',
                'location' => 'شارع البحر، الزاوية',
                'latitude' => 32.7520,
                'longitude' => 12.7261,
                'price_per_hour' => '145',
                'capacity' => '16',
                'description' => 'ملعب قريب من الشاطئ مع إطلالة رائعة',
                'rating' => 4.5,
                'status' => 'open',
            ],
        ];

        // Create stadiums and associate with random owners
        foreach ($stadiums as $stadiumData) {
            $owner = $owners->random();
            
            $stadiumData['user_id'] = $owner->id;
            
            Stadium::create($stadiumData);
        }

        // Create one closed stadium
        Stadium::create([
            'name' => 'ملعب تحت الصيانة',
            'location' => 'طريق المطار، طرابلس',
            'latitude' => 32.8635,
            'longitude' => 13.2289,
            'price_per_hour' => '200',
            'capacity' => '24',
            'description' => 'ملعب مغلق مؤقتاً للصيانة',
            'rating' => 0,
            'status' => 'closed',
            'user_id' => $owners->random()->id,
        ]);
    }
}