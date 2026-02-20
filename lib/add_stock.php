<?php
    session_start();

    require_once('connect.php');
    $mysqli = connectToDatabase();
    mysqli_report(MYSQLI_REPORT_ERROR);

    // Check if the user is logged-on
    if(!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        // Return to loggin screen
        header('Location: ../login.php');
    }

    $user_id = $_SESSION['id']; // Get the user id

    // Check if the user is an administrator
    if ($_SESSION['auth_level'] < 2) {
        // Return to loggin screen
        header('Location: ../index.php');
    }

    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Check if the quantity and product id are numbers
    if(!is_numeric($quantity) || !is_numeric($product_id)) {
        header('Location: ../index.php?add_stock_status=invalid');
        exit();
    }

    // Check if the product exists
    $req = 'SELECT id FROM products WHERE id = ?';
    if($stmt = $mysqli->prepare($req)) {
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows == 0) {
            // Product does not exist
            header('Location: ../index.php?add_stock_status=invalid');
            exit();
        }
    } else {
        // Database error
        header('Location: ../index.php?add_stock_status=database_error_1');
        exit();
    }

    // Add the stock
    $req = 'UPDATE products SET stock = stock + ? WHERE id = ?';
    if($stmt = $mysqli->prepare($req)) {
        $stmt->bind_param('ii', $quantity, $product_id);
        $stmt->execute();
        header('Location: ../index.php?add_stock_status=success');
        exit();
    } else {
        // Database error
        header('Location: ../index.php?add_stock_status=database_error_2');
        exit();
    }

    // Close the connection
    $mysqli->close();

    // Return to the index page
    header('Location: ../index.php');
    exit();
?>