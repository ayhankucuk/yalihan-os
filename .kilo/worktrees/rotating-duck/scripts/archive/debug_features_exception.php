<?php
use App\Models\FeatureCategory;
use Illuminate\Support\Facades\DB;

try {
    echo "Feature Categories:\n";
    $cats = FeatureCategory::all(['id', 'name', 'slug', 'aktiflik_durumu']);
    foreach ($cats as $cat) {
        echo "ID: {$cat->id} | Name: {$cat->name} | Slug: {$cat->slug} | Active: {$cat->aktiflik_durumu}\n";
    }

    echo "\nLatests 5 Exceptions in Telescope:\n";
    if (Schema::hasTable('telescope_entries')) {
        $entries = DB::table('telescope_entries')
            ->where('type', 'exception')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($entries as $entry) {
            $content = json_decode($entry->content, true);
            echo "Time: " . $entry->created_at . "\n";
            echo "Message: " . ($content['message'] ?? 'N/A') . "\n";
            echo "File: " . ($content['file'] ?? 'N/A') . ":" . ($content['line'] ?? 'N/A') . "\n";
            echo "----------------------------------------\n";
        }
    } else {
        echo "Telescope table not found.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
