<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = get_db_connection();
$type = $_GET['type'] ?? 'frame';
$validTypes = ['frame', 'liner', 'art'];
if (!in_array($type, $validTypes)) $type = 'frame';

$message = '';
$editItem = null;

// Handle Edit State (Fetch item to edit)
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ? AND type = ?");
    $stmt->execute([$editId, $type]);
    $editItem = $stmt->fetch();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ? AND type = ?");
    $stmt->execute([$id, $type]);
    $message = "Item deleted successfully.";
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $internal_id = $_POST['internal_id'] ?? '';
    $artist = $_POST['artist'] ?? '';
    $group = $_POST['group_name'] ?? 'General';

    $imagePath = null;
    $uploadSuccess = true;
    if (!empty($_FILES['image']['name'])) {
        $imagePath = upload_material_image($_FILES['image'], $type);
        if (!$imagePath) {
            $uploadSuccess = false;
            $error_info = error_get_last();
            $message = "Error uploading image. PHP Error: " . ($error_info['message'] ?? 'Unknown');
        }
    }

    if ($uploadSuccess) {
        try {
            if ($id) {
                // Update
                if ($imagePath) {
                    $stmt = $pdo->prepare("UPDATE materials SET name = ?, image_path = ?, artist = ?, group_name = ?, internal_id = ? WHERE id = ? AND type = ?");
                    $stmt->execute([$name, $imagePath, $artist, $group, $internal_id, $id, $type]);
                } else {
                    $stmt = $pdo->prepare("UPDATE materials SET name = ?, artist = ?, group_name = ?, internal_id = ? WHERE id = ? AND type = ?");
                    $stmt->execute([$name, $artist, $group, $internal_id, $id, $type]);
                }
                header("Location: inventory.php?type=$type&success=1");
                exit;
            } else {
                // Insert
                if ($imagePath) {
                    $stmt = $pdo->prepare("INSERT INTO materials (type, name, image_path, artist, group_name, internal_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$type, $name, $imagePath, $artist, $group, $internal_id]);
                    $message = "New " . ucfirst($type) . " added successfully.";
                } else {
                    $message = "Error: No image file selected.";
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Error: The ID '$internal_id' is already in use.";
            } else {
                $message = "Database Error: " . $e->getMessage();
            }
            
            // Re-populate editItem on error so form doesn't clear
            if ($id) {
                $editItem = [
                    'id' => $id,
                    'name' => $name,
                    'internal_id' => $internal_id,
                    'artist' => $artist,
                    'group_name' => $group,
                    'image_path' => $_POST['existing_image'] ?? ''
                ];
            }
        }
    } else {
        // Re-populate editItem on upload error
        if ($id) {
            $editItem = [
                'id' => $id,
                'name' => $name,
                'internal_id' => $internal_id,
                'artist' => $artist,
                'group_name' => $group,
                'image_path' => $_POST['existing_image'] ?? ''
            ];
        }
    }
}

if (isset($_GET['success'])) {
    $message = ucfirst($type) . " updated successfully.";
}

// Fetch Items
$stmt = $pdo->prepare("SELECT * FROM materials WHERE type = ? ORDER BY created_at DESC");
$stmt->execute([$type]);
$items = $stmt->fetchAll();

$title = ucfirst($type) . "s Inventory";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | Admin</title>
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
        .img-preview-container { width: 100px; height: 100px; border-radius: 0; border: 1px solid #eee; overflow: hidden; position: relative; background: #f9f9f9; }
        .img-preview-full { width: 100%; height: 100%; object-fit: cover; }
        .img-preview-zoom { width: 500%; height: auto; max-width: none; object-fit: cover; object-position: top left; position: absolute; top: 0; left: 0; }
        
        /* Aspect ratios for upload zone */
        .ratio-art { aspect-ratio: 794 / 455; }
        .ratio-square { aspect-ratio: 1 / 1; }
    </style>
</head>
<body class="flex min-h-screen text-gray-800">

    <!-- Sidebar (Same as index) -->
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
            <a href="inventory.php?type=frame" class="sidebar-link <?php echo $type==='frame'?'active':''; ?> flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-border-all w-6"></i> Frames
            </a>
            <a href="inventory.php?type=liner" class="sidebar-link <?php echo $type==='liner'?'active':''; ?> flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-maximize w-6"></i> Liners
            </a>
            <a href="inventory.php?type=art" class="sidebar-link <?php echo $type==='art'?'active':''; ?> flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest">
                <i class="fa-solid fa-image w-6"></i> Arts
            </a>
            <p class="text-[14px] text-gray-600 font-bold uppercase tracking-widest px-8 pt-8 pb-3">System</p>
            <a href="inquiries.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-rectangle-list w-6"></i> Inquiries
            </a>
            <a href="users.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
                <i class="fa-solid fa-users w-6"></i> Users
            </a>
            <a href="settings.php" class="sidebar-link flex items-center gap-4 px-8 py-5 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400">
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

    <main class="flex-1 p-6 md:p-12 overflow-y-auto">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-16">
            <div>
                <h2 class="text-4xl font-black uppercase tracking-tighter">Manage <?php echo ucfirst($type); ?>s</h2>
                <div class="flex items-center gap-2 mt-3">
                    <div class="h-[3px] w-6 bg-amber-500"></div>
                    <p class="text-[16px] text-gray-500 font-bold uppercase tracking-widest">Add or Remove Items from your Catalog</p>
                </div>
            </div>
            <a href="../" target="_blank" class="bg-gray-900 text-white px-10 py-5 rounded-3xl text-[16px] font-black uppercase tracking-widest hover:bg-amber-600 transition-all flex items-center gap-3">
                 Simulator
            </a>
        </header>

        <?php if ($message): ?>
            <div class="mb-10 p-6 rounded-2xl text-[16px] font-black uppercase tracking-widest <?php echo strpos($message, 'Error') !== false ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-600'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Add Form -->
            <div class="lg:col-span-1">
                <div class="bg-white card p-10 sticky top-10">
                    <div class="flex items-center justify-between mb-10">
                        <h3 class="text-[16px] font-black uppercase tracking-widest text-gray-500"><?php echo $editItem ? 'Edit' : 'Add New'; ?> <?php echo ucfirst($type); ?></h3>
                        <?php if ($editItem): ?>
                            <a href="inventory.php?type=<?php echo $type; ?>" class="text-[12px] font-black uppercase tracking-widest text-amber-600 hover:text-amber-700">Cancel</a>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="space-y-8">
                        <?php if ($editItem): ?>
                            <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo $editItem['image_path']; ?>">
                        <?php endif; ?>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-[0.2em] text-gray-400 mb-3 ml-1">Name / Title</label>
                            <input type="text" name="name" id="nameInput" value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>" required class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500/50 transition-all">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-[0.2em] text-gray-400 mb-3 ml-1">Internal Reference ID</label>
                            <input type="text" name="internal_id" id="internalIdInput" value="<?php echo $editItem ? htmlspecialchars($editItem['internal_id']) : ''; ?>" placeholder="e.g. F123-B" required class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500/50 transition-all">
                        </div>
                        <?php if ($type === 'art'): ?>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-[0.2em] text-gray-400 mb-3 ml-1">Artist Name</label>
                            <input type="text" name="artist" value="<?php echo $editItem ? htmlspecialchars($editItem['artist']) : ''; ?>" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500/50 transition-all">
                        </div>
                        <?php endif; ?>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-[0.2em] text-gray-400 mb-3 ml-1">Group / Category</label>
                            <input type="text" name="group_name" value="<?php echo $editItem ? htmlspecialchars($editItem['group_name']) : ''; ?>" placeholder="General" class="w-full bg-gray-50 border border-gray-100 rounded-2xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500/50 transition-all">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-[0.2em] text-gray-400 mb-3 ml-1">Texture / Image File <?php echo $editItem ? '(Optional)' : ''; ?></label>
                            <div id="dropzone" class="relative w-full <?php echo $type==='art'?'ratio-art':'ratio-square'; ?> bg-gray-50 border-2 border-dashed border-gray-200 flex items-center justify-center group hover:border-amber-300 transition-all cursor-pointer overflow-hidden">
                                <input type="file" name="image" id="imageInput" <?php echo $editItem ? '' : 'required'; ?> class="absolute inset-0 opacity-0 z-10 cursor-pointer" accept="image/jpeg, image/png, image/webp, image/avif">
                                <div id="upload-prompt" class="text-center <?php echo $editItem ? 'hidden' : ''; ?>">
                                    <i class="fa-solid fa-cloud-arrow-up text-gray-300 group-hover:text-amber-500 text-3xl mb-3"></i>
                                    <p class="text-[14px] font-black text-gray-500 uppercase tracking-widest">Choose Image</p>
                                </div>
                                <img id="preview-img" src="<?php echo $editItem ? '../' . $editItem['image_path'] : ''; ?>" class="absolute <?php echo $type==='art'?'inset-0 w-full h-full object-cover':'top-0 left-0 w-[500%] max-w-none h-auto object-cover origin-top-left'; ?> <?php echo $editItem ? '' : 'hidden'; ?>">
                            </div>
                        </div>

                        <script>
                            document.getElementById('imageInput').onchange = function(evt) {
                                const [file] = this.files;
                                if (file) {
                                    document.getElementById('preview-img').src = URL.createObjectURL(file);
                                    document.getElementById('preview-img').classList.remove('hidden');
                                    document.getElementById('upload-prompt').classList.add('hidden');

                                    // Auto-populate name and reference from filename
                                    const nameInput = document.getElementById('nameInput');
                                    const internalIdInput = document.getElementById('internalIdInput');
                                    if (nameInput || internalIdInput) {
                                        const fileName = file.name.split('.').slice(0, -1).join('.');
                                        
                                        // Replace underscores with spaces
                                        const cleanName = fileName.replace(/_/g, ' ');
                                        
                                        // Extract first part as reference
                                        const reference = cleanName.split(' ')[0];

                                        const isEdit = document.querySelector('input[name="id"]') !== null;
                                        
                                        if (nameInput && (!isEdit || nameInput.value.trim() === '')) {
                                            nameInput.value = cleanName;
                                        }
                                        
                                        if (internalIdInput && (!isEdit || internalIdInput.value.trim() === '')) {
                                            internalIdInput.value = reference;
                                        }
                                    }
                                }
                            };
                        </script>
                        <button type="submit" class="w-full bg-gray-900 text-white py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-[16px] shadow-xl hover:bg-amber-600 transition-all shadow-gray-200">
                            <?php echo $editItem ? 'Update' : 'Save'; ?> Item
                        </button>
                    </form>
                </div>
            </div>

            <!-- List -->
            <div class="lg:col-span-2">
                <div class="bg-white card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 text-[14px] text-gray-400 font-black uppercase tracking-widest">
                                <tr>
                                    <th class="px-10 py-6">Preview</th>
                                    <th class="px-10 py-6">Identification</th>
                                    <th class="px-10 py-6">Category</th>
                                    <th class="px-10 py-6 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-[11px]">
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="4" class="px-10 py-20 text-center text-gray-500 italic uppercase font-bold tracking-[0.2em]">The collection is currently empty</td>
                                    </tr>
                                <?php else: foreach ($items as $item): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-10 py-8">
                                            <div class="img-preview-container group shadow-sm cursor-pointer" onclick="openLightbox('../<?php echo $item['image_path']; ?>')">
                                                <img src="../<?php echo $item['image_path']; ?>" class="<?php echo $item['type']==='art'?'img-preview-full':'img-preview-zoom'; ?>" alt="Preview">
                                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <i class="fa-solid fa-magnifying-glass text-white text-[16px]"></i>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-10 py-8">
                                            <p class="text-[16px] font-black text-gray-800 uppercase"><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p class="text-[14px] font-black text-amber-600 uppercase tracking-widest mt-1">ID: <?php echo htmlspecialchars($item['internal_id']); ?></p>
                                            <?php if ($item['artist']): ?>
                                                <p class="text-[14px] text-gray-500 italic">By: <?php echo htmlspecialchars($item['artist']); ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-10 py-8">
                                            <span class="bg-gray-100 text-gray-500 px-4 py-1.5 rounded-full text-[12px] font-black uppercase tracking-widest">
                                                <?php echo htmlspecialchars($item['group_name']); ?>
                                            </span>
                                        </td>
                                        <td class="px-10 py-8 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="?type=<?php echo $type; ?>&edit=<?php echo $item['id']; ?>" class="w-12 h-12 inline-flex items-center justify-center rounded-xl text-gray-300 hover:text-amber-500 hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100">
                                                    <i class="fa-solid fa-pen-to-square text-lg"></i>
                                                </a>
                                                <a href="?type=<?php echo $type; ?>&delete=<?php echo $item['id']; ?>" onclick="return confirm('Archive this item?')" class="w-12 h-12 inline-flex items-center justify-center rounded-xl text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all border border-transparent hover:border-red-100">
                                                    <i class="fa-solid fa-trash-can text-lg"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Lightbox Modal -->
    <div id="lightbox-modal" onclick="if(event.target === this) closeLightbox()" class="fixed inset-0 bg-black/90 backdrop-blur-sm z-[100] hidden items-center justify-center p-4 md:p-10 cursor-pointer">
        <button onclick="closeLightbox()" class="absolute top-10 right-10 text-white/50 hover:text-white transition-colors z-50">
            <i class="fa-solid fa-xmark text-3xl"></i>
        </button>
        <div class="max-w-5xl w-full h-full flex items-center justify-center pointer-events-none">
            <img id="lightbox-img" src="" class="max-w-full max-h-full object-contain shadow-2xl rounded-lg">
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTop" onclick="scrollToTop()" class="fixed bottom-10 right-10 w-12 h-12 bg-gray-900 text-white rounded-2xl shadow-2xl flex items-center justify-center opacity-0 translate-y-20 pointer-events-none transition-all duration-500 hover:bg-amber-600 z-[90] active:scale-95">
        <i class="fa-solid fa-arrow-up text-lg"></i>
    </button>

    <script>
        function openLightbox(src) {
            const modal = document.getElementById('lightbox-modal');
            const img = document.getElementById('lightbox-img');
            img.src = src;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeLightbox() {
            const modal = document.getElementById('lightbox-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = ''; // Restore scrolling
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeLightbox();
        });

        // Back to Top Logic
        const backToTopBtn = document.getElementById('backToTop');
        const mainContent = document.querySelector('main');

        function handleScroll() {
            const scrollTop = (mainContent ? mainContent.scrollTop : 0) || document.documentElement.scrollTop || window.pageYOffset;
            
            if (scrollTop > 200) {
                backToTopBtn.classList.remove('opacity-0', 'translate-y-20', 'pointer-events-none');
                backToTopBtn.classList.add('opacity-100', 'translate-y-0');
            } else {
                backToTopBtn.classList.add('opacity-0', 'translate-y-20', 'pointer-events-none');
                backToTopBtn.classList.remove('opacity-100', 'translate-y-0');
            }
        }

        if (mainContent) mainContent.addEventListener('scroll', handleScroll);
        window.addEventListener('scroll', handleScroll);

        function scrollToTop() {
            const scrollConfig = { top: 0, behavior: 'smooth' };
            if (mainContent) mainContent.scrollTo(scrollConfig);
            window.scrollTo(scrollConfig);
            document.documentElement.scrollTo(scrollConfig);
        }
    </script>
</body>
</html>
