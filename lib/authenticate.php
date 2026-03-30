<?php
session_start();

require_once('connect.php');

// Check if the data from the login form was submitted
if(isset($_POST['student_number'], $_POST['password'])) {
    $student_number = $_POST['student_number'];
    $password = $_POST['password'];
} else if(isset($_COOKIE["kfet-login"], $_COOKIE["kfet-token"]) && $_COOKIE["kfet-login"] != "" && $_COOKIE["kfet-token"] != "") {
    // Cookie-based auto-login using secure token
    $student_number = $_COOKIE["kfet-login"];
    $cookie_token = $_COOKIE["kfet-token"];

    $connection = connectToDatabase();
    if($connection == FALSE) exit();

    require_once "util.php";
    $student_number = formatStudentNumber($student_number);

    $req = 'SELECT id, username, bdlc_member, auth_level, credit, remember_token FROM users WHERE student_number = ?';
    if ($stmt = $connection->prepare($req)) {
        $stmt->bind_param('s', $student_number);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $username, $bdlc_member, $auth_level, $credit, $remember_token);
            $stmt->fetch();

            if ($remember_token && hash_equals($remember_token, $cookie_token)) {
                session_regenerate_id();
                $_SESSION['logged_in'] = TRUE;
                $_SESSION['username'] = $username;
                $_SESSION['bdlc_member'] = $bdlc_member;
                $_SESSION['auth_level'] = $auth_level;
                $_SESSION['id'] = $id;
                $_SESSION['credit'] = $credit;
                header('Location: ../index.php');
                exit();
            }
        }
    }

    // Invalid token - clear cookies
    setcookie("kfet-login", "", time() - 3600, "/");
    setcookie("kfet-token", "", time() - 3600, "/");
    header('Location: ../login.php?login_status=expired');
    exit();
} else {
    exit('Please fill both the student_number and password fields!');
}

// Connect to the database
$connection = connectToDatabase();
if($connection == FALSE) {
	exit();
}

require_once "util.php";
$student_number = formatStudentNumber($student_number);

// Prepare our SQL, preparing the SQL statement will prevent SQL injection.
$req = 'SELECT id, password, username, bdlc_member, auth_level, credit FROM users WHERE student_number = ?';
if ($stmt = $connection->prepare($req)) {

	$stmt->bind_param('s', $student_number);
	$stmt->execute();
	$stmt->store_result();

	if ($stmt->num_rows > 0) {
		$stmt->bind_result($id, $password_db, $username, $bdlc_member, $auth_level, $credit);
		$stmt->fetch();
		// Account exists, now we verify the password.
		if (password_verify($password, $password_db)) {
			// Verification success! User has logged-in!
			session_regenerate_id();
			$_SESSION['logged_in'] = TRUE;
			$_SESSION['username'] = $username;
			$_SESSION['bdlc_member'] = $bdlc_member;
			$_SESSION['auth_level'] = $auth_level;
			$_SESSION['id'] = $id;
			$_SESSION['credit'] = $credit;

            // Create a secure remember token cookie (not the plain password)
            if(isset($_POST['remember_me'])) {
                $n_days = 30;
                $remember_token = bin2hex(random_bytes(32));
                setcookie("kfet-login", $student_number, time() + (86400 * $n_days), "/");
                setcookie("kfet-token", $remember_token, time() + (86400 * $n_days), "/");

                // Store the token in database
                if($update_stmt = $connection->prepare('UPDATE users SET remember_token = ? WHERE id = ?')) {
                    $update_stmt->bind_param('si', $remember_token, $id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }

			header('Location: ../index.php');
			exit();
		}
	}

	// Incorrect password or student number
	header('Location: ../login.php?login_status=wrong');
	exit();
}

// There has been an error
header('Location: ../login.php?login_status=error');
exit();
?>