<?php
// app/admin/admin_mailer.php — Campaign Mailer Admin
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title"><i class="anticon anticon-mail" style="margin-right:8px;"></i> Campaign Mailer</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Campaign Mailer</span>
            </nav>
        </div>
    </div>

    <!-- Stats overview -->
    <div class="row" id="mailerStats">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 id="statSmtp" class="text-primary mb-1">—</h4>
                    <small class="text-muted">SMTP Accounts</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 id="statLeads" class="text-success mb-1">—</h4>
                    <small class="text-muted">Active Leads</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 id="statCampaigns" class="text-info mb-1">—</h4>
                    <small class="text-muted">Campaigns</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h4 id="statSent" class="text-warning mb-1">—</h4>
                    <small class="text-muted">Total Emails Sent</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card">
        <div class="card-body p-0">
            <ul class="nav nav-tabs px-3 pt-3" id="mailerTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-campaigns-link" data-toggle="tab" href="#tab-campaigns">
                        <i class="anticon anticon-thunderbolt"></i> Campaigns
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-smtp-link" data-toggle="tab" href="#tab-smtp">
                        <i class="anticon anticon-deployment-unit"></i> SMTP Accounts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-leads-link" data-toggle="tab" href="#tab-leads">
                        <i class="anticon anticon-contacts"></i> Leads
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-templates-link" data-toggle="tab" href="#tab-templates">
                        <i class="anticon anticon-file-text"></i> Email Templates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-logs-link" data-toggle="tab" href="#tab-logs">
                        <i class="anticon anticon-ordered-list"></i> Send Logs
                    </a>
                </li>
            </ul>

            <div class="tab-content p-4">

                <!-- ── CAMPAIGNS ─────────────────────────────────────── -->
                <div class="tab-pane fade show active" id="tab-campaigns">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Campaigns</h5>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#campaignModal">
                            <i class="anticon anticon-plus"></i> New Campaign
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="campaignsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Recipients</th>
                                    <th>Sent</th>
                                    <th>Failed</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- ── SMTP ACCOUNTS ────────────────────────────────── -->
                <div class="tab-pane fade" id="tab-smtp">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">SMTP Accounts</h5>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#smtpModal">
                            <i class="anticon anticon-plus"></i> Add Account
                        </button>
                    </div>
                    <div class="alert alert-info">
                        <i class="anticon anticon-info-circle"></i>
                        Rotation: every <strong>N emails per campaign</strong>, the mailer switches to the next active account and pauses.
                        Ensure each account has valid SPF/DKIM/DMARC records for best deliverability.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="smtpTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Label</th>
                                    <th>Host : Port</th>
                                    <th>From</th>
                                    <th>Sent</th>
                                    <th>Last Used</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- ── LEADS ─────────────────────────────────────────── -->
                <div class="tab-pane fade" id="tab-leads">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Leads / Recipients</h5>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm mr-2" data-toggle="modal" data-target="#importLeadsModal">
                                <i class="anticon anticon-upload"></i> Import CSV
                            </button>
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#leadModal">
                                <i class="anticon anticon-plus"></i> Add Lead
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="leadsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Email</th>
                                    <th>Name</th>
                                    <th>Source</th>
                                    <th>Tags</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- ── TEMPLATES ────────────────────────────────────── -->
                <div class="tab-pane fade" id="tab-templates">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Email Templates</h5>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#templateModal">
                            <i class="anticon anticon-plus"></i> New Template
                        </button>
                    </div>
                    <div id="templateCards" class="row"></div>
                </div>

                <!-- ── LOGS ──────────────────────────────────────────── -->
                <div class="tab-pane fade" id="tab-logs">
                    <h5 class="mb-3">Send Logs</h5>
                    <div class="form-row mb-3">
                        <div class="col-md-4">
                            <select class="form-control form-control-sm" id="logCampaignFilter">
                                <option value="">All campaigns</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="logStatusFilter">
                                <option value="">All statuses</option>
                                <option value="sent">Sent</option>
                                <option value="failed">Failed</option>
                                <option value="skipped">Skipped</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-secondary btn-sm" id="filterLogsBtn">Apply</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="logsTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Campaign</th>
                                    <th>Email</th>
                                    <th>SMTP</th>
                                    <th>Status</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /tab-content -->
        </div>
    </div>
</div><!-- /main-content -->

<!-- ═══════════════════════════════════════════════
     MODALS
════════════════════════════════════════════════ -->

<!-- Campaign Modal -->
<div class="modal fade" id="campaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="campaignModalTitle">New Campaign</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="campaignForm">
                <div class="modal-body">
                    <input type="hidden" id="cmpId" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="e.g. March Crypto Recovery Outreach">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Emails per Account <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="emails_per_account" value="3" min="1" max="50" required>
                            <small class="text-muted">Switch SMTP account after this many sends</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Email Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required
                                   placeholder="e.g. Ihre Anfrage zur Blockchain-Analyse">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Pause Between Accounts (sec)</label>
                            <input type="number" class="form-control" name="pause_seconds" value="60" min="0" max="3600">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Email Template <span class="text-danger">*</span></label>
                            <select class="form-control" name="template_id" required id="cmpTemplateSelect">
                                <option value="">— Select template —</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Reply-To Address</label>
                            <input type="email" class="form-control" name="reply_to" placeholder="contact@yourdomain.com">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>CTA URL (call-to-action link in email)</label>
                        <input type="url" class="form-control" name="cta_url" placeholder="https://yourdomain.com/kontakt.php">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveCampaignBtn">Save Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SMTP Modal -->
<div class="modal fade" id="smtpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smtpModalTitle">Add SMTP Account</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="smtpForm">
                <div class="modal-body">
                    <input type="hidden" id="smtpId" name="id">
                    <div class="form-group">
                        <label>Label / Nickname</label>
                        <input type="text" class="form-control" name="label" placeholder="e.g. Mailgun Account 1">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>SMTP Host <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="host" required placeholder="smtp.mailgun.org">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Port</label>
                            <input type="number" class="form-control" name="port" value="587">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" required placeholder="noreply@yourdomain.com">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required placeholder="••••••••" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label>From Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="from_email" required placeholder="noreply@yourdomain.com">
                        </div>
                        <div class="form-group col-md-5">
                            <label>Encryption</label>
                            <select class="form-control" name="encryption">
                                <option value="tls" selected>TLS (STARTTLS) — port 587</option>
                                <option value="ssl">SSL (smtps) — port 465</option>
                                <option value="none">None (plain)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label>From Name</label>
                            <input type="text" class="form-control" name="from_name" placeholder="Novalnet AI">
                        </div>
                        <div class="form-group col-md-5">
                            <label>Active</label>
                            <select class="form-control" name="is_active">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lead Modal -->
<div class="modal fade" id="leadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadModalTitle">Add Lead</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="leadForm">
                <div class="modal-body">
                    <input type="hidden" id="leadId" name="id">
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Max Mustermann">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Source</label>
                            <input type="text" class="form-control" name="source" placeholder="website / linkedin / manual" value="manual">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Tags (comma-separated)</label>
                            <input type="text" class="form-control" name="tags" placeholder="crypto, defi, victim">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Status</label>
                            <select class="form-control" name="status">
                                <option value="active">Active</option>
                                <option value="unsubscribed">Unsubscribed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Lead</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Leads Modal -->
<div class="modal fade" id="importLeadsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Leads from CSV</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="importLeadsForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        CSV format: <code>email,name</code> (header row is auto-detected and skipped).
                        Duplicate emails are ignored.
                    </div>
                    <div class="form-group">
                        <label>CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control-file" name="csv_file" accept=".csv,.txt" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Default Source</label>
                            <input type="text" class="form-control" name="source" value="csv-import">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Default Tags</label>
                            <input type="text" class="form-control" name="tags" placeholder="crypto, lead">
                        </div>
                    </div>
                    <div id="importResult"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tplModalTitle">New Email Template</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="templateForm">
                <div class="modal-body">
                    <input type="hidden" id="tplId" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Template Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="e.g. Crypto Recovery DE">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Subject Line <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" required
                                   placeholder="e.g. Ihre Anfrage zur Blockchain-Analyse">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>HTML Body (partial — no DOCTYPE/html/body tags required)
                            <span class="text-muted ml-2" style="font-size:12px;">
                                Placeholders: <code>{first_name}</code> <code>{name}</code> <code>{email}</code> <code>{cta_url}</code>
                            </span>
                        </label>
                        <div id="htmlEditor" style="height:380px;border:1px solid #ced4da;border-radius:4px;"></div>
                        <textarea id="htmlBody" name="html_body" style="display:none"></textarea>
                        <small class="text-muted">
                            Avoid spam trigger words: free, click here, winner, guarantee, act now, congratulations, 100%, urgent, cash, prize.
                        </small>
                    </div>
                    <div class="form-group">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" name="is_default" id="tplDefault" value="1" class="mr-2">
                            <label for="tplDefault" class="mb-0">Set as default template</label>
                        </div>
                    </div>
                    <!-- Preview -->
                    <div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="previewTplBtn">
                            <i class="anticon anticon-eye"></i> Preview
                        </button>
                        <div id="tplPreviewBox" class="mt-2" style="display:none;border:1px solid #e9ecef;border-radius:6px;padding:16px;max-height:400px;overflow:auto;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Campaign Logs Modal -->
<div class="modal fade" id="campaignLogsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cmpLogsTitle">Campaign Logs</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm" id="cmpLogsTable">
                        <thead>
                            <tr><th>Time</th><th>Email</th><th>SMTP</th><th>Status</th><th>Error</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     JavaScript
════════════════════════════════════════════════ -->
<!-- Ace code editor (SRI-verified) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.6/ace.js"
        integrity="sha512-AiKprgIzXMjNL4gLpJPaRsKVqfKb+FpPdgSBJHRuPl0n2XBi9hTqBqf4I9Tvy9bG+TiGSZm3D8VNLxTDmm6Q=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
// ── Ace editor setup ──────────────────────────────────────────────────────────
const editor = ace.edit('htmlEditor');
editor.setTheme('ace/theme/chrome');
editor.session.setMode('ace/mode/html');
editor.setOptions({ wrap: true, showPrintMargin: false, fontSize: '13px' });

function syncEditor() {
    document.getElementById('htmlBody').value = editor.getValue();
}
editor.session.on('change', syncEditor);

// ── Helpers ───────────────────────────────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

function apiFetch(url, data, method = 'POST') {
    if (method === 'POST') {
        data._csrf = CSRF;
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        }).then(r => r.json());
    }
    return fetch(url + '?' + new URLSearchParams(data)).then(r => r.json());
}

function statusBadge(s) {
    const map = {
        draft: 'secondary', running: 'warning', paused: 'info',
        completed: 'success', failed: 'danger',
        active: 'success', unsubscribed: 'secondary', bounced: 'danger', invalid: 'dark'
    };
    return `<span class="badge badge-${map[s] || 'secondary'}">${s}</span>`;
}

function toast(msg, type = 'success') {
    if (window.toastr) toastr[type](msg);
    else alert(msg);
}

// ── Load stats ─────────────────────────────────────────────────────────────────
function loadStats() {
    fetch('admin_ajax/mailer_stats.php').then(r => r.json()).then(d => {
        if (!d.ok) return;
        document.getElementById('statSmtp').textContent      = d.smtp;
        document.getElementById('statLeads').textContent     = d.leads;
        document.getElementById('statCampaigns').textContent = d.campaigns;
        document.getElementById('statSent').textContent      = d.sent.toLocaleString();
    });
}
loadStats();

// ═══════════════════════════════════════════════
// CAMPAIGNS
// ═══════════════════════════════════════════════
let campaignsTable;

function loadCampaigns() {
    fetch('admin_ajax/mailer_campaigns.php?action=list').then(r => r.json()).then(d => {
        if (!d.ok) return;

        const tbody = document.querySelector('#campaignsTable tbody');
        tbody.innerHTML = '';
        (d.campaigns || []).forEach(c => {
            const running = c.status === 'running';
            tbody.innerHTML += `
              <tr>
                <td>${c.id}</td>
                <td>${escHtml(c.name)}</td>
                <td>${escHtml(c.subject)}</td>
                <td>${statusBadge(c.status)}</td>
                <td>${c.total_recipients}</td>
                <td class="text-success">${c.sent_count}</td>
                <td class="text-danger">${c.failed_count}</td>
                <td>${c.created_at}</td>
                <td>
                  ${running
                    ? `<button class="btn btn-xs btn-warning mr-1 cmp-pause" data-id="${c.id}">Pause</button>`
                    : `<button class="btn btn-xs btn-success mr-1 cmp-start" data-id="${c.id}" ${c.status === 'completed' ? 'disabled' : ''}><i class="anticon anticon-caret-right"></i> Start</button>`
                  }
                  <button class="btn btn-xs btn-outline-secondary mr-1 cmp-logs" data-id="${c.id}" data-name="${escAttr(c.name)}">Logs</button>
                  <button class="btn btn-xs btn-info mr-1 cmp-edit" data-id="${c.id}">Edit</button>
                  <button class="btn btn-xs btn-danger cmp-delete" data-id="${c.id}">Del</button>
                </td>
              </tr>`;
        });

        // Populate log filter dropdown
        const sel = document.getElementById('logCampaignFilter');
        const existing = Array.from(sel.options).map(o => o.value);
        (d.campaigns || []).forEach(c => {
            if (!existing.includes(String(c.id))) {
                const o = document.createElement('option');
                o.value = c.id; o.textContent = c.name;
                sel.appendChild(o);
            }
        });
    });
}

document.querySelector('#tab-campaigns-link').addEventListener('click', loadCampaigns);
loadCampaigns();

// Auto-refresh running campaigns every 8 s
setInterval(() => {
    if (document.getElementById('tab-campaigns').classList.contains('active')) {
        loadCampaigns();
        loadStats();
    }
}, 8000);

// Start / Pause / Delete / Logs
document.getElementById('campaignsTable').addEventListener('click', e => {
    const btn = e.target.closest('button');
    if (!btn) return;
    const id = btn.dataset.id;

    if (btn.classList.contains('cmp-start')) {
        if (!confirm(`Start campaign #${id}? This will send emails to all active leads.`)) return;
        btn.disabled = true; btn.textContent = 'Starting…';
        apiFetch('admin_ajax/mailer_campaigns.php', { action: 'start', id })
            .then(d => { toast(d.message, d.ok ? 'success' : 'error'); loadCampaigns(); });
    }
    if (btn.classList.contains('cmp-pause')) {
        apiFetch('admin_ajax/mailer_campaigns.php', { action: 'pause', id })
            .then(d => { toast(d.message, d.ok ? 'success' : 'error'); loadCampaigns(); });
    }
    if (btn.classList.contains('cmp-delete')) {
        if (!confirm('Delete this campaign and all its logs?')) return;
        apiFetch('admin_ajax/mailer_campaigns.php', { action: 'delete', id })
            .then(d => { toast(d.message, d.ok ? 'success' : 'error'); loadCampaigns(); loadStats(); });
    }
    if (btn.classList.contains('cmp-edit')) {
        fetch(`admin_ajax/mailer_campaigns.php?action=get&id=${id}`).then(r => r.json()).then(d => {
            if (!d.ok) return toast(d.message, 'error');
            const c = d.campaign;
            const f = document.getElementById('campaignForm');
            f.reset();
            f.id.value = c.id; // hidden field
            document.getElementById('cmpId').value = c.id;
            f.name.value              = c.name;
            f.subject.value           = c.subject;
            f.emails_per_account.value = c.emails_per_account;
            f.pause_seconds.value     = c.pause_seconds;
            f.reply_to.value          = c.reply_to;
            f.cta_url.value           = c.cta_url;
            f.template_id.value       = c.template_id;
            document.getElementById('campaignModalTitle').textContent = 'Edit Campaign';
            $('#campaignModal').modal('show');
        });
    }
    if (btn.classList.contains('cmp-logs')) {
        document.getElementById('cmpLogsTitle').textContent = 'Logs — ' + btn.dataset.name;
        fetch(`admin_ajax/mailer_logs.php?campaign_id=${id}&limit=200`).then(r => r.json()).then(d => {
            const tbody = document.querySelector('#cmpLogsTable tbody');
            tbody.innerHTML = '';
            (d.logs || []).forEach(l => {
                const sm = l.smtp_from ? `<small>${escHtml(l.smtp_from)}</small>` : '—';
                tbody.innerHTML += `<tr>
                  <td><small>${l.sent_at}</small></td>
                  <td>${escHtml(l.to_email)}</td>
                  <td>${sm}</td>
                  <td>${statusBadge(l.status)}</td>
                  <td><small class="text-danger">${escHtml(l.error_msg)}</small></td>
                </tr>`;
            });
            $('#campaignLogsModal').modal('show');
        });
    }
});

// Campaign Form submit
document.getElementById('campaignForm').addEventListener('submit', e => {
    e.preventDefault();
    const fd = Object.fromEntries(new FormData(e.target));
    apiFetch('admin_ajax/mailer_campaigns.php', { action: fd.id ? 'update' : 'create', ...fd })
        .then(d => {
            toast(d.message, d.ok ? 'success' : 'error');
            if (d.ok) { $('#campaignModal').modal('hide'); loadCampaigns(); loadStats(); }
        });
});

// Populate template select when modal opens
$('#campaignModal').on('show.bs.modal', function() { loadTemplateSelect(); });

function loadTemplateSelect() {
    fetch('admin_ajax/mailer_templates.php?action=list_short').then(r => r.json()).then(d => {
        const sel = document.getElementById('cmpTemplateSelect');
        const cur = sel.value;
        sel.innerHTML = '<option value="">— Select template —</option>';
        if (!d.ok) {
            sel.innerHTML += '<option value="" disabled>⚠ Templates unavailable (run mailer_schema.sql)</option>';
            return;
        }
        (d.templates || []).forEach(t => {
            sel.innerHTML += `<option value="${t.id}" ${t.id == cur ? 'selected' : ''}>${escHtml(t.name)}</option>`;
        });
    }).catch(() => {
        document.getElementById('cmpTemplateSelect').innerHTML = '<option value="" disabled>⚠ Could not load templates</option>';
    });
}

// ═══════════════════════════════════════════════
// SMTP ACCOUNTS
// ═══════════════════════════════════════════════
function loadSmtp() {
    fetch('admin_ajax/mailer_smtp.php?action=list').then(r => r.json()).then(d => {
        const tbody = document.querySelector('#smtpTable tbody');
        tbody.innerHTML = '';
        (d.accounts || []).forEach(a => {
            tbody.innerHTML += `<tr>
              <td>${a.id}</td>
              <td>${escHtml(a.label || '—')}</td>
              <td><code>${escHtml(a.host)}:${a.port}</code></td>
              <td><small>${escHtml(a.from_name)} &lt;${escHtml(a.from_email)}&gt;</small></td>
              <td>${a.emails_sent}</td>
              <td>${a.last_used_at || '—'}</td>
              <td>${a.is_active ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>'}</td>
              <td>
                <button class="btn btn-xs btn-info mr-1 smtp-edit" data-id="${a.id}">Edit</button>
                <button class="btn btn-xs btn-danger smtp-del" data-id="${a.id}">Del</button>
              </td>
            </tr>`;
        });
    });
}

document.querySelector('#tab-smtp-link').addEventListener('click', loadSmtp);

document.getElementById('smtpTable').addEventListener('click', e => {
    const btn = e.target.closest('button');
    if (!btn) return;
    const id = btn.dataset.id;
    if (btn.classList.contains('smtp-del')) {
        if (!confirm('Delete this SMTP account?')) return;
        apiFetch('admin_ajax/mailer_smtp.php', { action: 'delete', id })
            .then(d => { toast(d.message, d.ok ? 'success' : 'error'); loadSmtp(); loadStats(); });
    }
    if (btn.classList.contains('smtp-edit')) {
        fetch(`admin_ajax/mailer_smtp.php?action=get&id=${id}`).then(r => r.json()).then(d => {
            if (!d.ok) return toast(d.message, 'error');
            const a = d.account;
            const f = document.getElementById('smtpForm');
            f.reset();
            document.getElementById('smtpId').value = a.id;
            f.label.value      = a.label;
            f.host.value       = a.host;
            f.port.value       = a.port;
            f.encryption.value = a.encryption;
            f.username.value   = a.username;
            f.from_email.value = a.from_email;
            f.from_name.value  = a.from_name;
            f.is_active.value  = a.is_active;
            // leave password blank when editing
            document.getElementById('smtpModalTitle').textContent = 'Edit SMTP Account';
            $('#smtpModal').modal('show');
        });
    }
});

document.getElementById('smtpForm').addEventListener('submit', e => {
    e.preventDefault();
    const fd = Object.fromEntries(new FormData(e.target));
    apiFetch('admin_ajax/mailer_smtp.php', { action: fd.id ? 'update' : 'create', ...fd })
        .then(d => {
            toast(d.message, d.ok ? 'success' : 'error');
            if (d.ok) { $('#smtpModal').modal('hide'); loadSmtp(); loadStats(); }
        });
});

$('#smtpModal').on('hidden.bs.modal', function() {
    document.getElementById('smtpModalTitle').textContent = 'Add SMTP Account';
    document.getElementById('smtpForm').reset();
    document.getElementById('smtpId').value = '';
});

// ═══════════════════════════════════════════════
// LEADS
// ═══════════════════════════════════════════════
function loadLeads() {
    fetch('admin_ajax/mailer_leads.php?action=list').then(r => r.json()).then(d => {
        const tbody = document.querySelector('#leadsTable tbody');
        tbody.innerHTML = '';
        (d.leads || []).forEach(l => {
            tbody.innerHTML += `<tr>
              <td>${l.id}</td>
              <td>${escHtml(l.email)}</td>
              <td>${escHtml(l.name)}</td>
              <td><small>${escHtml(l.source)}</small></td>
              <td><small>${escHtml(l.tags)}</small></td>
              <td>${statusBadge(l.status)}</td>
              <td><small>${l.added_at}</small></td>
              <td>
                <button class="btn btn-xs btn-info mr-1 lead-edit" data-id="${l.id}">Edit</button>
                <button class="btn btn-xs btn-danger lead-del" data-id="${l.id}">Del</button>
              </td>
            </tr>`;
        });
    });
}

document.querySelector('#tab-leads-link').addEventListener('click', loadLeads);

document.getElementById('leadsTable').addEventListener('click', e => {
    const btn = e.target.closest('button');
    if (!btn) return;
    const id = btn.dataset.id;
    if (btn.classList.contains('lead-del')) {
        if (!confirm('Delete this lead?')) return;
        apiFetch('admin_ajax/mailer_leads.php', { action: 'delete', id })
            .then(d => { toast(d.message, d.ok ? 'success' : 'error'); loadLeads(); loadStats(); });
    }
    if (btn.classList.contains('lead-edit')) {
        fetch(`admin_ajax/mailer_leads.php?action=get&id=${id}`).then(r => r.json()).then(d => {
            if (!d.ok) return toast(d.message, 'error');
            const l = d.lead;
            const f = document.getElementById('leadForm');
            f.reset();
            document.getElementById('leadId').value = l.id;
            f.email.value  = l.email;
            f.name.value   = l.name;
            f.source.value = l.source;
            f.tags.value   = l.tags;
            f.status.value = l.status;
            document.getElementById('leadModalTitle').textContent = 'Edit Lead';
            $('#leadModal').modal('show');
        });
    }
});

document.getElementById('leadForm').addEventListener('submit', e => {
    e.preventDefault();
    const fd = Object.fromEntries(new FormData(e.target));
    apiFetch('admin_ajax/mailer_leads.php', { action: fd.id ? 'update' : 'create', ...fd })
        .then(d => {
            toast(d.message, d.ok ? 'success' : 'error');
            if (d.ok) { $('#leadModal').modal('hide'); loadLeads(); loadStats(); }
        });
});

$('#leadModal').on('hidden.bs.modal', function() {
    document.getElementById('leadModalTitle').textContent = 'Add Lead';
    document.getElementById('leadForm').reset();
    document.getElementById('leadId').value = '';
});

// CSV import
document.getElementById('importLeadsForm').addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action', 'import');
    fetch('admin_ajax/mailer_leads.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            document.getElementById('importResult').innerHTML =
                `<div class="alert alert-${d.ok ? 'success' : 'danger'} mt-2">${escHtml(d.message)}</div>`;
            if (d.ok) { loadLeads(); loadStats(); }
        });
});

// ═══════════════════════════════════════════════
// TEMPLATES
// ═══════════════════════════════════════════════
function loadTemplates() {
    const container = document.getElementById('templateCards');
    container.innerHTML = '<div class="col-12 text-center text-muted py-4"><i class="anticon anticon-loading anticon-spin"></i> Loading templates…</div>';
    fetch('admin_ajax/mailer_templates.php?action=list')
        .then(r => r.json())
        .then(d => {
            container.innerHTML = '';
            if (!d.ok) {
                container.innerHTML = `<div class="col-12"><div class="alert alert-danger">
                    <strong>Could not load templates:</strong> ${escHtml(d.message || 'Unknown error')}
                    <br><small class="text-muted">Make sure the database schema has been imported (database/mailer_schema.sql).</small>
                </div></div>`;
                return;
            }
            const templates = d.templates || [];
            if (templates.length === 0) {
                container.innerHTML = `<div class="col-12"><div class="alert alert-info">
                    <i class="anticon anticon-info-circle"></i>
                    No email templates yet. Click <strong>New Template</strong> to create your first professional template.
                </div></div>`;
                return;
            }
            templates.forEach(t => {
                container.innerHTML += `
                  <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                      <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>${escHtml(t.name)}</strong>
                        ${t.is_default ? '<span class="badge badge-primary">Default</span>' : ''}
                      </div>
                      <div class="card-body">
                        <p class="text-muted mb-1" style="font-size:13px;">${escHtml(t.subject)}</p>
                        <small class="text-muted">Updated: ${t.updated_at || '—'}</small>
                      </div>
                      <div class="card-footer d-flex justify-content-end">
                        <button class="btn btn-xs btn-info mr-1 tpl-edit" data-id="${t.id}">Edit</button>
                        <button class="btn btn-xs btn-danger tpl-del" data-id="${t.id}">Del</button>
                      </div>
                    </div>
                  </div>`;
            });
        })
        .catch(err => {
            container.innerHTML = `<div class="col-12"><div class="alert alert-warning">
                <strong>Network error:</strong> ${escHtml(err.message)}
            </div></div>`;
        });
}

document.querySelector('#tab-templates-link').addEventListener('click', loadTemplates);
loadTemplates();

document.getElementById('templateCards').addEventListener('click', e => {
    const btn = e.target.closest('button');
    if (!btn) return;
    const id = btn.dataset.id;
    if (btn.classList.contains('tpl-del')) {
        if (!confirm('Delete this template?')) return;
        apiFetch('admin_ajax/mailer_templates.php', { action: 'delete', id })
            .then(d => { toast(d.message, d.ok ? 'success' : 'error'); loadTemplates(); });
    }
    if (btn.classList.contains('tpl-edit')) {
        fetch(`admin_ajax/mailer_templates.php?action=get&id=${id}`).then(r => r.json()).then(d => {
            if (!d.ok) return toast(d.message, 'error');
            const t = d.template;
            const f = document.getElementById('templateForm');
            f.reset();
            document.getElementById('tplId').value = t.id;
            f.name.value    = t.name;
            f.subject.value = t.subject;
            editor.setValue(t.html_body, -1);
            syncEditor();
            document.getElementById('tplDefault').checked = !!t.is_default;
            document.getElementById('tplModalTitle').textContent = 'Edit Template';
            document.getElementById('tplPreviewBox').style.display = 'none';
            $('#templateModal').modal('show');
        });
    }
});

document.getElementById('templateForm').addEventListener('submit', e => {
    e.preventDefault();
    syncEditor();
    const fd = Object.fromEntries(new FormData(e.target));
    fd.is_default = document.getElementById('tplDefault').checked ? 1 : 0;
    apiFetch('admin_ajax/mailer_templates.php', { action: fd.id ? 'update' : 'create', ...fd })
        .then(d => {
            toast(d.message, d.ok ? 'success' : 'error');
            if (d.ok) { $('#templateModal').modal('hide'); loadTemplates(); }
        });
});

$('#templateModal').on('hidden.bs.modal', function() {
    document.getElementById('tplModalTitle').textContent = 'New Email Template';
    document.getElementById('templateForm').reset();
    document.getElementById('tplId').value = '';
    editor.setValue('', -1);
    document.getElementById('tplPreviewBox').style.display = 'none';
});

document.getElementById('previewTplBtn').addEventListener('click', () => {
    syncEditor();
    const box = document.getElementById('tplPreviewBox');
    box.innerHTML = editor.getValue();
    box.style.display = 'block';
});

// ═══════════════════════════════════════════════
// SEND LOGS (global tab)
// ═══════════════════════════════════════════════
function loadLogs() {
    const cid = document.getElementById('logCampaignFilter').value;
    const st  = document.getElementById('logStatusFilter').value;
    const qs  = new URLSearchParams({ limit: 300 });
    if (cid) qs.set('campaign_id', cid);
    if (st)  qs.set('status', st);

    fetch('admin_ajax/mailer_logs.php?' + qs).then(r => r.json()).then(d => {
        const tbody = document.querySelector('#logsTable tbody');
        tbody.innerHTML = '';
        (d.logs || []).forEach(l => {
            const sm = l.smtp_from || '—';
            tbody.innerHTML += `<tr>
              <td><small>${l.sent_at}</small></td>
              <td>${escHtml(l.campaign_name || `#${l.campaign_id}`)}</td>
              <td>${escHtml(l.to_email)}</td>
              <td><small>${escHtml(sm)}</small></td>
              <td>${statusBadge(l.status)}</td>
              <td><small class="text-danger">${escHtml(l.error_msg)}</small></td>
            </tr>`;
        });
    });
}

document.querySelector('#tab-logs-link').addEventListener('click', loadLogs);
document.getElementById('filterLogsBtn').addEventListener('click', loadLogs);

// ── Utilities ──────────────────────────────────────────────────────────────────
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function escAttr(str) { return escHtml(str); }
</script>

<?php require_once 'admin_footer.php'; ?>
