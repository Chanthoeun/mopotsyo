<?php

namespace Database\Seeders;

use App\Models\PartnerType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PartnerType::create([
           'name' => [
                'en' => 'Headquarter',
                'km' => 'ទីស្នាក់ការកណ្តាល'
           ]
        ]);
        PartnerType::create([
           'name' => [
                'en' => 'Operational District',
                'km' => 'ស្រុកប្រតិបត្តិ'
           ]
        ]);
        PartnerType::create([
           'name' => [
                'en' => 'Referral Hospital',
                'km' => 'មន្ទីរពេទ្យបង្អែក'
           ]
        ]);
        PartnerType::create([
           'name' => [
                'en' => 'Health Center',
                'km' => 'មណ្ឌលសុខភាព'
           ]
        ]);
        PartnerType::create([
           'name' => [
                'en' => 'Supplier',
                'km' => 'អ្នកផ្គត់ផ្គង់'
           ]
        ]);
    }
}
