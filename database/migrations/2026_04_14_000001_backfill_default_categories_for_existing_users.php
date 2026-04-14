<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultCategories = [
            'Sekolah',
            'Kerja',
            'Pribadi',
            'Kesehatan',
            'Keuangan',
        ];

        $userIds = DB::table('users')->pluck('id');

        foreach ($userIds as $userId) {
            $existingNames = DB::table('categories')
                ->where('user_id', $userId)
                ->pluck('category_name')
                ->map(fn ($name) => strtolower((string) $name))
                ->all();

            $rows = [];
            foreach ($defaultCategories as $categoryName) {
                if (in_array(strtolower($categoryName), $existingNames, true)) {
                    continue;
                }

                $rows[] = [
                    'user_id' => $userId,
                    'category_name' => $categoryName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($rows)) {
                DB::table('categories')->insert($rows);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty to avoid removing user-managed categories.
    }
};
