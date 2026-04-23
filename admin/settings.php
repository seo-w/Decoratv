<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$pdo = get_db_connection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configs = [
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => $_POST['smtp_port'] ?? '587',
        'smtp_user' => $_POST['smtp_user'] ?? '',
        'smtp_pass' => $_POST['smtp_pass'] ?? '',
        'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
        'smtp_from_name' => $_POST['smtp_from_name'] ?? 'DecoraTV',
        'admin_email' => $_POST['admin_email'] ?? '',
    ];

    foreach ($configs as $key => $value) {
        if (DB_DRIVER === 'mysql') {
            $sql = "INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)";
        } else {
            $sql = "INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$key, $value]);
    }
    $message = "Settings updated successfully.";
}

// Load current settings
$settingsRaw = $pdo->query("SELECT * FROM settings")->fetchAll();
$s = [];
foreach ($settingsRaw as $row) {
    $s[$row['key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | DecoraTV Admin</title>
    <link rel="icon" type="image/png" href="../assets/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #0f0f0f; }
        .sidebar-link { transition: all 0.3s; color: #6c757d; }
        .sidebar-link:hover, .sidebar-link.active { color: #fff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active { border-right: 4px solid #f59e0b; }
        .card { border-radius: 2rem; border: 1px solid #edf2f7; }
    </style>
</head>
<body class="flex min-h-screen text-gray-800">

    <aside class="sidebar w-80 flex-shrink-0 flex flex-col hidden md:flex text-white">
        <!-- Sidebar Content -->
        <div class="p-10">
            <h1 class="text-white text-3xl font-black uppercase tracking-tighter">DecoraTV</h1>
            <p class="text-[16px] text-amber-500 font-black uppercase tracking-[0.2em] mt-1">Management Studio</p>
        </div>
        <nav class="flex-1 mt-8 px-6 space-y-3">
            <a href="index.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-chart-line w-6"></i> Dashboard
            </a>
            <p class="text-[14px] text-gray-600 font-bold uppercase tracking-widest px-8 pt-8 pb-3">Inventory</p>
            <a href="inventory.php?type=frame" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-border-all w-6"></i> Frames
            </a>
            <a href="inventory.php?type=liner" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-maximize w-6"></i> Liners
            </a>
            <a href="inventory.php?type=art" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-image w-6"></i> Arts
            </a>
            <p class="text-[14px] text-gray-600 font-bold uppercase tracking-widest px-8 pt-8 pb-3">System</p>
            <a href="inquiries.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-rectangle-list w-6"></i> Inquiries
            </a>
            <a href="users.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-users w-6"></i> Users
            </a>
            <a href="settings.php" class="sidebar-link active flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-envelope w-6"></i> SMTP Config
            </a>
            <a href="database.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-database w-6"></i> Database
            </a>
            <div class="pt-12 border-t border-white/5 mt-10">
                <a href="logout.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-600">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-12 overflow-y-auto">
        <header class="mb-16">
            <h2 class="text-4xl font-black uppercase tracking-tighter">System Settings</h2>
            <p class="text-[16px] text-gray-400 font-bold uppercase tracking-widest mt-3">Configure SMTP for Quote Delivery</p>
        </header>

        <?php if ($message): ?>
            <div class="mb-10 p-6 bg-emerald-100 text-emerald-600 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- SMTP Section -->
                <div class="bg-white card p-10 md:col-span-2">
                    <h3 class="text-[16px] font-black uppercase tracking-widest text-amber-600 mb-10 flex items-center gap-4">
                        <i class="fa-solid fa-server"></i> Mail Server Configuration
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="md:col-span-2">
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo $s['smtp_host'] ?? ''; ?>" placeholder="smtp.yourserver.com" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500/50">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">SMTP Port</label>
                            <input type="text" name="smtp_port" value="<?php echo $s['smtp_port'] ?? '587'; ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px]">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">SMTP User</label>
                            <input type="text" name="smtp_user" value="<?php echo $s['smtp_user'] ?? ''; ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px]">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">SMTP Password</label>
                            <input type="password" name="smtp_pass" value="<?php echo $s['smtp_pass'] ?? ''; ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px]">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">From Email</label>
                            <input type="email" name="smtp_from_email" value="<?php echo $s['smtp_from_email'] ?? ''; ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px]">
                        </div>
                    </div>
                </div>

                <!-- Notifications Section -->
                <div class="bg-white card p-10">
                    <h3 class="text-[16px] font-black uppercase tracking-widest text-gray-500 mb-10">Admin Notifications</h3>
                    <div class="space-y-8">
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">Receiver Email</label>
                            <input type="email" name="admin_email" value="<?php echo $s['admin_email'] ?? ''; ?>" placeholder="quotes@decoratv.com" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px]">
                            <p class="text-[12px] text-gray-400 mt-3 ml-1">Where you will receive the quote alerts.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-900 border border-gray-800 p-10 rounded-[2.5rem] flex flex-col justify-center shadow-xl">
                    <p class="text-white/50 text-[14px] font-medium leading-relaxed mb-10">Saving these settings will immediately apply them to the next quote generated by the simulator.</p>
                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-[16px] shadow-xl transition-all shadow-amber-900/20">
                        Update Settings
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
