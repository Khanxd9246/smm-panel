@extends('layouts.app')

@section('title', 'Low Quality Services')

@section('content')
<div class="container-fluid py-4">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="fw-bold mb-0">⚠️ Low Quality Services</h2>
      <small class="text-muted">Services with quality score ≤ 3 — review and take action</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-danger btn-sm" onclick="hideAllLowQuality()">🗑 Hide All</button>
      <a href="{{ route('admin.ai.services.index') }}" class="btn btn-outline-secondary btn-sm">← Services</a>
    </div>
  </div>

  @if($services->isEmpty())
    <div class="card shadow-sm text-center py-5">
      <div class="fs-1">🎉</div>
      <h4>No Low Quality Services</h4>
      <p class="text-muted">All services meet the minimum quality threshold.</p>
    </div>
  @else
  <div class="alert alert-danger">
    <strong>{{ $services->total() }} low quality services detected.</strong>
    These services have quality score ≤ 3 and may be hurting your platform's reputation.
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-dark">
          <tr>
            <th><input type="checkbox" id="sel-all" class="form-check-input"></th>
            <th>Service</th>
            <th>Issues</th>
            <th>Score</th>
            <th>Cancel Rate</th>
            <th>Success Rate</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($services as $service)
          <tr>
            <td><input type="checkbox" class="form-check-input lq-cb" value="{{ $service->id }}"></td>
            <td>
              <div class="fw-semibold">{{ $service->name }}</div>
              <small class="text-muted">{{ $service->category?->name }} · {{ $service->apiProvider?->name }}</small>
            </td>
            <td>
              @if($service->quality_issues)
                @foreach($service->quality_issues as $issue)
                  <div><small class="text-danger">• {{ $issue }}</small></div>
                @endforeach
              @else
                <small class="text-muted">No issues logged</small>
              @endif
            </td>
            <td>
              <span class="badge bg-danger fs-6">{{ $service->quality_score }}/10</span>
            </td>
            <td><span class="text-danger">{{ $service->cancel_rate }}%</span></td>
            <td><span class="text-{{ $service->success_rate >= 70 ? 'success' : 'danger' }}">{{ $service->success_rate }}%</span></td>
            <td>
              <span class="badge bg-{{ $service->status === 'active' ? 'success' : 'secondary' }}">{{ $service->status }}</span>
              @if($service->is_hidden) <span class="badge bg-warning text-dark">Hidden</span> @endif
            </td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn btn-xs btn-outline-warning" onclick="hideService({{ $service->id }})">Hide</button>
                <button class="btn btn-xs btn-outline-danger" onclick="disableService({{ $service->id }})">Disable</button>
                <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-xs btn-outline-secondary">Edit</a>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if($services->hasPages())
    <div class="card-footer">{{ $services->links() }}</div>
    @endif
  </div>

  {{-- Bulk bottom bar --}}
  <div class="mt-3 d-flex gap-2 align-items-center" id="lq-bulk" style="display:none!important;">
    <span id="lq-count" class="text-muted small">0 selected</span>
    <button class="btn btn-sm btn-warning" onclick="bulkHide()">Hide Selected</button>
    <button class="btn btn-sm btn-danger" onclick="bulkDisable()">Disable Selected</button>
  </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

document.getElementById('sel-all')?.addEventListener('change', function() {
  document.querySelectorAll('.lq-cb').forEach(cb => cb.checked = this.checked);
  updateBulkBar();
});
document.querySelectorAll('.lq-cb').forEach(cb => cb.addEventListener('change', updateBulkBar));

function updateBulkBar() {
  const n = document.querySelectorAll('.lq-cb:checked').length;
  document.getElementById('lq-bulk').style.display = n > 0 ? 'flex' : 'none';
  document.getElementById('lq-count').textContent = n + ' selected';
}

function getSelected() {
  return [...document.querySelectorAll('.lq-cb:checked')].map(cb => parseInt(cb.value));
}

async function hideService(id) {
  await bulkAction([id], 'hide');
}
async function disableService(id) {
  await bulkAction([id], 'disable');
}
async function bulkHide() {
  await bulkAction(getSelected(), 'hide');
}
async function bulkDisable() {
  await bulkAction(getSelected(), 'disable');
}
async function hideAllLowQuality() {
  if (!confirm('Hide ALL low quality services?')) return;
  const ids = [...document.querySelectorAll('.lq-cb')].map(cb => parseInt(cb.value));
  await bulkAction(ids, 'remove_low_quality');
}

async function bulkAction(ids, action) {
  if (!ids.length) return;
  const data = await fetch('{{ route("admin.ai.services.bulk") }}', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ action, service_ids: ids }),
  }).then(r => r.json());

  showToast(data.message ?? 'Done', data.success ? 'success' : 'danger');
  if (data.success) setTimeout(() => location.reload(), 1000);
}

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3 shadow`;
  t.style.zIndex = 9999;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}
</script>
@endpush
