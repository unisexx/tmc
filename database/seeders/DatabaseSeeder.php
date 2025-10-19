<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // AssessmentSectionsBasicSeed::class,
            // AssessmentSectionsMediumSeed::class,
            // AssessmentSectionsAdvancedSeed::class,
            ServiceUnitDemoSeeder::class,
        ]);
    }
}
