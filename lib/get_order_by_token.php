<?php
    session_start();

    header('Content-Type: application/json');

    require_once('redirect.php');

    // Seuls les baristas et admins peuvent consulter les commandes
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['auth_level'] < 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès refusé']);
        exit();
    }

    $token = isset($_GET['token']) ? trim($_GET['token']) : '';

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token manquant']);
        exit();
    }

    require_once('connect.php');
    $mysqli = connectToDatabase();

    // Récupérer la commande et le nom de l'utilisateur
    if (!($stmt = $mysqli->prepare(
        'SELECT o.id, o.datetime, o.status, u.username
         FROM orders o
         JOIN users u ON o.user_id = u.id
         WHERE o.qr_token = ?'
    ))) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur base de données']);
        exit();
    }

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

    // Récupérer les articles
    $items = [];
    if ($stmt = $mysqli->prepare(
        'SELECT p.name, p.image, io.quantity, p.price, p.bdlc_price
         FROM item_orders io
         JOIN products p ON io.product_id = p.id
         WHERE io.order_id = ?'
    )) {
        $stmt->bind_param('i', $order['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'name'      => $row['name'],
                'image'     => $row['image'],
                'quantity'  => (int) $row['quantity'],
                'price'     => (float) $row['price'],
                'bdlc_price'=> (float) $row['bdlc_price'],
            ];
        }
        $stmt->close();
    }

    echo json_encode([
        'order_id' => (int) $order['id'],
        'username' => $order['username'],
        'datetime' => $order['datetime'],
        'status'   => (int) $order['status'],
        'items'    => $items,
    ]);
?>
