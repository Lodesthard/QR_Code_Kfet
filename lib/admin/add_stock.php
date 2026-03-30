<?php
    session_start();

    require_once('../redirect.php');
    auth_level(1);

    require_once('../connect.php');
    $mysqli = connectToDatabase();
    mysqli_report(MYSQLI_REPORT_ERROR);

    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $redirect = ($_SESSION['auth_level'] >= 2) ? '../../administrate_products.php' : '../../manage_stock.php';

    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
    $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : null;

    if(!is_numeric($quantity) || !is_numeric($product_id)) {
        if($isAjax) { http_response_code(400); echo json_encode(['error' => 'invalid']); exit(); }
        header('Location: ' . $redirect . '?stock_status=invalid');
        exit();
    }

    // Update stock and return new value
    if($stmt = $mysqli->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')) {
        $stmt->bind_param('ii', $quantity, $product_id);
        $stmt->execute();
        $stmt->close();

        if($isAjax) {
            // Return the new stock value
            $stmt = $mysqli->prepare('SELECT stock FROM products WHERE id = ?');
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $stmt->bind_result($newStock);
            $stmt->fetch();
            $stmt->close();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'stock' => (int)$newStock]);
            exit();
        }

        header('Location: ' . $redirect . '?stock_status=success');
        exit();
    } else {
        if($isAjax) { http_response_code(500); echo json_encode(['error' => 'db']); exit(); }
        header('Location: ' . $redirect . '?stock_status=error');
        exit();
    }
?>