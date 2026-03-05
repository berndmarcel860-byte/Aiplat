<?php
/**
 * Cryptocurrency and Network Management
 * Allows admins to add, edit, and manage cryptocurrencies and their networks
 */

include 'admin_session.php';
include 'admin_header.php';
?>

<style>
.crypto-card {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}
.crypto-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.crypto-card.inactive {
    opacity: 0.6;
    background: #f8f9fc;
}
.network-badge {
    display: inline-block;
    padding: 5px 10px;
    margin: 3px;
    background: #f1f3f5;
    border-radius: 4px;
    font-size: 12px;
}
.network-badge.inactive {
    background: #ced4da;
    text-decoration: line-through;
}
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <h2 class="header-title">Cryptocurrency Management</h2>
            <p class="text-muted">Manage supported cryptocurrencies and their networks</p>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <button class="btn btn-primary" onclick="showAddCryptoModal()">
                    <i class="fas fa-plus-circle"></i> Add Cryptocurrency
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fab fa-bitcoin"></i> Cryptocurrencies
                        </h4>
                    </div>
                    <div class="card-body">
                        <div id="cryptoList">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2">Loading cryptocurrencies...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Cryptocurrency Modal -->
<div class="modal fade" id="addCryptoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Cryptocurrency</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addCryptoForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Symbol <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="symbol" required placeholder="e.g., BTC" maxlength="20">
                        <small class="text-muted">Short code for the cryptocurrency (uppercase)</small>
                    </div>
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g., Bitcoin">
                    </div>
                    <div class="form-group">
                        <label>Icon Class</label>
                        <input type="text" class="form-control" name="icon" placeholder="e.g., fab fa-bitcoin">
                        <small class="text-muted">Font Awesome icon class</small>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the cryptocurrency"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" class="form-control" name="sort_order" value="0" min="0">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="cryptoIsActive" checked>
                        <label class="form-check-label" for="cryptoIsActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Cryptocurrency
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Network Modal -->
<div class="modal fade" id="addNetworkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Network</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addNetworkForm">
                <div class="modal-body">
                    <input type="hidden" name="crypto_id" id="networkCryptoId">
                    <div class="form-group">
                        <label>Network Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="network_name" required placeholder="e.g., Ethereum (ERC-20)">
                    </div>
                    <div class="form-group">
                        <label>Network Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="network_type" required placeholder="e.g., ERC-20">
                    </div>
                    <div class="form-group">
                        <label>Chain ID</label>
                        <input type="text" class="form-control" name="chain_id" placeholder="e.g., 1 for Ethereum mainnet">
                        <small class="text-muted">Blockchain chain ID (if applicable)</small>
                    </div>
                    <div class="form-group">
                        <label>Explorer URL</label>
                        <input type="text" class="form-control" name="explorer_url" placeholder="https://etherscan.io/tx/">
                        <small class="text-muted">Blockchain explorer URL for transactions</small>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" class="form-control" name="sort_order" value="0" min="0">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="networkIsActive" checked>
                        <label class="form-check-label" for="networkIsActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Network
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'admin_footer.php'; ?>
<script>
$(document).ready(function() {
    loadCryptocurrencies();
});

function loadCryptocurrencies() {
    console.log('Admin: Loading cryptocurrencies...');
    $.ajax({
        url: 'admin_ajax/get_all_cryptocurrencies.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Admin: AJAX Response:', response);
            if (response.success) {
                console.log('Admin: Cryptocurrencies loaded:', response.cryptocurrencies.length);
                displayCryptocurrencies(response.cryptocurrencies);
            } else {
                console.error('Admin: Server returned error:', response.message);
                $('#cryptoList').html(`<div class="alert alert-danger">${response.message}</div>`);
            }
        },
        error: function(xhr, status, error) {
            console.error('Admin: AJAX Error:', status, error);
            console.error('Admin: Response:', xhr.responseText);
            $('#cryptoList').html(`<div class="alert alert-danger">Failed to load cryptocurrencies. Check console for details.<br>Error: ${error}</div>`);
        }
    });
}

function displayCryptocurrencies(cryptos) {
    if (cryptos.length === 0) {
        $('#cryptoList').html(`
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No cryptocurrencies added yet</p>
            </div>
        `);
        return;
    }

    let html = '';
    cryptos.forEach(crypto => {
        const isActive = crypto.is_active == 1;
        const networks = crypto.networks || [];
        
        html += `
            <div class="crypto-card ${!isActive ? 'inactive' : ''}" id="crypto-${crypto.id}">
                <div class="row">
                    <div class="col-md-8">
                        <h4>
                            <i class="${crypto.icon || 'fas fa-coins'}"></i> 
                            ${crypto.name} (${crypto.symbol})
                            ${!isActive ? '<span class="badge badge-secondary ml-2">Inactive</span>' : '<span class="badge badge-success ml-2">Active</span>'}
                        </h4>
                        <p class="text-muted mb-2">${crypto.description || 'No description'}</p>
                        <div class="mb-2">
                            <strong>Networks (${networks.length}):</strong>
                        </div>
                        <div>
                            ${networks.length > 0 ? networks.map(n => 
                                `<span class="network-badge ${n.is_active == 0 ? 'inactive' : ''}">${n.network_name}</span>`
                            ).join('') : '<span class="text-muted">No networks added</span>'}
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <button class="btn btn-sm btn-info mb-2" onclick="showAddNetworkModal(${crypto.id}, '${crypto.name}')">
                            <i class="fas fa-plus"></i> Add Network
                        </button>
                        <button class="btn btn-sm ${isActive ? 'btn-warning' : 'btn-success'} mb-2" onclick="toggleCryptoStatus(${crypto.id}, ${!isActive})">
                            <i class="fas fa-${isActive ? 'eye-slash' : 'eye'}"></i> ${isActive ? 'Disable' : 'Enable'}
                        </button>
                        <button class="btn btn-sm btn-danger mb-2" onclick="deleteCrypto(${crypto.id}, '${crypto.symbol}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#cryptoList').html(html);
}

function showAddCryptoModal() {
    $('#addCryptoForm')[0].reset();
    $('#addCryptoModal').modal('show');
}

function showAddNetworkModal(cryptoId, cryptoName) {
    $('#networkCryptoId').val(cryptoId);
    $('#addNetworkModal .modal-title').text(`Add Network to ${cryptoName}`);
    $('#addNetworkForm')[0].reset();
    $('#networkCryptoId').val(cryptoId);
    $('#addNetworkModal').modal('show');
}

$('#addCryptoForm').submit(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'admin_ajax/add_cryptocurrency.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addCryptoModal').modal('hide');
                loadCryptocurrencies();
                toastr.success('Cryptocurrency added successfully');
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Failed to add cryptocurrency');
        }
    });
});

$('#addNetworkForm').submit(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'admin_ajax/add_crypto_network.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addNetworkModal').modal('hide');
                loadCryptocurrencies();
                toastr.success('Network added successfully');
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Failed to add network');
        }
    });
});

function toggleCryptoStatus(cryptoId, newStatus) {
    $.ajax({
        url: 'admin_ajax/toggle_crypto_status.php',
        type: 'POST',
        data: { 
            crypto_id: cryptoId, 
            is_active: newStatus ? 1 : 0 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                loadCryptocurrencies();
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Failed to update status');
        }
    });
}

function deleteCrypto(cryptoId, symbol) {
    if (!confirm(`Delete ${symbol}? This will also delete all associated networks and may affect existing payment methods.`)) {
        return;
    }
    
    $.ajax({
        url: 'admin_ajax/delete_cryptocurrency.php',
        type: 'POST',
        data: { crypto_id: cryptoId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                loadCryptocurrencies();
                toastr.success('Cryptocurrency deleted');
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Failed to delete cryptocurrency');
        }
    });
}
</script>

