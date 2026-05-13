@extends('layouts.app')
@section('title','Services')
@section('page-title','Services')
@section('css')
<style>
.tr-row{border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s}
.tr-row:hover{background:rgba(255,255,255,.025)}
.tr-row:last-child{border-bottom:none}
.filter-pill{padding:5px 12px;border-radius:20px;border:1px solid var(--c-border);font-size:11px;font-weight:700;color:var(--c-muted);background:transparent;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap}
.filter-pill.on{border-color:var(--c-primary);color:var(--c-primary-l);background:rgba(79,142,247,.08)}
</style>
@endsection

@section('content')

{{-- Filters --}}
<div class="card fade-up" style="padding:16px;margin-bottom:14px">
  <form action="{{ route('admin.services.index') }}" method="GET">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
      <a href="{{ request()->fullUrlWithQuery(['active'=>null,'page'=>null]) }}"
         class="filter-pill {{ request('active')===null?'on':'' }}">All</a>
      <a href="{{ request()->fullUrlWithQuery(['active'=>'1','page'=>null]) }}"
         class="filter-pill {{ request('active')==='1'?'on':'' }}">Active</a>
      <a href="{{ request()->fullUrlWithQuery(['active'=>'0','page'=>null]) }}"
         class="filter-pill {{ request('active')==='0'?'on':'' }}">Inactive</a>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <div style="position:relative;flex:1;min-width:180px">
        <span class="material-symbols-outlined" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:17px;color:var(--c-muted);pointer-events:none">search</span>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search services…" class="inp" style="padding-left:36px">
      </div>
      <div style="position:relative;min-width:160px">
        <select name="category_id" class="inp" style="padding-right:34px;appearance:none">
          <option value="">All Categories</option>
          @foreach($categories as $cat)
          <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
          @endforeach
        </select>
        <span class="material-symbols-outlined" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:16px;color:var(--c-muted);pointer-events:none">expand_more</span>
      </div>
      <button type="submit" class="btn-primary" style="padding:10px 18px">Filter</button>
      @if(request()->hasAny(['search','category_id','active']))
      <a href="{{ route('admin.services.index') }}" class="btn-ghost" style="padding:10px 14px">Clear</a>
      @endif
    </div>
  </form>
</div>

{{-- Table --}}
<div class="card fade-up" style="overflow:hidden">
  <div style="padding:14px 18px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)"><span style="color:var(--c-text);font-weight:700">{{ $services->total() }}</span> services</p>
    <a href="{{ route('admin.services.create') }}" class="btn-primary" style="padding:7px 14px;font-size:12px">
      <span class="material-symbols-outlined" style="font-size:15px">add</span> Add Service
    </a>
  </div>
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:12.5px">
      <thead>
        <tr style="border-bottom:1px solid var(--c-border)">
          @foreach(['ID','Name','Category','Rate/1K','Min','Max','Status','Actions'] as $h)
          <th style="padding:11px 14px;text-align:{{ $h==='Actions'?'right':($h==='Rate/1K'||$h==='Min'||$h==='Max'?'right':'left') }};font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);white-space:nowrap">{{ $h }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($services as $svc)
        <tr class="tr-row">
          <td style="padding:12px 14px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--c-muted)">#{{ $svc->id }}</td>
          <td style="padding:12px 14px;max-width:200px">
            <p style="font-size:12.5px;font-weight:600;color:var(--c-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $svc->name }}</p>
            @if($svc->api_service_id)
            <p style="font-size:10px;color:var(--c-muted);margin-top:2px">Provider ID: #{{ $svc->api_service_id }}</p>
            @endif
          </td>
          <td style="padding:12px 14px;font-size:12px;color:var(--c-muted)">{{ $svc->category->name??'—' }}</td>
          <td style="padding:12px 14px;text-align:right;font-weight:700;color:var(--c-primary-l)">${{ number_format($svc->rate,4) }}</td>
          <td style="padding:12px 14px;text-align:right;color:var(--c-muted)">{{ number_format($svc->min) }}</td>
          <td style="padding:12px 14px;text-align:right;color:var(--c-muted)">{{ number_format($svc->max) }}</td>
          <td style="padding:12px 14px">
            <span class="chip {{ $svc->is_active?'chip-green':'chip-gray' }}">
              <span style="width:5px;height:5px;border-radius:50%;background:{{ $svc->is_active?'var(--c-accent)':'var(--c-muted)' }};display:inline-block"></span>
              {{ $svc->is_active?'Active':'Off' }}
            </span>
          </td>
          <td style="padding:12px 14px;text-align:right">
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:5px">
              <a href="{{ route('admin.services.edit',$svc->id) }}" style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:5px;border-radius:6px;transition:color .15s;text-decoration:none" onmouseover="this.style.color='var(--c-primary-l)'" onmouseout="this.style.color='var(--c-muted)'" title="Edit">
                <span class="material-symbols-outlined" style="font-size:16px">edit</span>
              </a>
              <form method="POST" action="{{ route('admin.services.toggle',$svc->id) }}" style="display:inline">
                @csrf
                <button type="submit" title="{{ $svc->is_active?'Deactivate':'Activate' }}" style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:5px;border-radius:6px;transition:color .15s" onmouseover="this.style.color='{{ $svc->is_active?'var(--c-danger)':'var(--c-accent)' }}'" onmouseout="this.style.color='var(--c-muted)'">
                  <span class="material-symbols-outlined" style="font-size:16px">{{ $svc->is_active?'visibility_off':'visibility' }}</span>
                </button>
              </form>
              <form method="POST" action="{{ route('admin.services.destroy',$svc->id) }}" onsubmit="return confirm('Delete this service?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" title="Delete" style="background:none;border:none;cursor:pointer;color:var(--c-muted);display:flex;padding:5px;border-radius:6px;transition:color .15s" onmouseover="this.style.color='var(--c-danger)'" onmouseout="this.style.color='var(--c-muted)'">
                  <span class="material-symbols-outlined" style="font-size:16px">delete</span>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="padding:60px;text-align:center;color:var(--c-muted)">
            <span class="material-symbols-outlined" style="font-size:44px;opacity:.15;display:block;margin-bottom:10px">storefront</span>
            No services found
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($services->hasPages())
  <div style="padding:14px 18px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)">{{ $services->firstItem() }}–{{ $services->lastItem() }} of {{ $services->total() }}</p>
    <div style="display:flex;gap:6px">
      @if($services->onFirstPage())<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">← Prev</span>
      @else<a href="{{ $services->previousPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">← Prev</a>@endif
      @if($services->hasMorePages())<a href="{{ $services->nextPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">Next →</a>
      @else<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">Next →</span>@endif
    </div>
  </div>
  @endif
</div>
@endsection
