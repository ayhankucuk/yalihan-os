{{-- Flash Messages Partial --}}
{{-- Bu partial tüm flash mesajlarını gösterir --}}

@if(session()->has('success'))
    <div class="alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-2.5 rounded mb-4" role="alert">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>{{ session('success') }}</span>
        </div>
    </div>
@endif

@if(session()->has('error'))
    <div class="alert alert-error bg-red-100 border border-red-400 text-red-700 px-4 py-2.5 rounded mb-4" role="alert">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span>{{ session('error') }}</span>
        </div>
    </div>
@endif

@if(session()->has('warning'))
    <div class="alert alert-warning bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-2.5 rounded mb-4" role="alert">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span>{{ session('warning') }}</span>
        </div>
    </div>
@endif

@if(session()->has('info'))
    <div class="alert alert-info bg-blue-100 border border-blue-400 text-blue-700 px-4 py-2.5 rounded mb-4" role="alert">
        <div class="flex items-center">
            <i class="fas fa-info-circle mr-2"></i>
            <span>{{ session('info') }}</span>
        </div>
    </div>
@endif

{{-- Validation Errors --}}
@if($errors->any())
    <div class="alert alert-error bg-red-100 border border-red-400 text-red-700 px-4 py-2.5 rounded mb-4" role="alert">
        <div class="flex items-start">
            <i class="fas fa-exclamation-circle mr-2 mt-1 flex-shrink-0"></i>
            <div>
                <strong>Lütfen aşağıdaki hataları düzeltin:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

{{-- JavaScript için otomatik kapatma --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 5 saniye sonra success mesajlarını otomatik kapat
    setTimeout(function() {
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    // 10 saniye sonra info mesajlarını otomatik kapat
    setTimeout(function() {
        const infoAlerts = document.querySelectorAll('.alert-info');
        infoAlerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 10000);
});
</script>
