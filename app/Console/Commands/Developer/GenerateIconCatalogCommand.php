<?php

namespace App\Console\Commands\Developer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateIconCatalogCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'developer:icons';

    /**
     * The console command description.
     */
    protected $description = '🎨 Generate a local developer icon catalog from icon.blade.php';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $iconFile = resource_path('views/components/icon.blade.php');

        if (!File::exists($iconFile)) {
            $this->error("Icon component file not found at: {$iconFile}");
            return 1;
        }

        $content = File::get($iconFile);

        // Extract $ikonlar = [...]; content
        if (!preg_match('/\$ikonlar\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            $this->error("Failed to parse \$ikonlar array from icon component.");
            return 1;
        }

        $arrayContent = $matches[1];

        // Parse key => value pairs
        // Matches: 'icon-name' => '<path ... />',
        preg_match_all("/'([a-z0-9-]+)'\s*=>\s*'(.*?)'/s", $arrayContent, $pairMatches);

        $icons = [];
        for ($i = 0; $i < count($pairMatches[1]); $i++) {
            $name = $pairMatches[1][$i];
            $svgPath = $pairMatches[2][$i];
            $icons[$name] = $svgPath;
        }

        if (empty($icons)) {
            $this->error("No icons parsed.");
            return 1;
        }

        // Generate the gorgeous HTML page
        $html = $this->buildHtmlCatalog($icons);

        $outputDir = public_path('developer');
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/icons.html';
        File::put($outputPath, $html);

        $this->info("✅ Icon catalog successfully generated!");
        $this->info("👉 View it locally at: http://localhost:8000/developer/icons.html or open public/developer/icons.html directly.");

        return 0;
    }

    /**
     * Build the gorgeous premium HTML catalog page.
     */
    private function buildHtmlCatalog(array $icons): string
    {
        $iconCards = '';
        foreach ($icons as $name => $path) {
            $bladeSnippet = htmlspecialchars("<x-icon name=\"{$name}\" class=\"w-5 h-5\" />");
            
            $iconCards .= "
            <div class=\"icon-card\" data-name=\"{$name}\" onclick=\"copySnippet(this, '{$name}')\">
                <div class=\"icon-preview\">
                    <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\" class=\"w-8 h-8 text-gold\">
                        {$path}
                    </svg>
                </div>
                <div class=\"icon-name\">{$name}</div>
                <div class=\"icon-snippet-preview\">&lt;x-icon name=\"{$name}\" ... /&gt;</div>
                <textarea class=\"hidden-snippet\" readonly>{$bladeSnippet}</textarea>
            </div>
            ";
        }

        $count = count($icons);

        return <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yalıhan AI OS — İkon Kataloğu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0A1628;
            --navy-mid: #12253F;
            --navy-light: #1A3458;
            --gold: #C9A84C;
            --gold-light: #DFCA7B;
            --gold-dim: #9C8033;
            --cream: #F8F6F1;
            --cream-text: #E5DFD3;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--navy);
            color: var(--cream);
            font-family: 'Outfit', sans-serif;
            padding: 2rem;
            min-height: 100vh;
        }

        header {
            max-width: 1200px;
            margin: 0 auto 3rem auto;
            border-bottom: 1px solid var(--navy-light);
            padding-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .title-area h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--cream);
            letter-spacing: -0.02em;
        }

        .title-area h1 span {
            color: var(--gold);
        }

        .title-area p {
            color: var(--cream-text);
            margin-top: 0.5rem;
            font-size: 1rem;
            opacity: 0.8;
        }

        .search-container {
            position: relative;
            min-width: 300px;
            flex-grow: 1;
            max-width: 500px;
        }

        .search-input {
            width: 100%;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            border: 2px solid var(--navy-light);
            background-color: var(--navy-mid);
            color: var(--cream);
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 15px rgba(201, 168, 76, 0.2);
        }

        .catalog-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .count-badge {
            background-color: var(--navy-light);
            color: var(--gold);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 0.8rem;
            vertical-align: middle;
            border: 1px solid var(--gold-dim);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .icon-card {
            background-color: var(--navy-mid);
            border: 1px solid var(--navy-light);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .icon-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            background-color: var(--navy-light);
        }

        .icon-preview {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80px;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .icon-card:hover .icon-preview {
            transform: scale(1.15);
        }

        .icon-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--cream);
        }

        .icon-snippet-preview {
            font-size: 0.8rem;
            color: var(--cream-text);
            opacity: 0.5;
            font-family: monospace;
        }

        .hidden-snippet {
            display: none;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background-color: var(--gold);
            color: var(--navy);
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            transform: translateY(150%);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toast.show {
            transform: translateY(0);
        }

        .toast-icon {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

    <header>
        <div class="title-area">
            <h1>Yalıhan AI OS <span>İkon Kataloğu</span> <span class="count-badge">{$count} İkon</span></h1>
            <p>SAB Context7 Uyumlu SVG İkon Bileşen Listesi</p>
        </div>
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="İkon ara..." oninput="filterIcons()">
        </div>
    </header>

    <main class="catalog-container">
        <div class="grid" id="iconGrid">
            {$iconCards}
        </div>
    </main>

    <div class="toast" id="toast">
        <span class="toast-icon">✨</span>
        <span id="toastMessage">Blade kodu kopyalandı!</span>
    </div>

    <script>
        function filterIcons() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.icon-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function copySnippet(card, name) {
            const textarea = card.querySelector('.hidden-snippet');
            const snippet = textarea.value;
            
            navigator.clipboard.writeText(snippet).then(() => {
                showToast("<code>" + snippet + "</code> panoya kopyalandı!");
            }).catch(err => {
                console.error('Kopyalama başarısız: ', err);
            });
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            
            toastMessage.innerHTML = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>
HTML;
    }
}
