<div class="p-6">
  <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Mahrem Bilgiler</h3>
  @php($priv = $ilan->owner_private_data)
  @php($lastAudit = $ilan->privateAudits()->orderBy('created_at','desc')->first())
  @if($lastAudit)
    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Son güncelleyen: #{{ $lastAudit->user_id }} • {{ $lastAudit->created_at->format('d.m.Y H:i') }}</div>
  @endif
  <form method="POST" action="{{ route('admin.ilanlar.owner-private', $ilan) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    @csrf
    <div>
      <label class="block text-sm text-gray-600 dark:text-slate-200">İstenen Fiyat Min</label>
      <input type="number" name="owner_private_desired_price_min" value="{{ $priv['desired_price_min'] ?? '' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100" />
    </div>
    <div>
      <label class="block text-sm text-gray-600 dark:text-slate-200">İstenen Fiyat Max</label>
      <input type="number" name="owner_private_desired_price_max" value="{{ $priv['desired_price_max'] ?? '' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100" />
    </div>
    <div class="md:col-span-3">
      <label class="block text-sm text-gray-600 dark:text-slate-200">Özel Notlar</label>
      <textarea name="owner_private_notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100">{{ $priv['notes'] ?? '' }}</textarea>
    </div>
    <div class="md:col-span-3">
      <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg">Kaydet</button>
    </div>
  </form>
  <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Bu alanlar şifreli olarak saklanır ve yalnızca yetkili kullanıcılar görebilir.</p>
</div>