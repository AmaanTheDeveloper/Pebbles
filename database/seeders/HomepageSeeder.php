<?php

namespace Database\Seeders;

use App\Support\HomepageContent;
use Illuminate\Database\Seeder;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        HomepageContent::seedIfEmpty();
    }
}
