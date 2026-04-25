<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$pdo = get_db_connection();
$message = '';
$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);

        if (empty($username) || empty($password)) {
            $error = "Username and password are required.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Check if exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Username already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $hash, $full_name])) {
                    $message = "User created successfully.";
                } else {
                    $error = "Failed to create user.";
                }
            }
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['user_id'];
        if ($id === $_SESSION['user_id']) {
            $error = "You cannot delete yourself.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = "User deleted successfully.";
            } else {
                $error = "Failed to delete user.";
            }
        }
    }

    if ($_POST['action'] === 'toggle_status') {
        $id = (int)$_POST['user_id'];
        $new_status = (int)$_POST['status'];
        if ($id === $_SESSION['user_id']) {
            $error = "You cannot deactivate your own account.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $id])) {
                $message = "User status updated.";
            }
        }
    }

    if ($_POST['action'] === 'reset_password') {
        $id = (int)$_POST['user_id'];
        $new_pass = $_POST['new_password'];
        if (strlen($new_pass) < 8) {
            $error = "New password must be at least 8 characters long.";
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$hash, $id])) {
                $message = "Password updated successfully.";
            }
        }
    }
}

// Get all users
$stmt = $pdo->query("SELECT id, username, full_name, is_active FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | DecoraTV Admin</title>
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
            <a href="inquiries.php" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-rectangle-list w-5"></i> Inquiries
            </a>
            <a href="users.php" class="sidebar-link active flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-users w-5"></i> Users
            </a>
            <a href="settings.php" class="sidebar-link flex items-center gap-3 px-6 py-4 rounded-xl text-[14px] font-black uppercase tracking-widest text-gray-400">
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
            <h2 class="text-4xl font-black uppercase tracking-tighter">User Management</h2>
            <p class="text-[16px] text-gray-500 font-bold uppercase tracking-widest mt-3">Control administrative access</p>
        </header>

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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Add User Form -->
            <div class="lg:col-span-1">
                <div class="bg-white card p-10 border-amber-100 shadow-xl shadow-amber-900/5 h-fit sticky top-10">
                    <h3 class="text-2xl font-black uppercase tracking-tighter mb-8">Add New Admin</h3>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3">Full Name</label>
                            <input type="text" name="full_name" required class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-[16px] font-medium focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3">Username</label>
                            <input type="text" name="username" required class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-[16px] font-medium focus:outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3">Initial Password</label>
                            <input type="password" name="password" required class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-6 py-4 text-[16px] font-medium focus:outline-none focus:border-amber-500">
                        </div>
                        <button type="submit" class="w-full bg-gray-900 text-white py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest hover:bg-amber-600 transition-all shadow-lg flex items-center justify-center gap-3">
                            <i class="fa-solid fa-user-plus"></i> Create Account
                        </button>
                    </form>
                </div>
            </div>

            <!-- User List -->
            <div class="lg:col-span-2">
                <div class="bg-white card p-0 overflow-hidden border-gray-100">
                    <div class="p-10 border-b border-gray-50">
                        <h3 class="text-2xl font-black uppercase tracking-tighter">Current Administrators</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-10 py-6 text-left text-[14px] font-black uppercase tracking-widest text-gray-400">ID</th>
                                    <th class="px-10 py-6 text-left text-[14px] font-black uppercase tracking-widest text-gray-400">Admin Name</th>
                                    <th class="px-10 py-6 text-left text-[14px] font-black uppercase tracking-widest text-gray-400">Username</th>
                                    <th class="px-10 py-6 text-center text-[14px] font-black uppercase tracking-widest text-gray-400">Status</th>
                                    <th class="px-10 py-6 text-right text-[14px] font-black uppercase tracking-widest text-gray-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                        <td class="px-10 py-8 text-[16px] font-black text-gray-300">#<?php echo $user['id']; ?></td>
                                        <td class="px-10 py-8">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-700 font-black">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                                <span class="text-[16px] font-bold text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-10 py-8">
                                            <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-[14px] font-bold">@<?php echo htmlspecialchars($user['username']); ?></span>
                                        </td>
                                        <td class="px-10 py-8 text-center">
                                            <?php if ($user['is_active']): ?>
                                                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg text-[12px] font-black uppercase tracking-widest">Active</span>
                                            <?php else: ?>
                                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-lg text-[12px] font-black uppercase tracking-widest">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-10 py-8 text-right">
                                            <div class="flex items-center justify-end gap-6">
                                                <!-- Reset Password Form -->
                                                <form method="POST" class="flex items-center gap-2">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="password" name="new_password" placeholder="New Pass" required minlength="8" 
                                                        class="w-32 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 text-[12px] focus:outline-none focus:border-amber-500">
                                                    <button type="submit" title="Reset Password" class="text-blue-400 hover:text-blue-600 transition-colors">
                                                        <i class="fa-solid fa-key"></i>
                                                    </button>
                                                </form>

                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <!-- Toggle Status -->
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="status" value="<?php echo $user['is_active'] ? '0' : '1'; ?>">
                                                        <button type="submit" title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>" 
                                                            class="<?php echo $user['is_active'] ? 'text-amber-400 hover:text-amber-600' : 'text-emerald-400 hover:text-emerald-600'; ?> transition-colors">
                                                            <i class="fa-solid <?php echo $user['is_active'] ? 'fa-user-lock' : 'fa-user-check'; ?> text-xl"></i>
                                                        </button>
                                                    </form>

                                                    <form method="POST" onsubmit="return confirm('Allow access removal for this user?');" class="inline">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" title="Delete" class="text-red-300 hover:text-red-500 transition-colors">
                                                            <i class="fa-solid fa-trash-can text-xl"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-[12px] font-black uppercase tracking-widest text-emerald-500 bg-emerald-50 px-4 py-2 rounded-lg">
                                                        <i class="fa-solid fa-user-check mr-2"></i> Me
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
