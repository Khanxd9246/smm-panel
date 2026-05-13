@extends('layouts.app')
@section('title','Ticket #'.$ticket->id)
@section('page-title','Ticket #'.$ticket->id)

@section('content')
<div style="max-width:760px;margin:0 auto">

  {{-- Header card --}}
  <div class="card fade-up" style="padding:22px;margin-bottom:14px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">
      <div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
          <h2 style="font-size:17px;font-weight:800;color:var(--c-text)">{{ $ticket->subject }}</h2>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <span class="chip {{ $ticket->status==='open'?'chip-red':($ticket->status==='in_progress'?'chip-yellow':'chip-green') }}">
            {{ ucfirst(str_replace('_',' ',$ticket->status)) }}
          </span>
          <span class="chip {{ $ticket->priority==='high'?'chip-red':($ticket->priority==='medium'?'chip-yellow':'chip-gray') }}">
            {{ ucfirst($ticket->priority??'normal') }} priority
          </span>
          <span style="font-size:12px;color:var(--c-muted)">From: <strong style="color:var(--c-text)">{{ $ticket->user->name }}</strong> ({{ $ticket->user->email }})</span>
          <span style="font-size:12px;color:var(--c-muted)">{{ $ticket->created_at->format('d M Y H:i') }}</span>
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        @if($ticket->status!=='closed')
        <form method="POST" action="{{ route('admin.tickets.close',$ticket->id) }}" onsubmit="return confirm('Close this ticket?')">
          @csrf
          <button type="submit" class="btn-ghost" style="padding:7px 14px;font-size:12px">
            <span class="material-symbols-outlined" style="font-size:15px">check_circle</span> Close
          </button>
        </form>
        @endif
        <a href="{{ route('admin.tickets.index') }}" class="btn-ghost" style="padding:7px 14px;font-size:12px">← Back</a>
      </div>
    </div>
  </div>

  {{-- Messages --}}
  <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px">
    @foreach($ticket->messages as $msg)
    @php $isAdmin=$msg->user->is_admin??false; @endphp
    <div class="fade-up" style="display:flex;flex-direction:column;align-items:{{ $isAdmin?'flex-end':'flex-start' }}">
      <div style="max-width:80%;background:{{ $isAdmin?'rgba(79,142,247,.1)':'var(--c-card)' }};border:1px solid {{ $isAdmin?'rgba(79,142,247,.25)':'var(--c-border)' }};border-radius:{{ $isAdmin?'14px 14px 4px 14px':'14px 14px 14px 4px' }};padding:14px 16px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
          <div style="width:26px;height:26px;border-radius:7px;background:{{ $isAdmin?'linear-gradient(135deg,var(--c-primary),var(--c-accent))':'linear-gradient(135deg,var(--c-purple),var(--c-primary))' }};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:10px;color:#fff;flex-shrink:0">{{ strtoupper(substr($msg->user->name??'U',0,1)) }}</div>
          <span style="font-size:12px;font-weight:700;color:var(--c-text)">{{ $msg->user->name }}</span>
          @if($isAdmin)<span class="chip chip-blue" style="font-size:9px;padding:1px 7px">Admin</span>@endif
          <span style="font-size:11px;color:var(--c-muted);margin-left:auto">{{ $msg->created_at->diffForHumans() }}</span>
        </div>
        <p style="font-size:13.5px;color:var(--c-text);line-height:1.6;white-space:pre-wrap">{{ $msg->message }}</p>
        @if($msg->attachment_path)
        <a href="{{ Storage::url($msg->attachment_path) }}" target="_blank" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--c-primary-l);text-decoration:none;margin-top:8px">
          <span class="material-symbols-outlined" style="font-size:15px">attach_file</span> Attachment
        </a>
        @endif
      </div>
    </div>
    @endforeach
  </div>

  {{-- Reply box --}}
  @if($ticket->status !== 'closed')
  <div class="card fade-up" style="padding:22px">
    <h3 style="font-size:14px;font-weight:700;color:var(--c-text);margin-bottom:14px">Reply</h3>
    <form method="POST" action="{{ route('admin.tickets.reply',$ticket->id) }}" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:12px">
      @csrf
      <textarea name="message" rows="5" class="inp" placeholder="Type your reply…" required minlength="5" style="resize:vertical"></textarea>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <label class="btn-ghost" style="padding:8px 14px;font-size:12px;cursor:pointer">
          <span class="material-symbols-outlined" style="font-size:15px">attach_file</span> Attach
          <input type="file" name="attachment" style="display:none">
        </label>
        <button type="submit" class="btn-primary" style="padding:9px 20px">
          <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1">send</span> Send Reply
        </button>
      </div>
    </form>
  </div>
  @else
  <div style="text-align:center;padding:20px;background:rgba(56,217,169,.05);border:1px solid rgba(56,217,169,.15);border-radius:12px">
    <span class="material-symbols-outlined" style="font-size:28px;color:var(--c-accent);opacity:.5;display:block;margin-bottom:6px;font-variation-settings:'FILL' 1">check_circle</span>
    <p style="font-size:13px;color:var(--c-muted)">This ticket is closed.</p>
  </div>
  @endif

</div>
@endsection
