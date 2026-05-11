@extends('layouts.app')
@section('title', 'New Order')
@section('page-title', 'New Order Wizard')

@section('css')
<style>
    /* Step Indicator Styles */
    .step-wrap { display: flex; align-items: center; margin-bottom: 2rem; }
    .step-item { display: flex; flex-direction: column; align-items: center; gap: 4px; min-width: 56px; }
    .step-circle { 
        width: 36px; height: 36px; border-radius: 50%; border: 2px solid #424754; 
        display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #8c909f; transition: all .3s; 
    }
    .step-circle.active { border-color: #adc6ff; color: #adc6ff; background: rgba(173,198,255,.1); box-shadow: 0 0 12px rgba(173,198,255,.25); }
    .step-circle.done { border-color: #4edea3; background: #4edea3; color: #003824; }
    .step-label { font-size: 10px; color: #8c909f; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
    .step-line { flex: 1; height: 1px; background: #424754; margin-bottom: 18px; }

    /* Platform Grid */
    .platform-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    @media(max-width:480px){ .platform-grid { grid-template-columns: repeat(3, 1fr); } }
    .pf-card { 
        background: rgba(23,31,51,.6); border: 1.5px solid rgba(173,198,255,.1); border-radius: 14px; 
        padding: 14px 8px; display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; transition: all .2s; 
    }
    .pf-card:hover { border-color: #adc6ff; transform: translateY(-2px); }
    .pf-card.selected { border-color: #adc6ff; background: rgba(173,198,255,.09); }
    .pf-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; }
    .pf-name { font-size: 11px; font-weight: 600; color: #8c909f; }

    /* Type Buttons */
    .type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .type-btn { 
        background: rgba(23,31,51,.5); border: 1.5px solid transparent; border-radius: 12px; 
        padding: 14px 8px; display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; transition: all .2s; 
    }
    .type-btn.selected { border-color: #adc6ff; background: rgba(173,198,255,.09); }

    /* Service Rows */
    .svc-row { 
        padding: 12px 14px; border-radius: 10px; border: 1.5px solid rgba(173,198,255,.1); 
        margin-bottom: 6px; background: rgba(23,31,51,.3); width: 100%; text-align: left; transition: all .15s;
    }
    .svc-row.selected { border-color: #adc6ff; background: rgba(173,198,255,.09); }
    .step-content { display: none; }
    .step-content.active { display: block; }
</style>
@endsection

@section('content')
<div class="max-w-2xl mx-auto px-2">

    {{-- Progress Bar --}}
    <div class="step-wrap">
        @foreach(['Platform','Type','Service','Details','Confirm'] as $i => $lbl)
            <div class="step-item">
                <div class="step-circle {{ $i===0?'active':'' }}" id="sc-{{ $i+1 }}">{{ $i+1 }}</div>
                <p class="step-label">{{ $lbl }}</p>
            </div>
            @if($i < 4) <div class="step-line"></div> @endif
        @endforeach
    </div>

    {{-- STEP 1: Platform Selection --}}
    <div class="step-content active" id="step-1">
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-xl font-bold mb-1">New Order</h2>
            <p class="text-outline text-sm mb-5">Select a platform to begin.</p>
            <div class="platform-grid">
                @php
                $platforms = [
                    ['Instagram','linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045)','fab fa-instagram',['instagram','ig']],
                    ['TikTok',   'linear-gradient(135deg,#010101,#69C9D0)',         'fab fa-tiktok',   ['tiktok']],
                    ['YouTube',  'linear-gradient(135deg,#FF0000,#cc0000)',          'fab fa-youtube',  ['youtube','yt']],
                    ['Facebook', 'linear-gradient(135deg,#1877F2,#0A66C2)',          'fab fa-facebook-f',['facebook','fb']],
                    ['Twitter',  'linear-gradient(135deg,#1DA1F2,#0d8bd9)',          'fab fa-twitter',  ['twitter','x.com']],
                    ['Telegram', 'linear-gradient(135deg,#0088cc,#005a96)',          'fab fa-telegram', ['telegram']],
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
                <button onclick="goStep(1)" class="text-outline hover:text-white"><span class="material-symbols-outlined">arrow_back</span></button>
                <h3 class="text-lg font-bold">What do you need for <span id="lbl-platform" class="text-primary"></span>?</h3>
            </div>
            <div class="type-grid" id="type-grid">
                {{-- Dynamic Buttons --}}
                <div id="type-loading" class="col-span-3 text-center py-6 text-outline">Loading categories...</div>
            </div>
        </div>
    </div>

    {{-- STEP 3: Service Selection --}}
    <div class="step-content" id="step-3">
        <div class="glass-card rounded-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <button onclick="goStep(2)" class="text-outline hover:text-white"><span class="material-symbols-outlined">arrow_back</span></button>
                <h3 class="text-lg font-bold">Pick a Package</h3>
            </div>
            <div class="max-h-80 overflow-y-auto" id="svc-list">
                {{-- Dynamic Rows --}}
            </div>
        </div>
    </div>

    {{-- STEP 4: Order Details --}}
    <div class="step-content" id="step-4">
        <div class="glass-card rounded-xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <button onclick="goStep(3)" class="text-outline hover:text-white"><span class="material-symbols-outlined">arrow_back</span></button>
                <h3 class="text-lg font-bold">Details</h3>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-outline uppercase mb-2">Target URL</label>
                    <input type="url" id="order-link" class="w-full glass-input py-2" placeholder="https://...">
                </div>
                <div>
                    <label class="block text-xs font-bold text-outline uppercase mb-2">Quantity</label>
                    <input type="number" id="qty-num" class="w-full glass-input py-2" oninput="syncSlider()">
                    <input type="range" id="qty-range" class="w-full mt-4" oninput="syncNum()">
                </div>
                <div class="bg-black/20 p-4 rounded-xl">
                    <div class="flex justify-between mb-1"><span class="text-outline text-sm">Total Price</span><span id="d-usd" class="font-bold text-primary">$0.00</span></div>
                    <div class="flex justify-between"><span class="text-outline text-sm">In PKR</span><span id="d-pkr" class="font-bold text-tertiary">₨0</span></div>
                </div>
                <button onclick="goStep(5)" class="w-full bg-gradient-primary py-3 rounded-xl font-bold">Next Step</button>
            </div>
        </div>
    </div>

    {{-- STEP 5: Confirmation --}}
    <div class="step-content" id="step-5">
        <div class="glass-card rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 text-center">Confirm Your Order</h3>
            <div class="space-y-2 mb-6">
                <div class="flex justify-between text-sm border-b border-white/5 py-2"><span class="text-outline">Service</span><span id="r-service" class="text-right truncate ml-4"></span></div>
                <div class="flex justify-between text-sm border-b border-white/5 py-2"><span class="text-outline">Quantity</span><span id="r-qty"></span></div>
                <div class="flex justify-between text-sm border-b border-white/5 py-2"><span class="text-outline">Price</span><span id="r-usd" class="text-primary font-bold"></span></div>
            </div>
            <form action="{{ route('orders.store') }}" method="POST" onsubmit="return prepareSubmit()">
                @csrf
                <input type="hidden" name="service_id" id="f-service">
                <input type="hidden" name="link" id="f-link">
                <input type="hidden" name="quantity" id="f-quantity">
                <button type="submit" class="w-full bg-tertiary text-black py-4 rounded-xl font-black uppercase tracking-wider">Place Order Now</button>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    const PKR = {{ session('usd_pkr_rate', 280) }};
    const BAL = {{ auth()->user()->funds ?? 0 }};
    const ALL_CATS = @json($categories);
    const SVC_URL = '{{ route("orders.services_by_category") }}';

    let selPlatform='', selType='', selSvcId=null, selRate=0, selMin=10, selMax=10000, selName='';

    const TYPES = [
        { label:'Followers', icon:'group', kws:['follower','follow'] },
        { label:'Likes', icon:'favorite', kws:['like','heart'] },
        { label:'Views', icon:'visibility', kws:['view','watch'] },
        { label:'Comments', icon:'chat_bubble', kws:['comment'] },
        { label:'Everything', icon:'apps', kws:[] }
    ];

    function goStep(n) {
        document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
        document.getElementById('step-'+n).classList.add('active');
        document.querySelectorAll('.step-circle').forEach((c, i) => {
            c.className = 'step-circle ' + (i+1 === n ? 'active' : (i+1 < n ? 'done' : ''));
        });
        window.scrollTo(0,0);
    }

    function selectPlatform(name, keywords, el) {
        selPlatform = name;
        document.getElementById('lbl-platform').textContent = name;
        
        const grid = document.getElementById('type-grid');
        grid.innerHTML = '';
        
        // Filter categories that belong to this platform
        const platformCats = ALL_CATS.filter(c => 
            [name, ...keywords].some(k => c.name.toLowerCase().includes(k.toLowerCase()))
        );

        const catsToUse = platformCats.length > 0 ? platformCats : ALL_CATS;

        TYPES.forEach(t => {
            const btn = document.createElement('button');
            btn.className = 'type-btn';
            btn.innerHTML = `<span class="material-symbols-outlined">${t.icon}</span><span class="text-[10px] font-bold">${t.label}</span>`;
            btn.onclick = () => selectType(t, catsToUse);
            grid.appendChild(btn);
        });
        goStep(2);
    }

    function selectType(type, cats) {
        selType = type.label;
        const filteredCats = type.label === 'Everything' ? cats : cats.filter(c => 
            type.kws.some(k => c.name.toLowerCase().includes(k))
        );
        loadServices(filteredCats);
    }

    function loadServices(cats) {
        const list = document.getElementById('svc-list');
        list.innerHTML = '<div class="text-center py-10">Loading...</div>';
        goStep(3);

        const promises = cats.map(c => fetch(`${SVC_URL}?category_id=${c.id}`).then(r => r.json()));
        
        Promise.all(promises).then(data => {
            list.innerHTML = '';
            data.flat().forEach(svc => {
                const row = document.createElement('button');
                row.className = 'svc-row';
                row.innerHTML = `
                    <div class="flex justify-between items-center">
                        <div class="text-sm font-medium truncate pr-4">${svc.name}</div>
                        <div class="text-primary font-bold text-xs">$${parseFloat(svc.rate).toFixed(4)}</div>
                    </div>`;
                row.onclick = () => {
                    selSvcId = svc.id; selRate = svc.rate; selMin = svc.min; selMax = svc.max; selName = svc.name;
                    document.getElementById('qty-num').value = svc.min;
                    document.getElementById('qty-range').min = svc.min;
                    document.getElementById('qty-range').max = svc.max;
                    document.getElementById('qty-range').value = svc.min;
                    calc(); goStep(4);
                };
                list.appendChild(row);
            });
        });
    }

    function syncNum() { document.getElementById('qty-num').value = document.getElementById('qty-range').value; calc(); }
    function syncSlider() { document.getElementById('qty-range').value = document.getElementById('qty-num').value; calc(); }

    function calc() {
        const qty = parseInt(document.getElementById('qty-num').value) || 0;
        const total = (qty / 1000) * selRate;
        document.getElementById('d-usd').textContent = '$' + total.toFixed(4);
        document.getElementById('d-pkr').textContent = '₨' + Math.round(total * PKR).toLocaleString();
        
        // Populate Step 5 Review
        document.getElementById('r-service').textContent = selName;
        document.getElementById('r-qty').textContent = qty.toLocaleString();
        document.getElementById('r-usd').textContent = '$' + total.toFixed(4);
    }

    function prepareSubmit() {
        document.getElementById('f-service').value = selSvcId;
        document.getElementById('f-link').value = document.getElementById('order-link').value;
        document.getElementById('f-quantity').value = document.getElementById('qty-num').value;
        return true;
    }
</script>
@endsection
