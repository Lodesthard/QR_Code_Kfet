<?php
    session_start();

    header('Content-Type: application/json');

    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['auth_level'] < 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès refusé']);
        exit();
    }

    $token = isset($_POST['token']) ? trim($_POST['token']) : '';

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token manquant']);
        exit();
    }

    require_once('connect.php');
    $mysqli = connectToDatabase();

    // Récupérer la commande
    $stmt = $mysqli->prepare('SELECT o.id, o.user_id, o.status FROM orders o WHERE o.qr_token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Commande introuvable']);
        exit();
    }

    if ($order['status'] >= 2) {
        echo json_encode(['success' => true, 'already_done' => true]);
        exit();
    }

    // Calculer le total de la commande
    $total = 0;
    $items = [];
    $stmt = $mysqli->prepare(
        'SELECT io.product_id, io.quantity, p.price, p.bdlc_price
         FROM item_orders io
         JOIN products p ON io.product_id = p.id
         WHERE io.order_id = ?'
    );
    $stmt->bind_param('i', $order['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    // Vérifier si l'utilisateur est membre BDLC
    $stmt = $mysqli->prepare('SELECT bdlc_member, credit FROM users WHERE id = ?');
    $stmt->bind_param('i', $order['user_id']);
    $stmt->execute();
    $stmt->bind_result($bdlc_member, $credit);
    $stmt->fetch();
    $stmt->close();

    foreach ($items as $item) {
        $price = $bdlc_member ? $item['bdlc_price'] : $item['price'];
        $total += $price * $item['quantity'];
    }

    // Vérifier que l'utilisateur a toujours assez de crédit
    if ($credit < $total) {
        http_response_code(400);
        echo json_encode(['error' => 'Crédit insuffisant (' . number_format($credit, 2) . ' € / ' . number_format($total, 2) . ' € nécessaires)']);
        exit();
    }

    // Débiter le compte
    $stmt = $mysqli->prepare('UPDATE users SET credit = credit - ? WHERE id = ?');
    $stmt->bind_param('di', $total, $order['user_id']);
    $stmt->execute();
    $stmt->close();

    // Décrémenter le stock
    $stmt = $mysqli->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
    foreach ($items as $item) {
        $stmt->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmt->execute();
    }
    $stmt->close();

    // Marquer la commande comme servie
    $stmt = $mysqli->prepare('UPDATE orders SET status = 2 WHERE id = ?');
    $stmt->bind_param('i', $order['id']);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
?>