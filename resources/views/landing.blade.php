@extends('layouts.student')

@section('content')

<style>
* { box-sizing: border-box; }

/* HERO */
.hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    color: #fff;
    padding: 100px 40px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 30% 50%, rgba(29,158,117,0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(55,138,221,0.08) 0%, transparent 50%);
    pointer-events: none;
}
.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(29,158,117,0.15);
    border: 1px solid rgba(29,158,117,0.3);
    color: #5DCAA5;
    padding: 6px 14px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 24px;
}
.hero h1 {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 16px;
    line-height: 1.15;
    letter-spacing: -1px;
}
.hero h1 span { color: #1D9E75; }
.hero p {
    font-size: 17px;
    color: rgba(255,255,255,0.65);
    max-width: 520px;
    margin: 0 auto 36px;
    line-height: 1.7;
}
.hero-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 60px; }
.btn-primary-lg {
    background: #1D9E75;
    color: #fff;
    padding: 14px 32px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-primary-lg:hover { background: #0F6E56; color: #fff; transform: translateY(-2px); }
.btn-outline-lg {
    background: rgba(255,255,255,0.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.25);
    padding: 14px 32px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-outline-lg:hover { background: rgba(255,255,255,0.15); color: #fff; }
.hero-preview {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px;
    max-width: 700px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
.hero-preview-stat { text-align: center; }
.hero-preview-stat .num { font-size: 28px; font-weight: 800; color: #1D9E75; }
.hero-preview-stat .lbl { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 2px; }

/* STATS BAR */
.stats-section {
    background: #fff;
    border-bottom: 1px solid #f0f0f0;
    padding: 0 40px;
}
.stats-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    divide-x: 1px;
}
.stat-box {
    padding: 32px 24px;
    text-align: center;
    border-right: 1px solid #f0f0f0;
}
.stat-box:last-child { border-right: none; }
.stat-box .num { font-size: 32px; font-weight: 800; color: #1a1a2e; }
.stat-box .num span { color: #1D9E75; }
.stat-box .lbl { font-size: 13px; color: #888; margin-top: 4px; }

/* SECTIONS */
.section { padding: 80px 40px; }
.section.gray { background: #f8f9fa; }
.section-inner { max-width: 1100px; margin: 0 auto; }
.section-tag {
    display: inline-block;
    background: #E1F5EE;
    color: #0F6E56;
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.section-title {
    font-size: 32px;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 10px;
    letter-spacing: -0.5px;
}
.section-sub { font-size: 15px; color: #888; margin-bottom: 40px; line-height: 1.6; max-width: 500px; }
.text-center { text-align: center; }
.text-center .section-sub { margin: 0 auto 40px; }

/* STUDIOS */
.studio-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
.studio-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 16px;
    padding: 28px 24px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.25s;
    position: relative;
    overflow: hidden;
}
.studio-card::before {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: #1D9E75;
    transform: scaleX(0);
    transition: transform 0.25s;
}
.studio-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); border-color: #1D9E75; }
.studio-card:hover::before { transform: scaleX(1); }
.studio-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: #E1F5EE;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 26px;
}
.studio-name { font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 6px; }
.studio-desc { font-size: 13px; color: #888; line-height: 1.5; margin-bottom: 14px; }
.studio-badge {
    display: inline-block;
    background: #E1F5EE;
    color: #085041;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 500;
}

/* EQUIPMENT */
.items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 16px; }
.item-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #eee;
    transition: all 0.25s;
    cursor: pointer;
}
.item-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); border-color: #1D9E75; }
.item-img {
    width: 100%;
    height: 180px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    overflow: hidden;
    position: relative;
}
.item-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
    background: #f5f5f5;
}
.item-img .item-type-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 500;
}
.item-body { padding: 12px 14px; }
.item-name { font-size: 13px; font-weight: 700; color: #1a1a2e; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-cat { font-size: 11px; color: #aaa; margin-bottom: 10px; }
.item-footer { display: flex; justify-content: space-between; align-items: center; }
.badge-avail { background: #E1F5EE; color: #085041; padding: 3px 8px; border-radius: 99px; font-size: 10px; font-weight: 600; }
.badge-unavail { background: #FAECE7; color: #712B13; padding: 3px 8px; border-radius: 99px; font-size: 10px; font-weight: 600; }
.btn-borrow-sm {
    background: #1D9E75;
    color: #fff;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-borrow-sm:hover { background: #0F6E56; color: #fff; }
.view-all-wrap { text-align: center; margin-top: 36px; }
.btn-view-all {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #1a1a2e;
    color: #fff;
    padding: 13px 32px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-view-all:hover { background: #0f3460; color: #fff; transform: translateY(-2px); }

/* FEATURES */
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
.feature-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 16px;
    padding: 24px;
    transition: all 0.25s;
}
.feature-card:hover { border-color: #1D9E75; box-shadow: 0 8px 20px rgba(29,158,117,0.1); }
.feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #E1F5EE;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 14px;
}
.feature-title { font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 6px; }
.feature-desc { font-size: 13px; color: #888; line-height: 1.6; }

/* HOW IT WORKS */
.steps-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; position: relative; }
.step-card { text-align: center; padding: 20px; }
.step-num {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #1D9E75;
    color: #fff;
    font-size: 18px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.step-title { font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 6px; }
.step-desc { font-size: 13px; color: #888; line-height: 1.6; }
.step-connector {
    display: none;
}

/* GALLERY */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(2, 180px);
    gap: 12px;
}
.gallery-item {
    border-radius: 14px;
    overflow: hidden;
    background: #1a1a2e;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.gallery-item.large { grid-column: span 2; }
.gallery-item .gallery-inner {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 20px;
    text-align: center;
}
.gallery-item .gallery-emoji { font-size: 40px; }
.gallery-item .gallery-label { font-size: 14px; font-weight: 600; color: rgba(255,255,255,0.9); }
.gallery-item .gallery-sub { font-size: 12px; color: rgba(255,255,255,0.5); }
.gallery-item.music { background: linear-gradient(135deg, #0f3460, #16213e); }
.gallery-item.dance { background: linear-gradient(135deg, #2d1b4e, #1a0a2e); }
.gallery-item.gamelan { background: linear-gradient(135deg, #1a2e1a, #0a1e0a); }
.gallery-item.caklempong { background: linear-gradient(135deg, #2e1a0f, #1e0a00); }
.gallery-item.attire { background: linear-gradient(135deg, #1a1a2e, #0f0f1e); }

/* TESTIMONIALS */
.testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
.testimonial-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 16px;
    padding: 24px;
}
.testimonial-stars { color: #F0D000; font-size: 14px; margin-bottom: 12px; }
.testimonial-text { font-size: 14px; color: #555; line-height: 1.7; margin-bottom: 16px; font-style: italic; }
.testimonial-author { display: flex; align-items: center; gap: 10px; }
.testimonial-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #E1F5EE;
    color: #0F6E56;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.testimonial-name { font-size: 13px; font-weight: 700; color: #1a1a2e; }
.testimonial-role { font-size: 11px; color: #aaa; }

/* FAQ */
.faq-list { max-width: 700px; margin: 0 auto; }
.faq-item {
    border: 1px solid #eee;
    border-radius: 12px;
    margin-bottom: 10px;
    overflow: hidden;
}
.faq-q {
    padding: 16px 20px;
    font-size: 14px;
    font-weight: 600;
    color: #1a1a2e;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    user-select: none;
}
.faq-q:hover { background: #f8f9fa; }
.faq-q .faq-icon { font-size: 18px; color: #1D9E75; transition: transform 0.25s; }
.faq-q.open .faq-icon { transform: rotate(45deg); }
.faq-a {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s;
    font-size: 13px;
    color: #666;
    line-height: 1.7;
    background: #fafafa;
}
.faq-a.open { max-height: 200px; padding: 14px 20px; }

/* CTA */
.cta-section {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
    padding: 80px 40px;
    text-align: center;
    color: #fff;
}
.cta-section h2 { font-size: 32px; font-weight: 800; margin-bottom: 12px; }
.cta-section p { font-size: 15px; color: rgba(255,255,255,0.65); margin-bottom: 32px; }
.cta-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

/* FOOTER */
.footer {
    background: #111827;
    color: rgba(255,255,255,0.5);
    padding: 40px;
}
.footer-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 40px;
    margin-bottom: 32px;
}
.footer-brand h4 { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 8px; }
.footer-brand p { font-size: 13px; line-height: 1.6; }
.footer-col h5 { font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.8); margin-bottom: 12px; }
.footer-col a { display: block; font-size: 13px; color: rgba(255,255,255,0.4); text-decoration: none; margin-bottom: 6px; transition: color 0.2s; }
.footer-col a:hover { color: #1D9E75; }
.footer-bottom {
    max-width: 1100px;
    margin: 0 auto;
    padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    flex-wrap: wrap;
    gap: 8px;
}
.footer-bottom a { color: #1D9E75; text-decoration: none; }

/* MODAL */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.modal-overlay.show { display: flex; }
.modal-box {
    background: #fff;
    border-radius: 20px;
    max-width: 500px;
    width: 92%;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,0.4);
    position: relative;
    animation: modalIn 0.25s ease;
}
@keyframes modalIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.modal-close {
    position: absolute;
    top: 14px; right: 14px;
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(0,0,0,0.12);
    border: none;
    font-size: 16px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    z-index: 1;
    color: #fff;
    font-weight: 700;
}
.modal-img {
    width: 100%;
    height: 240px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    overflow: hidden;
}
.modal-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 16px;
    background: #f8f8f8;
}
.modal-body { padding: 24px; }
.modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.modal-name { font-size: 20px; font-weight: 800; color: #1a1a2e; margin-bottom: 4px; }
.modal-cat { font-size: 13px; color: #aaa; }
.modal-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
}
.modal-stat-item .modal-stat-label { font-size: 11px; color: #aaa; margin-bottom: 3px; }
.modal-stat-item .modal-stat-val { font-size: 14px; font-weight: 700; color: #1a1a2e; }
.modal-desc { font-size: 13px; color: #666; line-height: 1.7; margin-bottom: 20px; }
.modal-actions { display: flex; gap: 10px; }
.modal-btn-borrow {
    flex: 1;
    background: #1D9E75;
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    display: block;
    transition: all 0.2s;
}
.modal-btn-borrow:hover { background: #0F6E56; color: #fff; }
.modal-btn-close {
    padding: 12px 20px;
    border-radius: 12px;
    border: 1px solid #eee;
    background: #fff;
    font-size: 14px;
    color: #555;
    cursor: pointer;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
@keyframes zoomIn { from { transform: scale(0.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }

</style>

{{-- ① HERO --}}
<div class="hero">
    <div class="hero-badge">🎵 UTeM Music Studio Management System</div>
    <h1>Your Music Journey<br>Starts <span>Here</span></h1>
    <p>Browse musical equipment, formal attire and book studio sessions — all in one centralized platform.</p>
    <div class="hero-btns">
        <a href="{{ route('items.browse') }}" class="btn-primary-lg">🎸 Browse Equipment</a>
        @if(!session('user_type'))
            <a href="{{ route('register') }}" class="btn-outline-lg">Register as Student →</a>
        @else
            <a href="{{ route('student.dashboard') }}" class="btn-outline-lg">My Dashboard →</a>
        @endif
    </div>
    <div class="hero-preview">
        <div class="hero-preview-stat">
            <div class="num">{{ $totalItems }}+</div>
            <div class="lbl">Items available</div>
        </div>
        <div class="hero-preview-stat">
            <div class="num">4</div>
            <div class="lbl">Studios</div>
        </div>
        <div class="hero-preview-stat">
            <div class="num">{{ $availableItems }}</div>
            <div class="lbl">Ready to borrow</div>
        </div>
    </div>
</div>

{{-- ② STATISTICS --}}
<div class="stats-section">
    <div class="stats-inner">
        <div class="stat-box">
            <div class="num">{{ $totalItems }}<span>+</span></div>
            <div class="lbl">Total inventory items</div>
        </div>
        <div class="stat-box">
            <div class="num">{{ $availableItems }}<span>+</span></div>
            <div class="lbl">Available to borrow</div>
        </div>
        <div class="stat-box">
            <div class="num">4</div>
            <div class="lbl">Studio types</div>
        </div>
        <div class="stat-box">
            <div class="num">24<span>/7</span></div>
            <div class="lbl">Online access</div>
        </div>
    </div>
</div>

{{-- ③ STUDIO TYPES --}}
<div class="section">
    <div class="section-inner">
        <div class="text-center">
            <span class="section-tag">Our Studios</span>
            <div class="section-title">4 Dedicated Studio Spaces</div>
            <div class="section-sub">Each studio is purpose-built for its genre — book your session online in minutes.</div>
        </div>
        <div class="studio-grid">
            <a href="{{ route('studios.browse') }}" class="studio-card">
                <div class="studio-icon">🎸</div>
                <div class="studio-name">Music Studio</div>
                <div class="studio-desc">Full-equipped practice and recording space for bands, soloists and ensembles.</div>
                <span class="studio-badge">Available</span>
            </a>
            <a href="{{ route('studios.browse') }}" class="studio-card">
                <div class="studio-icon">💃</div>
                <div class="studio-name">Dance Studio</div>
                <div class="studio-desc">Mirrored studio with sprung floor for choreography, rehearsals and performances.</div>
                <span class="studio-badge">Available</span>
            </a>
            <a href="{{ route('studios.browse') }}" class="studio-card">
                <div class="studio-icon">🥁</div>
                <div class="studio-name">Gamelan Studio</div>
                <div class="studio-desc">Dedicated space for traditional Javanese gamelan ensemble practice and learning.</div>
                <span class="studio-badge">Available</span>
            </a>
            <a href="{{ route('studios.browse') }}" class="studio-card">
                <div class="studio-icon">🎶</div>
                <div class="studio-name">Caklempong Studio</div>
                <div class="studio-desc">Specially designed for traditional Malay caklempong instrument practice sessions.</div>
                <span class="studio-badge">Available</span>
            </a>
        </div>
    </div>
</div>

{{-- ④ EQUIPMENT SHOWCASE --}}
<div class="section gray">
    <div class="section-inner">
        <div class="text-center">
            <span class="section-tag">Equipment</span>
            <div class="section-title">Browse Our Inventory</div>
            <div class="section-sub">From musical instruments to formal attire — click any item to see full details.</div>
        </div>
        <div class="items-grid">
        @forelse($items->take(8) as $item)
        <div class="item-card"
            data-name="{{ $item->item_name }}"
            data-category="{{ $item->category->category_name ?? '' }}"
            data-image="{{ $item->image }}"
            data-quantity="{{ $item->quantity }}"
            data-available="{{ $item->available_quantity }}"
            data-condition="{{ ucfirst($item->condition_status) }}"
            data-type="{{ ucfirst($item->category->type ?? '') }}"
            data-description="{{ $item->item_description ?? '' }}"
            onclick="openModalFromCard(this)">
                <div class="item-img">
                    @if($item->image)
                        <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->item_name }}">
                    @else
                        📦
                    @endif
                    <span class="item-type-badge">{{ ucfirst($item->category->type ?? 'item') }}</span>
                </div>
                <div class="item-body">
                    <div class="item-name">{{ $item->item_name }}</div>
                    <div class="item-cat">{{ $item->category->category_name ?? '-' }}</div>
                    <div class="item-footer">
                        @if($item->item_status === 'available' && $item->available_quantity > 0)
                            <span class="badge-avail">{{ $item->available_quantity }} left</span>
                            <span style="font-size:10px;color:#bbb;">Click to view</span>
                        @else
                            <span class="badge-unavail">Unavailable</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div style="grid-column:1/-1; text-align:center; color:#888; padding:60px;">
                <div style="font-size:48px; margin-bottom:12px;">📦</div>
                <p>No items yet. Check back soon!</p>
            </div>
            @endforelse
        </div>
        <div class="view-all-wrap">
            <a href="{{ route('items.browse') }}" class="btn-view-all">View All Items →</a>
        </div>
    </div>
</div>

{{-- ⑤ FEATURES --}}
<div class="section">
    <div class="section-inner">
        <div class="text-center">
            <span class="section-tag">Features</span>
            <div class="section-title">Everything you need</div>
            <div class="section-sub">A complete platform for managing your music studio needs.</div>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📦</div>
                <div class="feature-title">Easy borrowing</div>
                <div class="feature-desc">Request equipment and attire online — no more manual paperwork or queuing.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <div class="feature-title">Studio booking</div>
                <div class="feature-desc">Check real-time availability and book your preferred studio session in seconds.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <div class="feature-title">Live inventory</div>
                <div class="feature-desc">See exactly how many items are available before making your request.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <div class="feature-title">Status tracking</div>
                <div class="feature-desc">Track the status of your borrowing requests and bookings in your dashboard.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <div class="feature-title">Secure access</div>
                <div class="feature-desc">Login with your student credentials — your data is safe and private.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <div class="feature-title">Works anywhere</div>
                <div class="feature-desc">Access the system from any device — laptop, tablet or mobile phone.</div>
            </div>
        </div>
    </div>
</div>

{{-- ⑥ HOW IT WORKS --}}
<div class="section gray">
    <div class="section-inner">
        <div class="text-center">
            <span class="section-tag">How it works</span>
            <div class="section-title">Get started in 3 steps</div>
            <div class="section-sub">It's quick, easy and completely online.</div>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">1</div>
                <div class="step-title">Create your account</div>
                <div class="step-desc">Register using your UTeM student email and matric number. Takes less than a minute.</div>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <div class="step-title">Browse & request</div>
                <div class="step-desc">Browse available equipment or studios, check availability and submit your request online.</div>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <div class="step-title">Collect & enjoy</div>
                <div class="step-desc">Once approved by staff, collect your item or show up to your booked studio session.</div>
            </div>
        </div>
    </div>
</div>

{{-- ⑦ GALLERY --}}
<div class="section">
    <div class="section-inner">
        <div class="text-center">
            <span class="section-tag">Gallery</span>
            <div class="section-title">Studio preview</div>
            <div class="section-sub">Get a feel of our studios and what we have to offer.</div>
        </div>
        <div class="gallery-grid">
            <div class="gallery-item music large">
                <div class="gallery-inner">
                    <div class="gallery-emoji">🎸</div>
                    <div class="gallery-label">Music Studio</div>
                    <div class="gallery-sub">Practice · Recording · Rehearsal</div>
                </div>
            </div>
            <div class="gallery-item dance">
                <div class="gallery-inner">
                    <div class="gallery-emoji">💃</div>
                    <div class="gallery-label">Dance Studio</div>
                    <div class="gallery-sub">Choreography · Performance</div>
                </div>
            </div>
            <div class="gallery-item attire">
                <div class="gallery-inner">
                    <div class="gallery-emoji">👔</div>
                    <div class="gallery-label">Formal Attire</div>
                    <div class="gallery-sub">Blazers · Traditional wear</div>
                </div>
            </div>
            <div class="gallery-item gamelan">
                <div class="gallery-inner">
                    <div class="gallery-emoji">🥁</div>
                    <div class="gallery-label">Gamelan Studio</div>
                    <div class="gallery-sub">Traditional · Ensemble</div>
                </div>
            </div>
            <div class="gallery-item caklempong large">
                <div class="gallery-inner">
                    <div class="gallery-emoji">🎶</div>
                    <div class="gallery-label">Caklempong Studio</div>
                    <div class="gallery-sub">Malay traditional music sessions</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ⑧ TESTIMONIALS --}}
<div class="section gray">
    <div class="section-inner">
        <div class="text-center">
            <span class="section-tag">Testimonials</span>
            <div class="section-title">What students say</div>
            <div class="section-sub">Hear from students who use the system regularly.</div>
        </div>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <div class="testimonial-text">"Super easy to use! I booked a Music Studio session in less than 2 minutes. No more back and forth with the staff office."</div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">AM</div>
                    <div>
                        <div class="testimonial-name">Ahmad Muzakkir</div>
                        <div class="testimonial-role">BITD Student · Semester 4</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <div class="testimonial-text">"I love being able to check if the blazer I need is available before coming to the studio. Saves so much time!"</div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">SF</div>
                    <div>
                        <div class="testimonial-name">Siti Farhana</div>
                        <div class="testimonial-role">BITI Student · Semester 3</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★☆</div>
                <div class="testimonial-text">"The Gamelan Studio booking system works really well. My ensemble can now plan our sessions without scheduling conflicts."</div>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">HZ</div>
                    <div>
                        <div class="testimonial-name">Haziq Zulkifli</div>
                        <div class="testimonial-role">BITM Student · Semester 5</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ⑨ FAQ --}}
<div class="section">
    <div class="section-inner">
        <div class="text-center">
            <span class="section-tag">FAQ</span>
            <div class="section-title">Frequently asked questions</div>
            <div class="section-sub">Got questions? We have answers.</div>
        </div>
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    Who can use the UTeM Music Studio system?
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-a">Any registered UTeM student can create an account and use the system to browse items, request borrowings and book studio sessions. Staff members manage approvals and inventory.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    How long can I borrow an item?
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-a">Borrowing duration is set when you submit your request. Standard borrowing is up to 7 days. Extensions can be requested through the system if needed.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    How do I book a studio session?
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-a">Register or login, go to Studios, select your preferred studio, choose a date and time, and submit your booking request. Staff will approve it and you'll see the status update in your dashboard.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    What happens if I return an item late?
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-a">Late returns are flagged in the system as overdue. Please return items on time so other students can borrow them. Consistent late returns may affect your borrowing privileges.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">
                    Can I cancel a studio booking?
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-a">Yes, you can cancel a booking from your dashboard as long as it hasn't been approved yet. Once approved, please contact the staff to cancel so the slot can be opened for others.</div>
            </div>
        </div>
    </div>
</div>

{{-- ⑩ FINAL CTA --}}
<div class="cta-section">
    <span class="section-tag" style="background:rgba(29,158,117,0.2);color:#5DCAA5;">Get started today</span>
    <h2>Ready to get started?</h2>
    <p>Join hundreds of UTeM students already using the system to manage their music studio needs.</p>
    <div class="cta-btns">
        @if(!session('user_type'))
            <a href="{{ route('register') }}" class="btn-primary-lg">🎓 Register Now — It's Free</a>
            <a href="{{ route('login') }}" class="btn-outline-lg">Already have an account? Login</a>
        @else
            <a href="{{ route('items.browse') }}" class="btn-primary-lg">🎸 Browse Items</a>
            <a href="{{ route('studios.browse') }}" class="btn-outline-lg">📅 Book a Studio</a>
        @endif
    </div>
</div>

{{-- ⑪ FOOTER --}}
<div class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <h4>🎵 UTeM Music Studio</h4>
            <p>A centralized web-based inventory and management system for UTeM Music Studio — making it easier for students to borrow equipment and book sessions.</p>
        </div>
        <div class="footer-col">
            <h5>Quick Links</h5>
            <a href="{{ route('landing') }}">Home</a>
            <a href="{{ route('items.browse') }}">Browse Items</a>
            <a href="{{ route('studios.browse') }}">Studios</a>
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Register</a>
        </div>
        <div class="footer-col">
            <h5>Studios</h5>
            <a href="{{ route('studios.browse') }}">Music Studio</a>
            <a href="{{ route('studios.browse') }}">Dance Studio</a>
            <a href="{{ route('studios.browse') }}">Gamelan Studio</a>
            <a href="{{ route('studios.browse') }}">Caklempong Studio</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© {{ date('Y') }} UTeM Music Studio Management System. All rights reserved.</span>
        <span>Built with ❤️ for UTeM students</span>
    </div>
</div>

{{-- ITEM DETAIL MODAL --}}
<div id="itemModal" class="modal-overlay">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div id="modal-img" class="modal-img" onclick="openFullscreen()" style="cursor:zoom-in;" title="Click to view fullscreen"></div>
        <div class="modal-body">
            <div class="modal-header">
                <div>
                    <div id="modal-name" class="modal-name"></div>
                    <div id="modal-category" class="modal-cat"></div>
                </div>
                <span id="modal-status"></span>
            </div>
            <div class="modal-stats">
                <div class="modal-stat-item">
                    <div class="modal-stat-label">Total quantity</div>
                    <div id="modal-qty" class="modal-stat-val"></div>
                </div>
                <div class="modal-stat-item">
                    <div class="modal-stat-label">Available</div>
                    <div id="modal-available" class="modal-stat-val" style="color:#1D9E75;"></div>
                </div>
                <div class="modal-stat-item">
                    <div class="modal-stat-label">Condition</div>
                    <div id="modal-condition" class="modal-stat-val"></div>
                </div>
                <div class="modal-stat-item">
                    <div class="modal-stat-label">Type</div>
                    <div id="modal-type" class="modal-stat-val"></div>
                </div>
            </div>
            <div id="modal-desc" class="modal-desc"></div>
            <div class="modal-actions">
                <a id="modal-borrow-btn" href="#" class="modal-btn-borrow">🔒 Login to Borrow</a>
                <button onclick="closeModal()" class="modal-btn-close">Close</button>
            </div>
        </div>
    </div>
</div>

<script>

function openModalFromCard(el) {
    openModal({
        name: el.dataset.name,
        category: el.dataset.category,
        image: el.dataset.image,
        quantity: el.dataset.quantity,
        available: parseInt(el.dataset.available),
        condition: el.dataset.condition,
        type: el.dataset.type,
        description: el.dataset.description
    });
}

function openModal(item) {
    const imgDiv = document.getElementById('modal-img');
    if (item.image) {
        imgDiv.innerHTML = `<img src="/storage/${item.image}" alt="${item.name}">`;
    } else {
        imgDiv.innerHTML = '📦';
    }
    document.getElementById('modal-name').textContent = item.name;
    document.getElementById('modal-category').textContent = item.category;
    document.getElementById('modal-qty').textContent = item.quantity + ' units';
    document.getElementById('modal-available').textContent = item.available + ' available';
    document.getElementById('modal-condition').textContent = item.condition;
    document.getElementById('modal-type').textContent = item.type;
    document.getElementById('modal-desc').textContent = item.description || 'No description available.';

    const statusEl = document.getElementById('modal-status');
    if (item.available > 0) {
        statusEl.innerHTML = '<span style="background:#E1F5EE;color:#085041;padding:4px 12px;border-radius:99px;font-size:12px;font-weight:600;">Available</span>';
    } else {
        statusEl.innerHTML = '<span style="background:#FAECE7;color:#712B13;padding:4px 12px;border-radius:99px;font-size:12px;font-weight:600;">Unavailable</span>';
    }

    const btn = document.getElementById('modal-borrow-btn');
    if (item.available > 0) {
        btn.href = '/login';
        btn.style.background = '#1D9E75';
        btn.style.color = '#fff';
        btn.style.cursor = 'pointer';
        btn.textContent = '🔒 Login to Borrow';
        btn.onclick = null;
    } else {
        btn.href = '#';
        btn.style.background = '#e0e0e0';
        btn.style.color = '#999';
        btn.style.cursor = 'not-allowed';
        btn.textContent = 'Not Available';
        btn.onclick = e => e.preventDefault();
    }

    document.getElementById('itemModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('itemModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

document.getElementById('itemModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

function toggleFaq(el) {
    el.classList.toggle('open');
    const ans = el.nextElementSibling;
    ans.classList.toggle('open');
}

function openFullscreen() {
    const imgDiv = document.getElementById('modal-img');
    const img = imgDiv.querySelector('img');
    if (!img) return;

    const overlay = document.createElement('div');
    overlay.id = 'fullscreen-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.95);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: zoom-out;
        animation: fadeIn 0.2s ease;
    `;

    const fullImg = document.createElement('img');
    fullImg.src = img.src;
    fullImg.style.cssText = `
        max-width: 92%;
        max-height: 92vh;
        object-fit: contain;
        border-radius: 12px;
        box-shadow: 0 30px 80px rgba(0,0,0,0.8);
        animation: zoomIn 0.25s ease;
    `;

    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '✕';
    closeBtn.style.cssText = `
        position: fixed;
        top: 20px; right: 24px;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        width: 40px; height: 40px;
        border-radius: 50%;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        z-index: 100000;
    `;
    closeBtn.onmouseover = () => closeBtn.style.background = 'rgba(255,255,255,0.25)';
    closeBtn.onmouseout  = () => closeBtn.style.background = 'rgba(255,255,255,0.15)';

    const hint = document.createElement('div');
    hint.innerHTML = 'Click anywhere or press ESC to close';
    hint.style.cssText = `
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        color: rgba(255,255,255,0.4);
        font-size: 12px;
        font-family: sans-serif;
    `;

    overlay.appendChild(fullImg);
    overlay.appendChild(closeBtn);
    overlay.appendChild(hint);
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    const closeFullscreen = () => {
        overlay.style.animation = 'fadeOut 0.2s ease';
        setTimeout(() => {
            if (document.getElementById('fullscreen-overlay')) {
                document.body.removeChild(overlay);
            }
        }, 200);
    };

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay || e.target === fullImg) closeFullscreen();
    });
    closeBtn.addEventListener('click', closeFullscreen);

    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
            closeFullscreen();
            document.removeEventListener('keydown', escHandler);
        }
    });
}

</script>

@endsection