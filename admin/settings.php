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
        
        /* Custom Scrollbar for Sidebar */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body class="h-[100dvh] overflow-hidden text-gray-800 relative flex bg-[#f8f9fa]">

    <!-- Mobile Header -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-20 bg-white border-b border-gray-100 flex items-center justify-between px-6 z-[60]">
        <h1 class="text-xl font-black uppercase tracking-tighter text-[#0f0f0f]">DecoraTV <span class="text-amber-500">Admin</span></h1>
        <button onclick="toggleMobileMenu()" class="w-12 h-12 flex items-center justify-center bg-[#0f0f0f] text-white rounded-xl shadow-lg active:scale-95 transition-transform">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- Mobile Overlay -->
    <div id="mobileOverlay" onclick="toggleMobileMenu()" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[70] hidden opacity-0 transition-opacity duration-300"></div>

    <!-- Sidebar -->
    <aside id="sidebarMenu" class="sidebar w-72 fixed md:static inset-y-0 left-0 z-[80] -translate-x-full md:translate-x-0 transition-transform duration-300 flex flex-col bg-[#0f0f0f] h-[100dvh] md:h-auto">
        <!-- Sidebar Header -->
        <div class="p-10 flex justify-between items-center flex-none">
            <div>
                <h1 class="text-white text-3xl font-black uppercase tracking-tighter">DecoraTV</h1>
                <p class="text-[16px] text-amber-500 font-black uppercase tracking-[0.2em] mt-1">Management Studio</p>
            </div>
            <button onclick="toggleMobileMenu()" class="md:hidden text-gray-500 hover:text-white p-2">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
        </div>

        <!-- Scrollable Navigation Area -->
        <div class="flex-1 overflow-y-auto custom-scrollbar px-4 pb-10">
            <nav class="space-y-1 mt-2">
            <a href="index.php" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-chart-line w-5"></i> Dashboard
            </a>
            <p class="text-[11px] text-gray-600 font-black uppercase tracking-widest px-6 pt-6 pb-2">Inventory</p>
            <a href="inventory.php?type=frame" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-border-all w-5"></i> Frames
            </a>
            <a href="inventory.php?type=liner" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-maximize w-5"></i> Liners
            </a>
            <a href="inventory.php?type=art" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-image w-5"></i> Arts
            </a>
            <p class="text-[11px] text-gray-600 font-black uppercase tracking-widest px-6 pt-6 pb-2">System</p>
            <a href="inquiries.php" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-rectangle-list w-5"></i> Inquiries
            </a>
            <a href="users.php" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-users w-5"></i> Users
            </a>
            <a href="settings.php" class="sidebar-link active flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-envelope w-5"></i> SMTP Config
            </a>
            <a href="database.php" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-database w-5"></i> Database
            </a>
            <div class="pt-6 border-t border-white/5 mt-6">
                <a href="logout.php" class="text-gray-500 hover:text-red-400 text-[14px] font-black uppercase tracking-widest transition-colors flex items-center gap-3 px-6 py-4">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </nav>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto custom-scrollbar mt-20 md:mt-0 p-6 md:p-12">
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
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">SMTP Encryption</label>
                            <select name="smtp_encryption" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px] appearance-none focus:outline-none focus:border-amber-500/50">
                                <option value="tls" <?php echo ($s['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Recommended for Port 587)</option>
                                <option value="ssl" <?php echo ($s['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                <option value="none" <?php echo ($s['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
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
                    <p class="text-white/50 text-[14px] font-medium leading-relaxed mb-6">Saving these settings will immediately apply them to the next quote generated by the simulator.</p>
                    <div class="grid grid-cols-1 gap-4">
                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-[16px] shadow-xl transition-all shadow-amber-900/20">
                            Update Settings
                        </button>
                        <button type="button" onclick="testConnection(this)" class="w-full bg-white/5 hover:bg-white/10 text-white border border-white/10 py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-[14px] transition-all">
                            Test Connection
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        function testConnection(btn) {
            if (!confirm('This will send a test email to ' + document.querySelector('input[name="admin_email"]').value + '. Save settings first?')) return;
            
            const originalText = btn.innerText;
            btn.innerText = 'Testing...';
            btn.disabled = true;

            fetch('../api/test_mail.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                })
                .catch(err => alert('Error testing connection'))
                .finally(() => {
                    btn.innerText = originalText;
                    btn.disabled = false;
                });
        }
    </script>
    <script>
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebarMenu');
            const overlay = document.getElementById('mobileOverlay');
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            
            if (!isOpen) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.add('opacity-100'), 10);
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('opacity-100');
                setTimeout(() => overlay.classList.add('hidden'), 300);
                document.body.style.overflow = '';
            }
        }

        // Auto-close on link click
        document.querySelectorAll('#sidebarMenu a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) toggleMobileMenu();
            });
        });
    </script>
</body>
</html>
