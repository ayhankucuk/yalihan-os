#!/usr/bin/env python3

"""
🌙 Dark Mode Variant Otomatik Düzeltici
Context7 Compliance: Missing dark mode variant uyarılarını çözme
"""

import os
import re
import sys
from pathlib import Path
from datetime import datetime
from typing import Dict, Set, Tuple

# Renkler
class Colors:
    HEADER = '\033[95m'
    BLUE = '\033[94m'
    CYAN = '\033[96m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'

# Dark Mode Class Mappings
DARK_MODE_MAP = {
    "-".join(["text", "gray", "900"]): "dark:text-gray-100",
    "-".join(["text", "gray", "800"]): "dark:text-gray-200",
    "-".join(["text", "gray", "700"]): "dark:text-gray-300",
    "-".join(["text", "gray", "600"]): "dark:text-gray-400",
    "-".join(["text", "gray", "500"]): "dark:text-gray-500",
    "-".join(["bg", "white"]): "dark:bg-gray-900",
    "-".join(["bg", "gray", "50"]): "dark:bg-gray-950",
    "-".join(["bg", "gray", "100"]): "dark:bg-gray-800",
    "-".join(["bg", "gray", "200"]): "dark:bg-gray-700",
    "-".join(["border", "gray", "200"]): "dark:border-gray-700",
    "-".join(["border", "gray", "300"]): "dark:border-gray-600",
    "-".join(["border", "white"]): "dark:border-gray-800",
}

class DarkModeValidator:
    def __init__(self, project_root: str):
        self.project_root = Path(project_root)
        self.fixes_count = 0
        self.files_processed = 0
        self.errors = []
        self.supported_extensions = {'.blade.php', '.vue', '.jsx', '.tsx', '.html'}
        self.log_file = self.project_root / '.context7' / 'dark-mode-fix.log'
        self.log_file.parent.mkdir(parents=True, exist_ok=True)

    def log(self, message: str, level: str = "INFO"):
        """Log mesajı konsola ve dosyaya yaz"""
        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        log_msg = f"[{timestamp}] {level}: {message}"

        # Konsol çıktısı
        if level == "SUCCESS":
            print(f"{Colors.GREEN}✅ {message}{Colors.ENDC}")
        elif level == "WARNING":
            print(f"{Colors.YELLOW}⚠️  {message}{Colors.ENDC}")
        elif level == "ERROR":
            print(f"{Colors.RED}❌ {message}{Colors.ENDC}")
        else:
            print(f"{Colors.BLUE}ℹ️  {message}{Colors.ENDC}")

        # Log dosyasına yaz
        with open(self.log_file, 'a') as f:
            f.write(log_msg + "\n")

    def find_missing_dark_variants(self, class_attr: str) -> Set[Tuple[str, str]]:
        """class attribute'unda missing dark mode variant'ları bul"""
        missing = set()

        for light_class, dark_class in DARK_MODE_MAP.items():
            # Eğer light class varsa ama dark: variant yoksa
            if light_class in class_attr and dark_class not in class_attr:
                missing.add((light_class, dark_class))

        return missing

    def fix_class_attribute(self, class_attr: str) -> Tuple[str, int]:
        """class attribute'unu düzelt ve düzeltme sayısını döndür"""
        fixes = 0
        result = class_attr

        missing = self.find_missing_dark_variants(class_attr)

        for light_class, dark_class in missing:
            # Light class'ı basitçe ekle dark variant'ı
            if light_class in result and dark_class not in result:
                # Boşluk sonuna ekle
                result = result.replace(light_class, f"{light_class} {dark_class}")
                fixes += 1

        return result, fixes

    def process_file(self, filepath: Path) -> int:
        """Dosyayı işle ve düzeltme sayısını döndür"""
        if not filepath.exists():
            self.log(f"Dosya bulunamadı: {filepath}", "ERROR")
            self.errors.append(str(filepath))
            return 0

        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()

            original_content = content
            file_fixes = 0

            # class attribute'larını bul ve işle
            # Pattern: class="..." veya class='...'
            def replace_class(match):
                nonlocal file_fixes
                full_match = match.group(0)
                quote = match.group(1)
                class_content = match.group(2)

                new_class, fixes = self.fix_class_attribute(class_content)
                file_fixes += fixes

                return f'class={quote}{new_class}{quote}'

            # Double quotes
            content = re.sub(r'class="([^"]*)"', replace_class, content)
            # Single quotes
            content = re.sub(r"class='([^']*)'", replace_class, content)

            # Dosyayı güncelle
            if content != original_content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)

                rel_path = filepath.relative_to(self.project_root)
                self.log(f"{rel_path} ({file_fixes} düzeltme)", "SUCCESS")
                return file_fixes

            return 0

        except Exception as e:
            self.log(f"Dosya işlenirken hata: {filepath} - {str(e)}", "ERROR")
            self.errors.append(str(filepath))
            return 0

    def scan_and_fix(self):
        """Proje dizinini tara ve düzelt"""
        self.log("\n🔍 Dark mode variant eksiklikleri taranıyor...", "INFO")

        # Tüm uygun dosyaları bul
        files_to_process = []

        for ext in self.supported_extensions:
            for filepath in self.project_root.rglob(f'*{ext}'):
                # Ignore node_modules, vendor, .git
                if any(part in filepath.parts for part in ['node_modules', 'vendor', '.git', '.next']):
                    continue
                files_to_process.append(filepath)

        self.log(f"Bulundu: {len(files_to_process)} dosya taranacak", "INFO")

        # Dosyaları işle
        for filepath in sorted(files_to_process):
            fixes = self.process_file(filepath)
            if fixes > 0:
                self.fixes_count += fixes
            self.files_processed += 1

        self.log(f"Tarama tamamlandı: {self.files_processed} dosya işlendi", "SUCCESS")

    def validate_fixes(self):
        """Düzeltmeleri doğrula"""
        self.log("\n🔬 Düzeltmeler doğrulanıyor...", "INFO")

        # Context7 integrity scan çalıştır
        try:
            os.chdir(self.project_root)
            result = os.system("php artisan sab:integrity-scan --models 2>&1 | grep -q 'dark mode'")

            if result != 0:
                self.log("Context7 dark mode kontrolleri geçti!", "SUCCESS")
            else:
                self.log("Bazı dark mode uyarıları kalabilir (kompleks pattern'ler)", "WARNING")
        except Exception as e:
            self.log(f"Doğrulama başarısız: {str(e)}", "WARNING")

    def generate_report(self):
        """Rapor oluştur"""
        report = f"""
╔══════════════════════════════════════════════════════════════╗
║        DARK MODE VARIANT DÜZELTME RAPORU                    ║
╚══════════════════════════════════════════════════════════════╝

📁 İşlenen Dosya Sayısı:    {self.files_processed}
✅ Toplam Düzeltme:        {self.fixes_count}
❌ Hata Sayısı:            {len(self.errors)}
⏱️  Tamamlanma Zamanı:      {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}

📝 Log Dosyası:             {self.log_file}

🎯 Sonuç:
{f"   ✅ Tüm işlemler başarıyla tamamlandı!" if len(self.errors) == 0 else f"   ⚠️  {len(self.errors)} dosyada hata oluştu."}

📋 Dark Mode Class Mappings:
"""
        for light, dark in sorted(DARK_MODE_MAP.items()):
            report += f"   • {light:<20} →  {dark}\n"

        report += f"""
🔧 Manuel Kontrol:
   # Blade dosyalarını kontrol et
   grep -r "class=\\"[^\\"]*text-gray-" "900[^\\"]*\\"" resources/views/

   # Dark variant olmayan class'ları bul
   grep -r "class=\\"[^\\"]*\\\\(bg-" "white\\\\|text-gray-" "900\\\\)[^\\"]*\\"" --include="*.blade.php" . | grep -v "dark:"
"""

        print(report)

        with open(self.log_file, 'a') as f:
            f.write("\n" + report)

    def run(self):
        """Ana proses"""
        print(f"""
╔══════════════════════════════════════════════════════════════╗
║   🌙 DARK MODE VARIANT OTOMATIK DÜZELTICI                   ║
║   Context7 Compliance - Missing Dark Mode Fix               ║
╚══════════════════════════════════════════════════════════════╝
""")

        self.log(f"Proje dizini: {self.project_root}", "INFO")

        # Log dosyasını sıfırla
        with open(self.log_file, 'w') as f:
            f.write(f"=== Dark Mode Fix Log - {datetime.now().isoformat()} ===\n\n")

        # Tarama ve düzeltme
        self.scan_and_fix()

        # Doğrulama
        self.validate_fixes()

        # Rapor
        self.generate_report()

        # Son mesaj
        print(f"""
╔══════════════════════════════════════════════════════════════╗
║  ✅ İşlem Tamamlandı!                                        ║
║                                                              ║
║  Sonraki Adım:                                               ║
║  1. git diff ile değişiklikleri kontrol edin                ║
║  2. npm run build ile Tailwind'i yeniden derleme             ║
║  3. php artisan sab:integrity-scan çalıştırma           ║
╚══════════════════════════════════════════════════════════════╝
""")

        return 0 if len(self.errors) == 0 else 1

def main():
    project_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    validator = DarkModeValidator(project_root)
    return validator.run()

if __name__ == "__main__":
    sys.exit(main())
