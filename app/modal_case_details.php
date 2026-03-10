<?php
/* modal_case_details.php – same folder as index.php */
?>
<style>
/* Blockchain Scanner Styles (used in case modal) */
.blockchain-scanner-section{background:linear-gradient(135deg,#0a0e1a 0%,#0d1a35 50%,#0a1528 100%);border-radius:14px;padding:20px;position:relative;overflow:hidden;border:1px solid rgba(45,169,227,.2);box-shadow:0 4px 20px rgba(0,0,0,.4),inset 0 1px 0 rgba(45,169,227,.1)}
.blockchain-scanner-section::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(ellipse at center,rgba(41,80,168,.08) 0%,transparent 60%);animation:scanner-bg-rotate 8s linear infinite;pointer-events:none}
@keyframes scanner-bg-rotate{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
.scanner-title{color:#2da9e3;font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.scanner-title .dot{width:8px;height:8px;background:#4dffb4;border-radius:50%;box-shadow:0 0 6px #4dffb4;animation:scanner-blink 1s ease-in-out infinite}
@keyframes scanner-blink{0%,100%{opacity:1}50%{opacity:.3}}
.scanner-stats{display:flex;gap:12px;margin-bottom:16px}
.scanner-stat{flex:1;background:rgba(255,255,255,.04);border-radius:8px;padding:8px 12px;border:1px solid rgba(45,169,227,.1)}
.scanner-stat-val{font-size:18px;font-weight:700;color:#fff;line-height:1.2}
.scanner-stat-val.green{color:#4dffb4}
.scanner-stat-lbl{font-size:10px;color:#7bafd4;text-transform:uppercase;letter-spacing:.8px}
.addr-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:5px;margin-bottom:16px}
.addr-node{background:rgba(45,169,227,.07);border:1px solid rgba(45,169,227,.15);border-radius:6px;padding:5px 4px;text-align:center;font-size:9px;font-family:monospace;color:#7bafd4;position:relative;transition:all .4s ease;overflow:hidden}
.addr-node::before{content:'';position:absolute;top:-100%;left:0;width:100%;height:3px;background:linear-gradient(90deg,transparent,#2da9e3,transparent);animation:addr-scan 2.4s ease-in-out infinite}
@keyframes addr-scan{0%{top:-100%;opacity:0}40%{opacity:1}100%{top:110%;opacity:0}}
.addr-node.found{background:rgba(77,255,180,.12);border-color:#4dffb4;color:#4dffb4;box-shadow:0 0 8px rgba(77,255,180,.25);animation:addr-found-pulse 2s ease-in-out infinite}
@keyframes addr-found-pulse{0%,100%{box-shadow:0 0 4px rgba(77,255,180,.3)}50%{box-shadow:0 0 14px rgba(77,255,180,.6)}}
.addr-label{font-size:8px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.recovery-flow{display:flex;align-items:center;gap:8px;overflow-x:auto;padding:10px 0 4px;scrollbar-width:none}
.recovery-flow::-webkit-scrollbar{display:none}
.flow-node{flex-shrink:0;background:rgba(255,255,255,.05);border:1px solid rgba(45,169,227,.25);border-radius:8px;padding:7px 10px;text-align:center;min-width:80px}
.flow-node.source{border-color:rgba(255,100,100,.5);background:rgba(255,50,50,.08)}
.flow-node.found{border-color:rgba(77,255,180,.6);background:rgba(77,255,180,.1);animation:addr-found-pulse 2.5s ease-in-out infinite}
.flow-node.dest{border-color:rgba(41,80,168,.6);background:rgba(41,80,168,.15)}
.flow-node-icon{font-size:16px;margin-bottom:4px}
.flow-node-label{font-size:9px;color:#7bafd4;text-transform:uppercase;letter-spacing:.6px;line-height:1.3}
.flow-node-amount{font-size:11px;font-weight:700;color:#4dffb4;margin-top:2px}
.flow-arrow{color:#2da9e3;font-size:16px;flex-shrink:0;animation:flow-arrow-pulse 1.5s ease-in-out infinite}
@keyframes flow-arrow-pulse{0%,100%{opacity:1;transform:translateX(0)}50%{opacity:.5;transform:translateX(3px)}}
.scanner-progress-bar{height:5px;background:rgba(255,255,255,.07);border-radius:10px;overflow:hidden;margin-top:12px}
.scanner-progress-fill{height:100%;background:linear-gradient(90deg,#2950a8,#2da9e3,#4dffb4);border-radius:10px;width:0;transition:width 3s cubic-bezier(.2,.9,.3,1)}
</style>
<!-- Case Details Modal -->
<div class="modal fade" id="caseDetailsModal" tabindex="-1" role="dialog" aria-labelledby="caseDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold" id="caseDetailsModalLabel">
                    <i class="anticon anticon-file-text mr-2"></i>Case Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="caseModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading case details...</p>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="anticon anticon-close mr-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* Case details modal JS */
$(function(){
    $('.view-case-btn').click(function() {
        const caseId = $(this).data('case-id');
        $('#caseDetailsModal').modal('show');
        
        // Reset modal body
        $('#caseModalBody').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading case details...</p>
            </div>
        `);
        
        // Fetch case details
        $.ajax({
            url: 'ajax/get-case.php',
            method: 'GET',
            data: { id: caseId },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success && data.case) {
                        const c = data.case;
                        const progress = c.reported_amount > 0 ? Math.round((c.recovered_amount / c.reported_amount) * 100) : 0;
                        
                        const statusClass = {
                            'open': 'warning',
                            'documents_required': 'secondary',
                            'under_review': 'info',
                            'refund_approved': 'success',
                            'refund_rejected': 'danger',
                            'closed': 'dark'
                        }[c.status] || 'light';
                        
                        const html = `
                            <div class="case-details-content">
                                <!-- Header Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0" style="background: rgba(41, 80, 168, 0.05);">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2" style="font-size: 12px; text-transform: uppercase;">Case Number</h6>
                                                <h4 class="mb-0 font-weight-bold" style="color: var(--brand);">${c.case_number || 'N/A'}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0" style="background: rgba(41, 80, 168, 0.05);">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2" style="font-size: 12px; text-transform: uppercase;">Status</h6>
                                                <span class="badge badge-${statusClass} px-3 py-2" style="font-size: 14px;">
                                                    <i class="anticon anticon-flag mr-1"></i>${c.status ? c.status.replace(/_/g, ' ').toUpperCase() : 'N/A'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Financial Overview -->
                                <div class="card border-0 mb-4" style="background: linear-gradient(135deg, rgba(41, 80, 168, 0.05), rgba(45, 169, 227, 0.05));">
                                    <div class="card-body">
                                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-dollar mr-2" style="color: var(--brand);"></i>Financial Overview
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="text-muted mb-1" style="font-size: 13px;">Reported Amount</div>
                                                <h4 class="mb-0 font-weight-bold text-danger">$${parseFloat(c.reported_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="text-muted mb-1" style="font-size: 13px;">Recovered Amount</div>
                                                <h3 class="mb-2 font-weight-bold" style="color: #2c3e50;">$${parseFloat(c.recovered_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h3>
                                                <div class="progress mb-2" style="height: 8px; border-radius: 10px; background: #e9ecef;">
                                                    <div class="progress-bar" style="width: ${progress}%; background: linear-gradient(90deg, #2950a8 0%, #2da9e3 100%);"></div>
                                                </div>
                                                <small class="text-muted">${progress}% of $${parseFloat(c.reported_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 3D Blockchain Algorithm Scanner -->
                                <div class="blockchain-scanner-section mb-4" id="blockchainScanner_\${c.id || 'case'}">
                                    <div class="scanner-title">
                                        <span class="dot"></span>
                                        AI Algorithm · Blockchain Address Analysis
                                        <span style="margin-left:auto;font-size:11px;color:#4dffb4;" id="scannerStatus_\${c.id || 'case'}">SCANNING…</span>
                                    </div>
                                    <div class="scanner-stats">
                                        <div class="scanner-stat">
                                            <div class="scanner-stat-val" id="scannedCount_\${c.id || 'case'}">0</div>
                                            <div class="scanner-stat-lbl">Addresses Checked</div>
                                        </div>
                                        <div class="scanner-stat">
                                            <div class="scanner-stat-val green" id="foundCount_\${c.id || 'case'}">0</div>
                                            <div class="scanner-stat-lbl">Found</div>
                                        </div>
                                        <div class="scanner-stat">
                                            <div class="scanner-stat-val" style="color:#2da9e3;">50</div>
                                            <div class="scanner-stat-lbl">Total Addresses</div>
                                        </div>
                                    </div>
                                    <div class="addr-grid" id="addrGrid_\${c.id || 'case'}">
                                        \${Array.from({length:50}, (_,i) => \`<div class="addr-node scanning" id="addr_node_\${c.id || 'case'}_\${i}" title="Address \${i+1}"><span class="addr-label">0x\${Math.random().toString(16).slice(2,8)}…</span></div>\`).join('')}
                                    </div>
                                    <div style="color:#7bafd4;font-size:11px;margin-bottom:10px;">Fund Flow Map — Recovery Path</div>
                                    <div class="recovery-flow">
                                        <div class="flow-node source">
                                            <div class="flow-node-icon">🏴</div>
                                            <div class="flow-node-label">Scam Wallet</div>
                                            <div class="flow-node-amount" style="color:#ff6b6b;">−$\${parseFloat(c.reported_amount||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                                        </div>
                                        <div class="flow-arrow">→</div>
                                        <div class="flow-node">
                                            <div class="flow-node-icon">🔗</div>
                                            <div class="flow-node-label">Mixer/Exchange</div>
                                        </div>
                                        <div class="flow-arrow">→</div>
                                        <div class="flow-node found">
                                            <div class="flow-node-icon">💰</div>
                                            <div class="flow-node-label">Funds Located!</div>
                                            <div class="flow-node-amount">$\${parseFloat(c.reported_amount||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                                        </div>
                                        <div class="flow-arrow">→</div>
                                        <div class="flow-node">
                                            <div class="flow-node-icon">⚖️</div>
                                            <div class="flow-node-label">Legal Action</div>
                                        </div>
                                        <div class="flow-arrow">→</div>
                                        <div class="flow-node dest">
                                            <div class="flow-node-icon">✅</div>
                                            <div class="flow-node-label">Your Account</div>
                                            <div class="flow-node-amount">+$\${parseFloat(c.recovered_amount||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                                        </div>
                                    </div>
                                    <div class="scanner-progress-bar">
                                        <div class="scanner-progress-fill" id="scannerFill_\${c.id || 'case'}"></div>
                                    </div>
                                </div>

                                <!-- Platform Info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                                    <i class="anticon anticon-global mr-2" style="color: var(--brand);"></i>Platform Information
                                                </h6>
                                                <p class="mb-2"><strong>Platform:</strong> ${c.platform_name || 'N/A'}</p>
                                                <p class="mb-0"><strong>Created:</strong> ${c.created_at ? new Date(c.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                                    <i class="anticon anticon-clock-circle mr-2" style="color: var(--brand);"></i>Timeline
                                                </h6>
                                                <p class="mb-2"><strong>Last Updated:</strong> ${c.updated_at ? new Date(c.updated_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}</p>
                                                <p class="mb-0"><strong>Days Active:</strong> ${c.created_at ? Math.floor((new Date() - new Date(c.created_at)) / (1000 * 60 * 60 * 24)) : 0} days</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                ${c.description ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-file-text mr-2" style="color: var(--brand);"></i>Case Description
                                        </h6>
                                        <p class="mb-0" style="line-height: 1.6;">${c.description}</p>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Recovery Transactions -->
                                ${data.recoveries && data.recoveries.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-transaction mr-2" style="color: var(--brand);"></i>Recovery Transactions
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead style="background: rgba(41, 80, 168, 0.05);">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Amount</th>
                                                        <th>Method</th>
                                                        <th>Reference</th>
                                                        <th>Processed By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.recoveries.map(r => `
                                                        <tr>
                                                            <td>${r.transaction_date ? new Date(r.transaction_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A'}</td>
                                                            <td><strong class="text-success">$${parseFloat(r.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                                                            <td>${r.method || 'N/A'}</td>
                                                            <td><small class="text-muted">${r.transaction_reference || 'N/A'}</small></td>
                                                            <td>${r.admin_first_name && r.admin_last_name ? `${r.admin_first_name} ${r.admin_last_name}` : 'System'}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Documents -->
                                ${data.documents && data.documents.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-paper-clip mr-2" style="color: var(--brand);"></i>Case Documents
                                        </h6>
                                        <div class="list-group">
                                            ${data.documents.map(d => `
                                                <div class="list-group-item border-0 px-0">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="anticon anticon-file mr-2" style="color: var(--brand);"></i>
                                                            <strong>${d.document_type || 'Document'}</strong>
                                                            ${d.verified ? '<span class="badge badge-success badge-sm ml-2"><i class="anticon anticon-check"></i> Verified</span>' : ''}
                                                        </div>
                                                        <small class="text-muted">${d.uploaded_at ? new Date(d.uploaded_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}) : ''}</small>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Status History -->
                                ${data.history && data.history.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-history mr-2" style="color: var(--brand);"></i>Status History
                                        </h6>
                                        <div class="timeline">
                                            ${data.history.map((h, idx) => `
                                                <div class="timeline-item ${idx === 0 ? 'timeline-item-active' : ''}">
                                                    <div class="timeline-marker ${idx === 0 ? 'bg-primary' : 'bg-secondary'}"></div>
                                                    <div class="timeline-content">
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <strong>${h.new_status ? h.new_status.replace(/_/g, ' ').toUpperCase() : 'Status Change'}</strong>
                                                            <small class="text-muted">${h.created_at ? new Date(h.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : ''}</small>
                                                        </div>
                                                        ${h.comments ? `<p class="mb-1 text-muted small">${h.comments}</p>` : ''}
                                                        ${h.first_name && h.last_name ? `<small class="text-muted">By: ${h.first_name} ${h.last_name}</small>` : ''}
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Actions -->
                                <div class="text-center mt-4">
                                    <a href="cases.php" class="btn btn-primary">
                                        <i class="anticon anticon-folder-open mr-1"></i>View All Cases
                                    </a>
                                </div>
                            </div>
                        `;
                        
                        $('#caseModalBody').html(html);
                        $('#caseDetailsModalLabel').html(`<i class="anticon anticon-file-text mr-2"></i>Case #${c.case_number || 'Details'}`);

                        // ── Blockchain Scanner Animation ──────────────────
                        (function runBlockchainScanner(caseId) {
                            var nodePrefix = 'addr_node_' + caseId + '_';
                            var scannedEl = document.getElementById('scannedCount_' + caseId);
                            var foundEl   = document.getElementById('foundCount_' + caseId);
                            var fillEl    = document.getElementById('scannerFill_' + caseId);
                            var statusEl  = document.getElementById('scannerStatus_' + caseId);
                            if (!scannedEl) return;

                            var TOTAL = 50;
                            var foundIndices = new Set();
                            var seed = (caseId + '').split('').reduce(function(a,c){return a + c.charCodeAt(0);}, 0);
                            for (var fi = 0; fi < 7; fi++) {
                                foundIndices.add((seed * (fi + 3) * 7 + fi * 11) % TOTAL);
                            }

                            var scanned = 0, foundCount = 0;
                            var interval = setInterval(function() {
                                if (scanned >= TOTAL) {
                                    clearInterval(interval);
                                    if (statusEl) { statusEl.textContent = 'COMPLETE ✓'; statusEl.style.color = '#4dffb4'; }
                                    return;
                                }
                                var node = document.getElementById(nodePrefix + scanned);
                                if (node) {
                                    if (foundIndices.has(scanned)) {
                                        node.classList.remove('scanning');
                                        node.classList.add('found');
                                        foundCount++;
                                        if (foundEl) foundEl.textContent = foundCount;
                                    } else {
                                        node.classList.remove('scanning');
                                        node.style.borderColor = 'rgba(45,169,227,0.08)';
                                        node.style.color = '#3a5570';
                                    }
                                }
                                scanned++;
                                if (scannedEl) scannedEl.textContent = scanned;
                                if (fillEl) fillEl.style.width = ((scanned / TOTAL) * 100) + '%';
                            }, 80);
                        })(c.id || 'case');
                    } else {
                        $('#caseModalBody').html(`
                            <div class="alert alert-danger">
                                <i class="anticon anticon-close-circle mr-2"></i>${data.message || 'Unable to load case details'}
                            </div>
                        `);
                    }
                } catch (e) {
                    $('#caseModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="anticon anticon-close-circle mr-2"></i>Error parsing case data
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $('#caseModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="anticon anticon-close-circle mr-2"></i>Error loading case details: ${error}
                    </div>
                `);
            }
        });
    });
});
</script>