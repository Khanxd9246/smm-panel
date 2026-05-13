@extends('layouts.app')
@section('title','Users')
@section('page-title','Users')
@section('css')
<style>
.tr-row{border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s;cursor:default}
.tr-row:hover{background:rgba(255,255,255,.025)}
.tr-row:last-child{border-bottom:none}
.search-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.stat-pill{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700;background:var(--c-card);border:1px solid var(--c-border)}
</style>
@endsection

@section('content')

{{-- Stats strip --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px" class="fade-up">
  <div class="stat-pill">
    <span class="material-symbols-outlined" style="font-size:15px;color:var(--c-primary-l)">people</span>
    <span style="color:var(--c-muted)">Total:</span>
    <span style="color:var(--c-text)">{{ $users->total() }}</span>
  </div>
  <div class="stat-pill">
    <span style="width:7px;height:7px;border-radius:50%;background:var(--c-accent);display:inline-block"></span>
    <span style="color:var(--c-muted)">Active:</span>
    <span style="color:var(--c-accent)">{{ $users->where('status','active')->count() }}</span>
  </div>
  <div class="stat-pill">
    <span style="width:7px;height:7px;border-radius:50%;background:var(--c-danger);display:inline-block"></span>
    <span style="color:var(--c-muted)">Banned:</span>
    <span style="color:var(--c-danger)">{{ $users->where('status','banned')->count() }}</span>
  </div>
</div>

{{-- Search --}}
<div class="card fade-up" style="padding:16px;margin-bottom:14px">
  <form action="{{ route('admin.users.index') }}" method="GET" class="search-bar">
    <div style="position:relative;flex:1;min-width:200px">
      <span class="material-symbols-outlined" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:17px;color:var(--c-muted);pointer-events:none">search</span>
      <input type="text" name="search" placeholder="Search name or email…" value="{{ request('search') }}"
        class="inp" style="padding-left:36px">
    </div>
    <button type="submit" class="btn-primary" style="padding:10px 20px">Search</button>
    @if(request('search'))
    <a href="{{ route('admin.users.index') }}" class="btn-ghost" style="padding:10px 16px">Clear</a>
    @endif
  </form>
</div>

{{-- Table --}}
<div class="card fade-up" style="overflow:hidden">
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <thead>
        <tr style="border-bottom:1px solid var(--c-border)">
          @foreach(['User','Email','Balance','Orders','Status','Joined','Actions'] as $h)
          <th style="padding:12px 16px;text-align:{{ $h==='Actions'?'right':'left' }};font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted)">{{ $h }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($users as $user)
        <tr class="tr-row">
          <td style="padding:14px 16px">
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--c-primary),var(--c-accent));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0">{{ strtoupper(substr($user->name,0,1)) }}</div>
              <span style="font-weight:600;color:var(--c-text)">{{ $user->name }}</span>
            </div>
          </td>
          <td style="padding:14px 16px;color:var(--c-muted);font-size:12.5px">{{ $user->email }}</td>
          <td style="padding:14px 16px;font-weight:700;color:var(--c-accent)">${{ number_format($user->funds,2) }}</td>
          <td style="padding:14px 16px;color:var(--c-text);font-weight:600">{{ $user->orders_count }}</td>
          <td style="padding:14px 16px">
            <span class="chip {{ $user->status==='active'?'chip-green':'chip-red' }}">
              <span style="width:5px;height:5px;border-radius:50%;background:{{ $user->status==='active'?'var(--c-accent)':'var(--c-danger)' }};display:inline-block"></span>
              {{ ucfirst($user->status) }}
            </span>
          </td>
          <td style="padding:14px 16px;font-size:12px;color:var(--c-muted)">{{ $user->created_at->format('d M Y') }}</td>
          <td style="padding:14px 16px;text-align:right">
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
              @if($user->status==='active')
              <form action="{{ route('admin.users.ban',$user->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Ban {{ addslashes($user->name) }}?')">
                @csrf
                <button type="submit" class="btn-xs btn-outline-danger">Ban</button>
              </form>
              @else
              <form action="{{ route('admin.users.unban',$user->id) }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" class="btn-xs btn-outline-success">Unban</button>
              </form>
              @endif
              <button onclick="openAddFunds({{ $user->id }},'{{ addslashes($user->name) }}')" class="btn-xs btn-ghost">+ Funds</button>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="padding:56px;text-align:center;color:var(--c-muted)">
            <span class="material-symbols-outlined" style="font-size:44px;opacity:.15;display:block;margin-bottom:10px">people</span>
            No users found
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($users->hasPages())
  <div style="padding:14px 18px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <p style="font-size:12px;color:var(--c-muted)">{{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}</p>
    <div style="display:flex;gap:6px">
      @if($users->onFirstPage())<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">← Prev</span>
      @else<a href="{{ $users->previousPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">← Prev</a>@endif
      @if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}" class="btn-ghost" style="padding:6px 14px;font-size:12px">Next →</a>
      @else<span class="btn-ghost" style="opacity:.35;cursor:not-allowed;padding:6px 14px;font-size:12px">Next →</span>@endif
    </div>
  </div>
  @endif
</div>

{{-- Add Funds Modal --}}
<div id="af-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:16px" onclick="closeAddFunds()">
  <div class="card" style="width:100%;max-width:420px;padding:26px" onclick="event.stopPropagation()">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h3 style="font-size:16px;font-weight:700;color:var(--c-text)">Add Funds</h3>
      <button onclick="closeAddFunds()" style="background:none;border:none;color:var(--c-muted);cursor:pointer;display:flex"><span class="material-symbols-outlined" style="font-size:20px">close</span></button>
    </div>
    <p style="font-size:13px;color:var(--c-muted);margin-bottom:18px">Adding to: <strong id="af-name" style="color:var(--c-text)"></strong></p>
    <form id="af-form" method="POST" style="display:flex;flex-direction:column;gap:14px">
      @csrf
      <div>
        <label style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);display:block;margin-bottom:7px">Amount (USD)</label>
        <input type="number" name="amount" step="0.01" min="0.01" class="inp" placeholder="e.g. 5.00" required>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-muted);display:block;margin-bottom:7px">Reason</label>
        <textarea name="reason" rows="3" class="inp" placeholder="Reason for adding funds…" required minlength="5" style="resize:vertical"></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="button" onclick="closeAddFunds()" class="btn-ghost" style="flex:1;justify-content:center">Cancel</button>
        <button type="submit" class="btn-primary" style="flex:1;justify-content:center">Add Funds</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
const afModal=document.getElementById('af-modal');
function openAddFunds(id,name){
  document.getElementById('af-name').textContent=name;
  document.getElementById('af-form').action=`/admin/users/${id}/add-funds`;
  afModal.style.display='flex';
}
function closeAddFunds(){ afModal.style.display='none'; }
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeAddFunds(); });
</script>
@endsection
