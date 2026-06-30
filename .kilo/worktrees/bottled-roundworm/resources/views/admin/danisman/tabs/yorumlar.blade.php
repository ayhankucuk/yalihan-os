<div class="space-y-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                <i class="fas fa-star mr-2"></i>
                Yorumlar ({{ $performans['onayli_yorum'] }} Onaylı)
            </h3>
            @if($performans['ortalama_rating'] > 0)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Ortalama Puan: 
                    <span class="font-semibold text-yellow-600">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $performans['ortalama_rating'])
                                <i class="fas fa-star"></i>
                            @else
                                <i class="far fa-star"></i>
                            @endif
                        @endfor
                        {{ number_format($performans['ortalama_rating'], 1) }}/5
                    </span>
                </p>
            @endif
        </div>
    </div>

    @if($yorumlar && $yorumlar->count() > 0)
        <div class="space-y-4">
            @foreach($yorumlar as $yorum)
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                {{ strtoupper(mb_substr($yorum->author_name, 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ $yorum->author_name }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $yorum->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $yorum->rating)
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                @else
                                    <i class="far fa-star text-gray-300 text-sm"></i>
                                @endif
                            @endfor
                            <span class="ml-2 text-sm font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $yorum->rating }}/5</span>
                        </div>
                    </div>
                    @if($yorum->yorum)
                        <p class="text-sm text-gray-700 dark:text-slate-200 leading-relaxed whitespace-pre-line dark:text-slate-300">{{ $yorum->yorum }}</p>
                    @endif
                    @if($yorum->kisi)
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-user mr-1"></i>
                                Müşteri: {{ $yorum->kisi->tam_ad ?? $yorum->kisi->name ?? 'N/A' }}
                                @if($yorum->kisi->email)
                                    ({{ $yorum->kisi->email }})
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $yorumlar->appends(request()->query())->links() }}
        </div>
    @else
        <div class="text-center py-12 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <i class="fas fa-star text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Henüz yorum bulunmuyor</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Bu danışman için henüz onaylı yorum yapılmamıştır.</p>
        </div>
    @endif
</div>

