@extends('layouts.app')

@section('title', 'Duplicate Service Detector')

@section('content')
<div class="container-fluid py-4">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="fw-bold mb-0">🔍 Duplicate Service Detector</h2>
      <small class="text-muted">AI-detected service duplicates by similarity, category &amp; provider</small>
    </div>
    <a href="{{ route('admin.ai.services.index') }}" class="btn btn-outline-secondary btn-sm">← Services</a>
  </div>

  @if(empty($groups))
    <div class="card shadow-sm text-center py-5">
      <div class="fs-1">🎉</div>
      <h4>No Duplicates Detected</h4>
      <p class="text-muted">Your service catalog looks clean! Run quality scoring to refresh detection.</p>
    </div>
  @else
    <div class="alert alert-warning">
      Found <strong>{{ count($groups) }}</strong> duplicate group(s). Review and resolve each group below.
    </div>

    @foreach($groups as $gIdx => $group)
    <div class="card shadow-sm mb-4" id="group-{{ $gIdx }}">
      <div class="card-header d-flex align-items-center justify-content-between bg-warning bg-opacity-10">
        <div>
          <strong>Group {{ $gIdx + 1 }}</strong> — {{ count($group) }} similar services
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button"
          data-bs-toggle="collapse" data-bs-target="#group-body-{{ $gIdx }}">
          Expand
        </button>
      </div>
      <div class="collapse show" id="group-body-{{ $gIdx }}">
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Keep?</th>
                <th>Name</th>
                <th>Category</th>
                <th>Provider</th>
                <th>Price</th>
                <th>Quality</th>
                <th>Orders</th>
              </tr>
            </thead>
            <tbody>
              @foreach($group as $sIdx => $service)
              <tr>
                <td>
                  <input type="radio" name="keep_{{ $gIdx }}" value="{{ $service['id'] }}"
                    class="form-check-input keep-radio" data-group="{{ $gIdx }}"
                    {{ $sIdx === 0 ? 'checked' : '' }}>
                </td>
                <td>
                  <div class="fw-semibold">{{ $service['name'] }}</div>
                  <small class="text-muted">ID: {{ $service['id'] }} · API: {{ $service['api_service_id'] }}</small>
                </td>
                <td><small>{{ $service['category']['name'] ?? '—' }}</small></td>
                <td><small>{{ $service['api_provider']['name'] ?? '—' }}</small></td>
                <td><strong class="text-success">${{ number_format($service['rate'], 4) }}</strong></td>
                <td>
                  <span class="badge bg-{{ match(true) {
                    ($service['quality_score'] ?? 5) >= 8 => 'success',
                    ($service['quality_score'] ?? 5) >= 5 => 'warning',
                    default => 'danger',
                  } }}">{{ $service['quality_score'] ?? '?' }}/10</span>
                </td>
                <td><small>{{ $service['orders_count'] ?? 0 }}</small></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <small class="text-muted">Select which service to KEEP; the rest will be hidden &amp; disabled.</small>
          <button class="btn btn-warning btn-sm"
            onclick="resolveGroup({{ $gIdx }}, {{ json_encode(array_column($group, 'id')) }})">
            ✅ Resolve This Group
          </button>
        </div>
      </div>
    </div>
    @endforeach
  @endif

</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';

async function resolveGroup(gIdx, allIds) {
  const keepId = document.querySelector(`input[name="keep_${gIdx}"]:checked`)?.value;
  if (!keepId) return alert('Select which service to keep.');

  const hideIds = allIds.filter(id => id != keepId);

  if (!confirm(`Keep service #${keepId} and hide ${hideIds.length} others?`)) return;

  const data = await fetch('{{ route("admin.ai.duplicates.resolve") }}', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ keep_id: parseInt(keepId), hide_ids: hideIds.map(Number), group: allIds }),
  }).then(r => r.json());

  if (data.success) {
    document.getElementById(`group-${gIdx}`).style.opacity = '.3';
    document.getElementById(`group-${gIdx}`).innerHTML += '<div class="text-center py-2 text-success fw-bold">✅ Resolved</div>';
  } else {
    alert('Error: ' + (data.message ?? 'Unknown error'));
  }
}
</script>
@endpush
