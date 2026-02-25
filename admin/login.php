<?php
session_start();
require_once __DIR__ . '/../app/bootstrap.php';

$userFile = APP_ROOT . '/storage/users.json';
$users = json_decode(file_get_contents($userFile), true) ?? [];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($users[$username]) &&
        $password === $users[$username]['password']) {

        $_SESSION['user'] = [
            'username' => $username,
            'role' => $users[$username]['role']
        ];

        header("Location: /admin/ads.php");
        exit;
    }

    $error = "Invalid username or password";
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<style>
body{font-family:system-ui;margin:50px}
input{display:block;margin:10px 0;padding:8px;width:250px}
button{padding:8px 12px}
.error{color:red}
</style>
</head>
<body>

<h2>Admin Login</h2>

<?php if($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
<input name="username" placeholder="Username">
<input name="password" type="password" placeholder="Password">
<button type="submit">Login</button>
</form>

</body>
</html>
