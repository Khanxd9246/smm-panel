@extends('layouts.app')
@section('title', 'New Order')
@section('page-title', 'New Order Wizard')

@section('css')
<style>
    .step-wrap{display:flex;align-items:center;margin-bottom:2rem}
    .step-item{display:flex;flex-direction:column;align-items:center;gap:4px;min-width:56px}
    .step-circle{width:36px;height:36px;border-radius:50%;border:2px solid #424754;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#8c909f;transition:all .3s}
    .step-circle.active{border-color:#adc6ff;color:#adc6ff;background:rgba(173,198,255,.1);box-shadow:0 0 12px rgba(173,198,255,.25)}
    .step-circle.done{border-color:#4edea3;background:#4edea3;color:#003824}
    .step-label{font-size:10px;color:#8c909f;font-weight:600;letter-spacing:.08em;text-transform:uppercase}
    .step-circle.active + .step-label{color:#adc6ff}
    .step-line{flex:1;height:1px;background:#424754;margin-bottom:18px}
    .platform-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
    @media(max-width:480px){.platform-grid{grid-template-columns:repeat(3,1fr)}}
    .pf-card{background:rgba(23,31,51,.6);border:1.5px solid rgba(173,198,255,.1);border-radius:14px;padding:14px 8px;display:flex;flex-direction:column;align-items:center;gap:8px;cursor:pointer;transition:all .2s}
    .pf-card:hover{border-color:#adc6ff;transform:translateY(-2px)}
    .pf-card.selected{border-color:#adc6ff;background:rgba(173,198,255,.09);box-shadow:0 0 16px rgba(173,198,255,.2)}
    .pf-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff}
    .pf-name{font-size:11px;font-weight:600;color:#8c909f;text-align:center}
    .pf-card.selected .pf-name{color:#adc6ff}
    .type-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
    @media(max-width:480px){.type-grid{grid-template-columns:repeat(2,1fr)}}
    .type-btn{background:rgba(23,31,51,.5);border:1.5px solid transparent;border-radius:12px;padding:14px 8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;transition:all .2s}
    .type-btn:hover{border-color:#adc6ff}
    .type-btn.selected{border-color:#adc6ff;background:rgba(173,198,255,.09)}
    .type-btn .ti{font-size:22px;color:#8c909f}
    .type-btn.selected .ti{color:#adc6ff}
    .type-btn span:last-child{font-size:11px;font-weight:600;color:#8c909f;text-align:center}
    .type-btn.selected span:last-child{color:#adc6ff}
    .svc-row{padding:12px 14px;border-radius:10px;border:1.5px solid rgba(173,198,255,.1);cursor:pointer;transition:all .15s;margin-bottom:6px;background:rgba(23,31,51,.3);display:block;width:100%;text-align:left}
    .svc-row:hover{border-color:#adc6ff;background:rgba(173,198,255,.05)}
    .svc-row.selected{border-color:#adc6ff;background:rgba(173,198,255,.09)}
    .svc-row.highlighted{border-color:#f5c518;background:rgba(245,197,24,.06);box-shadow:0 0 10px rgba(245,197,24,.15)}
    .step-content{display:none}.step-content.active{display:block}
    .tier-eco{background:rgba(78,222,163,.1);color:#4edea3;border:1px solid rgba(78,222,163,.3)}
    .tier-std{background:rgba(173,198,255,.1);color:#adc6ff;border:1px solid rgba(173,198,255,.3)}
    .tier-pre{background:rgba(224,168,255,.1);color:#e0a8ff;border:1px solid rgba(224,168,255,.3)}
    .glass-input{background:rgba(255,255,255,.05);border:1px solid rgba(173,198,255,.2);color:white;border-radius:8px}
    .glass-input:focus{outline:none;border-color:#adc6ff;box-shadow:0 0 0 2px rgba(173,198,255,.15)}
    .glass-input::placeholder{color:rgba(140,144,159,.6)}
    .bg-gradient-primary{background:linear-gradient(135deg,#6366f1 0%,#a855f7 100%)}
    .sort-tabs{display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap}
    .sort-tab{padding:5px 12px;border-radius:20px;border:1px solid rgba(173,198,255,.2);font-size:11px;font-weight:600;cursor:pointer;transition:all .15s;color:#8c909f;background:rgba(23,31,51,.5)}
    .sort-tab:hover{border-color:#adc6ff;color:#adc6ff}
    .sort-tab.active{background:rgba(173,198,255,.15);border-color:#adc6ff;color:#adc6ff}
    .id-search-wrap{background:rgba(245,197,24,.07);border:1px solid rgba(245,197,24,.25);border-radius:12px;padding:12px 14px;margin-bottom:14px}
    .id-search-label{font-size:10px;font-weight:700;color:#f5c518;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:6px}
    .id-search-input{background:rgba(0,0,0,.2);border:1px solid rgba(245,197,24,.3);color:#fff;border-radius:8px;padding:7px 12px;font-size:13px;width:100%}
    .id-search-input:focus{outline:none;border-color:#f5c518}
    .id-result-badge{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;margin-top:5px}
    .id-found{background:rgba(78,222,163,.15);color:#4edea3;border:1px solid rgba(78,222,163,.3)}
    .id-notfound{background:rgba(255,100,100,.15);color:#ff6464;border:1px solid rgba(255,100,100,.3)}
    .price-watch{display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap;align-items:center}
    .pw-btn{padding:4px 10px;border-radius:8px;font-size:10px;font-weight:700;cursor:pointer;border:1px solid rgba(173,198,255,.2);color:#8c909f;background:transparent;transition:all .15s}
    .pw-btn.active{background:rgba(78,222,163,.15);color:#4edea3;border-color:rgba(78,222,163,.3)}
    .compare-bar{position:sticky;bottom:0;background:rgba(13,18,32,.96);border-top:1px solid rgba(173,198,255,.15);padding:10px 14px;z-index:10;display:none;align-items:center;justify-content:space-between;gap:10px;border-radius:0 0 14px 14px;backdrop-filter:blur(8px)}
    .compare-bar.visible{display:flex}
    .compare-chip{background:rgba(173,198,255,.1);border:1px solid rgba(173,198,255,.2);border-radius:8px;padding:4px 10px;font-size:11px;color:#adc6ff;display:flex;align-items:center;gap:4px}
    .compare-chip button{background:none;border:none;color:#8c909f;cursor:pointer;padding:0;line-height:1;font-size:14px}
    @keyframes fadeSlideIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .svc-anim{animation:fadeSlideIn .15s ease both}
</style>
@endsection

@section('content')
<div class="max-w-2xl mx-auto px-2">

    <div class="step-wrap">
        @foreach(['Platform','Type','Service','Details','Confirm'] as $i => $lbl)
        <div class="step-item">
            <div class="step-circle {{ $i===0?'active':'' }}" id="sc-{{ $i+1 }}">{{ $i+1 }}</div>
            <p class="step-label">{{ $lbl }}</p>
        </div>
        @if($i<4)<div class="step-line"></div>@endif
        @endforeach
    </div>

    {{-- STEP 1: Platform --}}
    <div class="step-content active" id="step-1">
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-xl font-bold text-on-surface mb-1">New Order</h2>
            <p class="text-on-surface-variant text-sm mb-5">Which platform do you want to grow?</p>
            <div class="platform-grid">
                @php
                $platforms = [
                    ['Instagram','linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045)','fab fa-instagram',['instagram','ig']],
                    ['TikTok',   'linear-gradient(135deg,#010101,#69C9D0)',         'fab fa-tiktok',   ['tiktok','tik tok']],
                    ['YouTube',  'linear-gradient(135deg,#FF0000,#cc0000)',          'fab fa-youtube',  ['youtube','yt']],
                    ['Facebook', 'linear-gradient(135deg,#1877F2,#0A66C2)',          'fab fa-facebook-f',['facebook','fb']],
                    ['Twitter',  'linear-gradient(135deg,#1DA1F2,#0d8bd9)',          'fab fa-twitter',  ['twitter','x.com','tweet']],
                    ['Telegram', 'linear-gradient(135deg,#0088cc,#005a96)',          'fab fa-telegram', ['telegram']],
                    ['Spotify',  'linear-gradient(135deg,#1DB954,#0f8c3a)',          'fab fa-spotify',  ['spotify']],
                    ['Discord',  'linear-gradient(135deg,#5865F2,#3b4fd4)',          'fab fa-discord',  ['discord']],
                ];
                @endphp
                @foreach($platforms as [$name,$grad,$icon,$keywords])
                <div class="pf-card" onclick="selectPlatform('{{ $name }}',{{ json_encode($keywords) }},this)">
                    <div class="pf-icon" style="background:{{ $grad }}"><i class="{{ $icon }}"></i></div>
                    <span class="pf-name">{{ $name }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- STEP 2: Service Type --}}
    <div class="step-content" id="step-2">
        <div class="glass-card rounded-xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <button onclick="goStep(1)" class="text-outline hover:text-on-surface p-1"><span class="material-symbols-outlined">arrow_back</span></button>
                <div>
                    <h3 class="text-lg font-bold text-on-surface">What do you want?</h3>
                    <p class="text-xs text-outline">Platform: <span id="lbl-platform" class="text-primary font-semibold"></span></p>
                </div>
            </div>
            <div class="type-grid" id="type-grid">
                <div class="text-center py-8 col-span-3 text-outline" id="type-loading">
                    <span class="material-symbols-outlined animate-spin text-[28px] block mb-2">progress_activity</span>
                    Loading types...
                </div>
            </div>
        </div>
    </div>

    {{-- STEP 3: Pick Service --}}
    <div class="step-content" id="step-3">
        <div class="glass-card rounded-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <button onclick="goStep(2)" class="text-outline hover:text-on-surface p-1"><span class="material-symbols-outlined">arrow_back</span></button>
                <div>
                    <h3 class="text-lg font-bold text-on-surface">Choose a package</h3>
                    <p class="text-xs text-outline"><span id="lbl-platform2" class="text-primary font-semibold"></span> · <span id="lbl-type" class="text-primary font-semibold"></span></p>
                </div>
            </div>

            {{-- Service ID Quick Finder --}}
            <div class="id-search-wrap">
                <span class="id-search-label">⚡ Find by Provider Service ID</span>
                <input type="number" id="id-search-input" class="id-search-input" placeholder="Enter exact service ID e.g. 1042" oninput="searchById(this.value)">
                <div id="id-search-result" style="display:none;margin-top:6px"></div>
            </div>

            {{-- Name search --}}
            <input type="text" id="svc-search" placeholder="🔍 Search packages by name..." oninput="renderServices()"
                class="w-full glass-input py-2.5 px-3 mb-3 text-sm" style="background:rgba(255,255,255,.05)">

            {{-- Sort tabs --}}
            <div class="sort-tabs">
                <button class="sort-tab active" onclick="setSvcSort('price',this)">💰 Cheapest</button>
                <button class="sort-tab" onclick="setSvcSort('price_high',this)">💎 Highest</button>
                <button class="sort-tab" onclick="setSvcSort('quality',this)">🏆 Quality</button>
                <button class="sort-tab" onclick="setSvcSort('popularity',this)">🔥 Popular</button>
                <button class="sort-tab" onclick="setSvcSort('speed',this)">⚡ Fastest</button>
                <button class="sort-tab" onclick="setSvcSort('refill',this)">🔄 Refill</button>
            </div>

            {{-- Price filter --}}
            <div class="price-watch">
                <span style="font-size:10px;color:#8c909f;font-weight:600">Max rate:</span>
                <button class="pw-btn" data-max="0.5" onclick="setPriceMax(0.5,this)">Under $0.50</button>
                <button class="pw-btn" data-max="1" onclick="setPriceMax(1,this)">Under $1</button>
                <button class="pw-btn" data-max="2" onclick="setPriceMax(2,this)">Under $2</button>
                <button class="pw-btn active" data-max="9999" onclick="setPriceMax(9999,this)">Any</button>
            </div>

            <div class="max-h-80 overflow-y-auto pr-1" id="svc-list">
                <div class="text-center py-8 text-outline" id="svc-loading" style="display:none">
                    <span class="material-symbols-outlined animate-spin text-[28px] block mb-2">progress_activity</span>Loading...
                </div>
                <div class="text-center py-8 text-outline" id="svc-empty" style="display:none">
                    <span class="material-symbols-outlined text-[36px] block mb-2 opacity-30">inventory_2</span>No packages found.
                </div>
            </div>

            <div class="compare-bar" id="compare-bar">
                <div class="flex gap-2 flex-wrap flex-1" id="compare-chips"></div>
                <button onclick="clearCompare()" style="font-size:11px;color:#8c909f;background:none;border:none;cursor:pointer">Clear</button>
                <button onclick="showCompareModal()" style="font-size:11px;background:rgba(173,198,255,.15);border:1px solid rgba(173,198,255,.3);color:#adc6ff;padding:5px 14px;border-radius:8px;cursor:pointer;font-weight:700">Compare ↗</button>
            </div>
        </div>
    </div>

    {{-- STEP 4: Details --}}
    <div class="step-content" id="step-4">
        <div class="glass-card rounded-xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <button onclick="goStep(3)" class="text-outline hover:text-on-surface p-1"><span class="material-symbols-outlined">arrow_back</span></button>
                <div>
                    <h3 class="text-lg font-bold text-on-surface">Order Details</h3>
                    <p class="text-xs text-outline truncate max-w-xs" id="lbl-service"></p>
                </div>
            </div>
            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-widest text-outline mb-2">Target Link *</label>
                    <input type="url" id="order-link" class="w-full glass-input py-2.5 px-3 text-sm" placeholder="https://..." style="background:rgba(255,255,255,.05)">
                    <p class="text-xs text-outline mt-1">Must be a public profile or post URL</p>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-xs font-semibold uppercase tracking-widest text-outline">Quantity</label>
                        <input type="number" id="qty-num" class="w-24 glass-input py-1.5 px-3 text-sm text-center" oninput="syncSlider()" style="background:rgba(255,255,255,.05)">
                    </div>
                    <input type="range" id="qty-range" min="100" max="10000" step="1" value="1000" class="w-full accent-blue-400" oninput="syncNum()">
                    <div class="flex justify-between text-xs text-outline mt-1">
                        <span id="qty-min-lbl">Min: 100</span><span id="qty-max-lbl">Max: 10,000</span>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-outline mb-2">Quick Qty</p>
                    <div class="flex gap-2 flex-wrap" id="qty-presets"></div>
                </div>
                <div class="rounded-xl p-4 border border-outline-variant/30" style="background:rgba(0,0,0,.2)">
                    <p class="text-xs font-semibold uppercase tracking-widest text-outline mb-3">Price Summary</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-outline">Rate / 1K</p><p class="text-on-surface font-semibold text-sm" id="d-rate">$0.0000</p></div>
                        <div><p class="text-xs text-outline">Quantity</p><p class="text-on-surface font-semibold text-sm" id="d-qty">1,000</p></div>
                        <div><p class="text-xs text-outline">Total USD</p><p class="text-2xl font-bold text-primary" id="d-usd">$0.0000</p></div>
                        <div><p class="text-xs text-outline">Total PKR</p><p class="text-2xl font-bold text-tertiary" id="d-pkr">₨0</p></div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-outline-variant/30 flex justify-between text-sm">
                        <span class="text-outline">Balance after</span>
                        <span id="d-after" class="font-semibold text-on-surface">${{ number_format(auth()->user()->funds ?? 0, 2) }}</span>
                    </div>
                </div>
                <button onclick="goStep(5)" class="w-full bg-gradient-primary text-white font-semibold py-3 rounded-xl hover:brightness-110 transition-all text-sm">
                    Continue → Confirm
                </button>
            </div>
        </div>
    </div>

    {{-- STEP 5: Confirm --}}
    <div class="step-content" id="step-5">
        <div class="glass-card rounded-xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <button onclick="goStep(4)" class="text-outline hover:text-on-surface p-1"><span class="material-symbols-outlined">arrow_back</span></button>
                <h3 class="text-lg font-bold text-on-surface">Confirm Order</h3>
            </div>
            <div id="confirm-details" class="space-y-1">
                @foreach([['Platform','r-platform'],['Type','r-type'],['Service','r-service'],['Service ID','r-svcid'],['Link','r-link'],['Quantity','r-qty'],['Total (USD)','r-usd'],['Total (PKR)','r-pkr']] as [$lbl,$id])
                <div class="flex justify-between items-center py-3 border-b border-outline-variant/20">
                    <span class="text-outline text-sm">{{ $lbl }}</span>
                    <span id="{{ $id }}" class="text-on-surface font-medium text-sm text-right max-w-[55%] truncate">—</span>
                </div>
                @endforeach
            </div>
            <div class="flex items-start gap-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-3 my-5 text-yellow-400 text-xs">
                <span class="material-symbols-outlined text-[16px] flex-shrink-0 mt-0.5">warning</span>
                Verify the link is correct. Orders cannot be cancelled once processing starts.
            </div>
            <form method="POST" action="{{ route('orders.store') }}" id="order-form">
                @csrf
                <input type="hidden" name="service_id" id="f-service">
                <input type="hidden" name="link" id="f-link">
                <input type="hidden" name="quantity" id="f-quantity">
                <button type="submit" onclick="return prepareSubmit()"
                    class="w-full bg-gradient-primary text-white font-semibold py-3.5 rounded-xl hover:brightness-110 transition-all text-sm flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span> Place Order
                </button>
            </form>
        </div>
    </div>

</div>

{{-- Compare Modal --}}
<div id="compare-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);padding:16px;overflow-y:auto">
    <div style="max-width:640px;margin:40px auto;background:#0d1220;border:1px solid rgba(173,198,255,.2);border-radius:16px;padding:20px">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-on-surface">Compare Services</h3>
            <button onclick="document.getElementById('compare-modal').style.display='none'" class="text-outline hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div id="compare-table"></div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
const PKR     = {{ session('usd_pkr_rate', 280) }};
const BAL     = {{ auth()->user()->funds ?? 0 }};
const SVC_URL = '{{ route("orders.services_by_category") }}';
const ALL_CATS= @json($categories);

let selPlatform='', selKeywords=[], selType='', selSvcId=null;
let selRate=0, selMin=100, selMax=10000, selName='';
let svcCache={}, allLoadedServices=[], allServicesById={};
let currentSort='price', currentMaxPrice=9999, compareList=[];

const SERVICE_TYPES=[
    {label:'Followers', icon:'group',       keywords:['follower','follow','sub','subscriber']},
    {label:'Likes',     icon:'favorite',    keywords:['like','heart','react']},
    {label:'Views',     icon:'visibility',  keywords:['view','watch','impression','play']},
    {label:'Comments',  icon:'chat_bubble', keywords:['comment','reply','review']},
    {label:'Shares',    icon:'share',       keywords:['share','repost','retweet','rt']},
    {label:'Saves',     icon:'bookmark',    keywords:['save','bookmark','pin']},
    {label:'Streams',   icon:'music_note',  keywords:['stream','listen','play']},
    {label:'Members',   icon:'group_add',   keywords:['member','join']},
    {label:'Everything',icon:'apps',        keywords:[]},
];

// ── Sorting ────────────────────────────────────────────────────────────────
function sortServices(arr,key){
    const c=[...arr];
    switch(key){
        case 'price':      return c.sort((a,b)=>parseFloat(a.rate)-parseFloat(b.rate));
        case 'price_high': return c.sort((a,b)=>parseFloat(b.rate)-parseFloat(a.rate));
        case 'quality':    return c.sort((a,b)=>(b.quality_score||0)-(a.quality_score||0));
        case 'popularity': return c.sort((a,b)=>(b.orders_count||0)-(a.orders_count||0));
        case 'speed':      return c.sort((a,b)=>(a.avg_start_time||9999)-(b.avg_start_time||9999));
        case 'refill':     return c.sort((a,b)=>(b.has_refill?1:0)-(a.has_refill?1:0));
        default:           return c.sort((a,b)=>parseFloat(a.rate)-parseFloat(b.rate));
    }
}

function setSvcSort(sort,el){
    currentSort=sort;
    document.querySelectorAll('.sort-tab').forEach(t=>t.classList.remove('active'));
    el.classList.add('active');
    renderServices();
}

function setPriceMax(max,el){
    currentMaxPrice=max;
    document.querySelectorAll('.pw-btn').forEach(b=>b.classList.remove('active'));
    el.classList.add('active');
    renderServices();
}

// ── Platform selection ─────────────────────────────────────────────────────
function selectPlatform(name,keywords,el){
    document.querySelectorAll('.pf-card').forEach(c=>c.classList.remove('selected'));
    el.classList.add('selected');
    selPlatform=name; selKeywords=keywords;
    document.getElementById('lbl-platform').textContent=name;
    document.getElementById('lbl-platform2').textContent=name;
    buildTypeGrid(name,keywords);
    goStep(2);
}

function buildTypeGrid(platform,platformKws){
    const grid=document.getElementById('type-grid');
    document.getElementById('type-loading').style.display='none';
    const terms=[platform.toLowerCase(),...platformKws.map(k=>k.toLowerCase())];
    const matchCats=ALL_CATS.filter(c=>terms.some(t=>c.name.toLowerCase().includes(t)));
    grid.querySelectorAll('.type-btn').forEach(b=>b.remove());
    const catsToUse=matchCats.length>0?matchCats:ALL_CATS;
    const avail=SERVICE_TYPES.filter(t=>{
        if(t.label==='Everything') return true;
        return catsToUse.some(c=>t.keywords.some(kw=>c.name.toLowerCase().includes(kw)));
    });
    avail.forEach(type=>{
        const btn=document.createElement('button');
        btn.className='type-btn';
        btn.innerHTML=`<span class="material-symbols-outlined ti">${type.icon}</span><span>${type.label}</span>`;
        btn.onclick=()=>selectType(type,btn,catsToUse);
        grid.appendChild(btn);
    });
}

function selectType(type,el,catsToUse){
    document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('selected'));
    el.classList.add('selected');
    selType=type.label;
    document.getElementById('lbl-type').textContent=type.label;
    let cats=(type.label==='Everything')?catsToUse:catsToUse.filter(c=>type.keywords.some(kw=>c.name.toLowerCase().includes(kw)));
    if(!cats.length) cats=catsToUse;
    loadServicesForCats(cats);
    goStep(3);
}

// ── Load services ──────────────────────────────────────────────────────────
function loadServicesForCats(cats){
    const list=document.getElementById('svc-list');
    const loading=document.getElementById('svc-loading');
    const empty=document.getElementById('svc-empty');
    list.querySelectorAll('.svc-row').forEach(r=>r.remove());
    loading.style.display='block';
    empty.style.display='none';
    clearCompare();

    Promise.all(cats.map(cat=>{
        if(svcCache[cat.id]) return Promise.resolve(svcCache[cat.id]);
        return fetch(`${SVC_URL}?category_id=${cat.id}`,{headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(r=>r.json()).then(svcs=>{
                svcCache[cat.id]=svcs;
                svcs.forEach(s=>{
                    allServicesById[s.id]=s;
                    if(s.api_service_id) allServicesById[s.api_service_id]=s;
                });
                return svcs;
            });
    })).then(results=>{
        loading.style.display='none';
        allLoadedServices=results.flat();
        if(!allLoadedServices.length){empty.style.display='block';return;}
        renderServices();
    }).catch(()=>{
        loading.style.display='none';
        empty.style.display='block';
    });
}

function renderServices(){
    const list=document.getElementById('svc-list');
    const empty=document.getElementById('svc-empty');
    const q=(document.getElementById('svc-search').value||'').toLowerCase().trim();
    list.querySelectorAll('.svc-row').forEach(r=>r.remove());

    let filtered=allLoadedServices.filter(s=>{
        const matchName=!q||s.name.toLowerCase().includes(q);
        const matchPrice=parseFloat(s.rate)<=currentMaxPrice;
        return matchName&&matchPrice;
    });
    filtered=sortServices(filtered,currentSort);

    if(!filtered.length){empty.style.display='block';return;}
    empty.style.display='none';

    filtered.forEach((svc,i)=>{
        const row=buildSvcRow(svc);
        row.style.animationDelay=(i*0.025)+'s';
        row.classList.add('svc-anim');
        list.appendChild(row);
    });
}

function buildSvcRow(svc,highlight=false){
    const rate=parseFloat(svc.rate);
    const tier=rate<0.5?['Economy','tier-eco']:rate<2?['Standard','tier-std']:['Premium','tier-pre'];
    const inCmp=compareList.some(c=>c.id===svc.id);
    const row=document.createElement('button');
    row.className='svc-row'+(highlight?' highlighted':'');
    row.dataset.id=svc.id;
    row.dataset.name=svc.name.toLowerCase();

    const qHtml=svc.quality_score?`<span style="font-size:9px;color:${svc.quality_score>=8?'#4edea3':svc.quality_score>=5?'#adc6ff':'#f5c518'};font-weight:700">★${svc.quality_score}</span>`:'';
    const rHtml=svc.has_refill?`<span style="font-size:9px;color:#adc6ff">🔄</span>`:'';
    const pHtml=(svc.orders_count||0)>500?`<span style="font-size:9px">🔥</span>`:'';
    const spHtml=svc.delivery_badge==='instant'?`<span style="font-size:9px;color:#4edea3">⚡</span>`:'';
    const pidHtml=svc.api_service_id
        ?`<span style="font-size:9px;color:#666;background:rgba(245,197,24,.1);padding:1px 5px;border-radius:4px">#${svc.api_service_id}</span>`
        :'';

    row.innerHTML=`<div class="flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
            <p class="text-on-surface font-medium text-sm leading-snug">${esc(svc.name)}</p>
            <p class="text-outline text-xs mt-1 flex flex-wrap items-center gap-1">
                <span>Min:${Number(svc.min).toLocaleString()} · Max:${Number(svc.max).toLocaleString()}</span>
                ${qHtml}${rHtml}${pHtml}${spHtml}${pidHtml}
            </p>
        </div>
        <div class="text-right flex-shrink-0">
            <p class="text-primary font-bold text-sm">$${rate.toFixed(4)}</p>
            <span class="inline-block mt-1 px-2 py-0.5 rounded text-[9px] font-bold uppercase ${tier[1]}">${tier[0]}</span>
            <button class="compare-toggle-btn" data-svcid="${svc.id}" style="display:block;margin-top:4px;font-size:9px;color:${inCmp?'#4edea3':'#555'};background:none;border:none;cursor:pointer;padding:0">
                ${inCmp?'✓ Added':'+ Compare'}
            </button>
        </div>
    </div>`;

    row.querySelector('.compare-toggle-btn').addEventListener('click',e=>{
        e.stopPropagation();
        toggleCompare(svc);
        const btn=row.querySelector('.compare-toggle-btn');
        const nowIn=compareList.some(c=>c.id===svc.id);
        btn.textContent=nowIn?'✓ Added':'+ Compare';
        btn.style.color=nowIn?'#4edea3':'#555';
    });

    row.addEventListener('click',e=>{
        if(e.target.classList.contains('compare-toggle-btn')) return;
        selectService(svc,row);
    });
    return row;
}

// ── Service ID search ──────────────────────────────────────────────────────
function searchById(val){
    const el=document.getElementById('id-search-result');
    if(!val||!val.trim()){el.style.display='none';return;}
    const num=parseInt(val);
    // Search in loaded services first (by api_service_id or id), then global index
    let found=allLoadedServices.find(s=>parseInt(s.api_service_id)===num||parseInt(s.id)===num);
    if(!found) found=allServicesById[num];
    el.style.display='block';
    if(found){
        el.innerHTML=`<span class="id-result-badge id-found">✓ Found: ${esc(found.name.substring(0,35))} — $${parseFloat(found.rate).toFixed(4)}/1K</span>
        <button onclick="jumpToService(${found.id})" style="display:inline-block;margin-top:6px;font-size:11px;background:rgba(78,222,163,.15);border:1px solid rgba(78,222,163,.3);color:#4edea3;padding:5px 14px;border-radius:8px;cursor:pointer;font-weight:700">Jump to service →</button>`;
    } else {
        el.innerHTML=`<span class="id-result-badge id-notfound">✗ No service with ID ${num} in current view</span>`;
    }
}

function jumpToService(id){
    document.getElementById('svc-search').value='';
    currentMaxPrice=9999;
    document.querySelectorAll('.pw-btn').forEach(b=>b.classList.remove('active'));
    document.querySelector('.pw-btn[data-max="9999"]').classList.add('active');
    renderServices();
    setTimeout(()=>{
        let el=document.querySelector(`.svc-row[data-id="${id}"]`);
        if(!el){
            const svc=allServicesById[id];
            if(svc){
                const list=document.getElementById('svc-list');
                const row=buildSvcRow(svc,true);
                list.prepend(row);
                el=row;
            }
        }
        if(el){
            el.classList.add('highlighted');
            el.scrollIntoView({behavior:'smooth',block:'center'});
            setTimeout(()=>el.classList.remove('highlighted'),3500);
        }
    },120);
}

// ── Compare ────────────────────────────────────────────────────────────────
function toggleCompare(svc){
    const idx=compareList.findIndex(c=>c.id===svc.id);
    if(idx>=0){compareList.splice(idx,1);}
    else{
        if(compareList.length>=3){alert('Max 3 services for comparison.');return;}
        compareList.push(svc);
    }
    updateCompareBar();
}

function updateCompareBar(){
    const bar=document.getElementById('compare-bar');
    const chips=document.getElementById('compare-chips');
    chips.innerHTML='';
    compareList.forEach(s=>{
        const chip=document.createElement('div');
        chip.className='compare-chip';
        chip.innerHTML=`${esc(s.name.substring(0,20))} <button onclick="removeCompare(${s.id})">×</button>`;
        chips.appendChild(chip);
    });
    bar.className='compare-bar'+(compareList.length>=2?' visible':'');
}

function removeCompare(id){
    const svc=compareList.find(c=>c.id===id);
    if(svc) toggleCompare(svc);
    const btn=document.querySelector(`.compare-toggle-btn[data-svcid="${id}"]`);
    if(btn){btn.textContent='+ Compare';btn.style.color='#555';}
}

function clearCompare(){
    compareList=[];
    updateCompareBar();
    document.querySelectorAll('.compare-toggle-btn').forEach(b=>{b.textContent='+ Compare';b.style.color='#555';});
}

function showCompareModal(){
    if(compareList.length<2) return;
    const rows=[
        ['Rate/1K',     s=>`<b style="color:#adc6ff">$${parseFloat(s.rate).toFixed(4)}</b>`],
        ['Min / Max',   s=>`${Number(s.min).toLocaleString()} / ${Number(s.max).toLocaleString()}`],
        ['Quality',     s=>s.quality_score?`<span style="color:${s.quality_score>=8?'#4edea3':'#f5c518'}">★${s.quality_score}/10</span>`:'—'],
        ['Refill',      s=>s.has_refill?'✅':'❌'],
        ['Delivery',    s=>s.delivery_badge||'Standard'],
        ['Orders',      s=>(s.orders_count||0).toLocaleString()],
        ['Provider ID', s=>s.api_service_id?`#${s.api_service_id}`:`#${s.id}`],
        ['Est. Start',  s=>s.estimated_start||'—'],
        ['Cost 1K PKR', s=>`₨${Math.round((parseFloat(s.rate))*PKR).toLocaleString()}`],
    ];
    let html=`<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:12px"><thead><tr>
        <th style="text-align:left;padding:8px;color:#8c909f;border-bottom:1px solid rgba(173,198,255,.1)">Feature</th>`;
    compareList.forEach(s=>{
        html+=`<th style="padding:8px;color:#adc6ff;border-bottom:1px solid rgba(173,198,255,.1);text-align:center">${esc(s.name.substring(0,28))}${s.name.length>28?'…':''}</th>`;
    });
    html+=`</tr></thead><tbody>`;
    rows.forEach(([lbl,fn])=>{
        html+=`<tr><td style="padding:8px;color:#8c909f;border-bottom:1px solid rgba(255,255,255,.05)">${lbl}</td>`;
        compareList.forEach(s=>{html+=`<td style="padding:8px;text-align:center;border-bottom:1px solid rgba(255,255,255,.05);color:#e0e0e0">${fn(s)}</td>`;});
        html+=`</tr>`;
    });
    html+=`</tbody></table></div><div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">`;
    compareList.forEach(s=>{
        html+=`<button onclick="selectServiceFromCompare(${s.id})" style="flex:1;min-width:80px;background:rgba(173,198,255,.1);border:1px solid rgba(173,198,255,.3);color:#adc6ff;padding:10px;border-radius:10px;cursor:pointer;font-size:12px;font-weight:700">Select<br><span style="font-size:10px;opacity:.7">${esc(s.name.substring(0,18))}</span></button>`;
    });
    html+=`</div>`;
    document.getElementById('compare-table').innerHTML=html;
    document.getElementById('compare-modal').style.display='block';
}

function selectServiceFromCompare(id){
    document.getElementById('compare-modal').style.display='none';
    const svc=allLoadedServices.find(s=>s.id==id)||allServicesById[id];
    if(svc) selectService(svc,null);
}

// ── Service selection ──────────────────────────────────────────────────────
function selectService(svc,el){
    document.querySelectorAll('#svc-list .svc-row').forEach(r=>r.classList.remove('selected'));
    if(el) el.classList.add('selected');
    selSvcId=svc.id; selRate=parseFloat(svc.rate);
    selMin=parseInt(svc.min); selMax=parseInt(svc.max); selName=svc.name;
    document.getElementById('lbl-service').textContent=svc.name;
    document.getElementById('d-rate').textContent='$'+selRate.toFixed(4);
    const sl=document.getElementById('qty-range');
    sl.min=selMin; sl.max=selMax; sl.value=selMin;
    document.getElementById('qty-num').value=selMin;
    document.getElementById('qty-min-lbl').textContent='Min: '+selMin.toLocaleString();
    document.getElementById('qty-max-lbl').textContent='Max: '+selMax.toLocaleString();
    buildQtyPresets(selMin,selMax);
    calcPrice();
    goStep(4);
}

function buildQtyPresets(min,max){
    const wrap=document.getElementById('qty-presets');
    wrap.innerHTML='';
    const pts=[min,
        Math.round(min+(max-min)*0.1),
        Math.round(min+(max-min)*0.25),
        Math.round(min+(max-min)*0.5),
        max
    ].filter((v,i,a)=>a.indexOf(v)===i);
    pts.forEach(v=>{
        const pkr=Math.round((v/1000)*selRate*PKR);
        const btn=document.createElement('button');
        btn.style.cssText='font-size:10px;padding:5px 10px;border-radius:8px;border:1px solid rgba(173,198,255,.2);color:#8c909f;background:rgba(23,31,51,.5);cursor:pointer;transition:all .15s;white-space:nowrap';
        btn.textContent=`${v>=1000?(v/1000).toFixed(v%1000===0?0:1)+'K':v} (₨${pkr.toLocaleString()})`;
        btn.onclick=()=>{
            document.getElementById('qty-range').value=v;
            document.getElementById('qty-num').value=v;
            calcPrice();
        };
        wrap.appendChild(btn);
    });
}

function syncNum(){document.getElementById('qty-num').value=document.getElementById('qty-range').value;calcPrice();}
function syncSlider(){
    let v=Math.min(Math.max(parseInt(document.getElementById('qty-num').value)||selMin,selMin),selMax);
    document.getElementById('qty-range').value=v;calcPrice();
}

function calcPrice(){
    const qty=parseInt(document.getElementById('qty-range').value)||selMin;
    const total=(qty/1000)*selRate;
    const after=BAL-total;
    document.getElementById('d-qty').textContent=qty.toLocaleString();
    document.getElementById('d-usd').textContent='$'+total.toFixed(4);
    document.getElementById('d-pkr').textContent='₨'+Math.round(total*PKR).toLocaleString();
    const el=document.getElementById('d-after');
    el.textContent='$'+Math.max(0,after).toFixed(2);
    el.className='font-semibold '+(after<0?'text-red-500':'text-on-surface');
}

function goStep(n){
    document.querySelectorAll('.step-content').forEach(s=>s.classList.remove('active'));
    document.getElementById('step-'+n).classList.add('active');
    for(let i=1;i<=5;i++){
        const sc=document.getElementById('sc-'+i);
        if(!sc) continue;
        sc.className='step-circle'+(i===n?' active':i<n?' done':'');
        sc.innerHTML=i<n?'<span class="material-symbols-outlined text-[14px]">check</span>':i;
    }
    if(n===5){
        const link=document.getElementById('order-link').value;
        const qty=document.getElementById('qty-range').value;
        document.getElementById('r-platform').textContent=selPlatform;
        document.getElementById('r-type').textContent=selType;
        document.getElementById('r-service').textContent=selName;
        document.getElementById('r-svcid').textContent=selSvcId?`#${selSvcId}`:'—';
        document.getElementById('r-link').textContent=link;
        document.getElementById('r-qty').textContent=parseInt(qty).toLocaleString();
        document.getElementById('r-usd').textContent=document.getElementById('d-usd').textContent;
        document.getElementById('r-pkr').textContent=document.getElementById('d-pkr').textContent;
    }
    window.scrollTo({top:0,behavior:'smooth'});
}

function prepareSubmit(){
    const link=document.getElementById('order-link').value.trim();
    if(!selSvcId||!link){alert('Please provide a valid link.');return false;}
    document.getElementById('f-service').value=selSvcId;
    document.getElementById('f-link').value=link;
    document.getElementById('f-quantity').value=document.getElementById('qty-range').value;
    return true;
}

function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

document.getElementById('compare-modal').addEventListener('click',function(e){if(e.target===this)this.style.display='none';});
</script>
@endsection
