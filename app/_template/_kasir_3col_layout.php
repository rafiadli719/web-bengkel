<?php /* CSS: Kasir 3-Column Viewport Layout v1.0 */ ?>
<style>
/* ============================================================
   KASIR SHELL — 3-column, no-scroll viewport layout
   ============================================================ */
html, body {
    height: 100%;
    overflow: hidden;
    margin: 0;
    padding: 0;
}

.ks-shell {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #eef0f3;
}

/* ---- Top Bar ---- */
.ks-topbar {
    background: linear-gradient(135deg, #2C3E50 0%, #1a3a5c 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    height: 50px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
    gap: 12px;
    z-index: 10;
}

.ks-topbar-brand {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #d8e6f3;
    text-decoration: none;
    flex-shrink: 0;
}
.ks-topbar-brand i { color: #4a90d9; font-size: 16px; }
.ks-topbar-brand:hover { color: #fff; text-decoration: none; }

.ks-topbar-info {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}

.ks-topbar-item {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
    min-width: 0;
}
.ks-topbar-item .lbl {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: rgba(255,255,255,.5);
    white-space: nowrap;
}
.ks-topbar-item .val {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 160px;
}

.ks-topbar-divider {
    width: 1px;
    height: 26px;
    background: rgba(255,255,255,.18);
    flex-shrink: 0;
}

.ks-status-pill {
    padding: 2px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    border: 1px solid;
}
.ks-status-pill.datang  { background: rgba(91,192,222,.15);  color: #7dd4ed; border-color: rgba(91,192,222,.3); }
.ks-status-pill.proses  { background: rgba(240,173,78,.15);  color: #f5c96e; border-color: rgba(240,173,78,.3); }
.ks-status-pill.selesai { background: rgba(92,184,92,.15);   color: #7dcc7d; border-color: rgba(92,184,92,.3); }
.ks-status-pill.bayar   { background: rgba(92,184,92,.3);    color: #b3e8b3; border-color: rgba(92,184,92,.4); }
.ks-status-pill.jemput  { background: rgba(240,173,78,.2);   color: #f5c96e; border-color: rgba(240,173,78,.3); }
.ks-status-pill.garansi { background: rgba(155,89,182,.2);   color: #c99fe0; border-color: rgba(155,89,182,.3); }

.ks-topbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.ks-total-live-wrap {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
    text-align: right;
}
.ks-total-live-wrap .lbl { font-size: 9px; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .07em; }
.ks-total-live         { font-size: 16px; font-weight: 800; color: #7effa4; letter-spacing: .01em; white-space: nowrap; }

.ks-topbar-btn {
    padding: 5px 11px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: 1px solid rgba(255,255,255,.28);
    color: #fff;
    background: rgba(255,255,255,.08);
    cursor: pointer;
    transition: background .15s;
    white-space: nowrap;
}
.ks-topbar-btn:hover { background: rgba(255,255,255,.18); color: #fff; text-decoration: none; }

.ks-user-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: rgba(255,255,255,.75);
}
.ks-user-photo {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.35);
    object-fit: cover;
}
.ks-user-name { font-size: 12px; font-weight: 600; white-space: nowrap; }

/* Form wrapper inside ks-shell: must be flex to pass height down to ks-body */
.ks-shell > form {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}


/* ---- 3-Column Body ---- */
.ks-body {
    display: grid;
    grid-template-columns: 268px 1fr 292px;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

/* ---- Left Panel ---- */
.ks-left {
    background: #fff;
    border-right: 1px solid #dde1e7;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* ---- Center Panel ---- */
.ks-center {
    display: flex;
    flex-direction: column;
    background: #f4f6f9;
    overflow: hidden;
    min-width: 0;
}

/* ---- Right Panel ---- */
.ks-right {
    background: #fff;
    border-left: 1px solid #dde1e7;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* ============================================================
   LEFT PANEL components
   ============================================================ */
.ks-section-hdr {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #8a94a6;
    letter-spacing: .07em;
    padding: 0 0 3px;
    border-bottom: 1px solid #eef0f3;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Plate display */
.ks-plat-card {
    background: #1a2332;
    border-radius: 8px;
    padding: 8px 12px;
    border: 2px solid #3a6db5;
    text-align: center;
    position: relative;
}
.ks-plat-val {
    font-size: 22px;
    font-weight: 900;
    color: #fff;
    letter-spacing: 4px;
    line-height: 1.1;
    font-family: 'Courier New', monospace;
    display: block;
}
.ks-plat-sub {
    font-size: 10px;
    color: #7eb6e8;
    margin-top: 2px;
    display: block;
}
.ks-plat-none {
    font-size: 13px;
    color: #f0ad4e;
    font-style: italic;
    display: block;
    padding: 6px 0;
}
.ks-riwayat-btn {
    position: absolute;
    top: 5px;
    right: 6px;
    background: rgba(74,144,217,.3);
    border: 1px solid rgba(74,144,217,.5);
    color: #7eb6e8;
    font-size: 10px;
    padding: 1px 6px;
    border-radius: 3px;
    cursor: pointer;
    text-decoration: none;
}
.ks-riwayat-btn:hover { background: rgba(74,144,217,.5); color: #fff; text-decoration: none; }

/* Vehicle info grid */
.ks-vehicle-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 8px;
    background: #f8f9fb;
    border-radius: 6px;
    padding: 7px 9px;
}
.ks-vehicle-grid .vg-item { display: flex; flex-direction: column; }
.ks-vehicle-grid .vg-label { font-size: 9px; color: #8a94a6; text-transform: uppercase; letter-spacing: .04em; }
.ks-vehicle-grid .vg-val   { font-weight: 600; color: #2d3748; font-size: 12px; line-height: 1.3; }

/* Customer card */
.ks-pelanggan-card {
    background: linear-gradient(135deg, #eaf4ff, #f0f7ff);
    border-radius: 6px;
    padding: 7px 10px;
    border-left: 3px solid #4a90d9;
}
.ks-pelanggan-nama { font-weight: 700; font-size: 13px; color: #1a3a5c; line-height: 1.3; }
.ks-pelanggan-meta { font-size: 10px; color: #6b8aa8; margin-top: 1px; }
.ks-pelanggan-badges { display: flex; gap: 5px; margin-top: 4px; flex-wrap: wrap; }
.ks-member-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 10px;
}
.ks-member-badge.gold    { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
.ks-member-badge.silver  { background: #f1f5f9; color: #475569; border: 1px solid #94a3b8; }
.ks-member-badge.bronze  { background: #fdf2e9; color: #7c3514; border: 1px solid #d97706; }
.ks-member-badge.regular { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
.ks-member-badge.neutral { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

/* KM row */
.ks-km-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}
.ks-km-group { display: flex; flex-direction: column; gap: 2px; }
.ks-km-group label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #8a94a6;
    letter-spacing: .05em;
    margin: 0;
}
.ks-km-input {
    width: 100%;
    border: 1px solid #d1d9e0;
    border-radius: 5px;
    padding: 5px 7px;
    font-size: 13px;
    font-weight: 600;
    color: #2d3748;
    background: #f8f9fb;
    text-align: right;
}
.ks-km-input:focus { outline: none; border-color: #4a90d9; background: #fff; box-shadow: 0 0 0 2px rgba(74,144,217,.15); }

/* Keluhan section */
.ks-keluhan-input-row {
    display: flex;
    gap: 4px;
    align-items: center;
}
.ks-keluhan-input {
    flex: 1;
    border: 1px solid #d1d9e0;
    border-radius: 5px;
    padding: 5px 7px;
    font-size: 12px;
    background: #fff;
    min-width: 0;
}
.ks-keluhan-input:focus { outline: none; border-color: #f0ad4e; box-shadow: 0 0 0 2px rgba(240,173,78,.15); }

.ks-btn-srch-keluhan {
    padding: 5px 7px;
    border: 1px solid #d1d9e0;
    border-radius: 5px;
    background: #fff;
    color: #4a90d9;
    font-size: 12px;
    cursor: pointer;
    flex-shrink: 0;
}
.ks-btn-srch-keluhan:hover { background: #eaf4ff; }

.ks-btn-add-keluhan {
    padding: 5px 9px;
    border: none;
    border-radius: 5px;
    background: #f0ad4e;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
}
.ks-btn-add-keluhan:hover { background: #e09938; }

.ks-keluhan-list {
    display: flex;
    flex-direction: column;
    gap: 3px;
    max-height: 108px;
    overflow-y: auto;
    margin-top: 4px;
}
.ks-keluhan-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 7px;
    background: #f8f9fb;
    border-radius: 4px;
    font-size: 11px;
    border-left: 3px solid #ccc;
}
.ks-keluhan-item.selesai       { border-left-color: #5cb85c; }
.ks-keluhan-item.diproses      { border-left-color: #f0ad4e; }
.ks-keluhan-item.tidak_selesai { border-left-color: #d9534f; }
.ks-keluhan-item.datang        { border-left-color: #5bc0de; }
.ks-keluhan-no {
    font-size: 10px;
    font-weight: 700;
    color: #999;
    min-width: 16px;
    text-align: center;
}
.ks-keluhan-teks { flex: 1; font-weight: 500; color: #333; line-height: 1.2; }
.ks-keluhan-badge {
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 8px;
    font-weight: 700;
    text-transform: uppercase;
    background: #eee;
    color: #666;
    flex-shrink: 0;
}
.ks-keluhan-badge.selesai       { background: #d4edda; color: #155724; }
.ks-keluhan-badge.diproses      { background: #fff3cd; color: #856404; }
.ks-keluhan-badge.tidak_selesai { background: #f8d7da; color: #721c24; }
.ks-keluhan-badge.datang        { background: #d1ecf1; color: #0c5460; }

/* KM Harian alert */
.ks-km-harian {
    padding: 6px 9px;
    border-radius: 6px;
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 6px;
    line-height: 1.3;
}
.ks-km-harian.ok      { background: #f0fff4; border: 1px solid #9ae6b4; color: #276749; }
.ks-km-harian.warning { background: #fffbeb; border: 1px solid #f6e05e; color: #744210; }
.ks-km-harian-link {
    margin-left: auto;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
    background: #f0ad4e;
    color: #fff;
    white-space: nowrap;
    flex-shrink: 0;
}
.ks-km-harian-link:hover { background: #e09938; color: #fff; text-decoration: none; }

/* Mekanik assignment */
.ks-mekanik-block { display: flex; flex-direction: column; gap: 3px; }
.ks-staff-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #8a94a6;
    letter-spacing: .05em;
    margin: 0 0 1px;
}
.ks-staff-row {
    display: grid;
    grid-template-columns: 1fr 60px;
    gap: 3px;
    align-items: center;
}
.ks-persen-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.ks-persen-slider {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 4px;
    border-radius: 2px;
    background: #d1d9e0;
    outline: none;
    cursor: pointer;
    transition: background .15s;
}
.ks-persen-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #4a90d9;
    cursor: pointer;
    transition: background .15s, transform .1s;
}
.ks-persen-slider::-moz-range-thumb {
    width: 12px;
    height: 12px;
    border: none;
    border-radius: 50%;
    background: #4a90d9;
    cursor: pointer;
    transition: background .15s, transform .1s;
}
.ks-persen-slider:active::-webkit-slider-thumb { transform: scale(1.15); }
.ks-persen-slider:disabled { opacity: .35; cursor: not-allowed; }
.ks-persen-slider:disabled::-webkit-slider-thumb { background: #b0b8c1; }
.ks-staff-row select {
    font-size: 11px !important;
    padding: 3px 5px !important;
    height: 27px !important;
    border: 1px solid #d1d9e0 !important;
    border-radius: 4px !important;
    background: #fff !important;
    color: #333 !important;
    width: 100% !important;
}
.ks-staff-row input[type=number] {
    font-size: 11px !important;
    padding: 3px 4px !important;
    height: 27px !important;
    border: 1px solid #d1d9e0 !important;
    border-radius: 4px !important;
    text-align: center !important;
    width: 100% !important;
    background: #f8f9fb !important;
}
.ks-staff-row select:focus, .ks-staff-row input[type=number]:focus {
    outline: none !important;
    border-color: #4a90d9 !important;
}
.ks-mekanik-group-hdr {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #4a90d9;
    letter-spacing: .06em;
    margin: 4px 0 2px;
    padding-bottom: 2px;
    border-bottom: 1px dashed #d1d9e0;
}

.ks-btn-mini {
    padding: 4px 9px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background .12s;
    text-decoration: none;
}
.ks-btn-mini.primary   { background: #4a90d9; color: #fff; border: 1px solid #3a7bc8; }
.ks-btn-mini.primary:hover { background: #3a7bc8; }
.ks-btn-mini.outline   { background: #fff; border: 1px solid #4a90d9; color: #4a90d9; }
.ks-btn-mini.outline:hover { background: #eaf4ff; }
.ks-btn-mini.success   { background: #5cb85c; color: #fff; border: 1px solid #4cae4c; }
.ks-btn-mini.success:hover { background: #4cae4c; }
.ks-btn-mini.warning   { background: #f0ad4e; color: #fff; border: 1px solid #e09938; }
.ks-btn-mini.danger    { background: #d9534f; color: #fff; border: 1px solid #c9302c; }
.ks-btn-mini.secondary { background: #f1f3f5; color: #555; border: 1px solid #d1d9e0; }
.ks-btn-mini.secondary:hover { background: #e5e9ef; }
.ks-btn-mini.info      { background: #5bc0de; color: #fff; border: 1px solid #46b8da; }

.ks-left-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    padding-top: 6px;
    border-top: 1px solid #eef0f3;
    margin-top: 2px;
}

/* ============================================================
   CENTER — Tabs
   ============================================================ */
.ks-tabs-nav {
    display: flex;
    background: #fff;
    border-bottom: 2px solid #e2e8f0;
    flex-shrink: 0;
    overflow-x: auto;
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.ks-tabs-nav::-webkit-scrollbar { display: none; }

.ks-tab-btn {
    padding: 9px 14px;
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    border: none;
    background: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    white-space: nowrap;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: color .15s;
    flex-shrink: 0;
}
.ks-tab-btn:hover  { color: #4a90d9; background: #f8f9ff; text-decoration: none; }
.ks-tab-btn.active { color: #4a90d9; border-bottom-color: #4a90d9; font-weight: 600; }

.ks-badge {
    background: #4a90d9;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 9px;
    line-height: 1.4;
}
.ks-badge.warning { background: #f0ad4e; }
.ks-badge.success { background: #5cb85c; }

.ks-tab-contents {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 12px;
    min-height: 0;
}

.ks-tab-pane { display: none; }
.ks-tab-pane.active { display: block; animation: ksFadeIn .18s ease; }

@keyframes ksFadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ============================================================
   RIGHT PANEL — Payment
   ============================================================ */
.ks-ringkasan {
    background: #f8f9fb;
    border-radius: 7px;
    padding: 9px 11px;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.ks-ring-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    font-size: 12px;
    color: #555;
    border-bottom: 1px dashed #e2e8f0;
}
.ks-ring-row:last-child  { border-bottom: none; }
.ks-ring-row .r-label { color: #888; }
.ks-ring-row .r-val   { font-weight: 600; color: #333; font-family: monospace; }

.ks-ring-subtotal {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 13px;
    font-weight: 700;
    color: #1a3a5c;
    border-top: 2px solid #ccd4dd;
    border-bottom: 1px solid #e2e8f0;
    margin-top: 1px;
}

.ks-diskon-row {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 0;
    border-bottom: 1px dashed #e2e8f0;
}
.ks-diskon-row .dr-label { font-size: 11px; color: #888; flex: 1; }
.ks-diskon-row .dr-input {
    width: 52px;
    border: 1px solid #d1d9e0;
    border-radius: 4px;
    padding: 3px 4px;
    font-size: 11px;
    text-align: center;
    background: #fff;
    flex-shrink: 0;
}
.ks-diskon-row .dr-input:focus { outline: none; border-color: #4a90d9; }
.ks-diskon-row .dr-suffix { font-size: 10px; color: #aaa; flex-shrink: 0; }
.ks-diskon-row .dr-val { font-size: 11px; font-weight: 600; color: #d9534f; white-space: nowrap; font-family: monospace; }

.ks-ppn-row {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 0;
    border-bottom: 1px dashed #e2e8f0;
}
.ks-ppn-row .pr-label { font-size: 11px; color: #888; flex: 1; }
.ks-ppn-row .pr-input {
    width: 52px;
    border: 1px solid #d1d9e0;
    border-radius: 4px;
    padding: 3px 4px;
    font-size: 11px;
    text-align: center;
    background: #fff;
    flex-shrink: 0;
}
.ks-ppn-row .pr-input:focus { outline: none; border-color: #4a90d9; }
.ks-ppn-row .pr-suffix { font-size: 10px; color: #aaa; flex-shrink: 0; }
.ks-ppn-row .pr-val { font-size: 11px; font-weight: 600; color: #555; white-space: nowrap; font-family: monospace; }

.ks-total-bayar-box {
    background: linear-gradient(135deg, #1c5f2e, #28a745);
    border-radius: 8px;
    padding: 9px 13px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 8px rgba(40,167,69,.2);
}
.ks-total-bayar-label { font-size: 10px; font-weight: 700; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .05em; }
.ks-total-bayar-val   { font-size: 19px; font-weight: 900; color: #fff; letter-spacing: .01em; font-family: monospace; }

/* Metode pembayaran */
.ks-metode-select {
    width: 100%;
    border: 1px solid #d1d9e0;
    border-radius: 6px;
    padding: 7px 9px;
    font-size: 13px;
    background: #fff;
    color: #333;
}
.ks-metode-select:focus { outline: none; border-color: #4a90d9; }

/* Bukti upload */
.ks-bukti-group {
    background: #fffbeb;
    border: 1px solid #f6e05e;
    border-radius: 6px;
    padding: 7px 10px;
}
.ks-bukti-group label { font-size: 10px; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 4px; }
.ks-bukti-group input[type=file] { font-size: 11px; }

/* Tunai + Kembalian */
.ks-tunai-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}
.ks-inp-group { display: flex; flex-direction: column; gap: 2px; }
.ks-inp-group label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #8a94a6;
    margin: 0;
    letter-spacing: .05em;
}
.ks-inp-wrap {
    display: flex;
    align-items: center;
    border: 1px solid #d1d9e0;
    border-radius: 5px;
    overflow: hidden;
    background: #fff;
}
.ks-inp-wrap .rp-pre {
    padding: 0 6px;
    background: #f1f3f5;
    color: #6b7280;
    font-size: 11px;
    font-weight: 600;
    border-right: 1px solid #d1d9e0;
    white-space: nowrap;
    align-self: stretch;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}
.ks-inp-wrap input {
    flex: 1;
    border: none;
    padding: 6px 7px;
    font-size: 13px;
    font-weight: 600;
    text-align: right;
    background: transparent;
    color: #333;
    min-width: 0;
}
.ks-inp-wrap input:focus { outline: none; }
.ks-inp-wrap.kembalian { background: #f0fff8; border-color: #9ae6b4; }
.ks-inp-wrap.kembalian input { color: #276749; }

/* Action buttons */
.ks-btn-bayar {
    width: 100%;
    padding: 12px;
    font-size: 15px;
    font-weight: 800;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #28a745, #20c556);
    color: #fff;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(40,167,69,.25);
    transition: all .18s;
    letter-spacing: .02em;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}
.ks-btn-bayar:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(40,167,69,.35); }

.ks-btn-simpan {
    flex: 1;
    padding: 9px;
    font-size: 13px;
    font-weight: 600;
    border: 2px solid #4a90d9;
    border-radius: 7px;
    background: #fff;
    color: #4a90d9;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: background .12s;
}
.ks-btn-simpan:hover { background: #eaf4ff; }

.ks-btn-action {
    padding: 8px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: background .12s;
}
.ks-btn-action.danger  { border: 1px solid #d9534f; background: #fff; color: #d9534f; }
.ks-btn-action.danger:hover  { background: #fff5f5; }
.ks-btn-action.info    { border: 1px solid #5bc0de; background: #fff; color: #31b0d5; }
.ks-btn-action.info:hover    { background: #f0faff; }
.ks-btn-action.warning { border: 1px solid #f0ad4e; background: #fff; color: #c87f00; }
.ks-btn-action.warning:hover { background: #fffbf0; }

.ks-right-secondary-btns {
    display: flex;
    gap: 4px;
}
.ks-right-secondary-btns .ks-btn-action {
    flex: 1;
    justify-content: center;
    font-size: 11px;
    padding: 6px 5px;
}

/* Scrollbar thin */
.ks-left::-webkit-scrollbar, .ks-right::-webkit-scrollbar, .ks-tab-contents::-webkit-scrollbar { width: 4px; }
.ks-left::-webkit-scrollbar-track, .ks-right::-webkit-scrollbar-track, .ks-tab-contents::-webkit-scrollbar-track { background: #f1f1f1; }
.ks-left::-webkit-scrollbar-thumb, .ks-right::-webkit-scrollbar-thumb, .ks-tab-contents::-webkit-scrollbar-thumb { background: #c8d0dc; border-radius: 4px; }

/* Ensure existing rd- card styles still render inside tab content */
.ks-tab-contents .rd-card { border-radius: 6px; }
</style>
