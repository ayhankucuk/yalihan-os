@extends('admin.layouts.admin')

@section('title', 'PropertyHub Governance & Rollback')

@section('content')
<div class="py-4 dark:bg-slate-900">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800 dark:text-slate-100">PropertyHub Governance</h1>
        <div class="badge bg-primary px-3 py-2">PropertyHub V3</div>
    </div>

    <div class="bg-white dark:bg-slate-900 shadow-sm rounded-xl mb-6 overflow-hidden border border-gray-100 dark:border-slate-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center bg-gray-50/50 dark:bg-slate-900/50">
            <h6 class="m-0 font-bold text-blue-700 dark:text-blue-400">Configuration Versions</h6>
        </div>
        <div class="p-0">
            <div class="overflow-x-auto">
                <table class="table table-hover mb-0 dark:text-slate-300">
                    <thead class="bg-gray-50/50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Hash</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">State</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Rules</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Created At</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Applied At</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($versions as $version)
                            <tr class="align-middle border-bottom dark:border-slate-800">
                                <td class="font-monospace small">
                                    <span title="{{ $version->version_hash }}">{{ substr($version->version_hash, 0, 12) }}...</span>
                                </td>
                                <td>
                                    <span class="badge {{ match($version->governance_state) {
                                        'ACTIVE' => 'bg-success',
                                        'APPROVED' => 'bg-info',
                                        'REVIEW' => 'bg-warning',
                                        'ARCHIVED' => 'bg-secondary',
                                        default => 'bg-light text-dark'
                                    } }}">
                                        {{ $version->governance_state }}
                                    </span>
                                </td>
                                <td>{{ $version->rules_count }}</td>
                                <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $version->applied_at ? $version->applied_at->format('Y-m-d H:i') : '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        @if($version->governance_state === 'DRAFT')
                                            <button onclick="changeState('{{ $version->id }}', 'submit')" class="bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 p-2 rounded-lg hover:bg-blue-100 transition-colors" title="Submit for Review">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        @endif

                                        @if($version->governance_state === 'REVIEW')
                                            <button onclick="changeState('{{ $version->id }}', 'approve')" class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 p-2 rounded-lg hover:bg-green-100 transition-colors" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif

                                        @if($version->governance_state === 'APPROVED')
                                            <button onclick="changeState('{{ $version->id }}', 'activate')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2" title="Activate Now">
                                                <i class="fas fa-rocket"></i> Activate
                                            </button>
                                        @endif

                                        @if($version->governance_state === 'ARCHIVED')
                                            <button onclick="promptRollback('{{ $version->id }}')" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2" title="Rollback to this version">
                                                <i class="fas fa-undo"></i> Rollback
                                            </button>
                                        @endif

                                        <a href="{{ route('admin.property-hub.versions.diff', ['version' => $version->id, 'other' => $activeVersion?->id ?? $version->id]) }}"
                                           class="bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 p-2 rounded-lg hover:bg-gray-200 transition-colors" title="View Diff with Active">
                                            <i class="fas fa-columns"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800">
            {{ $versions->links() }}
        </div>
    </div>
</div>

{{-- Rollback Modal --}}
<div class="modal fade" id="rollbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content dark:bg-slate-900 dark:text-white">
            <div class="modal-header border-0">
                <h5 class="modal-title">Confirm Rollback</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to rollback to this version? This will ARCHIVE the current ACTIVE version and reset the Circuit Breaker.</p>
                <div class="form-group">
                    <label>Reason for Rollback</label>
                    <textarea id="rollbackReason" class="form-control dark:bg-slate-800 dark:border-slate-700 dark:text-white" rows="3" required></textarea>
                </div>
            </div>
            <div class="bg-gray-50/50 dark:bg-slate-900/50 px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-3">
                <button type="button" class="text-gray-600 dark:text-slate-400 font-medium text-sm px-4 py-2" data-dismiss="modal">Cancel</button>
                <button type="button" onclick="executeRollback()" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-bold transition-all text-sm">Execute Rollback</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedVersionId = null;

function changeState(id, action) {
    if(!confirm(`Are you sure you want to ${action} this version?`)) return;

    fetch(`/admin/property-hub/versions/${id}/${action}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    }).then(res => res.json())
      .then(data => {
          if(data.success) {
              window.location.reload();
          } else {
              alert(data.message || 'Error occurred');
          }
      });
}

function promptRollback(id) {
    selectedVersionId = id;
    $('#rollbackModal').modal('show');
}

function executeRollback() {
    const reason = $('#rollbackReason').val();
    if(!reason) return alert('Reason is required');

    fetch(`/admin/property-hub/versions/${selectedVersionId}/rollback`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ reason })
    }).then(res => res.json())
      .then(data => {
          if(data.success) {
              window.location.reload();
          } else {
              alert(data.message || 'Rollback failed');
          }
      });
}
</script>
@endpush
@endsection
