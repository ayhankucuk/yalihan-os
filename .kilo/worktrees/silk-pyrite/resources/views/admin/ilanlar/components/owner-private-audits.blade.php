<div class="p-6">
  <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Mahrem Değişiklik Geçmişi</h3>
  @php($audits = $ilan->privateAudits()->orderBy('created_at','desc')->limit(10)->get())
  @if($audits->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Kayıt bulunamadı.</p>
  @else
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 dark:text-gray-400">
            <th class="px-3 py-2">Kullanıcı</th>
            <th class="px-3 py-2">Zaman</th>
            <th class="px-3 py-2">Önce</th>
            <th class="px-3 py-2">Sonra</th>
          </tr>
        </thead>
        <tbody class="text-gray-900 dark:text-slate-100 dark:text-white">
          @foreach($audits as $a)
          <tr class="border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <td class="px-3 py-2">#{{ $a->user_id }}</td>
            <td class="px-3 py-2">{{ $a->created_at->format('d.m.Y H:i') }}</td>
            <td class="px-3 py-2 font-mono">{{ json_encode($a->changes['before'] ?? [], JSON_UNESCAPED_UNICODE) }}</td>
            <td class="px-3 py-2 font-mono">{{ json_encode($a->changes['after'] ?? [], JSON_UNESCAPED_UNICODE) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>