let html5QrCode = null;
let currentToken = null;

// Démarrer le scanner au chargement
document.addEventListener('DOMContentLoaded', () => {
    startScanner();
});

function startScanner() {
    html5QrCode = new Html5Qrcode('reader');

    const config = { fps: 10, qrbox: { width: 240, height: 240 } };

    html5QrCode.start(
        { facingMode: 'environment' },
        config,
        onScanSuccess,
        null   // ignorer les erreurs de frame intermédiaires
    ).catch(err => {
        // Si la caméra n'est pas disponible, afficher uniquement la saisie manuelle
        document.getElementById('reader-container').innerHTML =
            '<p class="text-warning text-center small"><i class="fas fa-exclamation-triangle mr-1"></i>Caméra non disponible. Utilisez la saisie manuelle.</p>';
    });
}

function onScanSuccess(decodedText) {
    // Arrêter le scanner après un scan réussi
    if (html5QrCode) {
        html5QrCode.stop().catch(() => {});
    }
    loadOrderByToken(decodedText.trim());
}

function loadOrderByToken(token) {
    token = token.trim();

    if (!token) return;

    currentToken = token;

    // Cacher les sections
    document.getElementById('scan-section').classList.add('d-none');
    document.getElementById('order-section').classList.add('d-none');
    document.getElementById('error-section').classList.add('d-none');

    fetch('lib/get_order_by_token.php?token=' + encodeURIComponent(token))
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                showError(data.error);
                return;
            }
            showOrder(data);
        })
        .catch(() => showError('Impossible de contacter le serveur.'));
}

function showOrder(data) {
    document.getElementById('order-username').textContent = data.username;
    document.getElementById('order-id').textContent = 'Commande #' + data.order_id;

    // Badge statut
    const badge = document.getElementById('order-status-badge');
    const statusLabels = { 0: 'En attente', 1: 'En préparation', 2: 'Servie' };
    const statusClasses = { 0: 'badge-warning', 1: 'badge-info', 2: 'badge-success' };
    badge.textContent = statusLabels[data.status] || 'Inconnu';
    badge.className = 'badge ' + (statusClasses[data.status] || 'badge-secondary');

    // Liste des articles
    const list = document.getElementById('order-items-list');
    list.innerHTML = '';
    let total = 0;

    data.items.forEach(item => {
        const price = item.price;
        const lineTotal = price * item.quantity;
        total += lineTotal;

        const row = document.createElement('div');
        row.className = 'order-item-row';
        row.innerHTML = `
            <span class="item-qty badge badge-secondary">${item.quantity}x</span>
            <span class="item-name">${escapeHtml(item.name)}</span>
            <span class="item-price">${lineTotal.toFixed(2)} €</span>
        `;
        list.appendChild(row);
    });

    document.getElementById('order-total').textContent = total.toFixed(2) + ' €';

    // Désactiver le bouton si déjà servie
    const btn = document.getElementById('btn-done');
    if (data.status === 2) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-check mr-2"></i>Déjà servie';
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check mr-2"></i>Commande servie';
    }

    document.getElementById('order-section').classList.remove('d-none');
}

function showError(message) {
    document.getElementById('error-message').textContent = message;
    document.getElementById('error-section').classList.remove('d-none');
}

function markOrderDone() {
    if (!currentToken) return;

    const btn = document.getElementById('btn-done');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>En cours…';

    const formData = new FormData();
    formData.append('token', currentToken);

    fetch('lib/mark_order_done.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('order-status-badge');
                badge.textContent = 'Servie';
                badge.className = 'badge badge-success';
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Servie !';
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Commande servie';
                alert(data.error || 'Erreur inconnue');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Commande servie';
            alert('Impossible de contacter le serveur.');
        });
}

function resetScanner() {
    currentToken = null;
    document.getElementById('order-section').classList.add('d-none');
    document.getElementById('error-section').classList.add('d-none');
    document.getElementById('manual-token').value = '';
    document.getElementById('scan-section').classList.remove('d-none');

    // Redémarrer le scanner si le reader existe
    const readerEl = document.getElementById('reader');
    if (readerEl && !readerEl.innerHTML.includes('fa-exclamation')) {
        readerEl.innerHTML = '';
        startScanner();
    }
}

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;');
}
