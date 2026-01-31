<?php
session_start();
require_once __DIR__ . '/../src/Database.php';

require_once __DIR__ . '/../src/Captcha.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $captcha = $_POST['captcha'];

    if (!Captcha::verify($captcha)) {
        $error = "Invalid Captcha. Please try again.";
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            // Clear captcha after success
            unset($_SESSION['captcha']);
            header("Location: settings.php");
            exit;
        } else {
            $error = "Invalid credentials";
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="card shadow-sm" style="width: 400px;">
        <div class="card-body">
            <h4 class="card-title text-center mb-4">Login</h4>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Security Code</label>
                    <div class="d-flex align-items-center mb-2">
                        <img src="captcha_image.php" alt="Captcha" class="border me-2" style="height:40px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.querySelector('img[alt=Captcha]').src='captcha_image.php?'+Math.random()">Refresh</button>
                    </div>
                    <input type="text" name="captcha" class="form-control" placeholder="Enter code" required autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <div class="text-center mt-3">
                <a href="register.php" class="text-decoration-none">Create an account</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
