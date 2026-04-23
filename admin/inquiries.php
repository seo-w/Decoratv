<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$pdo = get_db_connection();
$message = '';
$error = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = $_POST['id'];
    $new_status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE quotes SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $message = "Status updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM quotes WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Inquiry deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting inquiry: " . $e->getMessage();
    }
}

// Fetch all quotes
$quotes = $pdo->query("SELECT * FROM quotes ORDER BY created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Inquiries | DecoraTV Admin</title>
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
        .status-badge { @apply px-4 py-1.5 rounded-full text-[12px] font-black uppercase tracking-widest; }
    </style>
</head>
<body class="flex min-h-screen text-gray-800">

    <!-- Sidebar -->
    <aside class="sidebar w-80 flex-shrink-0 flex flex-col hidden md:flex">
        <div class="p-10">
            <h1 class="text-white text-3xl font-black uppercase tracking-tighter">DecoraTV</h1>
            <p class="text-[16px] text-amber-500 font-black uppercase tracking-[0.2em] mt-1">Management Studio</p>
        </div>

        <nav class="flex-1 mt-8 px-6 space-y-3">
            <a href="index.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-chart-line w-6"></i> Dashboard
            </a>
            <p class="text-[14px] text-gray-600 font-bold uppercase tracking-widest px-8 pt-8 pb-3">Inventory</p>
            <a href="inventory.php?type=frame" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-border-all w-6"></i> Frames
            </a>
            <a href="inventory.php?type=liner" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-maximize w-6"></i> Liners
            </a>
            <a href="inventory.php?type=art" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-image w-6"></i> Arts
            </a>
            <p class="text-[14px] text-gray-600 font-bold uppercase tracking-widest px-8 pt-8 pb-3">System</p>
            <a href="inquiries.php" class="sidebar-link active flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-rectangle-list w-6"></i> Inquiries
            </a>
            <a href="users.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-users w-6"></i> Users
            </a>
            <a href="settings.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-envelope w-6"></i> SMTP Config
            </a>
            <a href="database.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-database w-6"></i> Database
            </a>
        </nav>

        <div class="p-10 border-t border-white/5">
            <a href="logout.php" class="text-gray-500 hover:text-red-400 text-[16px] font-black uppercase tracking-widest transition-colors flex items-center gap-3">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-12 overflow-y-auto">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-16">
            <div>
                <h2 class="text-4xl font-black uppercase tracking-tighter">Customer Inquiries</h2>
                <div class="flex items-center gap-2 mt-3">
                    <div class="h-[3px] w-6 bg-amber-500"></div>
                    <p class="text-[16px] text-gray-500 font-bold uppercase tracking-widest">Full Record of Design Requests</p>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="mb-10 p-6 bg-emerald-100 text-emerald-600 rounded-3xl text-[16px] font-black uppercase tracking-widest shadow-sm">
                <i class="fa-solid fa-circle-check mr-2"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-10 p-6 bg-red-100 text-red-600 rounded-3xl text-[16px] font-black uppercase tracking-widest shadow-sm">
                <i class="fa-solid fa-circle-exclamation mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Inquiries Table -->
        <div class="bg-white card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/50 text-[14px] text-gray-400 font-black uppercase tracking-widest border-b border-gray-100">
                        <tr>
                            <th class="px-10 py-8">Customer Details</th>
                            <th class="px-10 py-8">Design Selection</th>
                            <th class="px-10 py-8">Management</th>
                            <th class="px-10 py-8">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($quotes)): ?>
                            <tr>
                                <td colspan="4" class="px-10 py-20 text-center text-gray-500 uppercase font-black tracking-widest">No inquiries found in database</td>
                            </tr>
                        <?php else:
                            foreach ($quotes as $q):
                                $sel = json_decode($q['selection_json'], true);
                                $status_class = '';
                                switch($q['status']) {
                                    case 'pending': $status_class = 'bg-amber-100 text-amber-600'; break;
                                    case 'contacted': $status_class = 'bg-blue-100 text-blue-600'; break;
                                    case 'completed': $status_class = 'bg-emerald-100 text-emerald-600'; break;
                                    case 'cancelled': $status_class = 'bg-red-100 text-red-600'; break;
                                    default: $status_class = 'bg-gray-100 text-gray-600';
                                }
                        ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-10 py-10">
                                    <p class="text-[18px] font-black text-gray-800 uppercase leading-none mb-2">
                                        <?php echo htmlspecialchars($q['customer_name'] . ' ' . ($q['customer_last_name'] ?? '')); ?>
                                    </p>
                                    <p class="text-[16px] text-gray-500 font-medium mb-3"><?php echo htmlspecialchars($q['customer_email']); ?></p>
                                    <?php if (!empty($q['customer_phone'])): ?>
                                        <p class="text-[14px] text-gray-900 font-black uppercase tracking-widest flex items-center gap-2">
                                            <i class="fa-solid fa-phone text-amber-600"></i> <?php echo htmlspecialchars($q['customer_phone']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-[12px] text-gray-500 font-bold uppercase tracking-widest mt-4">
                                        Submitted: <?php echo date('M d, Y - H:i', strtotime($q['created_at'])); ?>
                                    </p>
                                </td>
                                <td class="px-10 py-10">
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3">
                                            <span class="w-20 text-[10px] bg-gray-900 text-white px-3 py-1 rounded font-black uppercase tracking-widest text-center">Frame</span>
                                            <span class="text-[16px] font-black text-gray-700 uppercase"><?php echo htmlspecialchars($sel['frame_name'] ?? 'N/A'); ?></span>
                                            <span class="text-[12px] text-amber-600 font-black uppercase tracking-widest">[<?php echo htmlspecialchars($sel['frame_id'] ?? '-'); ?>]</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="w-20 text-[10px] bg-gray-400 text-white px-3 py-1 rounded font-black uppercase tracking-widest text-center">Liner</span>
                                            <span class="text-[16px] font-bold text-gray-600 uppercase"><?php echo htmlspecialchars($sel['liner_name'] ?? 'None'); ?></span>
                                            <span class="text-[12px] text-gray-500 font-black uppercase tracking-widest">[<?php echo htmlspecialchars($sel['liner_id'] ?? '-'); ?>]</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="w-20 text-[10px] bg-emerald-500 text-white px-3 py-1 rounded font-black uppercase tracking-widest text-center">Art</span>
                                            <span class="text-[16px] font-bold text-gray-600 uppercase"><?php echo htmlspecialchars($sel['art_name'] ?? 'N/A'); ?></span>
                                            <span class="text-[12px] text-gray-500 font-black uppercase tracking-widest">[<?php echo htmlspecialchars($sel['art_id'] ?? '-'); ?>]</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-10 py-10">
                                    <form method="POST" class="flex flex-col gap-3">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                        <div class="inline-flex flex-col">
                                            <span class="px-4 py-1.5 rounded-full text-[12px] font-black uppercase tracking-widest text-center mb-3 <?php echo $status_class; ?>">
                                                Current: <?php echo $q['status']; ?>
                                            </span>
                                            <select name="status" onchange="this.form.submit()" class="bg-gray-50 border border-gray-100 text-[14px] font-black uppercase tracking-widest px-4 py-3 rounded-xl focus:outline-none focus:border-amber-500">
                                                <option value="" disabled selected>Change Status</option>
                                                <option value="pending">Pending</option>
                                                <option value="contacted">Contacted</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                    </form>
                                </td>
                                <td class="px-10 py-10">
                                    <a href="?delete=<?php echo $q['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to PERMANENTLY delete this inquiry?')"
                                       class="inline-flex items-center justify-center w-12 h-12 bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>
