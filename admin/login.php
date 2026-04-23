<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $pdo = get_db_connection();
    $login_result = attempt_login($pdo, $username, $password);
    
    if ($login_result === true) {
        header("Location: index.php");
        exit;
    } elseif ($login_result === 'disabled') {
        $error = "This account has been deactivated. Please contact the administrator.";
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | DecoraTV Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(15, 15, 15, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-[#0a0a0a] text-white flex items-center justify-center min-h-screen p-6">
    <div class="w-full max-w-md">
        <div class="mb-12 text-center">
            <h1 class="text-5xl font-black uppercase tracking-tighter mb-4">DecoraTV</h1>
            <div class="flex justify-center items-center gap-3">
                <div class="h-[3px] w-8 bg-amber-500"></div>
                <p class="text-[16px] uppercase font-black tracking-[0.2em] text-amber-500">Management Studio</p>
            </div>
        </div>

        <div class="glass p-12 rounded-[3rem] shadow-2xl">
            <h2 class="text-2xl font-bold mb-10 uppercase tracking-widest text-gray-400 text-center border-b border-white/5 pb-8">Admin Access</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-900/40 border border-red-500 text-red-100 p-6 rounded-2xl mb-8 text-[16px] flex items-center gap-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="space-y-8">
                    <div>
                        <label class="block text-[14px] font-black uppercase tracking-widest text-gray-500 mb-3 ml-4">Username</label>
                        <input type="text" name="username" required 
                            class="w-full bg-[#1a1a1a] border border-gray-800 rounded-2xl px-8 py-5 text-[16px] text-white placeholder-gray-600 focus:outline-none focus:border-amber-500/50 focus:ring-4 focus:ring-amber-500/10 transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-[14px] font-black uppercase tracking-widest text-gray-500 mb-3 ml-4">Password</label>
                        <input type="password" name="password" required 
                            class="w-full bg-[#1a1a1a] border border-gray-800 rounded-2xl px-8 py-5 text-[16px] text-white placeholder-gray-600 focus:outline-none focus:border-amber-500/50 focus:ring-4 focus:ring-amber-500/10 transition-all">
                    </div>
                </div>

                <div class="pt-8">
                    <button type="submit" 
                        class="w-full bg-amber-600 hover:bg-amber-500 text-white py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-[16px] transition-all shadow-xl shadow-amber-900/20 active:scale-[0.98]">
                        Sign In
                    </button>
                </div>
            </form>
        </div>

        <p class="mt-16 text-center text-gray-600 text-[14px] uppercase font-bold tracking-[0.2em]">
            &copy; 2026 DecoraTV | Private Access
        </p>
    </div>
</body>
</html>
