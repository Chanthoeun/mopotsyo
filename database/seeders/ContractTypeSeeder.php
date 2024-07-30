<?php

namespace Database\Seeders;

use App\Models\ContractType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ContractType::create([
           'name' => [
                'en' => 'Probation Duration Contract',
                'km' => 'កិច្ចសន្យារយៈពេលសាកល្បង'
           ],
           'abbr' => 'PDC',   
        ]);
        ContractType::create([
           'name' => [
                'en' => 'Fixed Duration Contract',
                'km' => 'កិច្ចសន្យាមានថេរវេលាកំណត់'
           ],
           'abbr' => 'FDC',   
        ]);
        ContractType::create([
           'name' => [
                'en' => 'Unspecified Duration Contract',
                'km' => 'កិច្ចសន្យាមិនកំណត់រយៈពេល'
           ],
           'abbr' => 'UDC',   
        ]);
    }
}
