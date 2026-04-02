

<!-- Footer START -->
<footer class="footer">
    <div class="footer-content">
        <p class="m-b-0" style="color:#6c757d;font-size:14px;">
            <i class="anticon anticon-copyright mr-1"></i>
            <?= date('Y') ?> Crypto Finanze AI. All rights reserved.
        </p>
        <span>
            <a href="terms.php" class="text-gray m-r-15" style="color:#6c757d;text-decoration:none;font-size:14px;">
                <i class="anticon anticon-file-text mr-1"></i>Terms &amp; Conditions
            </a>
            <a href="privacy.php" class="text-gray" style="color:#6c757d;text-decoration:none;font-size:14px;">
                <i class="anticon anticon-lock mr-1"></i>Privacy Policy
            </a>
        </span>
    </div>
</footer>
<!-- Footer END -->

</div>
<!-- Page Container END -->

<!-- ================= CORE JS ================= -->
<script src="assets/js/vendors.min.js"></script>
<script src="https://cdn.datatables.net/v/bs4/dt-1.11.3/datatables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/pages/dashboard-default.js"></script>
<script src="assets/js/app.min.js"></script>

<!-- ================= CUSTOM JS ================= -->
<script src="assets/js/config.js"></script>
<script src="assets/js/sidebar.js"></script>
<script src="assets/js/charts.js"></script>
<script src="assets/js/docu1ments.js"></script>
<script src="assets/js/payment-methods.js"></script>
<script src="assets/js/transactions.js"></script>
<script src="assets/js/ky1c.js"></script>
<script src="assets/js/withdrawals1.js"></script>
<script src="assets/js/deposits.js"></script>

<!-- ================= NOTIFICATIONS ================= -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const notifList  = document.getElementById("notifList");
    const notifCount = document.getElementById("notifCount");

    function loadNotifications() {
        fetch("ajax/get_notifications.php")
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                notifList.innerHTML = "";
                const data = res.data;

                if (!data.length) {
                    notifList.innerHTML = '<div class="notif-empty">No notifications</div>';
                    notifCount.style.display = 'none';
                    return;
                }

                let unread = 0;
                data.forEach(n => {
                    if (n.is_read == 0) unread++;
                    const typeColor =
                        n.type === 'success' ? 'text-success' :
                        n.type === 'warning' ? 'text-warning' :
                        n.type === 'danger'  ? 'text-danger'  :
                        'text-info';
                    notifList.insertAdjacentHTML('beforeend', `
                        <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" data-id="${n.id}">
                            <div class="${typeColor}">${n.title}</div>
                            <small>${n.message}</small><br>
                            <small>${new Date(n.created_at).toLocaleString()}</small>
                        </div>
                    `);
                });

                notifCount.textContent = unread;
                notifCount.style.display = unread ? 'inline-block' : 'none';
            });
    }

    loadNotifications();
    setInterval(loadNotifications, 15000);

    notifList.addEventListener('click', e => {
        const item = e.target.closest('.notif-item');
        if (!item) return;
        fetch("ajax/mark_notification_read.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "id=" + item.dataset.id
        }).then(loadNotifications);
    });

    document.getElementById('markAllRead').addEventListener('click', () => {
        fetch("ajax/mark_notification_read.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "id=0"
        }).then(loadNotifications);
    });
});
</script>

<!-- ================= GOOGLE TRANSLATE ================= -->
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script>
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,de,no,sv',
        autoDisplay: false
    }, 'google_translate_element');

    setTimeout(() => {
        document.querySelectorAll('.goog-te-gadget-simple span')
            .forEach(el => { if (!el.querySelector('img')) el.style.display = 'none'; });
    }, 1000);
}
</script>

<?php
// ── Live Chat Widget ──────────────────────────────────────────────────────
// The admin can paste any live-chat embed code (Tawk.to, Crisp, Intercom …)
// via Admin → Settings → Live-Chat Code. It is output raw here.
try {
    $lcStmt = $pdo->query("SELECT live_chat_code FROM system_settings WHERE id = 1 LIMIT 1");
    $lcRow  = $lcStmt ? $lcStmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($lcRow && !empty(trim($lcRow['live_chat_code']))) {
        echo $lcRow['live_chat_code'];
    }
} catch (Exception $lcEx) {
    // Column does not exist yet (migration not applied) – silently skip
}
?>

</script>


