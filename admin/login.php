<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $username;

            if (!empty($_POST['remember_me'])) {
                $rawToken  = bin2hex(random_bytes(32));
                $hashToken = hash('sha256', $rawToken);
                $expires   = date('Y-m-d H:i:s', strtotime('+30 days'));
                $pdo->prepare("UPDATE `admins` SET `remember_token`=?, `remember_expires`=? WHERE `id`=?")
                    ->execute([$hashToken, $expires, $admin['id']]);
                setcookie('admin_remember', $admin['id'] . ':' . $rawToken, [
                    'expires'  => time() + 86400 * 30,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);
            }

            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — ThirstyBD Admin</title>
    <meta name="theme-color" content="#0A0A0A">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/branding/favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="../assets/branding/favicon-192.png">
    <link rel="apple-touch-icon" href="../assets/branding/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: {
        fontFamily: { sans: ['Inter','ui-sans-serif','system-ui','sans-serif'] },
        colors: {
            brand: { 50:'#FFF1F5',100:'#FFE1EA',200:'#FFC2D4',300:'#FF8FB0',400:'#FF5A8F',500:'#F5106C',600:'#DB1456',700:'#B80E43',800:'#8F0B35',900:'#660826' },
            peach: '#F8A996', cream: { DEFAULT:'#F9EFEA', soft:'#FBF6F2' }, ink: { DEFAULT:'#0A0A0A', soft:'#161616' }
        }
    } } };</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        :focus-visible { outline: 2px solid #DB1456; outline-offset: 2px; }
    </style>
</head>
<body class="min-h-screen bg-cream-soft text-slate-800">
<div class="grid min-h-screen lg:grid-cols-2">

    <!-- Brand panel -->
    <aside class="relative hidden overflow-hidden bg-ink lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-brand-600/35 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-20 h-[28rem] w-[28rem] rounded-full bg-peach/20 blur-3xl"></div>
        <div class="relative">
            <img src="../assets/branding/logo-full.png" alt="ThirstyBD" width="720" height="511" class="h-32 w-auto">
        </div>
        <div class="relative max-w-md">
            <h2 class="text-4xl font-bold leading-tight text-cream">Run your store <span class="bg-gradient-to-r from-peach to-brand-500 bg-clip-text text-transparent">without the clutter.</span></h2>
            <p class="mt-4 text-slate-400">Orders, products, landing pages and courier bookings — all in one calm workspace.</p>
            <ul class="mt-8 space-y-3 text-sm text-slate-300">
                <li class="flex items-center gap-3"><i class="fas fa-circle-check text-brand-400"></i> Live order tracking and status updates</li>
                <li class="flex items-center gap-3"><i class="fas fa-circle-check text-brand-400"></i> One-click landing page publishing</li>
                <li class="flex items-center gap-3"><i class="fas fa-circle-check text-brand-400"></i> Pathao courier integration</li>
            </ul>
        </div>
        <p class="relative text-xs text-slate-500">&copy; <?= date('Y') ?> ThirstyBD</p>
    </aside>

    <!-- Form -->
    <main class="flex items-center justify-center px-6 py-12 sm:px-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 inline-flex rounded-xl bg-ink px-5 py-3 lg:hidden">
                <img src="../assets/branding/logo-wordmark.png" alt="ThirstyBD" width="560" height="112" class="h-8 w-auto">
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Welcome back</h1>
            <p class="mt-1.5 text-sm text-slate-500">Sign in to your admin console.</p>

            <?php if ($error): ?>
                <div role="alert" class="mt-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <i class="fas fa-circle-exclamation mt-0.5"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="mt-8 space-y-5">
                <div>
                    <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
                    <div class="relative">
                        <i class="fas fa-user pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input id="username" type="text" name="username" autocomplete="username" required autofocus
                               class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition hover:border-slate-400 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15"
                               placeholder="Enter your username">
                    </div>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input id="password" type="password" name="password" autocomplete="current-password" required
                               class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-11 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition hover:border-slate-400 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15"
                               placeholder="Enter your password">
                        <button type="button" id="togglePw" aria-label="Show password" class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                            <i class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <label class="flex cursor-pointer select-none items-center gap-2.5">
                    <input type="checkbox" name="remember_me" value="1" class="h-4 w-4 cursor-pointer rounded border-slate-300 accent-brand-600">
                    <span class="text-sm text-slate-600">Keep me signed in for 30 days</span>
                </label>

                <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 transition hover:bg-brand-700 hover:shadow-md active:translate-y-px">
                    Sign in
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-slate-400">Protected area · authorised staff only</p>
        </div>
    </main>
</div>

<script>
document.getElementById('togglePw').addEventListener('click', function () {
    var i = document.getElementById('password');
    var show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    this.firstElementChild.className = show ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
});
</script>
</body>
</html>
