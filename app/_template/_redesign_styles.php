<?php
/**
 * REDESIGN STYLES - Global CSS untuk semua tab redesign
 * Version: 2.0 - Clean & Focused UI
 *
 * Digunakan oleh semua template -coba.php
 */
?>

<style>
/* ============================================
   GLOBAL REDESIGN STYLES
   ============================================ */

/* CSS Variables - Color Palette */
:root {
    --rd-primary: #4A90D9;
    --rd-primary-dark: #3A7BC8;
    --rd-primary-light: rgba(74, 144, 217, 0.1);
    --rd-success: #5CB85C;
    --rd-success-light: rgba(92, 184, 92, 0.1);
    --rd-warning: #F0AD4E;
    --rd-warning-light: rgba(240, 173, 78, 0.1);
    --rd-danger: #D9534F;
    --rd-danger-light: rgba(217, 83, 79, 0.1);
    --rd-info: #5BC0DE;
    --rd-info-light: rgba(91, 192, 222, 0.1);
    --rd-purple: #9B59B6;
    --rd-purple-light: rgba(155, 89, 182, 0.1);
    --rd-neutral: #6C757D;
    --rd-neutral-light: #ADB5BD;
    --rd-bg-light: #F8F9FA;
    --rd-bg-white: #FFFFFF;
    --rd-border: #E9ECEF;
    --rd-border-dark: #DEE2E6;
    --rd-text-dark: #333333;
    --rd-text-muted: #6C757D;
    --rd-text-light: #ADB5BD;
    --rd-shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
    --rd-shadow-md: 0 4px 12px rgba(0,0,0,0.1);
    --rd-radius-sm: 4px;
    --rd-radius-md: 8px;
    --rd-radius-lg: 12px;
    --rd-transition: all 0.2s ease;
}

/* ============================================
   TYPOGRAPHY
   ============================================ */
.rd-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--rd-text-dark);
    margin: 0 0 4px 0;
}

.rd-subtitle {
    font-size: 14px;
    color: var(--rd-text-muted);
    margin: 0;
}

.rd-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--rd-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: block;
}

.rd-value {
    font-size: 14px;
    font-weight: 500;
    color: var(--rd-text-dark);
}

.rd-value-lg {
    font-size: 18px;
    font-weight: 600;
    color: var(--rd-text-dark);
}

.rd-required::after {
    content: " *";
    color: var(--rd-danger);
}

/* ============================================
   TABS NAVIGATION (Redesign)
   ============================================ */
.rd-tabs-nav {
    display: flex;
    gap: 4px;
    background: var(--rd-bg-light);
    padding: 8px;
    border-radius: var(--rd-radius-lg);
    margin-bottom: 20px;
    overflow-x: auto;
}

.rd-tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    background: transparent;
    color: var(--rd-text-muted);
    font-size: 13px;
    font-weight: 500;
    border-radius: var(--rd-radius-md);
    cursor: pointer;
    transition: var(--rd-transition);
    white-space: nowrap;
}

.rd-tab-btn:hover {
    background: var(--rd-bg-white);
    color: var(--rd-primary);
}

.rd-tab-btn.active {
    background: var(--rd-bg-white);
    color: var(--rd-primary);
    box-shadow: var(--rd-shadow-sm);
}

.rd-tab-btn i {
    font-size: 14px;
}

.rd-tab-btn .rd-badge {
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    background: var(--rd-border);
    color: var(--rd-text-muted);
}

.rd-tab-btn.active .rd-badge,
.rd-tab-btn:hover .rd-badge {
    background: var(--rd-primary-light);
    color: var(--rd-primary);
}

.rd-tab-btn .rd-badge.warning {
    background: var(--rd-warning);
    color: white;
}

/* ============================================
   CARDS
   ============================================ */
.rd-card {
    background: var(--rd-bg-white);
    border: 1px solid var(--rd-border);
    border-radius: var(--rd-radius-md);
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: var(--rd-shadow-sm);
}

.rd-card-header {
    padding: 16px 20px;
    background: var(--rd-bg-light);
    border-bottom: 1px solid var(--rd-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rd-card-header h5 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--rd-text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.rd-card-header h5 i {
    color: var(--rd-primary);
}

.rd-card-body {
    padding: 20px;
}

.rd-card-footer {
    padding: 16px 20px;
    background: var(--rd-bg-light);
    border-top: 1px solid var(--rd-border);
}

/* Card Variants */
.rd-card.primary { border-left: 4px solid var(--rd-primary); }
.rd-card.success { border-left: 4px solid var(--rd-success); }
.rd-card.warning { border-left: 4px solid var(--rd-warning); }
.rd-card.danger { border-left: 4px solid var(--rd-danger); }
.rd-card.info { border-left: 4px solid var(--rd-info); }
.rd-card.purple { border-left: 4px solid var(--rd-purple); }

/* Collapsible Card */
.rd-card-header.collapsible {
    cursor: pointer;
    user-select: none;
}

.rd-card-header.collapsible:hover {
    background: var(--rd-border);
}

.rd-card-header .rd-collapse-icon {
    transition: transform 0.2s;
}

.rd-card-header.collapsed .rd-collapse-icon {
    transform: rotate(-90deg);
}

/* ============================================
   FORMS
   ============================================ */
.rd-form-group {
    margin-bottom: 16px;
}

.rd-form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.rd-form-row > .rd-form-group {
    flex: 1;
    margin-bottom: 0;
}

.rd-input,
.rd-select,
.rd-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--rd-border);
    border-radius: var(--rd-radius-sm);
    font-size: 14px;
    color: var(--rd-text-dark);
    background: var(--rd-bg-white);
    transition: var(--rd-transition);
}

.rd-input:focus,
.rd-select:focus,
.rd-textarea:focus {
    outline: none;
    border-color: var(--rd-primary);
    box-shadow: 0 0 0 3px var(--rd-primary-light);
}

.rd-input:disabled,
.rd-select:disabled,
.rd-textarea:disabled {
    background: var(--rd-bg-light);
    color: var(--rd-text-muted);
    cursor: not-allowed;
}

.rd-input.sm { padding: 8px 12px; font-size: 13px; }
.rd-input.lg { padding: 12px 16px; font-size: 15px; }

.rd-input-group {
    display: flex;
    gap: 0;
}

.rd-input-group .rd-input {
    border-radius: var(--rd-radius-sm) 0 0 var(--rd-radius-sm);
}

.rd-input-group .rd-btn {
    border-radius: 0 var(--rd-radius-sm) var(--rd-radius-sm) 0;
}

/* ============================================
   BUTTONS
   ============================================ */
.rd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border: none;
    border-radius: var(--rd-radius-sm);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--rd-transition);
    text-decoration: none;
}

.rd-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.rd-btn:active {
    transform: translateY(0);
}

.rd-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* Button Sizes */
.rd-btn.xs { padding: 6px 10px; font-size: 11px; }
.rd-btn.sm { padding: 8px 14px; font-size: 12px; }
.rd-btn.lg { padding: 12px 24px; font-size: 14px; }
.rd-btn.xl { padding: 14px 28px; font-size: 15px; }

/* Button Variants */
.rd-btn.primary { background: var(--rd-primary); color: white; }
.rd-btn.success { background: var(--rd-success); color: white; }
.rd-btn.warning { background: var(--rd-warning); color: white; }
.rd-btn.danger { background: var(--rd-danger); color: white; }
.rd-btn.info { background: var(--rd-info); color: white; }
.rd-btn.purple { background: var(--rd-purple); color: white; }
.rd-btn.neutral { background: var(--rd-neutral); color: white; }

/* Outline Buttons */
.rd-btn.outline-primary { background: transparent; border: 1px solid var(--rd-primary); color: var(--rd-primary); }
.rd-btn.outline-success { background: transparent; border: 1px solid var(--rd-success); color: var(--rd-success); }
.rd-btn.outline-danger { background: transparent; border: 1px solid var(--rd-danger); color: var(--rd-danger); }
.rd-btn.outline-neutral { background: transparent; border: 1px solid var(--rd-border-dark); color: var(--rd-text-muted); }

.rd-btn.outline-primary:hover { background: var(--rd-primary-light); }
.rd-btn.outline-success:hover { background: var(--rd-success-light); }
.rd-btn.outline-danger:hover { background: var(--rd-danger-light); }

/* Icon Only Button */
.rd-btn.icon-only {
    width: 36px;
    height: 36px;
    padding: 0;
}

.rd-btn.icon-only.sm { width: 30px; height: 30px; }
.rd-btn.icon-only.xs { width: 26px; height: 26px; }

/* Button Group */
.rd-btn-group {
    display: flex;
    gap: 8px;
}

.rd-btn-group.compact {
    gap: 0;
}

.rd-btn-group.compact .rd-btn {
    border-radius: 0;
}

.rd-btn-group.compact .rd-btn:first-child {
    border-radius: var(--rd-radius-sm) 0 0 var(--rd-radius-sm);
}

.rd-btn-group.compact .rd-btn:last-child {
    border-radius: 0 var(--rd-radius-sm) var(--rd-radius-sm) 0;
}

/* ============================================
   TABLES
   ============================================ */
.rd-table-wrapper {
    overflow-x: auto;
    border: 1px solid var(--rd-border);
    border-radius: var(--rd-radius-md);
}

.rd-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.rd-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: var(--rd-text-dark);
    background: var(--rd-bg-light);
    border-bottom: 2px solid var(--rd-border);
    white-space: nowrap;
}

.rd-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--rd-border);
    color: var(--rd-text-dark);
}

.rd-table tbody tr:hover {
    background: var(--rd-bg-light);
}

.rd-table tbody tr:last-child td {
    border-bottom: none;
}

.rd-table .text-right { text-align: right; }
.rd-table .text-center { text-align: center; }
.rd-table .text-nowrap { white-space: nowrap; }

/* Table Footer */
.rd-table tfoot td {
    padding: 14px 16px;
    font-weight: 600;
    background: var(--rd-bg-light);
    border-top: 2px solid var(--rd-border);
}

/* ============================================
   BADGES & TAGS
   ============================================ */
.rd-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.rd-badge.primary { background: var(--rd-primary-light); color: var(--rd-primary); }
.rd-badge.success { background: var(--rd-success-light); color: var(--rd-success); }
.rd-badge.warning { background: var(--rd-warning-light); color: #D68910; }
.rd-badge.danger { background: var(--rd-danger-light); color: var(--rd-danger); }
.rd-badge.info { background: var(--rd-info-light); color: #31A2B8; }
.rd-badge.purple { background: var(--rd-purple-light); color: var(--rd-purple); }
.rd-badge.neutral { background: var(--rd-bg-light); color: var(--rd-text-muted); }

.rd-badge.solid-primary { background: var(--rd-primary); color: white; }
.rd-badge.solid-success { background: var(--rd-success); color: white; }
.rd-badge.solid-warning { background: var(--rd-warning); color: white; }
.rd-badge.solid-danger { background: var(--rd-danger); color: white; }

.rd-tag {
    display: inline-block;
    padding: 3px 8px;
    background: var(--rd-bg-light);
    border: 1px solid var(--rd-border);
    border-radius: var(--rd-radius-sm);
    font-size: 11px;
    color: var(--rd-text-muted);
}

/* ============================================
   ALERTS & NOTIFICATIONS
   ============================================ */
.rd-alert {
    padding: 14px 18px;
    border-radius: var(--rd-radius-md);
    font-size: 13px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
}

.rd-alert i {
    font-size: 16px;
    margin-top: 2px;
}

.rd-alert.info { background: var(--rd-info-light); color: #31A2B8; border: 1px solid rgba(91, 192, 222, 0.3); }
.rd-alert.success { background: var(--rd-success-light); color: #27AE60; border: 1px solid rgba(92, 184, 92, 0.3); }
.rd-alert.warning { background: var(--rd-warning-light); color: #D68910; border: 1px solid rgba(240, 173, 78, 0.3); }
.rd-alert.danger { background: var(--rd-danger-light); color: #C0392B; border: 1px solid rgba(217, 83, 79, 0.3); }

/* ============================================
   INFO GRID
   ============================================ */
.rd-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}

.rd-info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.rd-info-item .label {
    font-size: 11px;
    font-weight: 600;
    color: var(--rd-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: inherit; /* Override Bootstrap .label centering */
}

.rd-info-item .value {
    font-size: 14px;
    font-weight: 500;
    color: var(--rd-text-dark);
}

.rd-info-item .value.lg {
    font-size: 20px;
    font-weight: 700;
}

.rd-info-item .value.primary { color: var(--rd-primary); }
.rd-info-item .value.success { color: var(--rd-success); }
.rd-info-item .value.warning { color: var(--rd-warning); }
.rd-info-item .value.danger { color: var(--rd-danger); }

/* ============================================
   STATS BOX
   ============================================ */
.rd-stats-row {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
}

.rd-stat-box {
    flex: 1;
    padding: 16px 20px;
    background: var(--rd-bg-white);
    border: 1px solid var(--rd-border);
    border-radius: var(--rd-radius-md);
    text-align: center;
}

.rd-stat-box .icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin: 0 auto 12px;
}

.rd-stat-box .icon.primary { background: var(--rd-primary-light); color: var(--rd-primary); }
.rd-stat-box .icon.success { background: var(--rd-success-light); color: var(--rd-success); }
.rd-stat-box .icon.warning { background: var(--rd-warning-light); color: var(--rd-warning); }
.rd-stat-box .icon.danger { background: var(--rd-danger-light); color: var(--rd-danger); }

.rd-stat-box .value {
    font-size: 24px;
    font-weight: 700;
    color: var(--rd-text-dark);
    margin-bottom: 4px;
}

.rd-stat-box .label {
    font-size: 12px;
    color: var(--rd-text-muted);
}

/* ============================================
   EMPTY STATE
   ============================================ */
.rd-empty-state {
    text-align: center;
    padding: 40px 20px;
}

.rd-empty-state i {
    font-size: 48px;
    color: var(--rd-text-light);
    margin-bottom: 16px;
}

.rd-empty-state h6 {
    font-size: 16px;
    color: var(--rd-text-dark);
    margin: 0 0 8px 0;
}

.rd-empty-state p {
    font-size: 13px;
    color: var(--rd-text-muted);
    margin: 0;
}

/* ============================================
   DIVIDERS
   ============================================ */
.rd-divider {
    height: 1px;
    background: var(--rd-border);
    margin: 20px 0;
}

.rd-divider.dashed {
    background: none;
    border-top: 1px dashed var(--rd-border);
}

/* ============================================
   LOADING
   ============================================ */
.rd-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 20px;
    color: var(--rd-text-muted);
}

.rd-loading i {
    animation: rd-spin 1s linear infinite;
}

@keyframes rd-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ============================================
   MODAL REDESIGN
   ============================================ */
.rd-modal .modal-content {
    border: none;
    border-radius: var(--rd-radius-lg);
    box-shadow: var(--rd-shadow-md);
}

.rd-modal .modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--rd-border);
    background: var(--rd-bg-light);
    border-radius: var(--rd-radius-lg) var(--rd-radius-lg) 0 0;
}

.rd-modal .modal-header.primary { background: var(--rd-primary); color: white; }
.rd-modal .modal-header.success { background: var(--rd-success); color: white; }
.rd-modal .modal-header.warning { background: var(--rd-warning); color: white; }
.rd-modal .modal-header.danger { background: var(--rd-danger); color: white; }

.rd-modal .modal-title {
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rd-modal .modal-body {
    padding: 24px;
}

.rd-modal .modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--rd-border);
    background: var(--rd-bg-light);
    border-radius: 0 0 var(--rd-radius-lg) var(--rd-radius-lg);
}

/* ============================================
   ACCORDION
   ============================================ */
.rd-accordion {
    border: 1px solid var(--rd-border);
    border-radius: var(--rd-radius-md);
    margin-bottom: 12px;
    overflow: hidden;
}

.rd-accordion-header {
    padding: 14px 18px;
    background: var(--rd-bg-white);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
}

.rd-accordion-header:hover {
    background: var(--rd-bg-light);
}

.rd-accordion-header.expanded {
    background: var(--rd-bg-light);
    border-bottom: 1px solid var(--rd-border);
}

.rd-accordion-header h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rd-accordion-chevron {
    transition: transform 0.2s;
}

.rd-accordion-header.expanded .rd-accordion-chevron {
    transform: rotate(180deg);
}

.rd-accordion-body {
    display: none;
    padding: 18px;
    background: var(--rd-bg-light);
}

.rd-accordion-body.show {
    display: block;
}

/* ============================================
   UTILITY CLASSES
   ============================================ */
.rd-flex { display: flex; }
.rd-flex-center { display: flex; align-items: center; justify-content: center; }
.rd-flex-between { display: flex; align-items: center; justify-content: space-between; }
.rd-flex-end { display: flex; align-items: center; justify-content: flex-end; }
.rd-gap-8 { gap: 8px; }
.rd-gap-12 { gap: 12px; }
.rd-gap-16 { gap: 16px; }

.rd-text-right { text-align: right; }
.rd-text-center { text-align: center; }
.rd-text-muted { color: var(--rd-text-muted); }
.rd-text-primary { color: var(--rd-primary); }
.rd-text-success { color: var(--rd-success); }
.rd-text-warning { color: var(--rd-warning); }
.rd-text-danger { color: var(--rd-danger); }

.rd-mt-8 { margin-top: 8px; }
.rd-mt-16 { margin-top: 16px; }
.rd-mt-20 { margin-top: 20px; }
.rd-mb-8 { margin-bottom: 8px; }
.rd-mb-16 { margin-bottom: 16px; }
.rd-mb-20 { margin-bottom: 20px; }

.rd-p-16 { padding: 16px; }
.rd-p-20 { padding: 20px; }

.rd-hidden { display: none; }
.rd-visible { display: block; }

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
    .rd-tabs-nav {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .rd-tab-btn {
        padding: 10px 14px;
        font-size: 12px;
    }

    .rd-form-row {
        flex-direction: column;
    }

    .rd-stats-row {
        flex-wrap: wrap;
    }

    .rd-stat-box {
        min-width: calc(50% - 8px);
    }

    .rd-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* ============================================
   ANIMATIONS
   ============================================ */
@keyframes rd-fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.rd-animate-fadeIn {
    animation: rd-fadeIn 0.3s ease;
}

@keyframes rd-slideDown {
    from { opacity: 0; max-height: 0; }
    to { opacity: 1; max-height: 500px; }
}

.rd-animate-slideDown {
    animation: rd-slideDown 0.3s ease;
}

/* Highlight effect for updated values */
@keyframes rd-highlight {
    0%, 100% { background-color: transparent; }
    50% { background-color: var(--rd-warning-light); }
}

.rd-highlight {
    animation: rd-highlight 1s ease;
}

/* ============================================
   ADDITIONAL STYLES
   ============================================ */

/* Additional Text Colors */
.rd-text { color: var(--rd-text-dark); }

/* Shadow */
.rd-shadow { box-shadow: var(--rd-shadow-md); }
.rd-shadow-sm { box-shadow: var(--rd-shadow-sm); }

/* Additional Stats Box Icon Colors */
.rd-stat-box .icon.info { background: var(--rd-info-light); color: var(--rd-info); }
.rd-stat-box .icon.purple { background: var(--rd-purple-light); color: var(--rd-purple); }

/* Gold Badge for Members */
.rd-badge.gold {
    background: linear-gradient(135deg, #F1C40F 0%, #D4A70F 100%);
    color: #6D4C00;
}

.rd-badge.silver {
    background: linear-gradient(135deg, #BDC3C7 0%, #95A5A6 100%);
    color: #2C3E50;
}

.rd-badge.bronze {
    background: linear-gradient(135deg, #CD7F32 0%, #A0522D 100%);
    color: white;
}

/* Input Addon */
.rd-input-addon {
    padding: 8px 12px;
    background: var(--rd-bg-light);
    border: 1px solid var(--rd-border);
    font-size: 13px;
    color: var(--rd-text-muted);
    display: flex;
    align-items: center;
}

.rd-input-group .rd-input-addon:first-child {
    border-right: none;
    border-radius: var(--rd-radius-sm) 0 0 var(--rd-radius-sm);
}

.rd-input-group .rd-input-addon:last-child {
    border-left: none;
    border-radius: 0 var(--rd-radius-sm) var(--rd-radius-sm) 0;
}

.rd-input-group .rd-input {
    border-radius: 0;
}

.rd-input-group .rd-input:first-child {
    border-radius: var(--rd-radius-sm) 0 0 var(--rd-radius-sm);
}

.rd-input-group .rd-input:last-child {
    border-radius: 0 var(--rd-radius-sm) var(--rd-radius-sm) 0;
}

/* Collapsible Card Header */
.rd-card-header.collapsible {
    cursor: pointer;
    user-select: none;
}

.rd-card-header.collapsible:hover {
    background: rgba(0,0,0,0.02);
}

.rd-card-header .rd-collapse-icon {
    transition: transform 0.2s ease;
}

.rd-card-header.collapsed .rd-collapse-icon {
    transform: rotate(-90deg);
}

/* Small Badge */
.rd-badge.sm {
    font-size: 10px;
    padding: 2px 6px;
}

/* Text Right for Inputs */
.rd-input.text-right,
.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}
</style>
