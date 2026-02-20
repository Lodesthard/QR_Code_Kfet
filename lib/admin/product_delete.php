<?php
    session_start();

    require_once('../redirect.php');
    auth_level(2);
    
    $databaseError = false;

    require_once('../connect.php');
    require_once('../util.php');
    require_once('../file.php');

    $mysqli = connectToDatabase();

    if(isset($_GET['id']) && !empty($_GET['id'])) {

        // Get the name of the image
        $data = select($mysqli, 'products', array('image'), $_GET['id']);
        $filenameToRemove = $data[0]['image'];

        // Remove the image
        removeFile('/var/www/html/res/images/products/', $filenameToRemove);

        // Delete from the database
        if($mysqli->query('DELETE FROM products WHERE id=' . htmlspecialchars($_GET['id']))) {
            header('Location: ../../administrate_products.php?delete_status=success');
        } else {
            header('Location: ../../administrate_products.php?delete_status=error');
        }
    }
?>