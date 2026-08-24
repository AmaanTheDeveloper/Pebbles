<?php

namespace Database\Seeders;

use App\Support\GalleryContentImporter;
use Illuminate\Database\Seeder;

class GalleryItemSeeder extends Seeder
{
    public function run(): void
    {
        app(GalleryContentImporter::class)->importIfEmpty();
    }
}
