<?php
require_once 'config.php';
require_once 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get KYC status and history
$kycRequests = [];
$hasPendingKyc = false;
$latestKyc = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM kyc_verification_requests
                          WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $kycRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($kycRequests)) {
        $latestKyc = $kycRequests[0];
        $hasPendingKyc = ($latestKyc['status'] === 'pending');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching KYC requests: " . $e->getMessage();
}
?>


<style>
/* ── KYC Page Styles ── */
:root {
    --kyc-brand:       #2950a8;
    --kyc-brand-light: #2da9e3;
    --kyc-brand-grad:  linear-gradient(135deg, #2950a8 0%, #2da9e3 100%);
    --kyc-success:     #16a34a;
    --kyc-warning:     #d97706;
    --kyc-danger:      #dc2626;
    --kyc-radius:      14px;
    --kyc-shadow:      0 4px 24px rgba(41, 80, 168, 0.10);
    --kyc-shadow-lg:   0 8px 40px rgba(41, 80, 168, 0.18);
}

/* ── Header banner ── */
.kyc-hero {
    background: var(--kyc-brand-grad);
    color: #fff;
    border-radius: var(--kyc-radius);
    padding: 2.5rem 2rem 2rem;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: var(--kyc-shadow-lg);
    position: relative;
    overflow: hidden;
}
.kyc-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.kyc-hero h1 { font-size: clamp(1.6rem, 4vw, 2.2rem); font-weight: 800; margin: 0 0 0.4rem; position: relative; }
.kyc-hero p  { opacity: .88; font-size: 1rem; margin: 0; position: relative; }
.kyc-hero-steps {
    display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;
    margin-top: 1.6rem; position: relative;
}
.kyc-step {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 30px;
    padding: 0.35rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    display: flex; align-items: center; gap: .4rem;
}

/* ── Status cards ── */
.kyc-status-card {
    border-radius: var(--kyc-radius);
    padding: 2.5rem 2rem;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: var(--kyc-shadow);
}
.kyc-status-card.pending  { background: linear-gradient(135deg,#fffbeb,#fef3c7); border: 2px solid #fbbf24; }
.kyc-status-card.approved { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border: 2px solid #4ade80; }
.kyc-status-card.rejected { background: linear-gradient(135deg,#fff1f2,#ffe4e6); border: 2px solid #f87171; }

.kyc-status-card h3 { font-size: 1.4rem; font-weight: 700; margin-bottom: .6rem; }
.kyc-status-card .subtitle { font-size: .95rem; opacity: .8; margin-bottom: 1.5rem; }

.kyc-progress-steps {
    display: flex; justify-content: center; gap: 0; flex-wrap: nowrap;
    margin: 1.5rem auto; max-width: 460px;
}
.kyc-progress-step {
    flex: 1;
    text-align: center;
    position: relative;
}
.kyc-progress-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 22px; left: 50%; right: -50%;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}
.kyc-progress-step.done:not(:last-child)::after  { background: var(--kyc-success); }
.kyc-progress-step.active:not(:last-child)::after { background: var(--kyc-warning); }

.kyc-ps-bubble {
    width: 44px; height: 44px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; font-weight: 700;
    margin: 0 auto .5rem;
    position: relative; z-index: 1;
    border: 3px solid #e5e7eb;
    background: #fff;
    color: #9ca3af;
}
.kyc-progress-step.done .kyc-ps-bubble   { background: var(--kyc-success); border-color: var(--kyc-success); color: #fff; }
.kyc-progress-step.active .kyc-ps-bubble { background: var(--kyc-warning); border-color: var(--kyc-warning); color: #fff; }
.kyc-progress-step small { font-size: .72rem; color: #6b7280; }

/* ── Upload card ── */
.kyc-upload-card {
    border-radius: var(--kyc-radius);
    border: 2px dashed #d1d5db;
    padding: 1.6rem 1.2rem 1.2rem;
    text-align: center;
    background: #fafbfc;
    transition: border-color .25s, background .25s, box-shadow .25s;
    cursor: pointer;
    position: relative;
    min-height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.kyc-upload-card:hover,
.kyc-upload-card.dragover {
    border-color: var(--kyc-brand);
    background: #eff6ff;
    box-shadow: 0 0 0 4px rgba(41,80,168,.07);
}
.kyc-upload-card.has-file {
    border-style: solid;
    border-color: var(--kyc-brand);
    background: #f0f7ff;
    padding: 0;
    overflow: hidden;
    justify-content: flex-start;
}
.kyc-upload-card input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
    z-index: 2;
}
.kyc-upload-card.has-file input[type="file"] { z-index: 0; }
.kyc-upload-icon {
    width: 52px; height: 52px;
    border-radius: 12px;
    background: rgba(41,80,168,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: var(--kyc-brand);
    margin: 0 auto .75rem;
}
.kyc-upload-card h6 { font-weight: 700; font-size: .9rem; margin-bottom: .2rem; color: #1e293b; }
.kyc-upload-card small { color: #64748b; font-size: .77rem; }

/* File preview inside upload card */
.kyc-file-preview {
    display: none;
    position: relative;
    width: 100%;
}
.kyc-file-thumb-wrap {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: calc(var(--kyc-radius) - 2px);
}
.kyc-file-thumb {
    width: 100%;
    height: 160px;
    object-fit: cover;
    display: block;
    border-radius: calc(var(--kyc-radius) - 2px);
    transition: transform .3s;
}
.kyc-file-thumb-wrap:hover .kyc-file-thumb { transform: scale(1.04); }
.kyc-thumb-zoom-hint {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.35);
    display: flex; align-items: center; justify-content: center;
    opacity: 0;
    transition: opacity .2s;
    border-radius: calc(var(--kyc-radius) - 2px);
    pointer-events: none;
    flex-direction: column;
    gap: .3rem;
    color: #fff;
    font-size: .82rem;
    font-weight: 600;
}
.kyc-thumb-zoom-hint i { font-size: 1.4rem; }
.kyc-file-thumb-wrap:hover .kyc-thumb-zoom-hint { opacity: 1; }

.kyc-file-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: .5rem .75rem;
    background: rgba(41,80,168,.06);
    gap: .5rem;
    border-top: 1px solid rgba(41,80,168,.12);
}
.kyc-file-footer-name {
    font-size: .76rem; font-weight: 600; color: #1e293b;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    flex: 1;
    text-align: left;
}
.kyc-file-footer-size {
    font-size: .72rem; color: #64748b; white-space: nowrap;
}
.kyc-file-check {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: var(--kyc-success);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .68rem;
    flex-shrink: 0;
}

.kyc-file-info {
    display: flex; align-items: center; gap: .6rem;
    padding: .75rem;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: .82rem;
    text-align: left;
    width: 100%;
}
.kyc-file-pdf-icon { font-size: 2rem; color: #dc2626; flex-shrink: 0; }
.kyc-remove-btn {
    position: absolute;
    top: 6px; right: 6px;
    width: 28px; height: 28px;
    border-radius: 50%;
    background: rgba(220,38,38,.85);
    backdrop-filter: blur(4px);
    color: #fff;
    border: none;
    font-size: .75rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: background .2s, transform .15s;
    box-shadow: 0 2px 6px rgba(0,0,0,.25);
}
.kyc-remove-btn:hover { background: #b91c1c; transform: scale(1.12); }

/* ── Select document type ── */
.kyc-doc-type-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: .75rem;
    margin-bottom: 1.5rem;
}
.kyc-doc-type-btn {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    background: #fff;
    transition: border-color .2s, background .2s, box-shadow .2s, transform .15s;
    position: relative;
}
.kyc-doc-type-btn:hover { border-color: var(--kyc-brand); background: #eff6ff; transform: translateY(-2px); }
.kyc-doc-type-btn.selected {
    border-color: var(--kyc-brand);
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(41,80,168,.12);
    transform: translateY(-2px);
}
.kyc-doc-type-btn .check-mark {
    display: none;
    position: absolute; top: 7px; right: 7px;
    width: 22px; height: 22px;
    background: var(--kyc-brand);
    border-radius: 50%;
    color: #fff;
    font-size: .65rem;
    align-items: center; justify-content: center;
    box-shadow: 0 2px 6px rgba(41,80,168,.3);
}
.kyc-doc-type-btn.selected .check-mark { display: flex; }
.kyc-doc-type-emoji { font-size: 1.8rem; display: block; margin-bottom: .3rem; }
.kyc-doc-type-label { font-size: .82rem; font-weight: 700; color: #1e293b; margin-bottom: .15rem; }
.kyc-doc-type-desc  { font-size: .71rem; color: #64748b; line-height: 1.3; }

/* ── Pending pulse animation ── */
@keyframes kyc-pulse-ring {
    0%   { transform: scale(1);   opacity: .7; }
    70%  { transform: scale(1.35); opacity: 0; }
    100% { transform: scale(1.35); opacity: 0; }
}
.kyc-status-icon-wrap {
    position: relative;
    width: 80px; height: 80px;
    margin: 0 auto 1.2rem;
}
.kyc-status-icon-wrap .kyc-pulse-ring {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 3px solid #d97706;
    animation: kyc-pulse-ring 2s ease-out infinite;
    pointer-events: none;
}
.kyc-status-icon {
    width: 100%; height: 100%;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem;
    position: relative; z-index: 1;
}
.pending  .kyc-status-icon { background: rgba(251,191,36,.2);  color: #d97706; }
.approved .kyc-status-icon { background: rgba(74,222,128,.2);  color: #16a34a; }
.rejected .kyc-status-icon { background: rgba(248,113,113,.2); color: #dc2626; }

/* ── Review modal ── */
.kyc-review-doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
}
.kyc-review-doc-item {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    text-align: center;
}
.kyc-review-doc-item .rdi-label {
    background: var(--kyc-brand-grad);
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    padding: .35rem .6rem;
}
.kyc-review-doc-item .rdi-body {
    padding: .75rem .5rem;
}
.kyc-review-img {
    width: 100%; max-height: 120px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    transition: opacity .2s;
}
.kyc-review-img:hover { opacity: .85; }
.kyc-review-pdf-box {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    height: 100px;
    color: #dc2626;
    font-size: .8rem;
}

/* ── Requirement list ── */
.kyc-req-list { list-style: none; padding: 0; margin: 0; }
.kyc-req-list li {
    display: flex; align-items: flex-start; gap: .5rem;
    padding: .5rem 0; border-bottom: 1px solid #f1f5f9;
    font-size: .88rem; color: #374151;
}
.kyc-req-list li:last-child { border-bottom: none; }
.kyc-req-list li .req-icon {
    flex-shrink: 0;
    width: 22px; height: 22px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .7rem;
    background: rgba(41,80,168,.1); color: var(--kyc-brand);
    margin-top: 1px;
}

/* ── Submission Preview Panel ── */
.kyc-preview-doc-row {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .45rem .6rem;
    border-radius: 8px;
    background: #f8fafc;
    font-size: .82rem;
}
.kyc-preview-dot {
    color: #cbd5e1;
    flex-shrink: 0;
    width: 14px;
    display: flex; align-items: center; justify-content: center;
}
.kyc-preview-doc-row.uploaded .kyc-preview-dot { color: var(--kyc-success); }
.kyc-preview-doc-label {
    flex: 1;
    font-weight: 600;
    color: #1e293b;
}
.kyc-preview-doc-status {
    font-size: .72rem;
    font-weight: 700;
    padding: .15rem .5rem;
    border-radius: 20px;
}
.kyc-preview-doc-status.pending  { background: #fef9c3; color: #a16207; }
.kyc-preview-doc-status.uploaded { background: #dcfce7; color: #15803d; }
.kyc-preview-doc-status.optional { background: #f1f5f9; color: #64748b; }
.kyc-preview-mini-thumb {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 5px;
    border: 1.5px solid #e2e8f0;
    flex-shrink: 0;
}

/* ── Cards ── */
.kyc-card {
    border-radius: var(--kyc-radius);
    box-shadow: var(--kyc-shadow);
    border: 1px solid #f1f5f9;
    background: #fff;
    margin-bottom: 1.5rem;
}
.kyc-card-header {
    padding: 1.1rem 1.4rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .6rem;
}
.kyc-card-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
}
.kyc-card-body { padding: 1.4rem; }

/* ── Progress bar ── */
.kyc-progress-wrap { display: none; margin-bottom: 1rem; }
.kyc-progress-bar-track {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}
.kyc-progress-bar-fill {
    height: 100%;
    background: var(--kyc-brand-grad);
    border-radius: 4px;
    transition: width .3s ease;
    width: 0;
}

/* ── Status message ── */
.kyc-msg {
    display: none;
    padding: .85rem 1.1rem;
    border-radius: 8px;
    font-size: .88rem;
    font-weight: 600;
    margin-bottom: 1rem;
    align-items: center;
    gap: .5rem;
}
.kyc-msg.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.kyc-msg.error   { background: #fff1f2; color: #b91c1c; border: 1px solid #fecaca; }

/* ── Table ── */
.kyc-history-table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 600; }
.kyc-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .75rem;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
}
.kyc-badge.success { background: #dcfce7; color: #15803d; }
.kyc-badge.warning { background: #fef3c7; color: #b45309; }
.kyc-badge.danger  { background: #fee2e2; color: #b91c1c; }

/* ── Submit button ── */
.kyc-submit-btn {
    width: 100%;
    padding: .9rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 1rem;
    background: var(--kyc-brand-grad);
    color: #fff;
    cursor: pointer;
    transition: opacity .2s, transform .2s;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.kyc-submit-btn:hover:not(:disabled) { opacity: .88; transform: translateY(-1px); }
.kyc-submit-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

.kyc-review-btn {
    width: 100%;
    padding: .9rem 1.5rem;
    border: 2px solid var(--kyc-brand);
    border-radius: 10px;
    font-weight: 700;
    font-size: 1rem;
    background: #fff;
    color: var(--kyc-brand);
    cursor: pointer;
    transition: background .2s, color .2s;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.kyc-review-btn:hover { background: var(--kyc-brand); color: #fff; }

@media (max-width: 576px) {
    .kyc-doc-type-grid { grid-template-columns: repeat(2, 1fr); }
    .kyc-hero h1 { font-size: 1.5rem; }
    .kyc-progress-steps { flex-direction: column; align-items: center; gap: .4rem; }
    .kyc-progress-step:not(:last-child)::after { display: none; }
}
</style>

<div class="main-content">

    <!-- ── Hero Header ── -->
    <div class="kyc-hero">
        <h1>&#x1F6E1; Identity Verification (KYC)</h1>
        <p>Verify your identity to unlock full platform features. Your data is encrypted and secure.</p>
        <div class="kyc-hero-steps">
            <div class="kyc-step"><span>1</span> Select Document</div>
            <div class="kyc-step"><span>2</span> Upload Files</div>
            <div class="kyc-step"><span>3</span> Review &amp; Submit</div>
        </div>
    </div>

    <!-- ── Flash messages ── -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- ── Status display ── -->
    <?php if ($hasPendingKyc): ?>
        <div class="kyc-status-card pending">
            <div class="kyc-status-icon-wrap">
                <div class="kyc-pulse-ring"></div>
                <div class="kyc-status-icon"><i class="fas fa-clock"></i></div>
            </div>
            <h3 style="color:#d97706;">&#x23F3; Under Review</h3>
            <p class="subtitle">Your KYC documents have been submitted and are being carefully reviewed by our compliance team.</p>
            <div class="kyc-progress-steps">
                <div class="kyc-progress-step done">
                    <div class="kyc-ps-bubble"><i class="fas fa-check"></i></div>
                    <small>Submitted</small>
                </div>
                <div class="kyc-progress-step active">
                    <div class="kyc-ps-bubble"><i class="fas fa-eye"></i></div>
                    <small>Under Review</small>
                </div>
                <div class="kyc-progress-step">
                    <div class="kyc-ps-bubble"><i class="fas fa-shield-alt"></i></div>
                    <small>Approved</small>
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-center gap-3 mt-3">
                <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3" style="background:rgba(217,119,6,.1);font-size:.88rem;">
                    <i class="fas fa-calendar-alt" style="color:#d97706;"></i>
                    <span><strong>Submitted:</strong>&nbsp;<?= htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($latestKyc['created_at']))) ?></span>
                </div>
                <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3" style="background:rgba(217,119,6,.08);font-size:.88rem;">
                    <i class="fas fa-hourglass-half" style="color:#d97706;"></i>
                    <span>Est. <strong>1–3 business days</strong></span>
                </div>
            </div>
            <div class="mt-3 text-muted" style="font-size:.82rem;">
                <i class="fas fa-lock me-1" style="color:#16a34a;"></i> Your data is encrypted and handled securely.
            </div>
        </div>

    <?php elseif (!empty($kycRequests) && $latestKyc['status'] === 'approved'): ?>
        <div class="kyc-status-card approved">
            <div class="kyc-status-icon-wrap">
                <div class="kyc-status-icon"><i class="fas fa-check-circle"></i></div>
            </div>
            <h3 style="color:#16a34a;">&#x2705; Verification Complete</h3>
            <p class="subtitle">Congratulations! Your identity has been successfully verified. You now have full access to all platform features.</p>
            <div class="kyc-progress-steps">
                <div class="kyc-progress-step done">
                    <div class="kyc-ps-bubble"><i class="fas fa-check"></i></div>
                    <small>Submitted</small>
                </div>
                <div class="kyc-progress-step done">
                    <div class="kyc-ps-bubble"><i class="fas fa-check"></i></div>
                    <small>Reviewed</small>
                </div>
                <div class="kyc-progress-step done">
                    <div class="kyc-ps-bubble"><i class="fas fa-check"></i></div>
                    <small>Approved</small>
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-center gap-3 mt-3">
                <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3" style="background:rgba(22,163,74,.12);font-size:.88rem;">
                    <i class="fas fa-calendar-check" style="color:#16a34a;"></i>
                    <span><strong>Approved:</strong>&nbsp;<?= htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($latestKyc['verified_at']))) ?></span>
                </div>
                <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3" style="background:rgba(22,163,74,.08);font-size:.88rem;">
                    <i class="fas fa-shield-alt" style="color:#16a34a;"></i>
                    <span>KYC <strong>Verified &amp; Active</strong></span>
                </div>
            </div>
        </div>

    <?php elseif (!empty($kycRequests) && $latestKyc['status'] === 'rejected'): ?>
        <div class="kyc-status-card rejected">
            <div class="kyc-status-icon-wrap">
                <div class="kyc-status-icon"><i class="fas fa-times-circle"></i></div>
            </div>
            <h3 style="color:#dc2626;">&#x274C; Verification Not Approved</h3>
            <p class="subtitle">Your submission could not be approved. Please review the reason below, correct the issue, and resubmit your documents.</p>
            <?php if (!empty($latestKyc['rejection_reason'])): ?>
            <div class="d-flex align-items-start gap-3 px-4 py-3 rounded-3 mb-3 mx-auto text-start" style="background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.2);max-width:520px;">
                <i class="fas fa-exclamation-triangle mt-1" style="color:#dc2626;flex-shrink:0;font-size:1.1rem;"></i>
                <div>
                    <div style="font-size:.78rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem;">Rejection Reason</div>
                    <div style="font-size:.9rem;color:#374151;"><?= htmlspecialchars($latestKyc['rejection_reason']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3" style="background:rgba(220,38,38,.08);font-size:.85rem;">
                <i class="fas fa-redo" style="color:#dc2626;"></i>
                <span>Correct the issue and <strong>resubmit below</strong></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── Submission form (shown when no pending/approved KYC) ── -->
    <?php if (!$hasPendingKyc && (empty($kycRequests) || $latestKyc['status'] === 'rejected')): ?>
    <div class="row g-4">

        <!-- Left: upload form -->
        <div class="col-lg-7">
            <div class="kyc-card">
                <div class="kyc-card-header">
                    <i class="fas fa-upload" style="color:var(--kyc-brand);"></i>
                    <h5>Submit KYC Documents</h5>
                </div>
                <div class="kyc-card-body">

                    <!-- Status/progress -->
                    <div class="kyc-progress-wrap" id="kycProgressWrap">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.8rem;">
                            <span id="kycProgressLabel">Uploading…</span>
                            <span id="kycProgressPct">0%</span>
                        </div>
                        <div class="kyc-progress-bar-track">
                            <div class="kyc-progress-bar-fill" id="kycProgressBar"></div>
                        </div>
                    </div>
                    <div class="kyc-msg" id="kycMsg"></div>

                    <form method="POST" enctype="multipart/form-data" id="kycForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <!-- Step 1: Document type -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2" style="font-size:.9rem;">
                                <i class="fas fa-id-card me-1" style="color:var(--kyc-brand);"></i>
                                Step 1: Select Document Type <span class="text-danger">*</span>
                            </label>
                            <div class="kyc-doc-type-grid">
                                <label class="kyc-doc-type-btn" id="dtBtn-passport">
                                    <input type="radio" name="document_type" value="passport" style="display:none;" required>
                                    <div class="check-mark"><i class="fas fa-check"></i></div>
                                    <span class="kyc-doc-type-emoji">&#x1F6C2;</span>
                                    <div class="kyc-doc-type-label">Passport</div>
                                    <div class="kyc-doc-type-desc">International travel document (front page only)</div>
                                </label>
                                <label class="kyc-doc-type-btn" id="dtBtn-id_card">
                                    <input type="radio" name="document_type" value="id_card" style="display:none;">
                                    <div class="check-mark"><i class="fas fa-check"></i></div>
                                    <span class="kyc-doc-type-emoji">&#x1F194;</span>
                                    <div class="kyc-doc-type-label">National ID Card</div>
                                    <div class="kyc-doc-type-desc">Government-issued national identity card (front &amp; back)</div>
                                </label>
                                <label class="kyc-doc-type-btn" id="dtBtn-driving_license">
                                    <input type="radio" name="document_type" value="driving_license" style="display:none;">
                                    <div class="check-mark"><i class="fas fa-check"></i></div>
                                    <span class="kyc-doc-type-emoji">&#x1F697;</span>
                                    <div class="kyc-doc-type-label">Driver's License</div>
                                    <div class="kyc-doc-type-desc">Valid driver's license with photo (front &amp; back)</div>
                                </label>
                                <label class="kyc-doc-type-btn" id="dtBtn-other">
                                    <input type="radio" name="document_type" value="other" style="display:none;">
                                    <div class="check-mark"><i class="fas fa-check"></i></div>
                                    <span class="kyc-doc-type-emoji">&#x1F4C4;</span>
                                    <div class="kyc-doc-type-label">Other Gov. ID</div>
                                    <div class="kyc-doc-type-desc">Any other government-issued photo ID</div>
                                </label>
                            </div>
                        </div>

                        <!-- Step 2: Upload files -->
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3" style="font-size:.9rem;">
                                <i class="fas fa-images me-1" style="color:var(--kyc-brand);"></i>
                                Step 2: Upload Documents
                            </label>

                            <div class="row g-3">
                                <!-- Document Front -->
                                <div class="col-sm-6">
                                    <div class="kyc-upload-card" id="dropFront">
                                        <input type="file" name="document_front" id="documentFront" accept="image/*,.pdf" required>
                                        <div class="kyc-upload-icon"><i class="fas fa-id-badge"></i></div>
                                        <h6>Document Front <span class="text-danger">*</span></h6>
                                        <small>JPG, PNG or PDF &bull; max 10 MB</small>
                                        <div class="kyc-file-preview" id="frontPreview">
                                            <div class="kyc-file-thumb-wrap" id="frontThumbWrap" style="display:none;">
                                                <img class="kyc-file-thumb" id="frontThumb" alt="preview">
                                                <div class="kyc-thumb-zoom-hint"><i class="fas fa-search-plus"></i>Click to zoom</div>
                                            </div>
                                            <div class="kyc-file-info" id="frontInfo" style="display:none;">
                                                <i class="fas fa-file-pdf kyc-file-pdf-icon" id="frontPdfIcon"></i>
                                                <div id="frontFileName" style="word-break:break-all;flex:1;"></div>
                                            </div>
                                            <div class="kyc-file-footer" id="frontFooter" style="display:none;">
                                                <span class="kyc-file-check"><i class="fas fa-check"></i></span>
                                                <span class="kyc-file-footer-name" id="frontFooterName"></span>
                                                <span class="kyc-file-footer-size" id="frontFooterSize"></span>
                                            </div>
                                            <button type="button" class="kyc-remove-btn" aria-label="Remove file" onclick="clearUpload('documentFront','frontPreview','frontThumb','frontThumbWrap','frontInfo','dropFront','frontFileName','frontPdfIcon','frontFooter','frontFooterName','frontFooterSize')">&#x2715;</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Back (hidden for passport) -->
                                <div class="col-sm-6" id="backDocumentGroup">
                                    <div class="kyc-upload-card" id="dropBack">
                                        <input type="file" name="document_back" id="documentBack" accept="image/*,.pdf">
                                        <div class="kyc-upload-icon"><i class="fas fa-id-card-alt"></i></div>
                                        <h6>Document Back</h6>
                                        <small>JPG, PNG or PDF &bull; max 10 MB</small>
                                        <div class="kyc-file-preview" id="backPreview">
                                            <div class="kyc-file-thumb-wrap" id="backThumbWrap" style="display:none;">
                                                <img class="kyc-file-thumb" id="backThumb" alt="preview">
                                                <div class="kyc-thumb-zoom-hint"><i class="fas fa-search-plus"></i>Click to zoom</div>
                                            </div>
                                            <div class="kyc-file-info" id="backInfo" style="display:none;">
                                                <i class="fas fa-file-pdf kyc-file-pdf-icon" id="backPdfIcon"></i>
                                                <div id="backFileName" style="word-break:break-all;flex:1;"></div>
                                            </div>
                                            <div class="kyc-file-footer" id="backFooter" style="display:none;">
                                                <span class="kyc-file-check"><i class="fas fa-check"></i></span>
                                                <span class="kyc-file-footer-name" id="backFooterName"></span>
                                                <span class="kyc-file-footer-size" id="backFooterSize"></span>
                                            </div>
                                            <button type="button" class="kyc-remove-btn" aria-label="Remove file" onclick="clearUpload('documentBack','backPreview','backThumb','backThumbWrap','backInfo','dropBack','backFileName','backPdfIcon','backFooter','backFooterName','backFooterSize')">&#x2715;</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selfie with document -->
                                <div class="col-sm-6">
                                    <div class="kyc-upload-card" id="dropSelfie">
                                        <input type="file" name="selfie_with_id" id="selfieWithId" accept="image/*" required>
                                        <div class="kyc-upload-icon"><i class="fas fa-camera"></i></div>
                                        <h6>Selfie with Document <span class="text-danger">*</span></h6>
                                        <small>JPG or PNG &bull; max 10 MB</small>
                                        <div class="kyc-file-preview" id="selfiePreview">
                                            <div class="kyc-file-thumb-wrap" id="selfieThumbWrap" style="display:none;">
                                                <img class="kyc-file-thumb" id="selfieThumb" alt="preview">
                                                <div class="kyc-thumb-zoom-hint"><i class="fas fa-search-plus"></i>Click to zoom</div>
                                            </div>
                                            <div class="kyc-file-info" id="selfieInfo" style="display:none;">
                                                <i class="fas fa-file-pdf kyc-file-pdf-icon" id="selfiePdfIcon"></i>
                                                <div id="selfieFileName" style="word-break:break-all;flex:1;"></div>
                                            </div>
                                            <div class="kyc-file-footer" id="selfieFooter" style="display:none;">
                                                <span class="kyc-file-check"><i class="fas fa-check"></i></span>
                                                <span class="kyc-file-footer-name" id="selfieFooterName"></span>
                                                <span class="kyc-file-footer-size" id="selfieFooterSize"></span>
                                            </div>
                                            <button type="button" class="kyc-remove-btn" aria-label="Remove file" onclick="clearUpload('selfieWithId','selfiePreview','selfieThumb','selfieThumbWrap','selfieInfo','dropSelfie','selfieFileName','selfiePdfIcon','selfieFooter','selfieFooterName','selfieFooterSize')">&#x2715;</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Proof of Address -->
                                <div class="col-sm-6">
                                    <div class="kyc-upload-card" id="dropAddress">
                                        <input type="file" name="address_proof" id="addressProof" accept="image/*,.pdf" required>
                                        <div class="kyc-upload-icon"><i class="fas fa-home"></i></div>
                                        <h6>Proof of Address <span class="text-danger">*</span></h6>
                                        <small>JPG, PNG or PDF &bull; max 10 MB</small>
                                        <div class="kyc-file-preview" id="addressPreview">
                                            <div class="kyc-file-thumb-wrap" id="addressThumbWrap" style="display:none;">
                                                <img class="kyc-file-thumb" id="addressThumb" alt="preview">
                                                <div class="kyc-thumb-zoom-hint"><i class="fas fa-search-plus"></i>Click to zoom</div>
                                            </div>
                                            <div class="kyc-file-info" id="addressInfo" style="display:none;">
                                                <i class="fas fa-file-pdf kyc-file-pdf-icon" id="addressPdfIcon"></i>
                                                <div id="addressFileName" style="word-break:break-all;flex:1;"></div>
                                            </div>
                                            <div class="kyc-file-footer" id="addressFooter" style="display:none;">
                                                <span class="kyc-file-check"><i class="fas fa-check"></i></span>
                                                <span class="kyc-file-footer-name" id="addressFooterName"></span>
                                                <span class="kyc-file-footer-size" id="addressFooterSize"></span>
                                            </div>
                                            <button type="button" class="kyc-remove-btn" aria-label="Remove file" onclick="clearUpload('addressProof','addressPreview','addressThumb','addressThumbWrap','addressInfo','dropAddress','addressFileName','addressPdfIcon','addressFooter','addressFooterName','addressFooterSize')">&#x2715;</button>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- /row g-3 -->
                        </div><!-- /mb-4 Step 2 -->

                        <!-- Step 3: Review & Submit -->
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-2" style="font-size:.9rem;">
                                <i class="fas fa-search-plus me-1" style="color:var(--kyc-brand);"></i>
                                Step 3: Review &amp; Submit
                            </label>
                            <div class="d-flex flex-column gap-2">
                                <button type="button" class="kyc-review-btn" id="kycReviewBtn">
                                    <i class="fas fa-eye"></i> Preview My Documents Before Submitting
                                </button>
                                <button type="submit" name="submit_kyc" class="kyc-submit-btn" id="submitKycBtn">
                                    <i class="fas fa-shield-alt"></i> Submit for Verification
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Right: requirements info -->
        <div class="col-lg-5">
            <div class="kyc-card mb-3">
                <div class="kyc-card-header">
                    <i class="fas fa-list-check" style="color:var(--kyc-brand);"></i>
                    <h5>Required Documents</h5>
                </div>
                <div class="kyc-card-body">
                    <ul class="kyc-req-list">
                        <li>
                            <div class="req-icon"><i class="fas fa-id-card"></i></div>
                            <div>
                                <strong>Government-issued ID</strong><br>
                                <small class="text-muted">Passport, National ID, or Driver's License</small>
                            </div>
                        </li>
                        <li>
                            <div class="req-icon"><i class="fas fa-camera"></i></div>
                            <div>
                                <strong>Selfie with your ID</strong><br>
                                <small class="text-muted">Hold document clearly visible next to your face</small>
                            </div>
                        </li>
                        <li>
                            <div class="req-icon"><i class="fas fa-home"></i></div>
                            <div>
                                <strong>Proof of address</strong><br>
                                <small class="text-muted">Utility bill or bank statement, max 3 months old</small>
                            </div>
                        </li>
                        <li>
                            <div class="req-icon"><i class="fas fa-image"></i></div>
                            <div>
                                <strong>High-quality photos</strong><br>
                                <small class="text-muted">All text and details must be clearly readable</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="kyc-card">
                <div class="kyc-card-header">
                    <i class="fas fa-info-circle" style="color:#d97706;"></i>
                    <h5>Important Notes</h5>
                </div>
                <div class="kyc-card-body">
                    <ul class="kyc-req-list">
                        <li>
                            <div class="req-icon" style="background:rgba(217,119,6,.1);color:#d97706;"><i class="fas fa-clock"></i></div>
                            <div>Processing time: <strong>1–3 business days</strong></div>
                        </li>
                        <li>
                            <div class="req-icon" style="background:rgba(217,119,6,.1);color:#d97706;"><i class="fas fa-file"></i></div>
                            <div>Max file size: <strong>10 MB per document</strong></div>
                        </li>
                        <li>
                            <div class="req-icon" style="background:rgba(22,163,74,.1);color:#16a34a;"><i class="fas fa-lock"></i></div>
                            <div>All documents are <strong>encrypted &amp; secure</strong></div>
                        </li>
                        <li>
                            <div class="req-icon" style="background:rgba(22,163,74,.1);color:#16a34a;"><i class="fas fa-shield-alt"></i></div>
                            <div>Stored in compliance with <strong>GDPR &amp; data privacy</strong> laws</div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ── Live Submission Preview (hidden until first upload) ── -->
            <div class="kyc-card mt-3" id="kycSubmissionPreview" style="display:none;">
                <div class="kyc-card-header" style="background:var(--kyc-brand-grad);color:#fff;border-bottom:none;">
                    <i class="fas fa-eye"></i>
                    <h5 style="color:#fff;">Submission Preview</h5>
                </div>
                <div class="kyc-card-body" style="padding:1.5rem 1.25rem;">
                    <!-- Mini status preview mimicking "Under Review" look -->
                    <div class="kyc-preview-status-wrap" id="kycPreviewStatusWrap">
                        <!-- defaults to "ready" state; switches to "complete" when all docs uploaded -->
                        <div class="d-flex align-items-center gap-3 mb-3" id="kycPreviewStatusBadge">
                            <div style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(41,80,168,.1);flex-shrink:0;">
                                <i class="fas fa-upload" style="color:var(--kyc-brand);font-size:1.15rem;" id="kycPreviewStatusIcon"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:.95rem;color:#1e293b;" id="kycPreviewStatusTitle">Uploading Documents…</div>
                                <div style="font-size:.78rem;color:#64748b;" id="kycPreviewStatusSub">Add all required files to continue</div>
                            </div>
                        </div>

                        <!-- Document checklist rows -->
                        <div style="display:flex;flex-direction:column;gap:.55rem;margin-bottom:1rem;">
                            <div class="kyc-preview-doc-row" id="previewRow_front">
                                <span class="kyc-preview-dot" id="previewDot_front"><i class="fas fa-circle" style="font-size:.45rem;"></i></span>
                                <span class="kyc-preview-doc-label">ID Front</span>
                                <span class="kyc-preview-doc-status pending" id="previewStatus_front">Pending</span>
                                <img src="" class="kyc-preview-mini-thumb" id="previewMiniThumb_front" style="display:none;" alt="">
                            </div>
                            <div class="kyc-preview-doc-row" id="previewRow_back">
                                <span class="kyc-preview-dot" id="previewDot_back"><i class="fas fa-circle" style="font-size:.45rem;"></i></span>
                                <span class="kyc-preview-doc-label">ID Back</span>
                                <span class="kyc-preview-doc-status optional" id="previewStatus_back">Optional</span>
                                <img src="" class="kyc-preview-mini-thumb" id="previewMiniThumb_back" style="display:none;" alt="">
                            </div>
                            <div class="kyc-preview-doc-row" id="previewRow_selfie">
                                <span class="kyc-preview-dot" id="previewDot_selfie"><i class="fas fa-circle" style="font-size:.45rem;"></i></span>
                                <span class="kyc-preview-doc-label">Selfie with ID</span>
                                <span class="kyc-preview-doc-status pending" id="previewStatus_selfie">Pending</span>
                                <img src="" class="kyc-preview-mini-thumb" id="previewMiniThumb_selfie" style="display:none;" alt="">
                            </div>
                            <div class="kyc-preview-doc-row" id="previewRow_address">
                                <span class="kyc-preview-dot" id="previewDot_address"><i class="fas fa-circle" style="font-size:.45rem;"></i></span>
                                <span class="kyc-preview-doc-label">Address Proof</span>
                                <span class="kyc-preview-doc-status pending" id="previewStatus_address">Pending</span>
                                <img src="" class="kyc-preview-mini-thumb" id="previewMiniThumb_address" style="display:none;" alt="">
                            </div>
                        </div>

                        <!-- Mini progress bar -->
                        <div style="margin-bottom:.75rem;">
                            <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#64748b;margin-bottom:.3rem;">
                                <span>Upload progress</span>
                                <span id="kycPreviewPct">0%</span>
                            </div>
                            <div style="height:6px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
                                <div id="kycPreviewProgressBar" style="height:100%;width:0%;background:var(--kyc-brand-grad);border-radius:10px;transition:width .4s ease;"></div>
                            </div>
                        </div>

                        <!-- Status message that mirrors the "Under Review" section -->
                        <div id="kycPreviewReadyMsg" style="display:none;background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fbbf24;border-radius:10px;padding:.85rem 1rem;text-align:center;">
                            <div style="font-size:1.5rem;margin-bottom:.3rem;">⏳</div>
                            <div style="font-weight:700;color:#d97706;font-size:.95rem;">Ready for Review</div>
                            <div style="font-size:.78rem;color:#92400e;margin-top:.2rem;">Once submitted, your documents will be reviewed within 1–3 business days.</div>
                        </div>
                        <div id="kycPreviewAllDoneMsg" style="display:none;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #4ade80;border-radius:10px;padding:.85rem 1rem;text-align:center;">
                            <div style="font-size:1.5rem;margin-bottom:.3rem;">✅</div>
                            <div style="font-weight:700;color:#16a34a;font-size:.95rem;">All Documents Ready!</div>
                            <div style="font-size:.78rem;color:#14532d;margin-top:.2rem;">All required files uploaded. Click <em>Review &amp; Submit</em> to proceed.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /row -->
    <?php endif; ?>

    <!-- ── KYC History ── -->
    <?php if (!empty($kycRequests)): ?>
    <div class="kyc-card mt-4">
        <div class="kyc-card-header">
            <i class="fas fa-history" style="color:var(--kyc-brand);"></i>
            <h5>Submission History</h5>
        </div>
        <div class="kyc-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 kyc-history-table" id="kycHistoryTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Document Type</th>
                            <th>Status</th>
                            <th class="pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kycRequests as $request): ?>
                        <tr>
                            <td class="ps-4"><?= htmlspecialchars(date('M d, Y H:i', strtotime($request['created_at']))) ?></td>
                            <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $request['document_type']))) ?></td>
                            <td>
                                <?php
                                    $st = $request['status'];
                                    $bc = $st === 'approved' ? 'success' : ($st === 'rejected' ? 'danger' : 'warning');
                                    $icon = $st === 'approved' ? 'fa-check-circle' : ($st === 'rejected' ? 'fa-times-circle' : 'fa-clock');
                                ?>
                                <span class="kyc-badge <?= $bc ?>">
                                    <i class="fas <?= $icon ?>"></i>
                                    <?= htmlspecialchars(ucfirst($request['status'])) ?>
                                </span>
                            </td>
                            <td class="pe-4">
                                <button class="btn btn-sm btn-outline-primary view-kyc"
                                        data-id="<?= htmlspecialchars((string)$request['id']) ?>"
                                        data-document-front="<?= htmlspecialchars($request['document_front'] ?? '') ?>"
                                        data-document-back="<?= htmlspecialchars($request['document_back'] ?? '') ?>"
                                        data-selfie="<?= htmlspecialchars($request['selfie_with_id'] ?? '') ?>"
                                        data-address="<?= htmlspecialchars($request['address_proof'] ?? '') ?>"
                                        data-type="<?= htmlspecialchars($request['document_type']) ?>"
                                        data-status="<?= htmlspecialchars($request['status']) ?>"
                                        data-created="<?= htmlspecialchars($request['created_at']) ?>"
                                        data-verified="<?= htmlspecialchars($request['verified_at'] ?? '') ?>"
                                        data-reason="<?= htmlspecialchars($request['rejection_reason'] ?? '') ?>">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /main-content -->

<!-- ──────────────────────────────────────────
     PRE-SUBMIT REVIEW MODAL
────────────────────────────────────────── -->
<div class="modal fade" id="kycReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:var(--kyc-brand-grad);color:#fff;border:none;">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-search-plus me-2"></i>Review Your Documents
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info mb-4" style="border-radius:10px;">
                    <i class="fas fa-info-circle me-2"></i>
                    Please review all documents carefully before submitting. Ensure all text is clearly readable.
                </div>
                <!-- Review doc type & summary -->
                <div class="row mb-3 g-3" id="reviewSummary"></div>
                <!-- Document thumbnails -->
                <div class="kyc-review-doc-grid" id="reviewDocGrid"></div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i> Go Back &amp; Edit
                </button>
                <button type="button" class="kyc-submit-btn" id="reviewSubmitBtn" style="width:auto;padding:.65rem 1.6rem;">
                    <i class="fas fa-shield-alt me-1"></i> Confirm &amp; Submit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ──────────────────────────────────────────
     KYC DETAILS MODAL (history view)
────────────────────────────────────────── -->
<div class="modal fade" id="kycDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:var(--kyc-brand-grad);color:#fff;border:none;">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-folder-open me-2"></i>KYC Submission Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="kycDetailsContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ──────────────────────────────────────────
     IMAGE ZOOM MODAL
────────────────────────────────────────── -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;background:#000;">
            <div class="modal-header" style="background:rgba(0,0,0,.7);border:none;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-search-plus me-2"></i>Document Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img src="" class="img-fluid" id="zoomedImage" style="max-height:80vh;border-radius:8px;">
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
(function () {
    'use strict';

    /* ── Helpers ── */
    function getEl(id) { return document.getElementById(id); }
    function showEl(id) { var el = getEl(id); if (el) el.style.display = ''; }
    function hideEl(id) { var el = getEl(id); if (el) el.style.display = 'none'; }
    function formatMB(bytes) { return (bytes / 1024 / 1024).toFixed(2) + ' MB'; }

    function showMsg(msg, type) {
        var el = getEl('kycMsg');
        if (!el) return;
        el.className = 'kyc-msg ' + type;
        el.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i> ' + msg;
        el.style.display = 'flex';
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* HTML-escape helper to prevent XSS when inserting paths into innerHTML */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setProgress(pct, label) {
        var wrap = getEl('kycProgressWrap');
        var bar  = getEl('kycProgressBar');
        var pctEl = getEl('kycProgressPct');
        var lblEl = getEl('kycProgressLabel');
        if (!wrap) return;
        wrap.style.display = 'block';
        if (bar)  bar.style.width = pct + '%';
        if (pctEl) pctEl.textContent = Math.round(pct) + '%';
        if (lblEl && label) lblEl.textContent = label;
    }

    /* ── Document type selector ── */
    var docTypeRadios = document.querySelectorAll('.kyc-doc-type-btn input[type=radio]');
    docTypeRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.kyc-doc-type-btn').forEach(function(b) { b.classList.remove('selected'); });
            var parent = radio.closest('.kyc-doc-type-btn');
            if (parent) parent.classList.add('selected');

            var backGroup = getEl('backDocumentGroup');
            var backInput = getEl('documentBack');
            if (radio.value === 'passport') {
                if (backGroup) backGroup.style.display = 'none';
                if (backInput) backInput.required = false;
            } else {
                if (backGroup) backGroup.style.display = '';
                if (backInput) backInput.required = true;
            }
        });
    });

    /* ── Shared file-upload handler ── */
    function setupUpload(inputId, previewId, thumbId, thumbWrapId, infoId, dropId, fileNameId, pdfIconId, footerId, footerNameId, footerSizeId) {
        var input    = getEl(inputId);
        var dropZone = getEl(dropId);
        if (!input || !dropZone) return;

        function handleFile(file) {
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) {
                showMsg('File size must be less than 10 MB. Please choose a smaller file.', 'error');
                input.value = '';
                return;
            }
            var preview   = getEl(previewId);
            var thumb     = getEl(thumbId);
            var thumbWrap = getEl(thumbWrapId);
            var info      = getEl(infoId);
            var pdfIcon   = getEl(pdfIconId);
            var fileName  = getEl(fileNameId);
            var footer    = getEl(footerId);
            var footerName = getEl(footerNameId);
            var footerSize = getEl(footerSizeId);

            var sizeMB = formatMB(file.size);

            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (thumb) { thumb.src = e.target.result; }
                    if (thumbWrap) { thumbWrap.style.display = ''; }
                    if (info)    { info.style.display  = 'none'; }
                    /* Clicking the thumb zooms it */
                    if (thumbWrap) {
                        thumbWrap.style.cursor = 'pointer';
                        thumbWrap.onclick = function() { zoomImage(thumb.src); };
                    }
                };
                reader.readAsDataURL(file);
            } else {
                if (thumbWrap) thumbWrap.style.display = 'none';
                if (pdfIcon)  pdfIcon.style.display = '';
                if (fileName) fileName.textContent = file.name;
                if (info)     info.style.display = 'flex';
            }

            /* Footer always shows for both types */
            if (footerName) footerName.textContent = file.name;
            if (footerSize) footerSize.textContent = sizeMB;
            if (footer)     footer.style.display = '';

            if (preview) preview.style.display = '';
            dropZone.classList.add('has-file');
            /* Hide the static placeholder content */
            var icon = dropZone.querySelector('.kyc-upload-icon');
            var h6   = dropZone.querySelector('h6');
            var sm   = dropZone.querySelector('small');
            if (icon) icon.style.display = 'none';
            if (h6)   h6.style.display   = 'none';
            if (sm)   sm.style.display   = 'none';
        }

        input.addEventListener('change', function() { handleFile(input.files[0]); });

        /* Drag-and-drop */
        ['dragenter','dragover'].forEach(function(evt) {
            dropZone.addEventListener(evt, function(e) {
                e.preventDefault(); e.stopPropagation();
                dropZone.classList.add('dragover');
            });
        });
        ['dragleave','drop'].forEach(function(evt) {
            dropZone.addEventListener(evt, function(e) {
                e.preventDefault(); e.stopPropagation();
                dropZone.classList.remove('dragover');
                if (evt === 'drop' && e.dataTransfer.files.length) {
                    var dt = new DataTransfer();
                    dt.items.add(e.dataTransfer.files[0]);
                    input.files = dt.files;
                    handleFile(e.dataTransfer.files[0]);
                }
            });
        });
    }

    setupUpload('documentFront', 'frontPreview',   'frontThumb',   'frontThumbWrap',   'frontInfo',   'dropFront',   'frontFileName',   'frontPdfIcon',   'frontFooter',   'frontFooterName',   'frontFooterSize');
    setupUpload('documentBack',  'backPreview',    'backThumb',    'backThumbWrap',    'backInfo',    'dropBack',    'backFileName',    'backPdfIcon',    'backFooter',    'backFooterName',    'backFooterSize');
    setupUpload('selfieWithId',  'selfiePreview',  'selfieThumb',  'selfieThumbWrap',  'selfieInfo',  'dropSelfie',  'selfieFileName',  'selfiePdfIcon',  'selfieFooter',  'selfieFooterName',  'selfieFooterSize');
    setupUpload('addressProof',  'addressPreview', 'addressThumb', 'addressThumbWrap', 'addressInfo', 'dropAddress', 'addressFileName', 'addressPdfIcon', 'addressFooter', 'addressFooterName', 'addressFooterSize');

    /* ── Submission preview panel ── */
    (function() {
        var previewPanel   = getEl('kycSubmissionPreview');
        var previewPct     = getEl('kycPreviewPct');
        var previewBar     = getEl('kycPreviewProgressBar');
        var previewTitle   = getEl('kycPreviewStatusTitle');
        var previewSub     = getEl('kycPreviewStatusSub');
        var previewIcon    = getEl('kycPreviewStatusIcon');
        var readyMsg       = getEl('kycPreviewReadyMsg');
        var allDoneMsg     = getEl('kycPreviewAllDoneMsg');

        var docMap = [
            { inputId: 'documentFront', rowId: 'previewRow_front',   dotId: 'previewDot_front',   statusId: 'previewStatus_front',   thumbId: 'previewMiniThumb_front',   label: 'Pending',  required: true  },
            { inputId: 'documentBack',  rowId: 'previewRow_back',    dotId: 'previewDot_back',    statusId: 'previewStatus_back',    thumbId: 'previewMiniThumb_back',    label: 'Optional', required: false },
            { inputId: 'selfieWithId',  rowId: 'previewRow_selfie',  dotId: 'previewDot_selfie',  statusId: 'previewStatus_selfie',  thumbId: 'previewMiniThumb_selfie',  label: 'Pending',  required: true  },
            { inputId: 'addressProof',  rowId: 'previewRow_address', dotId: 'previewDot_address', statusId: 'previewStatus_address', thumbId: 'previewMiniThumb_address', label: 'Pending',  required: true  }
        ];

        function updatePreviewPanel() {
            var totalRequired = 0, uploadedRequired = 0, totalUploaded = 0;
            docMap.forEach(function(doc) {
                if (doc.required) totalRequired++;
                var input = getEl(doc.inputId);
                if (input && input.files && input.files[0]) {
                    totalUploaded++;
                    if (doc.required) uploadedRequired++;
                    /* update row */
                    var row = getEl(doc.rowId);
                    var statusEl = getEl(doc.statusId);
                    var thumbEl  = getEl(doc.thumbId);
                    if (row) { row.classList.add('uploaded'); }
                    if (statusEl) {
                        statusEl.textContent = '✓ Uploaded';
                        statusEl.className = 'kyc-preview-doc-status uploaded';
                    }
                    /* show mini thumbnail for images */
                    if (thumbEl && input.files[0].type.startsWith('image/')) {
                        var rd = new FileReader();
                        rd.onload = (function(el) { return function(e) { el.src = e.target.result; el.style.display = ''; }; })(thumbEl);
                        rd.readAsDataURL(input.files[0]);
                    } else if (thumbEl) {
                        thumbEl.style.display = 'none';
                    }
                } else {
                    /* reset to initial */
                    var row2 = getEl(doc.rowId);
                    var statusEl2 = getEl(doc.statusId);
                    var thumbEl2  = getEl(doc.thumbId);
                    if (row2) { row2.classList.remove('uploaded'); }
                    if (statusEl2) {
                        statusEl2.textContent = doc.required ? 'Pending' : 'Optional';
                        statusEl2.className = 'kyc-preview-doc-status ' + (doc.required ? 'pending' : 'optional');
                    }
                    if (thumbEl2) { thumbEl2.src = ''; thumbEl2.style.display = 'none'; }
                }
            });

            /* show panel on first upload */
            if (previewPanel && totalUploaded > 0) { previewPanel.style.display = ''; }

            /* progress bar */
            var pct = Math.round((uploadedRequired / totalRequired) * 100);
            if (previewBar) { previewBar.style.width = pct + '%'; }
            if (previewPct) { previewPct.textContent = pct + '%'; }

            /* status header */
            if (pct === 100) {
                if (previewTitle) { previewTitle.textContent = 'All Documents Uploaded'; }
                if (previewSub)   { previewSub.textContent   = 'Ready to review and submit'; }
                if (previewIcon)  { previewIcon.className     = 'fas fa-check-circle'; previewIcon.style.color = '#16a34a'; }
                if (readyMsg)     { readyMsg.style.display    = 'none'; }
                if (allDoneMsg)   { allDoneMsg.style.display  = ''; }
            } else if (pct > 0) {
                if (previewTitle) { previewTitle.textContent = 'Uploading Documents…'; }
                if (previewSub)   { previewSub.textContent   = uploadedRequired + ' of ' + totalRequired + ' required files uploaded'; }
                if (previewIcon)  { previewIcon.className     = 'fas fa-cloud-upload-alt'; previewIcon.style.color = 'var(--kyc-brand)'; }
                if (readyMsg)     { readyMsg.style.display    = ''; }
                if (allDoneMsg)   { allDoneMsg.style.display  = 'none'; }
            } else {
                if (previewTitle) { previewTitle.textContent = 'Uploading Documents…'; }
                if (previewSub)   { previewSub.textContent   = 'Add all required files to continue'; }
                if (previewIcon)  { previewIcon.className     = 'fas fa-upload'; previewIcon.style.color = 'var(--kyc-brand)'; }
                if (readyMsg)     { readyMsg.style.display    = 'none'; }
                if (allDoneMsg)   { allDoneMsg.style.display  = 'none'; }
            }
        }

        /* hook into all file inputs */
        docMap.forEach(function(doc) {
            var input = getEl(doc.inputId);
            if (input) { input.addEventListener('change', updatePreviewPanel); }
        });
    })();

    /* ── Pre-submit review ── */
    var reviewBtn = getEl('kycReviewBtn');
    if (reviewBtn) {
        reviewBtn.addEventListener('click', function() {
            if (!validateForm()) return;
            buildReviewModal();
            var modal = new bootstrap.Modal(getEl('kycReviewModal'));
            modal.show();
        });
    }


    var reviewSubmitBtn = getEl('reviewSubmitBtn');
    if (reviewSubmitBtn) {
        reviewSubmitBtn.addEventListener('click', function() {
            var rm = bootstrap.Modal.getInstance(getEl('kycReviewModal'));
            if (rm) rm.hide();
            submitForm();
        });
    }

    function validateForm() {
        var docType = document.querySelector('input[name="document_type"]:checked');
        if (!docType) { showMsg('Please select a document type.', 'error'); return false; }

        var frontInput = getEl('documentFront');
        if (!frontInput || !frontInput.files.length) { showMsg('Please upload the front of your document.', 'error'); return false; }

        var selfieInput = getEl('selfieWithId');
        if (!selfieInput || !selfieInput.files.length) { showMsg('Please upload your selfie with document.', 'error'); return false; }

        var addressInput = getEl('addressProof');
        if (!addressInput || !addressInput.files.length) { showMsg('Please upload your proof of address.', 'error'); return false; }

        var backInput = getEl('documentBack');
        if (docType.value !== 'passport' && (!backInput || !backInput.files.length)) {
            showMsg('Please upload the back of your document (required for non-passport IDs).', 'error');
            return false;
        }

        return true;
    }

    function buildReviewModal() {
        var docType = document.querySelector('input[name="document_type"]:checked');
        var summary = getEl('reviewSummary');
        var grid    = getEl('reviewDocGrid');

        var typeLabel = { passport: '&#x1F6C2; Passport', id_card: '&#x1F194; National ID Card', driving_license: '&#x1F697; Driver\'s License', other: '&#x1F4C4; Other Gov. ID' };

        if (summary) {
            summary.innerHTML = '<div class="col-sm-6"><div class="rounded-3 p-3 h-100" style="background:#f8fafc;border:1px solid #e5e7eb;">' +
                '<div style="font-size:.78rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.05em;margin-bottom:.3rem;">Document Type</div>' +
                '<div style="font-size:1rem;font-weight:700;color:#1e293b;">' + (typeLabel[docType ? docType.value : ''] || '–') + '</div>' +
                '</div></div>' +
                '<div class="col-sm-6"><div class="rounded-3 p-3 h-100" style="background:#f0fdf4;border:1px solid #bbf7d0;">' +
                '<div style="font-size:.78rem;font-weight:700;text-transform:uppercase;color:#16a34a;letter-spacing:.05em;margin-bottom:.3rem;">&#10003; Ready to Submit</div>' +
                '<div style="font-size:.9rem;color:#374151;">All required documents selected. Review below, then confirm.</div>' +
                '</div></div>';
        }

        if (grid) {
            grid.innerHTML = '';
            var docs = [
                { label: 'Document Front',        input: 'documentFront' },
                { label: 'Document Back',         input: 'documentBack' },
                { label: 'Selfie with Document',  input: 'selfieWithId' },
                { label: 'Proof of Address',      input: 'addressProof' }
            ];
            docs.forEach(function(doc) {
                var inp = getEl(doc.input);
                if (!inp || !inp.files.length) return;
                var file = inp.files[0];
                var item = document.createElement('div');
                item.className = 'kyc-review-doc-item';
                var labelDiv = document.createElement('div');
                labelDiv.className = 'rdi-label';
                labelDiv.textContent = doc.label;
                var body = document.createElement('div');
                body.className = 'rdi-body';
                if (file.type.startsWith('image/')) {
                    var img = document.createElement('img');
                    img.className = 'kyc-review-img';
                    img.alt = doc.label;
                    var reader = new FileReader();
                    reader.onload = (function(imgEl) { return function(e) { imgEl.src = e.target.result; }; })(img);
                    reader.readAsDataURL(file);
                    img.addEventListener('click', function() { zoomImage(img.src); });
                    body.appendChild(img);
                } else {
                    var box = document.createElement('div');
                    box.className = 'kyc-review-pdf-box';
                    box.innerHTML = '<i class="fas fa-file-pdf" style="font-size:2.5rem;margin-bottom:.4rem;"></i><div>' + file.name + '</div><small>' + formatMB(file.size) + '</small>';
                    body.appendChild(box);
                }
                var sizeLine = document.createElement('div');
                sizeLine.style.cssText = 'font-size:.72rem;color:#64748b;margin-top:.4rem;';
                sizeLine.textContent = file.name + ' – ' + formatMB(file.size);
                body.appendChild(sizeLine);
                item.appendChild(labelDiv);
                item.appendChild(body);
                grid.appendChild(item);
            });
        }
    }

    /* ── Form submission ── */
    function submitForm() {
        var form    = getEl('kycForm');
        var btn     = getEl('submitKycBtn');
        var revBtn  = getEl('reviewSubmitBtn');
        var formData = new FormData(form);

        [btn, revBtn].forEach(function(b) {
            if (b) { b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading…'; }
        });

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/kyc_submit.php', true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) setProgress((e.loaded / e.total) * 85, 'Uploading documents…');
        };

        xhr.onload = function() {
            setProgress(100, 'Processing…');
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    showMsg(data.message || 'Documents submitted successfully!', 'success');
                    setTimeout(function() { window.location.reload(); }, 2000);
                } else {
                    showMsg(data.message || 'Submission failed. Please try again.', 'error');
                    resetButtons();
                }
            } catch (e) {
                showMsg('Unexpected server response. Please try again.', 'error');
                resetButtons();
            }
        };

        xhr.onerror = function() {
            showMsg('Network error. Please check your connection and try again.', 'error');
            resetButtons();
        };

        xhr.send(formData);
    }

    function resetButtons() {
        var btn    = getEl('submitKycBtn');
        var revBtn = getEl('reviewSubmitBtn');
        if (btn)    { btn.disabled = false;    btn.innerHTML    = '<i class="fas fa-shield-alt"></i> Submit for Verification'; }
        if (revBtn) { revBtn.disabled = false; revBtn.innerHTML = '<i class="fas fa-shield-alt me-1"></i> Confirm &amp; Submit'; }
        var wrap = getEl('kycProgressWrap');
        if (wrap) wrap.style.display = 'none';
    }

    /* ── Direct submit button also validates ── */
    var form = getEl('kycForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateForm()) return;
            submitForm();
        });
    }

    /* ── History "View Details" ── */
    document.querySelectorAll('.view-kyc').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var documentFront = btn.dataset.documentFront || '';
            var documentBack  = btn.dataset.documentBack  || '';
            var selfie        = btn.dataset.selfie        || '';
            var address       = btn.dataset.address       || '';
            var type          = btn.dataset.type          || '';
            var status        = btn.dataset.status        || '';
            var created       = btn.dataset.created       || '';
            var verified      = btn.dataset.verified      || '';
            var reason        = btn.dataset.reason        || '';

            var badgeMap = { approved: 'success', rejected: 'danger', pending: 'warning' };
            var iconMap  = { approved: 'fa-check-circle', rejected: 'fa-times-circle', pending: 'fa-clock' };
            var bc = badgeMap[status] || 'secondary';
            var ic = iconMap[status]  || 'fa-circle';

            var html = '<div class="row g-3 mb-4">' +
                '<div class="col-sm-6"><div class="rounded-3 p-3" style="background:#f8fafc;border:1px solid #e5e7eb;">' +
                '<div style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:.3rem;">Document Type</div>' +
                '<div style="font-weight:600;color:#1e293b;">' + type.replace(/_/g,' ') + '</div>' +
                '</div></div>' +
                '<div class="col-sm-6"><div class="rounded-3 p-3" style="background:#f8fafc;border:1px solid #e5e7eb;">' +
                '<div style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:.3rem;">Status</div>' +
                '<span class="kyc-badge ' + bc + '"><i class="fas ' + ic + '"></i> ' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>' +
                '</div></div>' +
                '<div class="col-sm-6"><div class="rounded-3 p-3" style="background:#f8fafc;border:1px solid #e5e7eb;">' +
                '<div style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:.3rem;">Submitted</div>' +
                '<div style="font-size:.9rem;color:#374151;">' + (created ? new Date(created).toLocaleString() : '–') + '</div>' +
                '</div></div>';

            if (verified) {
                html += '<div class="col-sm-6"><div class="rounded-3 p-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">' +
                    '<div style="font-size:.75rem;font-weight:700;text-transform:uppercase;color:#16a34a;margin-bottom:.3rem;">Verified</div>' +
                    '<div style="font-size:.9rem;color:#374151;">' + new Date(verified).toLocaleString() + '</div>' +
                    '</div></div>';
            }

            html += '</div>';

            if (reason) {
                html += '<div class="alert alert-danger mb-4" style="border-radius:10px;"><i class="fas fa-exclamation-triangle me-2"></i><strong>Rejection Reason:</strong> ' +
                    reason.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>';
            }

            html += '<h6 class="fw-bold mb-3" style="color:#1e293b;">Submitted Documents</h6>';
            html += '<div class="kyc-review-doc-grid">';

            function docCard(title, path) {
                if (!path) return '';
                var ext = path.split('.').pop().toLowerCase();
                var isImg = ['jpg','jpeg','png','gif','webp'].indexOf(ext) >= 0;
                var safePath  = escHtml(path);
                var safeTitle = escHtml(title);
                var safeExt   = escHtml(ext.toUpperCase());
                return '<div class="kyc-review-doc-item">' +
                    '<div class="rdi-label">' + safeTitle + '</div>' +
                    '<div class="rdi-body">' +
                    (isImg
                        ? '<img src="' + safePath + '" class="kyc-review-img" alt="' + safeTitle + '" onclick="zoomImage(this.getAttribute(\'src\'))">'
                        : '<div class="kyc-review-pdf-box"><i class="fas fa-file-pdf" style="font-size:2.5rem;margin-bottom:.4rem;"></i><div>' + safeExt + ' Document</div></div>'
                    ) +
                    '<div style="font-size:.72rem;color:#64748b;margin-top:.5rem;">' +
                    '<a href="' + safePath + '" download class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:.2rem .6rem;">' +
                    '<i class="fas fa-download me-1"></i>Download</a></div>' +
                    '</div></div>';
            }

            html += docCard('Document Front', documentFront);
            html += docCard('Document Back',  documentBack);
            html += docCard('Selfie with Doc', selfie);
            html += docCard('Proof of Address', address);
            html += '</div>';

            getEl('kycDetailsContent').innerHTML = html;
            var modal = new bootstrap.Modal(getEl('kycDetailsModal'));
            modal.show();
        });
    });

})();

/* ── Global helpers called from inline onclick ── */
function clearUpload(inputId, previewId, thumbId, thumbWrapId, infoId, dropId, fileNameId, pdfIconId, footerId, footerNameId, footerSizeId) {
    var input    = document.getElementById(inputId);
    var preview  = document.getElementById(previewId);
    var thumb    = document.getElementById(thumbId);
    var thumbWrap = document.getElementById(thumbWrapId);
    var info     = document.getElementById(infoId);
    var dropZone = document.getElementById(dropId);
    var pdfIcon  = document.getElementById(pdfIconId);
    var footer   = document.getElementById(footerId);
    if (input)    input.value = '';
    if (thumb)    { thumb.src = ''; }
    if (thumbWrap){ thumbWrap.style.display = 'none'; thumbWrap.onclick = null; }
    if (info)     info.style.display = 'none';
    if (pdfIcon)  pdfIcon.style.display = 'none';
    if (footer)   footer.style.display = 'none';
    if (preview)  preview.style.display = 'none';
    if (dropZone) {
        dropZone.classList.remove('has-file');
        /* Restore the placeholder content */
        var icon = dropZone.querySelector('.kyc-upload-icon');
        var h6   = dropZone.querySelector('h6');
        var sm   = dropZone.querySelector('small');
        if (icon) icon.style.display = '';
        if (h6)   h6.style.display   = '';
        if (sm)   sm.style.display   = '';
    }
}

function zoomImage(src) {
    document.getElementById('zoomedImage').src = src;
    var modal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
    modal.show();
}
</script>
</body>
