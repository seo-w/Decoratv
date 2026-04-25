<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/db_manager.php';
require_login();

$pdo = get_db_connection();
$dbm = new DBManager();
$message = isset($_GET['success']) ? 'Settings applied successfully.' : '';
$error = '';

// AJAX Connection Test
if (isset($_GET['ajax_test'])) {
    header('Content-Type: application/json');
    $driver = $_GET['driver'] ?? 'sqlite';
    $host = $_GET['host'] ?? '';
    $name = $_GET['name'] ?? '';
    $user = $_GET['user'] ?? '';
    $pass = $_GET['pass'] ?? '';
    try {
        if ($driver === 'mysql') {
            $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
            new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
        }
        echo json_encode(['success' => true, 'message' => "Connection Successful!"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 0. Handle Connection Config
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_config'])) {
    $new_driver = $_POST['driver'] ?? 'sqlite';
    $new_host = $_POST['host'] ?? 'localhost';
    $new_name = $_POST['name'] ?? 'decoratv';
    $new_user = $_POST['user'] ?? 'root';
    $new_pass = $_POST['pass'] ?? '';

    try {
        // Test Connection if switching to MySQL
        if ($new_driver === 'mysql') {
            $dsn = "mysql:host=$new_host;dbname=$new_name;charset=utf8mb4";
            new PDO($dsn, $new_user, $new_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
        }
        
        $config_content = "<?php\n"
            . "/**\n * Global Configuration for DecoraTV (Generated via UI)\n */\n\n"
            . "define('DB_DRIVER', '$new_driver');\n\n"
            . "define('DB_HOST', '$new_host');\n"
            . "define('DB_NAME', '$new_name');\n"
            . "define('DB_USER', '$new_user');\n"
            . "define('DB_PASS', '$new_pass');\n\n"
            . "define('DB_SQLITE_PATH', __DIR__ . '/../database/decoratv.sqlite');\n";
            
        if (file_put_contents(__DIR__ . '/../config/config.php', $config_content)) {
            header("Location: database.php?success=1");
            exit;
        } else {
            $error = "Failed to write config file. Check permissions.";
        }
    } catch (PDOException $e) {
        $error = "Connection Test Failed: " . $e->getMessage();
    }
}

// 1. Handle Setup/Recovery Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
    $action = $_POST['db_action'];
    
    if ($action === 'smart_init') {
        if ($dbm->initialize(false)) {
            $message = "Database structures fixed successfully. Missing tables were created.";
        } else {
            $error = "Failed to initialize database structures.";
        }
    } 
    elseif ($action === 'factory_reset') {
        if ($dbm->initialize(true)) {
            $message = "Factory Reset Completed. Data wiped and fresh tables created. User 'admin' restored.";
        } else {
            $error = "Failed to perform factory reset.";
        }
    }
    elseif ($action === 'migrate_sqlite') {
        $res = $dbm->migrate_from_sqlite();
        if (isset($res['success'])) {
            $message = "Migration Successful! Records moved: " . implode(', ', array_map(function($k, $v) { return "$k: $v"; }, array_keys($res['counts']), $res['counts']));
        } else {
            $error = $res['error'];
        }
    }
}

// 2. Existing Export Logic
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $file = DB_SQLITE_PATH; // Use the path from config
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="decoratv_backup_' . date('Y-m-d_H-i') . '.sqlite"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        $error = "Database file not found.";
    }
}

// 3. Existing SQLite Import Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['db_file'])) {
    $uploadedFile = $_FILES['db_file'];
    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        if ($ext === 'sqlite') {
            $dest = DB_SQLITE_PATH;
            if (file_exists($dest)) copy($dest, $dest . '.bak_' . date('Ymd_His'));
            if (move_uploaded_file($uploadedFile['tmp_name'], $dest)) {
                $message = "Database imported successfully. (Note: Only applies to SQLite mode)";
            } else {
                $error = "Failed to replace the database file.";
            }
        } else {
            $error = "Invalid file type. Please upload a .sqlite file.";
        }
    }
}

// Diagnostics
$missing = $dbm->get_missing_tables();
$sqlite_exists = file_exists(DB_SQLITE_PATH);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management | DecoraTV Admin</title>
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
        .card { border-radius: 2rem; border: 1px solid #edf2f7; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
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
            <a href="settings.php" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-envelope w-5"></i> SMTP Config
            </a>
            <a href="database.php" class="sidebar-link active flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest">
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
            <h2 class="text-4xl font-black uppercase tracking-tighter">Database Management</h2>
            <p class="text-[16px] text-gray-500 font-bold uppercase tracking-widest mt-3">Health, Migration and Backups</p>
        </header>

        <!-- Notification Zone -->
        <?php if ($message): ?>
            <div class="mb-10 p-8 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-[2.5rem] text-[18px] font-black uppercase tracking-widest flex items-center gap-4 shadow-sm">
                <i class="fa-solid fa-circle-check text-2xl"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-10 p-8 bg-red-50 text-red-600 border border-red-100 rounded-[2.5rem] text-[18px] font-black uppercase tracking-widest flex items-center gap-4 shadow-sm">
                <i class="fa-solid fa-circle-exclamation text-2xl"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Critical Status Banner -->
        <?php if (!empty($missing)): ?>
            <div class="mb-16 p-10 bg-amber-50 border-2 border-amber-200 rounded-[2.5rem] flex flex-col md:flex-row items-center justify-between gap-8 shadow-xl shadow-amber-900/5">
                <div class="flex items-center gap-8">
                    <div class="w-20 h-20 bg-amber-200 text-amber-800 rounded-3xl flex items-center justify-center text-3xl shrink-0 animate-pulse">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black uppercase tracking-tighter text-amber-900">Database Incomplete</h3>
                        <p class="text-[16px] text-amber-800/70 font-bold uppercase tracking-widest mt-2">Missing Tables: <?php echo implode(', ', $missing); ?></p>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="db_action" value="smart_init">
                    <button type="submit" class="bg-amber-600 text-white px-10 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest hover:bg-amber-700 transition-all shadow-lg shadow-amber-600/20">
                        Repair Now
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Connection Configuration Form -->
        <div class="bg-white card p-10 mb-10 border-amber-100/50 shadow-xl shadow-amber-900/5">
            <h3 class="text-2xl font-black uppercase tracking-tighter mb-8 flex items-center gap-4">
                <i class="fa-solid fa-gear text-amber-500"></i> Engine Configuration
            </h3>
            
            <form method="POST" class="space-y-8">
                <input type="hidden" name="db_config" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3">Database Engine</label>
                        <select name="driver" id="db-driver-select" onchange="toggleMysqlFields()" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-[16px] font-bold focus:outline-none focus:border-amber-500">
                            <option value="sqlite" <?php echo DB_DRIVER === 'sqlite' ? 'selected' : ''; ?>>SQLite (Local File)</option>
                            <option value="mysql" <?php echo DB_DRIVER === 'mysql' ? 'selected' : ''; ?>>MySQL / MariaDB</option>
                        </select>
                    </div>
                    
                    <div class="mysql-field">
                        <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3">Host Address</label>
                        <input type="text" name="host" value="<?php echo htmlspecialchars(DB_HOST); ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-[16px] font-medium focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div class="mysql-field">
                        <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3">Database Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars(DB_NAME); ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-[16px] font-medium focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mysql-field">
                    <div>
                        <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3">Database User</label>
                        <input type="text" name="user" value="<?php echo htmlspecialchars(DB_USER); ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-[16px] font-medium focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3">Password</label>
                        <input type="password" name="pass" value="<?php echo htmlspecialchars(DB_PASS); ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-[16px] font-medium focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div class="pt-4 flex flex-wrap items-center gap-6">
                    <button type="button" onclick="testConnection()" id="test-btn" class="bg-gray-100 text-gray-700 px-10 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest hover:bg-gray-200 transition-all border border-gray-200 flex items-center gap-3">
                        <i class="fa-solid fa-vial"></i> Test Connection
                    </button>
                    <button type="submit" class="bg-gray-900 text-white px-12 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest hover:bg-amber-600 transition-all shadow-lg flex items-center gap-3">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Apply & Restart Engine
                    </button>
                    <p class="text-[12px] text-gray-400 font-bold uppercase tracking-widest max-w-sm">Note: If you switch to MySQL, make sure the database exists and credentials are correct.</p>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mb-16">
            <!-- Mode Status Card -->
            <div class="bg-gray-900 card p-10 flex flex-col justify-between text-white border-none shadow-2xl">
                <div>
                    <span class="bg-amber-500 text-white px-4 py-1.5 rounded-full text-[12px] font-black uppercase tracking-[0.2em] inline-block mb-8">Active Engine</span>
                    <p class="text-[48px] font-black uppercase tracking-tighter leading-tight mb-4"><?php echo strtoupper(DB_DRIVER); ?></p>
                    <div class="space-y-4">
                        <p class="text-gray-400 text-[14px] font-black uppercase tracking-widest flex items-center gap-3">
                            <i class="fa-solid fa-circle <?php echo empty($missing)?'text-emerald-500':'text-amber-500'; ?> text-[8px]"></i> 
                            Status: <?php echo empty($missing)?'Healthy':'Needs Setup'; ?>
                        </p>
                        <p class="text-gray-400 text-[14px] font-black uppercase tracking-widest">Host: <?php echo DB_DRIVER==='mysql'?DB_HOST:'Local File'; ?></p>
                    </div>
                </div>
                
                <?php if (DB_DRIVER === 'mysql' && $sqlite_exists): ?>
                    <form method="POST" class="mt-12 pt-12 border-t border-white/5">
                        <input type="hidden" name="db_action" value="migrate_sqlite">
                        <button type="submit" onclick="return confirm('MIGRATE FROM SQLITE: This will copy ALL data from decoratv.sqlite to your MySQL DB. Existing MySQL data in those tables will be replaced. Continue?')" 
                                class="w-full bg-blue-600 hover:bg-blue-500 text-white py-6 rounded-2xl font-black uppercase tracking-widest text-[16px] transition-all flex items-center justify-center gap-3 shadow-xl shadow-blue-900/40">
                            <i class="fa-solid fa-shuffle"></i> Import SQLite Data
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Management Card -->
            <div class="bg-white card p-10 lg:col-span-2">
                <h3 class="text-2xl font-black uppercase tracking-tighter mb-10 flex items-center gap-4">
                    <i class="fa-solid fa-terminal text-gray-300"></i> Setup & Recovery Tools
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Tool 1: Initialization -->
                    <div class="p-8 border border-gray-100 rounded-3xl hover:border-amber-200 transition-colors group">
                        <h4 class="text-[18px] font-black text-gray-800 uppercase mb-3">Safe Initialization</h4>
                        <p class="text-[14px] text-gray-500 mb-8 leading-relaxed">Creates missing tables and the admin user if they don't exist. Your existing data stays safe.</p>
                        <form method="POST">
                            <input type="hidden" name="db_action" value="smart_init">
                            <button type="submit" class="w-full bg-gray-50 text-gray-600 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest group-hover:bg-amber-50 group-hover:text-amber-600 transition-all">
                                Run Smart Init
                            </button>
                        </form>
                    </div>

                    <!-- Tool 2: Factory Reset -->
                    <div class="p-8 border border-gray-100 rounded-3xl hover:border-red-100 transition-colors group">
                        <h4 class="text-[18px] font-black text-gray-800 uppercase mb-3">Factory Reset</h4>
                        <p class="text-[14px] text-gray-500 mb-8 leading-relaxed">Wipes all database tables and creates fresh ones. <b>Uploaded photos are kept safe.</b></p>
                        <form method="POST">
                            <input type="hidden" name="db_action" value="factory_reset">
                            <button type="submit" onclick="return confirm('DANGER: This will delete ALL users, quotes, settings and inventory. There is NO UNDO. Continue?')" 
                                    class="w-full bg-gray-50 text-gray-400 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest group-hover:bg-red-50 group-hover:text-red-500 transition-all">
                                Wipe & Reset DB
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <!-- Physical Backups (SQLite Only) -->
            <?php if (DB_DRIVER === 'sqlite'): ?>
                <div class="bg-white card p-10 border-blue-50">
                    <h3 class="text-2xl font-black uppercase tracking-tighter mb-8 flex items-center gap-4">
                        <i class="fa-solid fa-file-export text-blue-200"></i> Local File Backup
                    </h3>
                    <div class="flex flex-col md:flex-row gap-6">
                        <a href="?action=download" class="flex-1 bg-blue-50 text-blue-600 py-6 rounded-2xl text-[16px] font-black uppercase tracking-widest hover:bg-blue-600 hover:text-white transition-all text-center">
                            Download .sqlite
                        </a>
                        <div class="flex-1">
                            <form method="POST" enctype="multipart/form-data">
                                <label class="block w-full bg-gray-50 text-gray-500 border border-dashed border-gray-200 py-6 rounded-2xl text-[14px] font-black uppercase tracking-widest text-center cursor-pointer hover:border-blue-300">
                                    Restore from File
                                    <input type="file" name="db_file" accept=".sqlite" class="hidden" onchange="this.form.submit()">
                                </label>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Maintenance Info -->
            <div class="bg-emerald-900/5 card p-10 border-emerald-100 flex items-center justify-between <?php echo DB_DRIVER !== 'sqlite' ? 'lg:col-span-2' : ''; ?>">
                <div class="flex items-center gap-6">
                    <i class="fa-solid fa-shield-halved text-4xl text-emerald-500"></i>
                    <div>
                        <h4 class="text-[16px] font-black text-emerald-900 uppercase">System Integrity</h4>
                        <p class="text-[14px] text-emerald-700/60 font-medium">Automatic safety backups were created during major operations.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        function toggleMysqlFields() {
            const driver = document.getElementById('db-driver-select').value;
            const fields = document.querySelectorAll('.mysql-field');
            fields.forEach(f => {
                if (driver === 'sqlite') {
                    f.style.opacity = '0.3';
                    f.style.pointerEvents = 'none';
                } else {
                    f.style.opacity = '1';
                    f.style.pointerEvents = 'auto';
                }
            });
        }

        async function testConnection() {
            const btn = document.getElementById('test-btn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch animate-spin"></i> Testing...';

            const driver = document.getElementById('db-driver-select').value;
            const form = btn.closest('form');
            const params = new URLSearchParams({
                ajax_test: 1,
                driver: driver,
                host: form.host.value,
                name: form.name.value,
                user: form.user.value,
                pass: form.pass.value
            });

            try {
                const response = await fetch(`database.php?${params.toString()}`);
                const data = await response.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Connection Failed:\n' + data.message);
                }
            } catch (err) {
                alert('❌ Network error while testing connection.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        // Run on load
        toggleMysqlFields();

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
