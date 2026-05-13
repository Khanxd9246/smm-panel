@extends('layouts.app')
@section('title','Manage Services')
@section('page-title','Service Manager')

@section('css')
<style>
/* ── Stats row ── */
.stat-mini{background:var(--c-card);border:1px solid var(--c-border);border-radius:10px;padding:12px 16px;display:flex;flex-direction:column;gap:2px}
.stat-mini-val{font-size:22px;font-weight:800;color:var(--c-text)}
.stat-mini-lbl{font-size:10.5px;color:var(--c-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em}

/* ── Filter bar ── */
.filter-bar{background:var(--c-card);border:1px solid var(--c-border);border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:center}

/* ── Table ── */
.svc-table{width:100%;border-collapse:collapse}
.svc-table th{font-size:10.5px;font-weight:700;color:var(--c-muted);text-transform:uppercase;letter-spacing:.07em;padding:10px 14px;text-align:left;border-bottom:1px solid var(--c-border);white-space:nowrap}
.svc-table td{padding:10px 14px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;font-size:13px;color:var(--c-text)}
[data-theme="light"] .svc-table td{border-bottom-color:var(--c-border)}
.svc-table tr:hover td{background:var(--c-nav-hover)}
.svc-table tr.selected td{background:var(--c-nav-active)}

/* ── Visibility toggle ── */
.vis-toggle{
  position:relative;width:44px;height:24px;cursor:pointer;display:inline-flex;align-items:center;
}
.vis-toggle input{opacity:0;width:0;height:0;position:absolute}
.vis-track{
  position:absolute;inset:0;border-radius:999px;background:var(--c-border);
  transition:background .2s;border:1px solid transparent;
}
.vis-toggle input:checked ~ .vis-track{background:var(--c-accent)}
.vis-thumb{
  position:absolute;width:16px;height:16px;background:#fff;border-radius:50%;
  top:3px;left:4px;transition:transform .2s;box-shadow:0 1px 4px rgba(0,0,0,.2);
}
.vis-toggle input:checked ~ .vis-thumb{transform:translateX(20px)}

/* ── Inline edit panel ── */
.edit-panel{
  display:none;background:var(--c-surface);border:1px solid var(--c-border);
  border-radius:10px;padding:16px;margin:4px 0 8px;
}
.edit-panel.open{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:700px){.edit-panel.open{grid-template-columns:1fr}}
.ep-label{font-size:10.5px;font-weight:700;color:var(--c-muted);margin-bottom:4px;letter-spacing:.04em;text-transform:uppercase}
.ep-full{grid-column:1/-1}

/* ── Speed badge ── */
.sp-instant{background:rgba(79,142,247,.12);color:var(--c-primary);border:1px solid rgba(79,142,247,.3)}
.sp-fast{background:rgba(56,217,169,.12);color:var(--c-accent);border:1px solid rgba(56,217,169,.3)}
.sp-standard{background:rgba(138,155,192,.1);color:var(--c-muted);border:1px solid rgba(138,155,192,.2)}
.sp-slow{background:rgba(247,201,72,.12);color:var(--c-warn);border:1px solid rgba(247,201,72,.3)}
.sp-badge{display:inline-flex;align-items:center;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:.04em}

/* ── Delivery preview inline ── */
.dlv-preview{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--c-muted)}
.dlv-bar{height:3px;border-radius:2px;flex:1;max-width:60px;background:var(--c-border);overflow:hidden}
.dlv-fill{height:100%;border-radius:2px;background:var(--c-primary)}

/* ── Bulk bar ── */
#bulk-bar{
  position:sticky;bottom:16px;z-index:40;
  background:var(--c-card);border:1px solid var(--c-primary);border-radius:12px;
  padding:10px 18px;display:none;align-items:center;gap:12px;
  box-shadow:0 8px 32px rgba(0,0,0,.2);margin-top:12px;
}
#bulk-bar.show{display:flex}
.price-override{
  display:flex;align-items:center;gap:4px;background:var(--c-input-bg);
  border:1px solid var(--c-border);border-radius:7px;padding:4px 10px;
  font-size:12px;color:var(--c-text);font-family:'JetBrains Mono',monospace;
}
.price-override.has-override{border-color:var(--c-warn);color:var(--c-warn)}
</style>
@endsection

@section('content')

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
  @foreach([
    ['Total Services', $stats['total'], 'inventory_2', 'var(--c-primary)'],
    ['Visible to Users', $stats['visible'], 'visibility', 'var(--c-accent)'],
    ['Hidden', $stats['hidden'], 'visibility_off', 'var(--c-muted)'],
    ['Custom Priced', $stats['custom_price'], 'price_change', 'var(--c-warn)'],
  ] as [$lbl,$val,$icon,$clr])
  <div class="stat-mini">
    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
      <span class="material-symbols-outlined" style="font-size:16px;color:{{ $clr }}">{{ $icon }}</span>
      <span class="stat-mini-lbl">{{ $lbl }}</span>
    </div>
    <div class="stat-mini-val">{{ number_format($val) }}</div>
  </div>
  @endforeach
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('admin.services.manage') }}" class="filter-bar" id="filterForm">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search services…" class="inp" style="width:220px;padding:7px 12px">
  <select name="category_id" class="inp" style="width:160px;padding:7px 12px" onchange="this.form.submit()">
    <option value="">All Categories</option>
    @foreach($categories as $cat)
    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
    @endforeach
  </select>
  <select name="visible" class="inp" style="width:130px;padding:7px 12px" onchange="this.form.submit()">
    <option value="">All Visibility</option>
    <option value="1" {{ request('visible') === '1' ? 'selected' : '' }}>Visible</option>
    <option value="0" {{ request('visible') === '0' ? 'selected' : '' }}>Hidden</option>
  </select>
  <select name="speed" class="inp" style="width:140px;padding:7px 12px" onchange="this.form.submit()">
    <option value="">All Speeds</option>
    @foreach(['instant'=>'⚡ Instant','fast'=>'🚀 Fast','standard'=>'⏱ Standard','slow'=>'🐢 Slow'] as $k=>$v)
    <option value="{{ $k }}" {{ request('speed') === $k ? 'selected' : '' }}>{{ $v }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn-primary" style="padding:8px 16px">Search</button>
  <a href="{{ route('admin.services.manage') }}" class="btn-ghost" style="padding:8px 14px">Reset</a>
  <div style="margin-left:auto;font-size:12px;color:var(--c-muted)">{{ $services->total() }} services</div>
</form>

{{-- Bulk actions bar --}}
<div id="bulk-bar">
  <span class="material-symbols-outlined" style="font-size:16px;color:var(--c-primary)">checklist</span>
  <span id="bulk-count" style="font-size:13px;font-weight:600;color:var(--c-text)">0 selected</span>
  <button type="button" class="btn-primary" style="padding:7px 14px;font-size:12px" onclick="bulkSetVisible(true)">
    <span class="material-symbols-outlined" style="font-size:14px">visibility</span> Show All
  </button>
  <button type="button" class="btn-ghost" style="padding:7px 14px;font-size:12px" onclick="bulkSetVisible(false)">
    <span class="material-symbols-outlined" style="font-size:14px">visibility_off</span> Hide All
  </button>
  <button type="button" style="margin-left:auto;background:none;border:none;color:var(--c-muted);cursor:pointer;font-size:13px" onclick="clearSelection()">✕ Clear</button>
</div>

{{-- Table --}}
<div class="card" style="overflow:hidden;margin-bottom:80px">
  <div style="overflow-x:auto">
    <table class="svc-table" id="svcTable">
      <thead>
        <tr>
          <th style="width:36px"><input type="checkbox" id="selectAll" onchange="toggleAll(this)" style="cursor:pointer"></th>
          <th>
            <a href="{{ request()->fullUrlWithQuery(['sort_by'=>'name','sort_dir'=>request('sort_by')==='name'&&request('sort_dir')==='asc'?'desc':'asc']) }}" style="color:inherit;text-decoration:none">
              Service {{ request('sort_by')==='name' ? (request('sort_dir')==='asc'?'↑':'↓') : '' }}
            </a>
          </th>
          <th>Visible</th>
          <th>
            <a href="{{ request()->fullUrlWithQuery(['sort_by'=>'admin_price','sort_dir'=>request('sort_by')==='admin_price'&&request('sort_dir')==='asc'?'desc':'asc']) }}" style="color:inherit;text-decoration:none">
              Price {{ request('sort_by')==='admin_price' ? (request('sort_dir')==='asc'?'↑':'↓') : '' }}
            </a>
          </th>
          <th>Delivery</th>
          <th>Speed</th>
          <th>
            <a href="{{ request()->fullUrlWithQuery(['sort_by'=>'sort_order','sort_dir'=>request('sort_by')==='sort_order'&&request('sort_dir')==='asc'?'desc':'asc']) }}" style="color:inherit;text-decoration:none">
              Order {{ request('sort_by')==='sort_order' ? (request('sort_dir')==='asc'?'↑':'↓') : '' }}
            </a>
          </th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($services as $svc)
        <tr data-id="{{ $svc->id }}" id="row-{{ $svc->id }}">
          <td><input type="checkbox" class="row-check" value="{{ $svc->id }}" onchange="updateBulk()" style="cursor:pointer"></td>
          <td>
            <div style="font-weight:600;color:var(--c-text);font-size:13px;margin-bottom:2px">
              {{ $svc->display_name }}
              @if($svc->admin_name)
              <span style="font-size:10px;color:var(--c-warn);margin-left:4px" title="Custom name set">✏</span>
              @endif
            </div>
            <div style="font-size:10.5px;color:var(--c-muted)">
              {{ $svc->category->name ?? '—' }} &nbsp;·&nbsp;
              <span title="Provider Service ID" style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--c-primary)">#{{ $svc->api_service_id }}</span>
              @if($svc->apiProvider) &nbsp;·&nbsp; {{ $svc->apiProvider->name }} @endif
            </div>
          </td>
          <td>
            {{-- Visibility toggle --}}
            <label class="vis-toggle" title="{{ $svc->admin_visible ? 'Visible — click to hide' : 'Hidden — click to show' }}" onclick="toggleVisibility({{ $svc->id }}, {{ $svc->admin_visible ? 'false' : 'true' }}, this)">
              <input type="checkbox" {{ $svc->admin_visible ? 'checked' : '' }} readonly>
              <div class="vis-track"></div>
              <div class="vis-thumb"></div>
            </label>
          </td>
          <td>
            @php $hasCustom = $svc->admin_price > 0; @endphp
            <div class="price-override {{ $hasCustom ? 'has-override' : '' }}" id="price-{{ $svc->id }}">
              @if($hasCustom)
              <span title="Custom price">✏</span> ${{ number_format($svc->admin_price, 4) }}
              @else
              ${{ number_format($svc->rate, 4) }}
              @endif
            </div>
            <div style="font-size:9.5px;color:var(--c-muted);margin-top:2px">per 1,000</div>
          </td>
          <td>
            <div style="font-size:12px;color:var(--c-text);font-weight:500">{{ $svc->delivery_label }}</div>
            @if($svc->estimated_start_min || $svc->estimated_complete_min)
            <div class="dlv-preview" style="margin-top:3px">
              <span>{{ $svc->estimated_start_label }}</span>
              <div class="dlv-bar">
                @php $pct = min(100, round(($svc->estimated_complete_min ?? 60) / 1440 * 100)); @endphp
                <div class="dlv-fill" style="width:{{ $pct }}%"></div>
              </div>
            </div>
            @endif
          </td>
          <td>
            <span class="sp-badge sp-{{ $svc->delivery_speed ?? 'standard' }}">
              {{ ['instant'=>'⚡ Instant','fast'=>'🚀 Fast','standard'=>'⏱ Standard','slow'=>'🐢 Slow'][$svc->delivery_speed ?? 'standard'] ?? '⏱ Standard' }}
            </span>
          </td>
          <td>
            <span style="font-size:12px;color:var(--c-muted);font-family:'JetBrains Mono',monospace">{{ $svc->sort_order }}</span>
          </td>
          <td>
            <button type="button" class="btn-ghost" style="padding:5px 10px;font-size:11px" onclick="toggleEdit({{ $svc->id }})">
              <span class="material-symbols-outlined" style="font-size:14px">tune</span> Edit
            </button>
          </td>
        </tr>
        {{-- Inline edit panel (hidden by default) --}}
        <tr id="edit-{{ $svc->id }}" style="display:none">
          <td colspan="8" style="padding:0 14px 12px">
            <div class="edit-panel open" id="panel-{{ $svc->id }}">
              <form method="POST" action="{{ route('admin.services.admin_update', $svc->id) }}" id="form-{{ $svc->id }}">
                @csrf
                {{-- Custom name --}}
                <div>
                  <div class="ep-label">Custom Display Name</div>
                  <input type="text" name="admin_name" class="inp" value="{{ $svc->admin_name }}" placeholder="{{ $svc->name }}" style="padding:8px 12px">
                </div>
                {{-- Custom price --}}
                <div>
                  <div class="ep-label">Custom Price (per 1000)</div>
                  <div style="display:flex;align-items:center;gap:6px">
                    <span style="color:var(--c-muted);font-size:13px">$</span>
                    <input type="number" name="admin_price" class="inp" value="{{ $svc->admin_price }}" placeholder="{{ $svc->rate }}" step="0.000001" min="0" style="padding:8px 12px">
                  </div>
                  <div style="font-size:10px;color:var(--c-muted);margin-top:3px">Provider cost: ${{ number_format($svc->rate,4) }} / 1000 — leave blank to use</div>
                </div>
                {{-- Delivery time label --}}
                <div>
                  <div class="ep-label">Delivery Time Label</div>
                  <input type="text" name="delivery_time_label" class="inp" value="{{ $svc->delivery_time_label }}" placeholder="e.g. 1–3 hours, Instant (< 1 min)" style="padding:8px 12px">
                </div>
                {{-- Delivery speed --}}
                <div>
                  <div class="ep-label">Speed Category</div>
                  <select name="delivery_speed" class="inp" style="padding:8px 12px">
                    @foreach(['instant'=>'⚡ Instant','fast'=>'🚀 Fast','standard'=>'⏱ Standard','slow'=>'🐢 Slow'] as $k=>$v)
                    <option value="{{ $k }}" {{ ($svc->delivery_speed ?? 'standard') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                  </select>
                </div>
                {{-- Est start --}}
                <div>
                  <div class="ep-label">Est. Start (minutes)</div>
                  <input type="number" name="estimated_start_min" class="inp" value="{{ $svc->estimated_start_min }}" placeholder="e.g. 5" min="0" style="padding:8px 12px">
                </div>
                {{-- Est complete --}}
                <div>
                  <div class="ep-label">Est. Complete (minutes)</div>
                  <input type="number" name="estimated_complete_min" class="inp" value="{{ $svc->estimated_complete_min }}" placeholder="e.g. 120" min="0" style="padding:8px 12px">
                </div>
                {{-- Sort order --}}
                <div>
                  <div class="ep-label">Sort Order (lower = first)</div>
                  <input type="number" name="sort_order" class="inp" value="{{ $svc->sort_order ?? 0 }}" min="0" style="padding:8px 12px">
                </div>
                {{-- Visible --}}
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="ep-label" style="margin-bottom:0">Visible to Users</div>
                  <label class="vis-toggle">
                    <input type="hidden" name="admin_visible" value="0">
                    <input type="checkbox" name="admin_visible" value="1" {{ $svc->admin_visible ? 'checked' : '' }}>
                    <div class="vis-track"></div>
                    <div class="vis-thumb"></div>
                  </label>
                </div>
                {{-- Description --}}
                <div class="ep-full">
                  <div class="ep-label">Custom Description (shown to users)</div>
                  <textarea name="admin_description" class="inp" rows="2" placeholder="{{ $svc->description ?? 'Optional custom description' }}" style="resize:vertical;padding:8px 12px">{{ $svc->admin_description }}</textarea>
                </div>
                {{-- Actions --}}
                <div class="ep-full" style="display:flex;gap:10px;padding-top:4px">
                  <button type="submit" class="btn-primary" style="padding:8px 18px">
                    <span class="material-symbols-outlined" style="font-size:15px">save</span> Save
                  </button>
                  <button type="button" class="btn-ghost" style="padding:8px 14px" onclick="toggleEdit({{ $svc->id }})">Cancel</button>
                </div>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--c-muted)">No services found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{-- Pagination --}}
  @if($services->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--c-border)">
    {{ $services->links() }}
  </div>
  @endif
</div>

@endsection

@section('scripts')
<script>
/* ── Visibility toggle (AJAX) ── */
function toggleVisibility(id, newVal, labelEl) {
  var token = document.querySelector('meta[name="csrf-token"]').content;
  fetch('/admin/services/' + id + '/visibility', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
    body: JSON.stringify({visible: newVal})
  })
  .then(function(r){ return r.json(); })
  .then(function(data) {
    if (data.ok) {
      var cb = labelEl.querySelector('input[type=checkbox]');
      cb.checked = data.admin_visible;
      showToast(data.admin_visible ? 'Service now visible to users' : 'Service hidden from users',
                data.admin_visible ? 'success' : 'info');
    }
  })
  .catch(function() { showToast('Update failed', 'danger'); });
}

/* ── Inline edit panel ── */
function toggleEdit(id) {
  var row = document.getElementById('edit-' + id);
  var isOpen = row.style.display !== 'none';
  // Close all others first
  document.querySelectorAll('[id^="edit-"]').forEach(function(r){ r.style.display = 'none'; });
  if (!isOpen) row.style.display = 'table-row';
}

/* ── Select all / bulk ── */
function toggleAll(cb) {
  document.querySelectorAll('.row-check').forEach(function(c){ c.checked = cb.checked; });
  updateBulk();
}
function updateBulk() {
  var checked = document.querySelectorAll('.row-check:checked');
  var bar = document.getElementById('bulk-bar');
  document.getElementById('bulk-count').textContent = checked.length + ' selected';
  bar.classList.toggle('show', checked.length > 0);
  document.getElementById('selectAll').indeterminate =
    checked.length > 0 && checked.length < document.querySelectorAll('.row-check').length;
}
function clearSelection() {
  document.querySelectorAll('.row-check').forEach(function(c){ c.checked = false; });
  document.getElementById('selectAll').checked = false;
  updateBulk();
}
function getSelectedIds() {
  return Array.from(document.querySelectorAll('.row-check:checked')).map(function(c){ return parseInt(c.value); });
}
function bulkSetVisible(visible) {
  var ids = getSelectedIds();
  if (!ids.length) return;
  var token = document.querySelector('meta[name="csrf-token"]').content;
  fetch('/admin/services/bulk-visibility', {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
    body: JSON.stringify({ids: ids, visible: visible})
  })
  .then(function(r){ return r.json(); })
  .then(function(data) {
    if (data.ok) {
      showToast(data.updated + ' service(s) ' + (visible ? 'made visible' : 'hidden'), 'success');
      setTimeout(function(){ window.location.reload(); }, 800);
    }
  });
}
</script>
@endsection
