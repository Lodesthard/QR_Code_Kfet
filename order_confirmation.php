<?php
    session_start();

    require_once('lib/redirect.php');
    auth_level(0);

    require_once('lib/connect.php');
    $mysqli = connectToDatabase();

    // Récupérer le token depuis l'URL
    $token = isset($_GET['token']) ? trim($_GET['token']) : '';

    if (empty($token)) {
        header('Location: index.php');
        exit();
    }

    // Vérifier que ce token appartient bien à l'utilisateur connecté
    $order = null;
    $items = [];

    if ($stmt = $mysqli->prepare(
        'SELECT o.id, o.datetime, o.status FROM orders o WHERE o.qr_token = ? AND o.user_id = ?'
    )) {
        $stmt->bind_param('si', $token, $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
    }

    if (!$order) {
        header('Location: index.php');
        exit();
    }

    // Récupérer les articles de la commande
    if ($stmt = $mysqli->prepare(
        'SELECT p.name, p.image, io.quantity,
                IF(?, p.bdlc_price, p.price) AS unit_price
         FROM item_orders io
         JOIN products p ON io.product_id = p.id
         WHERE io.order_id = ?'
    )) {
        $stmt->bind_param('ii', $_SESSION['bdlc_member'], $order['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
    }

    $total = array_sum(array_map(fn($r) => $r['unit_price'] * $r['quantity'], $items));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kfet – Confirmation de commande</title>
    <?php include 'templates/head.php'; ?>
    <link rel="stylesheet" href="css/order_confirmation.css">
</head>
<body>
<?php include 'templates/nav.php'; ?>

<div id="container">
    <main class="confirmation-wrapper">

        <div class="confirmation-header">
            <i class="fas fa-check-circle confirmation-icon"></i>
            <h2>Commande confirmée !</h2>
            <p class="text-muted">Montrez ce QR code à un·e barista pour récupérer votre commande.</p>
        </div>

        <div class="qr-zone">
            <div id="qrcode"></div>
            <p class="qr-hint"><small>Commande #<?php echo htmlspecialchars($order['id']); ?></small></p>
        </div>

        <div class="order-summary-box">
            <h5>Récapitulatif</h5>
            <table class="table table-sm">
                <thead>
                    <tr><th>Produit</th><th>Qté</th><th>Prix</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td><?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th><?php echo number_format($total, 2); ?> €</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <a href="index.php" class="btn btn-outline-secondary mt-3">
            <i class="fas fa-home mr-1"></i> Retour à l'accueil
        </a>

    </main>
</div>

<!-- Bibliothèque QR Code (qrcodejs) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById('qrcode'), {
        text: <?php echo json_encode($token); ?>,
        width: 220,
        height: 220,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });
</script>
</body>
</html>
