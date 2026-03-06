<?php
require_once 'config.php';
require_once 'EmailHelper.php';
require_once 'TelegramHelper.php';
require_once 'header.php';

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject  = trim($_POST['subject']  ?? '');
    $message  = trim($_POST['message']  ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';

    // Validate priority against allowed values; fall back to 'medium' for unknown input
    $allowedPriorities = ['low', 'medium', 'high', 'critical'];
    if (!in_array($priority, $allowedPriorities, true)) {
        $priority = 'medium';
    }

    // Translate priority to German for notifications
    $priorityLabels = [
        'low'      => 'Niedrig',
        'medium'   => 'Mittel',
        'high'     => 'Hoch',
        'critical' => 'Kritisch',
    ];
    $priorityLabel = $priorityLabels[$priority];

    // Generate ticket number
    $ticket_number = 'TICKET-' . strtoupper(uniqid());

    try {
        $stmt = $pdo->prepare("INSERT INTO support_tickets
                              (user_id, ticket_number, subject, message, category, priority)
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $ticket_number, $subject, $message, $category, $priority]);

        $ticketVars = [
            'ticket_number'   => $ticket_number,
            'ticket_subject'  => $subject,
            'ticket_category' => $category,
            'ticket_priority' => $priorityLabel,
        ];

        // Instantiate once; both user and admin emails are sent through EmailHelper
        $emailHelper = new EmailHelper($pdo);

        // Send ticket-created confirmation email to the user (non-fatal)
        try {
            $emailHelper->sendTicketCreatedEmail($_SESSION['user_id'], $ticketVars);
        } catch (Exception $emailError) {
            error_log("Ticket creation user email failed (ticket $ticket_number): " . $emailError->getMessage());
        }

        // Notify admin of new ticket by email (non-fatal)
        try {
            $emailHelper->sendAdminNewTicketEmail($ticketVars);
        } catch (Exception $adminEmailError) {
            error_log("Admin ticket notification email failed (ticket $ticket_number): " . $adminEmailError->getMessage());
        }

        // Send Telegram notification to admin (non-fatal)
        try {
            $telegramHelper = new TelegramHelper($pdo);
            $telegramHelper->sendTicketNotification(
                $ticket_number,
                $subject,
                $category,
                $priorityLabel
            );
        } catch (Exception $tgError) {
            error_log("Telegram ticket notification failed (ticket $ticket_number): " . $tgError->getMessage());
        }

        $_SESSION['success'] = "Ticket erfolgreich eingereicht! Ihre Ticketnummer lautet: $ticket_number";
        header("Location: support.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Fehler beim Einreichen des Tickets: " . $e->getMessage();
    }
}

// Get user's tickets
$tickets = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Fehler beim Laden der Tickets: " . $e->getMessage();
}
?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff;">
                    <div class="card-body py-4">
                        <h2 class="mb-2 text-white" style="font-weight: 700;">
                            <i class="anticon anticon-customer-service mr-2"></i>Support-Center
                        </h2>
                        <p class="mb-0" style="color: rgba(255,255,255,0.9); font-size: 15px;">
                            Erhalten Sie Hilfe von unserem Support-Team
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-info-circle mr-2" style="color: var(--brand);"></i>Support-Informationen
                        </h5>
                        <ul class="list-unstyled" style="line-height: 2.2;">
                            <li class="d-flex align-items-center mb-2">
                                <div class="mr-3" style="width: 40px; height: 40px; background: rgba(41, 80, 168, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="anticon anticon-mail" style="color: var(--brand); font-size: 18px;"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size: 12px;">E-Mail</div>
                                    <strong>support@kryptox.co.uk</strong>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <div class="mr-3" style="width: 40px; height: 40px; background: rgba(41, 80, 168, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="anticon anticon-clock-circle" style="color: var(--brand); font-size: 18px;"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size: 12px;">Öffnungszeiten</div>
                                    <strong>Mo–Fr 24/7</strong>
                                </div>
                            </li>
                        </ul>
                        <div class="alert alert-info border-0 mt-4" style="border-radius: 10px;">
                            <i class="anticon anticon-exclamation-circle mr-2"></i>
                            Bei dringenden Anliegen erstellen Sie bitte ein Ticket mit der Priorität <strong>„Kritisch"</strong>.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-plus-circle mr-2" style="color: var(--brand);"></i>Neues Ticket erstellen
                        </h5>
                        <form method="POST" id="ticketForm">
                            <div class="form-group">
                                <label class="font-weight-500">Betreff</label>
                                <input type="text" class="form-control" name="subject" required placeholder="Kurze Beschreibung Ihres Anliegens" style="border-radius: 8px;">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-500">Kategorie</label>
                                <select class="form-control" name="category" required style="border-radius: 8px;">
                                    <option value="">Kategorie wählen</option>
                                    <option value="Case Inquiry">📁 Fallanfrage</option>
                                    <option value="Document Submission">📄 Dokumenteneinreichung</option>
                                    <option value="Payment Issue">💳 Zahlungsproblem</option>
                                    <option value="Technical Problem">⚙️ Technisches Problem</option>
                                    <option value="Other">💬 Sonstiges</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-500">Priorität</label>
                                <select class="form-control" name="priority" style="border-radius: 8px;">
                                    <option value="low">Niedrig</option>
                                    <option value="medium" selected>Mittel</option>
                                    <option value="high">Hoch</option>
                                    <option value="critical">Kritisch</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-500">Nachricht</label>
                                <textarea class="form-control" rows="5" name="message" required placeholder="Beschreiben Sie Ihr Anliegen ausführlich …" style="border-radius: 8px;"></textarea>
                            </div>
                            <button type="submit" name="submit_ticket" class="btn btn-primary btn-block" style="border-radius: 8px;">
                                <i class="anticon anticon-plus-circle mr-1"></i> Ticket einreichen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tickets Section -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0" style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-file-text mr-2" style="color: var(--brand);"></i>Meine Support-Tickets
                            </h5>
                            <button class="btn btn-outline-primary btn-sm" id="refreshTickets">
                                <i class="anticon anticon-reload mr-1"></i> Aktualisieren
                            </button>
                        </div>
                        
                        <?php if (empty($tickets)): ?>
                            <div class="alert alert-info border-0 d-flex align-items-center" style="border-radius: 10px;">
                                <i class="anticon anticon-info-circle mr-2" style="font-size: 20px;"></i>
                                <span>Keine Support-Tickets vorhanden. Erstellen Sie oben Ihr erstes Ticket!</span>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="ticketsTable">
                                    <thead>
                                        <tr>
                                            <th>Ticket-Nr.</th>
                                            <th>Betreff</th>
                                            <th>Kategorie</th>
                                            <th>Priorität</th>
                                            <th>Status</th>
                                            <th>Letzte Antwort</th>
                                            <th>Erstellt am</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                            <td><?= htmlspecialchars($ticket['category']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= 
                                                    $ticket['priority'] == 'low' ? 'info' : 
                                                    ($ticket['priority'] == 'medium' ? 'warning' : 
                                                    ($ticket['priority'] == 'high' ? 'danger' : 'dark')) 
                                                ?>" style="font-size: 11px;">
                                                    <?php
                                                    $priorityLabels = ['low' => 'Niedrig', 'medium' => 'Mittel', 'high' => 'Hoch', 'critical' => 'Kritisch'];
                                                    echo $priorityLabels[$ticket['priority']] ?? ucfirst($ticket['priority']);
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= 
                                                    $ticket['status'] == 'open' ? 'primary' : 
                                                    ($ticket['status'] == 'in_progress' ? 'warning' :
                                                    ($ticket['status'] == 'resolved' ? 'success' : 'secondary'))
                                                ?>" style="font-size: 11px;">
                                                    <?php
                                                    $statusLabels = ['open' => 'Offen', 'in_progress' => 'In Bearbeitung', 'resolved' => 'Gelöst', 'closed' => 'Geschlossen'];
                                                    echo $statusLabels[$ticket['status']] ?? ucfirst(str_replace('_', ' ', $ticket['status']));
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?= $ticket['last_reply_at'] ? date('d.m.Y H:i', strtotime($ticket['last_reply_at'])) : '-' ?></td>
                                            <td><?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex" style="gap: 5px;">
                                                    <button class="btn btn-sm btn-outline-info view-ticket" 
                                                            data-id="<?= $ticket['id'] ?>" 
                                                            data-ticket="<?= htmlspecialchars($ticket['ticket_number']) ?>"
                                                            title="Ticket anzeigen">
                                                        <i class="anticon anticon-eye"></i>
                                                    </button>
                                                    <?php if ($ticket['status'] != 'closed'): ?>
                                                    <button class="btn btn-sm btn-outline-primary reply-ticket" 
                                                            data-id="<?= $ticket['id'] ?>"
                                                            data-ticket="<?= htmlspecialchars($ticket['ticket_number']) ?>"
                                                            title="Auf Ticket antworten">
                                                        <i class="anticon anticon-message"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ticket-Details Modal -->
<div class="modal fade" id="viewTicketModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ticket-Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="ticketDetails">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Wird geladen …</span>
                        </div>
                        <p>Ticket-Details werden geladen …</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-primary" id="replyFromView" style="display: none;">
                    <i class="anticon anticon-message"></i> Antworten
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Antwort-Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Auf Ticket antworten</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="replyForm">
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="reply_ticket_id">
                    <div class="alert alert-info">
                        <strong>Ticket-Nr.:</strong> <span id="reply_ticket_number"></span>
                    </div>
                    <div class="form-group">
                        <label>Ihre Antwort</label>
                        <textarea class="form-control" name="message" rows="6" required placeholder="Geben Sie hier Ihre Antwort ein …"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Dateien anhängen (optional)</label>
                        <input type="file" class="form-control-file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="form-text text-muted">Sie können mehrere Dateien anhängen. Max. 10 MB pro Datei.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-send"></i> Antwort senden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
$(document).ready(function() {
    let currentTicketId = null;
    
    // View ticket handler
    $(document).on('click', '.view-ticket', function() {
        const ticketId = $(this).data('id');
        const ticketNumber = $(this).data('ticket');
        currentTicketId = ticketId;
        
        // Show loading
        $('#ticketDetails').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Wird geladen …</span>
                </div>
                <p>Ticket-Details werden geladen …</p>
            </div>
        `);
        
        $('#viewTicketModal').modal('show');
        
        // Load ticket details
        $.get('ajax/get_ticket_details.php', { id: ticketId })
        .done(function(response) {
            if (response.success) {
                const ticket = response.data.ticket;
                const replies = response.data.replies || [];
                
                let repliesHtml = '';
                replies.forEach(function(reply) {
                    const isAdmin = reply.admin_id ? true : false;
                    const senderName = isAdmin ? reply.admin_name : 'Sie';
                    const cardClass = isAdmin ? 'border-primary' : 'border-info';
                    const headerClass = isAdmin ? 'bg-primary text-white' : 'bg-info text-white';
                    
                    repliesHtml += `
                        <div class="card mb-3 ${cardClass}">
                            <div class="card-header ${headerClass}">
                                <div class="d-flex justify-content-between">
                                    <strong>${senderName}</strong>
                                    <small>${new Date(reply.created_at).toLocaleString()}</small>
                                </div>
                            </div>
                            <div class="card-body">
                                ${reply.message.replace(/\n/g, '<br>')}
                                ${reply.attachments ? getAttachmentsHtml(reply.attachments) : ''}
                            </div>
                        </div>
                    `;
                });
                
                $('#ticketDetails').html(`
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Ticket-Informationen</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Ticket-Nr.:</strong></td><td>${ticket.ticket_number}</td></tr>
                                <tr><td><strong>Betreff:</strong></td><td>${ticket.subject}</td></tr>
                                <tr><td><strong>Kategorie:</strong></td><td>${ticket.category}</td></tr>
                                <tr><td><strong>Priorität:</strong></td><td><span class="badge badge-${getPriorityClass(ticket.priority)}">${getPriorityLabel(ticket.priority)}</span></td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge badge-${getStatusClass(ticket.status)}">${getStatusLabel(ticket.status)}</span></td></tr>
                                <tr><td><strong>Erstellt am:</strong></td><td>${new Date(ticket.created_at).toLocaleString('de-DE')}</td></tr>
                                ${ticket.last_reply_at ? `<tr><td><strong>Letzte Antwort:</strong></td><td>${new Date(ticket.last_reply_at).toLocaleString('de-DE')}</td></tr>` : ''}
                            </table>
                        </div>
                        <div class="col-md-8">
                            <h6>Originalnachricht</h6>
                            <div class="card mb-3">
                                <div class="card-body">
                                    ${ticket.message.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6>Gesprächsverlauf</h6>
                    <div style="max-height: 500px; overflow-y: auto;">
                        ${repliesHtml || '<p class="text-muted">Noch keine Antworten.</p>'}
                    </div>
                `);
                
                // Show reply button if ticket is not closed
                if (ticket.status !== 'closed') {
                    $('#replyFromView').show();
                } else {
                    $('#replyFromView').hide();
                }
            } else {
                $('#ticketDetails').html('<div class="alert alert-danger">Fehler beim Laden der Ticket-Details</div>');
            }
        })
        .fail(function() {
            $('#ticketDetails').html('<div class="alert alert-danger">Laden der Ticket-Details fehlgeschlagen</div>');
        });
    });
    
    // Reply button handlers
    $(document).on('click', '.reply-ticket', function() {
        const ticketId = $(this).data('id');
        const ticketNumber = $(this).data('ticket');
        openReplyModal(ticketId, ticketNumber);
    });
    
    $('#replyFromView').click(function() {
        if (currentTicketId) {
            const ticketNumber = $('#ticketDetails').find('td').first().next().text();
            openReplyModal(currentTicketId, ticketNumber);
        }
    });
    
    function openReplyModal(ticketId, ticketNumber) {
        $('#reply_ticket_id').val(ticketId);
        $('#reply_ticket_number').text(ticketNumber);
        $('#replyModal').modal('show');
    }
    
    // Reply form submission
    $('#replyForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'ajax/reply_ticket.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#replyModal').modal('hide');
                    $('#replyForm')[0].reset();
                    
                    // Show success message
                    toastr.success('Antwort erfolgreich gesendet');
                    
                    // Refresh the tickets table
                    location.reload();
                } else {
                    toastr.error(response.message || 'Antwort konnte nicht gesendet werden');
                }
            },
            error: function() {
                toastr.error('Antwort konnte nicht gesendet werden');
            }
        });
    });
    
    // Refresh tickets
    $('#refreshTickets').click(function() {
        location.reload();
    });
    
    // Hilfsfunktionen
    function getPriorityClass(priority) {
        const classes = {
            'low': 'info',
            'medium': 'warning',
            'high': 'danger',
            'critical': 'dark'
        };
        return classes[priority] || 'secondary';
    }

    function getPriorityLabel(priority) {
        const labels = {
            'low':      'Niedrig',
            'medium':   'Mittel',
            'high':     'Hoch',
            'critical': 'Kritisch'
        };
        return labels[priority] || priority.charAt(0).toUpperCase() + priority.slice(1);
    }
    
    function getStatusClass(status) {
        const classes = {
            'open': 'primary',
            'in_progress': 'warning',
            'resolved': 'success',
            'closed': 'secondary'
        };
        return classes[status] || 'secondary';
    }

    function getStatusLabel(status) {
        const labels = {
            'open':        'Offen',
            'in_progress': 'In Bearbeitung',
            'resolved':    'Gelöst',
            'closed':      'Geschlossen'
        };
        return labels[status] || status.replace('_', ' ').charAt(0).toUpperCase() + status.replace('_', ' ').slice(1);
    }
    
    function getAttachmentsHtml(attachments) {
        if (!attachments) return '';
        
        try {
            const files = JSON.parse(attachments);
            if (!files.length) return '';
            
            let html = '<div class="mt-2"><small class="text-muted">Anhänge:</small><br>';
            files.forEach(function(file) {
                html += `<a href="uploads/tickets/${file}" target="_blank" class="btn btn-sm btn-outline-info mr-1 mt-1">
                    <i class="anticon anticon-paper-clip"></i> ${file}
                </a>`;
            });
            html += '</div>';
            return html;
        } catch (e) {
            return '';
        }
    }
});
</script>