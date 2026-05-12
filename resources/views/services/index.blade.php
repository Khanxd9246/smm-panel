@extends('layouts.app')
@section('title', 'Services')

@push('styles')
<style>
/* ── Layout ── */
.service-card{border:none;border-radius:14px;transition:all .25s cubic-bezier(.4,0,.2,1)}
.service-card:hover{transform:translateY(-4px) scale(1.01);box-shadow:0 12px 36px rgba(79,142,247,.15)!important}
.delivery-badge{font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:12px}
.tag-chip{display:inline-block;font-size:.64rem;padding:1px 7px;border-radius:10px;margin:1px;background:#f1f5f9;color:#475569}
.quality-bar{height:5px;border-radius:3px;background:#e2e8f0;overflow:hidden}
.quality-fill{height:100%;border-radius:3px}

/* ── Filter pills with animation ── */
.filter-pill{border-radius:20px;font-size:.8rem;padding:4px 14px;transition:all .2s cubic-bezier(.4,0,.2,1)}
.filter-pill:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(99,102,241,.2)}
.filter-pill.active{background:#6366f1;color:white;border-color:#6366f1;transform:scale(1.05)}

/* ── Sort tabs ── */
.sort-pill{border-radius:20px;font-size:.78rem;padding:4px 12px;border:1px solid #dee2e6;cursor:pointer;transition:all .2s;background:white;color:#6c757d;white-space:nowrap}
.sort-pill.active{background:#6366f1;color:white;border-color:#6366f1;transform:scale(1.02)}
.sort-pill:hover:not(.active){background:#f3f4f6;border-color:#aaa;transform:translateY(-1px)}

/* ── Price range filter ── */
.price-filter-bar{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.pf-chip{font-size:.72rem;padding:3px 10px;border-radius:12px;border:1px solid #dee2e6;cursor:pointer;background:white;color:#6c757d;transition:all .2s}
.pf-chip:hover{transform:translateY(-1px);box-shadow:0 2px 8px rgba(16,185,129,.15)}
.pf-chip.active{background:#10b981;color:white;border-color:#10b981;transform:scale(1.02)}

/* ── ID search ── */
.id-search-box{background:linear-gradient(135deg,#fef9c3,#fefce8);border:1.5px solid #fde047;border-radius:12px;padding:10px 14px}
.id-search-box label{font-size:.7rem;font-weight:700;color:#854d0e;text-transform:uppercase;letter-spacing:.05em}
.id-found-card{background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:8px 12px;margin-top:6px;display:none}
.id-notfound{background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:6px 12px;margin-top:6px;font-size:.75rem;color:#991b1b;display:none}

/* ── View toggle ── */
.view-toggle .btn{padding:4px 10px;transition:all .2s}
.view-toggle .btn:hover{transform:scale(1.1)}
.view-toggle .btn.active{background:#6366f1;color:white;border-color:#6366f1;transform:scale(1.05)}

/* ── List view ── */
.service-list-row{border-radius:10px;border:1px solid #e5e7eb;transition:all .25s cubic-bezier(.4,0,.2,1);padding:10px 14px;margin-bottom:6px;display:flex;align-items:center;gap:12px;background:white}
.service-list-row:hover{border-color:#6366f1;box-shadow:0 4px 16px rgba(99,102,241,.15);transform:translateX(4px)}

/* ── Highlight ── */
.service-card.id-highlight,.service-list-row.id-highlight{box-shadow:0 0 0 3px #fde047,0 4px 16px rgba(250,204,21,.3)!important;border-color:#fde047!important}

/* ── Compare floater ── */
.compare-float{position:fixed;bottom:20px;right:20px;z-index:999;background:#1e1b4b;border:1px solid rgba(173,198,255,.3);border-radius:16px;padding:12px 16px;color:white;box-shadow:0 8px 32px rgba(0,0,0,.3);display:none;min-width:200px;backdrop-filter:blur(8px)}
.compare-float.visible{display:block;animation:slideInUp .3s ease}
.compare-float-chip{font-size:.7rem;background:rgba(255,255,255,.1);border-radius:6px;padding:2px 8px;margin:2px;display:inline-flex;align-items:center;gap:4px}
.compare-float-chip button{background:none;border:none;color:rgba(255,255,255,.5);cursor:pointer;padding:0;font-size:12px;line-height:1}

@keyframes slideInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

/* ── Compare modal ── */
#compare-modal-overlay{display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.6);backdrop-filter:blur(4px)}
#compare-modal-box{background:white;border-radius:16px;max-width:700px;margin:40px auto;padding:24px;max-height:85vh;overflow-y:auto}

/* Fade-in cards with stagger */
.service-card-wrap{opacity:0;transform:translateY(15px)}
.service-card-wrap.loaded{animation:cardIn .4s ease forwards}
@keyframes cardIn{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}

/* ── Quick finder animation ── */
.id-search-box{transition:all .3s}
.id-search-box:focus-within{box-shadow:0 0 0 3px rgba(253,224,71,.3)}
</style>
@endpush

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="fw-bold mb-0" style="animation:fadeUp .4s ease">📦 Our Services</h2>
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted">{{ $services->total() }} services</small>
            {{-- View toggle --}}
            <div class="view-toggle btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" id="view-grid-btn" onclick="setView('grid',this)" title="Grid view">⊞</button>
                <button type="button" class="btn btn-outline-secondary" id="view-list-btn" onclick="setView('list',this)" title="List view">☰</button>
            </div>
        </div>
    </div>

    {{-- ── ID Quick Finder ──────────────────────────────────────────────── --}}
    <div class="id-search-box mb-3 fade-up" style="animation-delay:.1s">
        <label>⚡ Find by Provider Service ID</label>
        <div class="input-group mt-1" style="max-width:360px">
            <input type="number" id="id-finder-input" class="form-control form-control-sm" placeholder="Enter service ID e.g. 1042" oninput="idFinder(this.value)">
            <button class="btn btn-warning btn-sm" onclick="idFinderGo()">Find</button>
        </div>
        <div class="id-found-card" id="id-found-card"></div>
        <div class="id-notfound" id="id-notfound-msg"></div>
    </div>

    {{-- ── Main filter card ──────────────────────────────────────────────── --}}
    <div class="card shadow-sm mb-3 border-0 fade-up" style="animation-delay:.15s">
        <div class="card-body p-3">

            {{-- Quick filter pills --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
                @php
                $filters=[''=>'🌍 All','instant'=>'⚡ Instant','fast'=>'🚀 Fast','refill'=>'🔄 Refill','premium'=>'⭐ Premium','high_quality'=>'🏆 High Quality','best_seller'=>'🔥 Best Seller'];
                @endphp
                @foreach($filters as $val=>$label)
                <a href="{{ request()->fullUrlWithQuery(['filter'=>$val,'page'=>null]) }}"
                   class="btn btn-outline-secondary filter-pill {{ request('filter','')===$val?'active':'' }}">{{ $label }}</a>
                @endforeach
            </div>

            {{-- Sort pills --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="text-muted" style="font-size:.75rem;align-self:center">Sort:</span>
                @php $sorts=['price'=>'💰 Cheapest','quality'=>'🏆 Quality','speed'=>'⚡ Fastest','popularity'=>'🔥 Popular','price_high'=>'💎 Highest','name'=>'🔤 Name']; @endphp
                @foreach($sorts as $val=>$label)
                <a href="{{ request()->fullUrlWithQuery(['sort'=>$val,'page'=>null]) }}"
                   class="sort-pill {{ request('sort','price')===$val?'active':'' }}">{{ $label }}</a>
                @endforeach
            </div>

            {{-- Price range chips --}}
            <div class="price-filter-bar mb-3">
                <span class="text-muted" style="font-size:.75rem">Max rate:</span>
                @foreach(['0.5'=>'Under $0.50','1'=>'Under $1.00','2'=>'Under $2.00','5'=>'Under $5.00','9999'=>'Any Price'] as $max=>$label)
                <a href="{{ request()->fullUrlWithQuery(['max_rate'=>($max=='9999'?null:$max),'page'=>null]) }}"
                   class="pf-chip {{ (request('max_rate','9999')===$max||(request('max_rate')===null&&$max==='9999'))?'active':'' }}">{{ $label }}</a>
                @endforeach
            </div>

            {{-- Search + category row --}}
            <form method="GET" class="row g-2 align-items-end">
                @if(request('filter'))   <input type="hidden" name="filter"    value="{{ request('filter') }}"> @endif
                @if(request('sort'))     <input type="hidden" name="sort"      value="{{ request('sort') }}"> @endif
                @if(request('max_rate')) <input type="hidden" name="max_rate"  value="{{ request('max_rate') }}"> @endif
                <div class="col-md-5">
                    <label class="form-label form-label-sm text-muted mb-1">🔍 Search by name</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search services..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm text-muted mb-1">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category')==$cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                </div>
                @if(request()->hasAny(['search','category','sort','filter','max_rate']))
                <div class="col-md-2">
                    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    {{-- ── Results ──────────────────────────────────────────────────────── --}}
    @if($services->isEmpty())
    <div class="text-center py-5 text-muted fade-up">
        <div class="fs-1">🔍</div>
        <h5>No services found</h5>
        <a href="{{ route('services.index') }}" class="btn btn-outline-primary btn-sm mt-2">Clear Filters</a>
    </div>
    @else

    {{-- GRID VIEW --}}
    <div id="view-grid">
        <div class="row g-3" id="services-grid">
            @foreach($services as $i=>$service)
            <div class="col-md-6 col-xl-4 service-card-wrap"
                 style="animation-delay:{{ $i * 0.04 }}s"
                 data-svcid="{{ $service->id }}"
                 data-providerid="{{ $service->api_service_id }}"
                 data-name="{{ strtolower($service->name) }}">
                <div class="card service-card shadow-sm h-100" id="svc-card-{{ $service->id }}">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1 pe-2">
                                <h6 class="mb-1 fw-bold" style="line-height:1.3">
                                    {{ $service->name }}
                                    @if($service->is_premium)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">⭐ Premium</span>
                                    @endif
                                </h6>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <small class="text-muted">{{ $service->category?->name }}</small>
                                    @if($service->api_service_id)
                                    <span class="badge bg-light text-secondary border" style="font-size:.6rem">ID #{{ $service->api_service_id }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fs-5 fw-bold text-success">${{ number_format($service->rate, 4) }}</div>
                                <small class="text-muted">per 1000</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            @if($service->delivery_badge)
                            <span class="delivery-badge bg-{{ match($service->delivery_badge){'instant'=>'success','fast'=>'info','slow'=>'warning',default=>'secondary'} }} text-white">
                                {{ $service->delivery_label }}
                            </span>
                            @endif
                            @if($service->has_refill)
                            <span class="delivery-badge bg-primary text-white">🔄 Refill</span>
                            @endif
                            @if(($service->quality_score??0)>=8)
                            <span class="delivery-badge bg-warning text-dark">🏆 Top Quality</span>
                            @endif
                            @if(($service->orders_count??0)>500)
                            <span class="delivery-badge bg-danger text-white">🔥 Popular</span>
                            @endif
                        </div>

                        @if($service->delivery_time_preview)
                        <div class="bg-light rounded p-2 mb-3 small">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">⏱ Delivery:</span>
                                <strong>{{ $service->delivery_time_preview }}</strong>
                            </div>
                        </div>
                        @endif

                        @if($service->quality_score)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Quality</small>
                                <small class="fw-bold text-{{ $service->quality_color }}">{{ $service->quality_score }}/10</small>
                            </div>
                            <div class="quality-bar">
                                <div class="quality-fill bg-{{ $service->quality_color }}" style="width:{{ $service->quality_score*10 }}%"></div>
                            </div>
                        </div>
                        @endif

                        @if($service->success_rate>0)
                        <div class="d-flex gap-3 mb-3 small">
                            <div><span class="text-success fw-bold">{{ $service->success_rate }}%</span> <span class="text-muted">success</span></div>
                            @if($service->orders_count>0)
                            <div><span class="fw-bold">{{ number_format($service->orders_count) }}</span> <span class="text-muted">orders</span></div>
                            @endif
                        </div>
                        @endif

                        @if($service->all_tags)
                        <div class="mb-3">
                            @foreach(array_slice($service->all_tags,0,5) as $tag)
                            <span class="tag-chip">{{ $tag }}</span>
                            @endforeach
                        </div>
                        @endif

                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                <span>Min: {{ number_format($service->min) }}</span>
                                <span>Max: {{ number_format($service->max) }}</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('orders.create',['service_id'=>$service->id]) }}" class="btn btn-primary btn-sm flex-grow-1">Order Now →</a>
                                <button class="btn btn-outline-secondary btn-sm compare-add-btn"
                                    data-id="{{ $service->id }}"
                                    data-name="{{ $service->name }}"
                                    data-rate="{{ $service->rate }}"
                                    data-min="{{ $service->min }}"
                                    data-max="{{ $service->max }}"
                                    data-quality="{{ $service->quality_score }}"
                                    data-refill="{{ $service->has_refill?1:0 }}"
                                    data-orders="{{ $service->orders_count }}"
                                    data-providerid="{{ $service->api_service_id }}"
                                    onclick="toggleServiceCompare(this)"
                                    title="Compare">⊕</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- LIST VIEW --}}
    <div id="view-list" style="display:none">
        @foreach($services as $i=>$service)
        <div class="service-list-row" style="animation-delay:{{ $i * 0.03 }}s"
             id="svc-list-{{ $service->id }}"
             data-svcid="{{ $service->id }}"
             data-providerid="{{ $service->api_service_id }}">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="fw-bold text-dark" style="font-size:.9rem">{{ $service->name }}</span>
                    @if($service->is_premium)<span class="badge bg-warning text-dark" style="font-size:.6rem">⭐ Premium</span>@endif
                    @if($service->has_refill)<span class="badge bg-primary" style="font-size:.6rem">🔄 Refill</span>@endif
                    @if($service->delivery_badge==='instant')<span class="badge bg-success" style="font-size:.6rem">⚡ Instant</span>@endif
                    @if($service->api_service_id)<span class="badge bg-light text-secondary border" style="font-size:.6rem">ID #{{ $service->api_service_id }}</span>@endif
                </div>
                <div class="d-flex gap-3 text-muted" style="font-size:.75rem">
                    <span>{{ $service->category?->name }}</span>
                    <span>Min: {{ number_format($service->min) }}</span>
                    <span>Max: {{ number_format($service->max) }}</span>
                    @if($service->quality_score)<span>★ {{ $service->quality_score }}/10</span>@endif
                    @if($service->success_rate>0)<span>{{ $service->success_rate }}% success</span>@endif
                </div>
            </div>
            <div class="text-end flex-shrink-0 d-flex align-items-center gap-3">
                <div>
                    <div class="fw-bold text-success">${{ number_format($service->rate,4) }}</div>
                    <small class="text-muted" style="font-size:.65rem">per 1000</small>
                </div>
                <a href="{{ route('orders.create',['service_id'=>$service->id]) }}" class="btn btn-primary btn-sm">Order →</a>
                <button class="btn btn-outline-secondary btn-sm compare-add-btn"
                    data-id="{{ $service->id }}"
                    data-name="{{ $service->name }}"
                    data-rate="{{ $service->rate }}"
                    data-min="{{ $service->min }}"
                    data-max="{{ $service->max }}"
                    data-quality="{{ $service->quality_score }}"
                    data-refill="{{ $service->has_refill?1:0 }}"
                    data-orders="{{ $service->orders_count }}"
                    data-providerid="{{ $service->api_service_id }}"
                    onclick="toggleServiceCompare(this)">⊕</button>
            </div>
        </div>
        @endforeach
    </div>

    @if($services->hasPages())
    <div class="d-flex justify-content-center mt-4">{{ $services->links() }}</div>
    @endif
    @endif

</div>

{{-- ── Compare floater ── --}}
<div class="compare-float" id="compare-float">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span style="font-size:.75rem;font-weight:700;color:#c7d2fe">Compare Services</span>
        <button onclick="clearServiceCompare()" style="background:none;border:none;color:rgba(255,255,255,.5);cursor:pointer;font-size:12px">Clear</button>
    </div>
    <div id="compare-float-chips" class="mb-2"></div>
    <button onclick="openServiceCompareModal()" class="btn btn-sm w-100" style="background:#6366f1;color:white;font-size:.75rem" id="compare-go-btn" disabled>Compare Now →</button>
</div>

{{-- ── Compare Modal ── --}}
<div id="compare-modal-overlay">
    <div id="compare-modal-box">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Service Comparison</h5>
            <button onclick="document.getElementById('compare-modal-overlay').style.display='none'" class="btn-close"></button>
        </div>
        <div id="compare-modal-content"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
  // Animate service cards on load
  setTimeout(function(){
    document.querySelectorAll('.service-card-wrap').forEach(function(el){
      el.classList.add('loaded');
    });
    // GSAP stagger animation if available
    if(typeof gsap!=='undefined'){
      gsap.fromTo('.service-card-wrap',
        {opacity:0,y:20},
        {opacity:1,y:0,duration:0.5,stagger:0.05,ease:'power2.out'}
      );
    }
  },100);
});

// ── View toggle ────────────────────────────────────────────────────────────
function setView(v,el){
    document.getElementById('view-grid').style.display=v==='grid'?'':'none';
    document.getElementById('view-list').style.display=v==='list'?'':'none';
    document.querySelectorAll('.view-toggle .btn').forEach(b=>b.classList.remove('active'));
    el.classList.add('active');
    localStorage.setItem('svc_view',v);
}
// Restore preference
(function(){
    const v=localStorage.getItem('svc_view')||'grid';
    if(v==='list'){
        document.getElementById('view-grid').style.display='none';
        document.getElementById('view-list').style.display='';
        document.getElementById('view-list-btn').classList.add('active');
        document.getElementById('view-grid-btn').classList.remove('active');
    }
})();

// ── ID Finder ──────────────────────────────────────────────────────────────
<?php $serviceData = collect($services->items())->map(function($s) { return ['id'=>$s->id,'api_service_id'=>$s->api_service_id,'name'=>$s->name,'rate'=>$s->rate]; })->all(); ?>
const allServiceData = <?php echo json_encode($serviceData); ?>;

function idFinder(val){
    const foundEl=document.getElementById('id-found-card');
    const nfEl=document.getElementById('id-notfound-msg');
    if(!val||!val.trim()){foundEl.style.display='none';nfEl.style.display='none';return;}
    const num=parseInt(val);
    const match=allServiceData.find(s=>parseInt(s.api_service_id)===num||parseInt(s.id)===num);
    if(match){
        foundEl.style.display='block';
        nfEl.style.display='none';
        foundEl.innerHTML=`<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><strong class="text-success">✓ Found:</strong> ${esc(match.name)} <span class="badge bg-success">$${parseFloat(match.rate).toFixed(4)}/1K</span></div>
            <div class="d-flex gap-2">
                <button class="btn btn-warning btn-sm" onclick="scrollToService(${match.id})">Jump to Card ↓</button>
                <a href="/orders/create?service_id=${match.id}" class="btn btn-primary btn-sm">Order Now →</a>
            </div>
        </div>`;
    } else {
        foundEl.style.display='none';
        nfEl.style.display='block';
        nfEl.innerHTML=`✗ No service with ID <strong>${num}</strong> on this page. Try a different page or filter.`;
    }
}

function idFinderGo(){idFinder(document.getElementById('id-finder-input').value);}

function scrollToService(id){
    const card=document.getElementById('svc-card-'+id)||document.getElementById('svc-list-'+id);
    if(!card) return;
    card.classList.add('id-highlight');
    card.scrollIntoView({behavior:'smooth',block:'center'});
    setTimeout(()=>card.classList.remove('id-highlight'),3500);
}

// ── Compare Feature ────────────────────────────────────────────────────────
let compareItems=[];

function toggleServiceCompare(btn){
    const d=btn.dataset;
    const id=parseInt(d.id);
    const idx=compareItems.findIndex(c=>c.id===id);
    if(idx>=0){
        compareItems.splice(idx,1);
        btn.textContent='⊕';
        btn.classList.remove('btn-info');
        btn.classList.add('btn-outline-secondary');
    } else {
        if(compareItems.length>=3){alert('Max 3 services for comparison.');return;}
        compareItems.push({id,name:d.name,rate:parseFloat(d.rate),min:parseInt(d.min),max:parseInt(d.max),quality:d.quality,refill:d.refill,orders:d.orders,providerid:d.providerid});
        btn.textContent='✓';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-info');
    }
    updateCompareFloat();
}

function updateCompareFloat(){
    const float=document.getElementById('compare-float');
    const chips=document.getElementById('compare-float-chips');
    const goBtn=document.getElementById('compare-go-btn');
    chips.innerHTML='';
    compareItems.forEach(s=>{
        const chip=document.createElement('span');
        chip.className='compare-float-chip';
        chip.innerHTML=`${esc(s.name.substring(0,20))} <button onclick="removeCompareItem(${s.id})">×</button>`;
        chips.appendChild(chip);
    });
    float.className='compare-float'+(compareItems.length>=1?' visible':'');
    goBtn.disabled=compareItems.length<2;
}

function removeCompareItem(id){
    const btn=document.querySelector(`.compare-add-btn[data-id="${id}"]`);
    if(btn) toggleServiceCompare(btn);
    else {
        compareItems=compareItems.filter(c=>c.id!==id);
        updateCompareFloat();
    }
}

function clearServiceCompare(){
    compareItems.forEach(s=>{
        document.querySelectorAll(`.compare-add-btn[data-id="${s.id}"]`).forEach(b=>{
            b.textContent='⊕';b.classList.remove('btn-info');b.classList.add('btn-outline-secondary');
        });
    });
    compareItems=[];
    updateCompareFloat();
}

function openServiceCompareModal(){
    if(compareItems.length<2) return;
    const overlay=document.getElementById('compare-modal-overlay');
    const content=document.getElementById('compare-modal-content');
    const rows=[
        ['Rate / 1K',   s=>`<strong class="text-success">$${s.rate.toFixed(4)}</strong>`],
        ['Min / Max',   s=>`${Number(s.min).toLocaleString()} / ${Number(s.max).toLocaleString()}`],
        ['Quality',     s=>s.quality?`<span class="${s.quality>=8?'text-success':s.quality>=5?'text-info':'text-warning'} fw-bold">★ ${s.quality}/10</span>`:'—'],
        ['Refill',      s=>s.refill=='1'?'✅ Yes':'❌ No'],
        ['Total Orders',s=>parseInt(s.orders||0).toLocaleString()],
        ['Provider ID', s=>s.providerid?`#${s.providerid}`:`#${s.id}`],
    ];
    let html=`<div class="table-responsive"><table class="table table-bordered table-sm">
        <thead class="table-light"><tr><th>Feature</th>`;
    compareItems.forEach(s=>{html+=`<th class="text-center">${esc(s.name.substring(0,30))}</th>`;});
    html+=`</tr></thead><tbody>`;
    rows.forEach(([lbl,fn])=>{
        html+=`<tr><td class="text-muted fw-semibold" style="white-space:nowrap">${lbl}</td>`;
        compareItems.forEach(s=>{html+=`<td class="text-center">${fn(s)}</td>`;});
        html+=`</tr>`;
    });
    html+=`</tbody></table></div><div class="d-flex gap-2 flex-wrap mt-3">`;
    compareItems.forEach(s=>{
        html+=`<a href="/orders/create?service_id=${s.id}" class="btn btn-primary flex-grow-1" style="min-width:100px">Order: ${esc(s.name.substring(0,20))}</a>`;
    });
    html+=`</div>`;
    content.innerHTML=html;
    overlay.style.display='block';
}

document.getElementById('compare-modal-overlay').addEventListener('click',function(e){if(e.target===this)this.style.display='none';});

function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
</script>
@endpush
