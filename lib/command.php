<?php

    // Get the user informations
    session_start();

    // Connect to the database
    require_once('connect.php');
    $mysqli = connectToDatabase();
    mysqli_report(MYSQLI_REPORT_ERROR);

    // Check if the user is logged-on
    if(!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ../login.php');
        exit();
    }

    $user_id = $_SESSION['id'];

    // Go through each ids and add a ? to the request
    $req = "SELECT id, price, bdlc_price FROM products WHERE id IN (";
    $product_ids = [];
    $product_quantities = [];
    $i = 0;

    foreach ($_POST as $id => $quantity) {
        if(!is_numeric($quantity) || !is_numeric($id)) {
            header('Location: ../index.php?command_status=invalid');
            exit();
        }

        $req .= ($i++ == 0) ? "?" : ", ?";
        $product_ids[] = intval($id);
        $product_quantities[] = intval($quantity);
    }

    $req .= ")";
    $parameterTypes = str_repeat("i", count($product_ids));

    // Check if the command is empty
    if(count($product_ids) == 0) {
        header('Location: ../index.php?command_status=empty_order');
        exit();
    }

    // Calculate total price and check stock
    $total_price = 0;
    $i = 0;
    $product_prices = [];

    if($stmt = $mysqli->prepare($req)) {
        $stmt->bind_param($parameterTypes, ...$product_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()) {
            // Find the index of this product in our arrays
            $idx = array_search($row['id'], $product_ids);
            if($idx === false) continue;

            $price = ($_SESSION['bdlc_member']) ? $row['bdlc_price'] : $row['price'];
            $total_price += $price * $product_quantities[$idx];
            $product_prices[$idx] = $price;
        }
        $stmt->close();
    } else {
        header('Location: ../index.php?command_status=database_error_1');
        exit();
    }

    // Get the user credits
    if($stmt = $mysqli->prepare("SELECT credit FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($credit);
        $stmt->fetch();
        $stmt->close();
    } else {
        header('Location: ../index.php?command_status=database_error_2');
        exit();
    }

    // Check if the user has enough money
    if($credit < $total_price) {
        header('Location: ../index.php?command_status=not_enough_money');
        exit();
    }

    $is_staff = ($_SESSION['auth_level'] >= 1);

    // Baristas/admins : débit immédiat, pas de QR code
    if ($is_staff) {
        // Débiter le compte
        if($stmt = $mysqli->prepare("UPDATE users SET credit = credit - ? WHERE id = ?")) {
            $stmt->bind_param("di", $total_price, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            header('Location: ../index.php?command_status=database_error_3');
            exit();
        }
    }

    // Générer un token et créer la commande
    $qr_token = bin2hex(random_bytes(32));
    $datetime = date('Y-m-d H:i:s');
    $status = $is_staff ? 2 : 0; // Servie directement pour le staff, en attente pour les clients

    if($stmt = $mysqli->prepare('INSERT INTO orders (user_id, datetime, qr_token, status) VALUES (?, ?, ?, ?)')) {
        $stmt->bind_param("issi", $user_id, $datetime, $qr_token, $status);
        $stmt->execute();
        $order_id = $mysqli->insert_id;
        $stmt->close();
    } else {
        header('Location: ../index.php?command_status=database_error_4');
        exit();
    }

    // Créer les item_orders
    if($stmt = $mysqli->prepare('INSERT INTO item_orders (order_id, product_id, quantity) VALUES (?, ?, ?)')) {
        for($i = 0; $i < count($product_ids); $i++) {
            $stmt->bind_param('iii', $order_id, $product_ids[$i], $product_quantities[$i]);
            $stmt->execute();
        }
        $stmt->close();
    } else {
        header('Location: ../index.php?command_status=database_error_5');
        exit();
    }

    // Décrémenter le stock immédiatement pour le staff
    if ($is_staff) {
        if($stmt = $mysqli->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')) {
            for($i = 0; $i < count($product_ids); $i++) {
                $stmt->bind_param('ii', $product_quantities[$i], $product_ids[$i]);
                $stmt->execute();
            }
            $stmt->close();
        }
    }

    // Staff : retour à l'accueil, Client : page QR code
    if ($is_staff) {
        header('Location: ../index.php?command_status=success_order');
    } else {
        header('Location: ../order_confirmation.php?token=' . urlencode($qr_token));
    }
    exit();
?>