<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
// initializing variables
$name = "";
$email = "";
$phone = "";
$role = "";
$branch = "";
$errors = array(); 



// connect to the database
$db = mysqli_connect('localhost', 'root', '', 'test2');


// REGISTER USER
if (isset($_POST['reg_user'])) {
    // Receive all input values from the form
    $name = mysqli_real_escape_string($db, $_POST['name']);
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $phone = mysqli_real_escape_string($db, $_POST['phone']);
    $password_1 = mysqli_real_escape_string($db, $_POST['password_1']);
    $password_2 = mysqli_real_escape_string($db, $_POST['password_2']);
    $role = mysqli_real_escape_string($db, $_POST['role']);
    $branch = mysqli_real_escape_string($db, $_POST['branch']);

    // Form validation
    if (empty($name)) { array_push($errors, "Name is required"); }
    if (empty($email)) { array_push($errors, "Email is required"); }
    if (empty($phone)) { array_push($errors, "Phone is required"); }
    if (empty($password_1)) { array_push($errors, "Password is required"); }
    if ($password_1 != $password_2) {
        array_push($errors, "The two passwords do not match");
    }

    // Check if user already exists
    $user_check_query = "SELECT * FROM users WHERE name='$name' OR email='$email' LIMIT 1";
    $result = mysqli_query($db, $user_check_query);
    $user = mysqli_fetch_assoc($result);

    if ($user) { // if user exists
        if ($user['name'] === $name) {
            array_push($errors, "Name already exists");
        }
        if ($user['email'] === $email) {
            array_push($errors, "Email already exists");
        }
    }

    // Register user if no errors
    if (count($errors) == 0) {
        $password = $password_1; // Directly use the password

        $query = "INSERT INTO users (name, email, phone, password, role, branch) 
                  VALUES('$name', '$email', '$phone', '$password', '$role', '$branch')";
        mysqli_query($db, $query);
        $_SESSION['name'] = $name;
        $_SESSION['role'] = $role;
        $_SESSION['branch'] = $branch;
        $_SESSION['success'] = "You are now logged in";
        echo "<script> window.location.assign('login.php'); </script>"; 
        exit;
    }
}

// LOGIN USER
if (isset($_POST['login_user'])) {
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $password = mysqli_real_escape_string($db, $_POST['password']);

    if (empty($email)) {
        array_push($errors, "Email is required");
    }
    if (empty($password)) {
        array_push($errors, "Password is required");
    }

    if (count($errors) == 0) {
        $query = "SELECT * FROM users WHERE email='$email'";
        $results = mysqli_query($db, $query);
        if (mysqli_num_rows($results) == 1) {
            $user = mysqli_fetch_assoc($results);
            if ($user['password'] === $password) { // Direct comparison
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role'];
                $_SESSION['branch'] = $user['branch'];
               $_SESSION['modules'] = [];

if (!empty($user['access_modules'])) {

    $items = explode(',', $user['access_modules']);

    foreach ($items as $item) {

        $item = trim($item);

        if (strpos($item, ':') !== false) {

            list($module, $sub) = explode(':', $item);

            $module = trim($module);
            $sub = trim($sub);

            $_SESSION['modules'][$module][] = $sub;

        } else {

            // full module access
            $_SESSION['modules'][trim($item)] = [];
        }
    }
}

                // ✅ ADD THIS LINE HERE (after login success)
                mysqli_query($db, "UPDATE users SET status='online', last_active=NOW() WHERE email='$email'");

                $_SESSION['success'] = "You are now logged in";
                echo "<script> window.location.assign('homepage.php'); </script>"; 
            } else {
                array_push($errors, "Wrong email/password");
            }
        } else {
            array_push($errors, "Wrong email/password");
        }
    }
}

// Close the database connection
mysqli_close($db);

?>
