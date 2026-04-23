<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | DecoraTV Admin</title>
    <link rel="icon" type="image/png" href="../assets/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            background-color: #0f0f0f;
        }

        .sidebar-link {
            transition: all 0.3s;
            color: #6c757d;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar-link.active {
            border-right: 4px solid #f59e0b;
        }

        .card {
            border-radius: 2rem;
            border: 1px solid #edf2f7;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
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
            <a href="index.php"
                class="sidebar-link active flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-chart-line w-6"></i> Dashboard
            </a>
            <p class="text-[14px] text-gray-600 font-bold uppercase tracking-widest px-8 pt-8 pb-3">Inventory</p>
            <a href="inventory.php?type=frame"
                class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-border-all w-6"></i> Frames
            </a>
            <a href="inventory.php?type=liner"
                class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-maximize w-6"></i> Liners
            </a>
            <a href="inventory.php?type=art"
                class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-image w-6"></i> Arts
            </a>
            <p class="text-[14px] text-gray-600 font-bold uppercase tracking-widest px-8 pt-8 pb-3">System</p>
            <a href="inquiries.php"
                class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-rectangle-list w-6"></i> Inquiries
            </a>
            <a href="users.php"
                class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-users w-6"></i> Users
            </a>
            <a href="settings.php"
                class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-envelope w-6"></i> SMTP Config
            </a>
            <a href="database.php"
                class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-database w-6"></i> Database
            </a>
        </nav>

        <div class="p-10 border-t border-white/5">
            <div class="flex items-center gap-5 mb-6">
                <div
                    class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center text-black font-black uppercase text-lg shadow-lg shadow-amber-500/20">
                    <?php echo substr($_SESSION['username'], 0, 1); ?>
                </div>
                <div>
                    <p class="text-white text-[16px] font-black uppercase"><?php echo $_SESSION['username']; ?></p>
                    <p class="text-gray-500 text-[14px] font-bold uppercase tracking-widest">Administrator</p>
                </div>
            </div>
            <a href="logout.php"
                class="text-gray-500 hover:text-red-400 text-[16px] font-black uppercase tracking-widest transition-colors flex items-center gap-3">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-12 overflow-y-auto">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-16">
            <div>
                <h2 class="text-4xl font-black uppercase tracking-tighter">System Console</h2>
                <div class="flex items-center gap-2 mt-3">
                    <div class="h-[3px] w-6 bg-amber-500"></div>
                    <p class="text-[16px] text-gray-400 font-bold uppercase tracking-widest">Overview & Recent Activity
                    </p>
                </div>
            </div>
            <a href="../" target="_blank"
                class="bg-white border border-gray-200 px-10 py-5 rounded-3xl text-[16px] font-black uppercase tracking-widest hover:border-amber-500 hover:text-amber-600 transition-all flex items-center gap-3 shadow-sm">
                <i class="fa-solid fa-eye"></i> View Simulator
            </a>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <?php
            $pdo = get_db_connection();
            $counts = [
                'frames' => $pdo->query("SELECT COUNT(*) FROM materials WHERE type='frame'")->fetchColumn(),
                'liners' => $pdo->query("SELECT COUNT(*) FROM materials WHERE type='liner'")->fetchColumn(),
                'arts' => $pdo->query("SELECT COUNT(*) FROM materials WHERE type='art'")->fetchColumn(),
                'quotes' => $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn(),
            ];
            ?>
            <div class="bg-white p-10 card">
                <p class="text-[14px] text-gray-400 font-black uppercase tracking-widest mb-2">Frames</p>
                <p class="text-5xl font-black"><?php echo $counts['frames']; ?></p>
            </div>
            <div class="bg-white p-10 card">
                <p class="text-[14px] text-gray-400 font-black uppercase tracking-widest mb-2">Liners</p>
                <p class="text-5xl font-black"><?php echo $counts['liners']; ?></p>
            </div>
            <div class="bg-white p-10 card">
                <p class="text-[14px] text-gray-400 font-black uppercase tracking-widest mb-2">Artwork</p>
                <p class="text-5xl font-black"><?php echo $counts['arts']; ?></p>
            </div>
            <a href="inquiries.php" class="bg-amber-100/50 border border-amber-200 p-10 rounded-[2.5rem] hover:bg-amber-100 transition-all group">
                <p class="text-[14px] text-amber-600 font-black uppercase tracking-widest mb-2">Total Quotes</p>
                <div class="flex items-center justify-between">
                    <p class="text-5xl font-black text-amber-700"><?php echo $counts['quotes']; ?></p>
                    <i class="fa-solid fa-chevron-right text-amber-300 group-hover:text-amber-500 transition-all text-2xl"></i>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Recent Quotes Table -->
            <div class="lg:col-span-2">
                <div class="bg-white card overflow-hidden">
                    <div class="px-10 py-8 border-b border-gray-50 flex justify-between items-center">
                        <h3 class="text-[16px] font-black uppercase tracking-widest text-gray-500">Recent Customer Inquiries</h3>
                        <a href="inquiries.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-[12px] px-6 py-2.5 rounded-full font-black uppercase tracking-widest transition-all">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 text-[14px] text-gray-400 font-black uppercase tracking-widest">
                                <tr>
                                    <th class="px-10 py-6">Client</th>
                                    <th class="px-10 py-6">Selection Summary</th>
                                    <th class="px-10 py-6">Status</th>
                                    <th class="px-10 py-6">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-[11px]">
                                <?php
                                $quotes = $pdo->query("SELECT * FROM quotes ORDER BY created_at DESC LIMIT 5")->fetchAll();
                                if (empty($quotes)): ?>
                                    <tr>
                                        <td colspan="4"
                                            class="px-8 py-10 text-center text-gray-400 uppercase font-bold tracking-widest">
                                            No inquiries registered yet</td>
                                    </tr>
                                <?php else:
                                    foreach ($quotes as $q):
                                        $sel = json_decode($q['selection_json'], true);
                                        ?>
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-10 py-8">
                                                <p class="text-[16px] font-black text-gray-800 uppercase">
                                                    <?php echo htmlspecialchars($q['customer_name'] . ' ' . ($q['customer_last_name'] ?? '')); ?>
                                                </p>
                                                <p class="text-[14px] text-gray-400 font-medium lowercase mb-1">
                                                    <?php echo htmlspecialchars($q['customer_email']); ?></p>
                                                <?php if (!empty($q['customer_phone'])): ?>
                                                    <p
                                                        class="text-[12px] text-amber-600 font-black uppercase tracking-widest flex items-center gap-2">
                                                        <i class="fa-solid fa-phone text-[10px]"></i>
                                                        <?php echo htmlspecialchars($q['customer_phone']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-10 py-8">
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="text-[10px] bg-gray-900 text-white px-2 py-0.5 rounded font-black uppercase tracking-widest">Frame</span>
                                                        <span
                                                            class="text-[14px] font-black text-gray-700 uppercase"><?php echo htmlspecialchars($sel['frame_name'] ?? 'N/A'); ?></span>
                                                        <span
                                                            class="text-[10px] text-amber-600 font-black uppercase tracking-widest">[<?php echo htmlspecialchars($sel['frame_id'] ?? '-'); ?>]</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="text-[10px] bg-gray-400 text-white px-2 py-0.5 rounded font-black uppercase tracking-widest">Liner</span>
                                                        <span
                                                            class="text-[14px] font-bold text-gray-600 uppercase"><?php echo htmlspecialchars($sel['liner_name'] ?? 'None'); ?></span>
                                                        <span
                                                            class="text-[10px] text-gray-400 font-black uppercase tracking-widest">[<?php echo htmlspecialchars($sel['liner_id'] ?? '-'); ?>]</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="text-[10px] bg-emerald-500 text-white px-2 py-0.5 rounded font-black uppercase tracking-widest">Art</span>
                                                        <span
                                                            class="text-[14px] font-bold text-gray-600 uppercase"><?php echo htmlspecialchars($sel['art_name'] ?? 'N/A'); ?></span>
                                                        <span
                                                            class="text-[10px] text-gray-400 font-black uppercase tracking-widest">[<?php echo htmlspecialchars($sel['art_id'] ?? '-'); ?>]</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-10 py-8">
                                                <span
                                                    class="bg-emerald-100 text-emerald-600 px-4 py-1.5 rounded-full text-[12px] font-black uppercase tracking-widest">
                                                    <?php echo $q['status']; ?>
                                                </span>
                                            </td>
                                            <td class="px-10 py-8 text-[14px] text-gray-400 font-bold whitespace-nowrap">
                                                <?php echo date('M d, Y', strtotime($q['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($quotes) > 0): ?>
                        <div class="p-8 bg-gray-50/30 border-t border-gray-50 text-center">
                            <a href="inquiries.php" class="inline-flex items-center gap-3 text-[14px] font-black uppercase tracking-widest text-gray-500 hover:text-amber-600 transition-all">
                                Go to full inquiries list <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions / Status -->
            <div class="space-y-6">
                <div class="bg-gray-900 text-white p-10 rounded-[2.5rem] shadow-xl">
                    <h3 class="text-[16px] font-black uppercase tracking-[0.2em] text-amber-500 mb-8">Service Health
                    </h3>

                    <div class="space-y-6">
                        <?php
                        // Check SMTP Config
                        $smtp = $pdo->query("SELECT value FROM settings WHERE key='smtp_host'")->fetchColumn();
                        $smtp_ok = !empty($smtp);
                        ?>
                        <div class="flex items-center justify-between">
                            <span class="text-[14px] uppercase font-bold text-gray-400 tracking-widest">SMTP
                                Connectivity</span>
                            <span
                                class="<?php echo $smtp_ok ? 'text-emerald-400' : 'text-amber-400'; ?> text-[14px] font-black uppercase">
                                <?php echo $smtp_ok ? 'Configured' : 'Missing'; ?>
                            </span>
                        </div>
                        <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full <?php echo $smtp_ok ? 'bg-emerald-500' : 'bg-amber-500'; ?> transition-all duration-1000"
                                style="width: <?php echo $smtp_ok ? '100%' : '30%'; ?>"></div>
                        </div>

                        <div class="pt-8 border-t border-white/5">
                            <p class="text-[14px] text-gray-500 uppercase font-black tracking-widest mb-4">Storage Usage
                            </p>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[16px] font-black">SQLite DB Size</span>
                                <span class="text-[16px] font-black text-amber-500">
                                    <?php echo round(filesize(DB_PATH) / 1024, 2); ?> KB
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white card p-10">
                    <h3 class="text-[16px] font-black uppercase tracking-widest text-gray-400 mb-6">Database Management</h3>
                    <p class="text-[16px] text-gray-500 mb-8 leading-relaxed">Ensure data integrity by regularly
                        downloading the latest database snapshot or switching engines.</p>
                    <a href="database.php"
                        class="inline-flex items-center gap-3 bg-gray-100 hover:bg-gray-200 text-gray-900 px-8 py-4 rounded-2xl text-[14px] font-black uppercase tracking-widest transition-all">
                        <i class="fa-solid fa-database"></i> Manage Database
                    </a>
                </div>
            </div>
        </div>
    </main>

</body>

</html>