<?php
require_once __DIR__ . '/config/database.php';
$pdo = get_db_connection();

// Load all materials from DB
$frames = $pdo->query("SELECT * FROM materials WHERE type='frame' ORDER BY name ASC")->fetchAll();
$liners = $pdo->query("SELECT * FROM materials WHERE type='liner' ORDER BY name ASC")->fetchAll();
$arts   = $pdo->query("SELECT * FROM materials WHERE type='art' ORDER BY name ASC")->fetchAll();

// Default selections
$defFrame = !empty($frames) ? json_encode($frames[0]) : 'null';
$defLiner = !empty($liners) ? json_encode($liners[0]) : 'null';
$defArt   = !empty($arts) ? json_encode($arts[0]) : 'null';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DecoraTV | Custom TV Enclosure & Premium Designer Frames</title>
    <meta name="description" content="Transform your living space with DecoraTV. Design and customize premium TV concealment solutions with our exclusive collection of designer frames and art. Get your custom quote today.">
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: url('assets/bg-decoratv.avif');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
        }
        .canvas-container { aspect-ratio: 794 / 455; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        
        /* Layer scaling logic */
        .art-layer { transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        .liner-layer { transition: all 0.5s ease; }
        
        .zoom-thumbnail { 
            width: 500%; 
            max-width: none; 
            height: auto; 
            object-fit: cover; 
            origin-top-left: 0 0;
            position: absolute;
            top: 0;
            left: 0;
        }

        /* Glassmorphism */
        .glass-panel {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        html { scrollbar-gutter: stable; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 selection:bg-amber-100 min-h-screen">

    <div class="max-w-7xl mx-auto p-4 md:p-10" id="app">
        <!-- Header -->
        <header class="flex flex-col md:flex-row items-center justify-between gap-8 mb-12">
            <div class="flex items-center gap-6">
                <div class="bg-white p-5 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center justify-center">
                    <img src="https://avitp.com/wp-content/uploads/2015/06/logo.png" alt="Logo" class="h-10 w-auto">
                </div>
                <div>
                    <h1 class="text-4xl font-black uppercase tracking-tighter leading-none text-gray-900">DecoraTV Studio</h1>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="h-[2px] w-6 bg-amber-600"></div>
                        <p class="text-[14px] font-black text-amber-600 uppercase tracking-[0.2em]">Custom TV Concealment & Designer Frames</p>
                    </div>
                </div>
            </div>

        </header>
            <main class="max-w-screen-2xl mx-auto px-6 md:px-12 py-10 relative z-10">
        <!-- Subtitle / SEO -->
        <header class="mb-12">
            <h2 class="text-2xl md:text-3xl font-black uppercase tracking-tighter text-gray-900 mb-6">Transform your television into Art.</h2>
            <p class="text-[18px] text-gray-600 leading-relaxed font-medium max-w-3xl">
                Elevate your living space with our premium TV concealment solutions. Explore our high-end collection of designer frames and high-quality liners, tailored to complement any interior style with elegance and sophistication.
            </p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- Left: Visualization Area -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-none shadow-2xl border border-gray-100 relative overflow-hidden canvas-container flex items-center justify-center group">
                    <!-- Subtle pattern background -->
                    <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#000 1.5px, transparent 1.5px); background-size: 40px 40px;"></div>

                    <!-- Canvas Canvas Logic -->
                    <div class="relative w-full h-full flex items-center justify-center">
                        
                        <!-- 1. BASE: FRAME -->
                        <div class="absolute z-0 w-full h-full pointer-events-none">
                            <img id="v-frame-img" src="" class="w-full h-full object-fill" alt="Frame">
                        </div>

                        <!-- 2. MIDDLE: LINER -->
                        <div id="v-liner-layer" class="liner-layer absolute z-10 w-[91.5%] h-[85%] flex items-center justify-center overflow-hidden shadow-2xl">
                             <img id="v-liner-img" src="" class="w-full h-full object-fill hidden" alt="Liner">
                             <div id="v-liner-empty" class="w-full h-full bg-white/50 backdrop-blur-sm"></div>
                        </div>

                        <!-- 3. TOP: ART -->
                        <div id="v-art-container" class="art-layer absolute z-20 flex items-center justify-center pointer-events-none" style="width: 86%; height: 76%;">
                            <div class="w-full h-full relative shadow-[0_10px_60px_rgba(0,0,0,0.4)] bg-black overflow-hidden">
                                <img id="v-art-img" src="" class="w-full h-full object-cover" alt="Art">
                                <div class="absolute inset-0 bg-gradient-to-tr from-white/10 via-transparent to-white/10 pointer-events-none opacity-40"></div>
                            </div>
                        </div>

                        <!-- Finisher Shade -->
                        <div class="absolute inset-0 shadow-[inset_0_0_80px_rgba(0,0,0,0.3)] pointer-events-none z-30"></div>
                    </div>
                </div>

                <p class="text-center text-[16px] text-gray-500 mt-8 font-black uppercase tracking-[0.2em] opacity-80">Experimental Visualization Area - Not to Scale</p>

                <!-- Selection Specs -->
                <div class="mt-12 grid grid-cols-3 gap-8">
                    <!-- Frame Selection Spec -->
                    <div onclick="handleLightbox('frame')" class="glass-panel p-0 rounded-[2.5rem] flex flex-col items-center overflow-hidden group cursor-pointer hover:shadow-xl transition-all border-2 border-transparent hover:border-amber-500/30">
                        <div class="pt-8 pb-4 flex flex-col items-center">
                            <i class="fa-solid fa-border-all text-amber-600 mb-2"></i>
                            <span class="text-[14px] text-gray-400 font-black uppercase tracking-widest">Frame</span>
                        </div>
                        <div id="s-frame-preview" class="w-full aspect-square overflow-hidden hidden">
                             <img id="s-frame-thumb" src="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                    </div>
                    
                    <!-- Liner Selection Spec -->
                    <div onclick="handleLightbox('liner')" class="glass-panel p-0 rounded-[2.5rem] flex flex-col items-center overflow-hidden group cursor-pointer hover:shadow-xl transition-all border-2 border-transparent hover:border-blue-500/30">
                        <div class="pt-8 pb-4 flex flex-col items-center">
                            <i class="fa-solid fa-maximize text-blue-500 mb-2"></i>
                            <span class="text-[14px] text-gray-400 font-black uppercase tracking-widest">Liner</span>
                        </div>
                        <div id="s-liner-preview" class="w-full aspect-square overflow-hidden hidden">
                             <img id="s-liner-thumb" src="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                    </div>

                    <!-- Art Selection Spec -->
                    <div onclick="handleLightbox('art')" class="glass-panel p-0 rounded-[2.5rem] flex flex-col items-center overflow-hidden group cursor-pointer hover:shadow-xl transition-all border-2 border-transparent hover:border-emerald-500/30">
                        <div class="pt-8 pb-4 flex flex-col items-center">
                            <i class="fa-solid fa-palette text-emerald-500 mb-2"></i>
                            <span class="text-[14px] text-gray-400 font-black uppercase tracking-widest">Artwork</span>
                        </div>
                        <div id="s-art-preview" class="w-full aspect-square overflow-hidden hidden">
                             <img id="s-art-thumb" src="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Panel Controls -->
            <div class="lg:col-span-4">
                <div class="bg-white rounded-[3rem] shadow-2xl border border-gray-100 flex flex-col h-[700px] overflow-hidden relative">
                    <!-- UX Guide Title -->
                    <div class="px-8 pt-8">
                        <p class="text-[14px] font-black text-gray-400 uppercase tracking-[0.3em]">Customize your selection</p>
                    </div>

                    <!-- Tabs -->
                    <div class="p-4 bg-gray-50/50">
                        <nav class="flex w-full bg-white rounded-2xl p-2 shadow-sm border border-gray-100">
                            <button onclick="switchTab('frame')" id="tab-frame" class="tab-btn flex-1 py-4 rounded-xl text-[16px] font-black uppercase tracking-widest transition-all bg-gray-900 text-white">Frames</button>
                            <button onclick="switchTab('liner')" id="tab-liner" class="tab-btn flex-1 py-4 rounded-xl text-[16px] font-black uppercase tracking-widest transition-all text-gray-500">Liners</button>
                            <button onclick="switchTab('art')" id="tab-art" class="tab-btn flex-1 py-4 rounded-xl text-[16px] font-black uppercase tracking-widest transition-all text-gray-500">Arts</button>
                        </nav>
                    </div>

                    <!-- Items List -->
                    <div class="flex-1 overflow-y-auto px-8 pb-8 custom-scrollbar pt-8">
                        <h2 class="text-[16px] font-black text-gray-500 uppercase tracking-[0.2em] mb-10">Studio Collection</h2>
                        
                        <div id="selector-container" class="grid grid-cols-2 gap-4">
                            <!-- Items populated by JS -->
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="p-8 bg-gray-50 border-t border-gray-100">
                        <button onclick="openQuoteModal()" class="w-full bg-amber-600 hover:bg-amber-700 text-white py-8 rounded-[1.8rem] font-black uppercase tracking-[0.2em] text-[16px] shadow-xl shadow-amber-100 transition-all active:scale-95">
                            Confirm Design
                        </button>
                    </div>

                    <!-- Sidebar Back to Top -->
                    <button id="sidebarToTop" onclick="scrollSidebarTop()" class="absolute bottom-32 right-12 w-12 h-12 bg-gray-900/80 text-white rounded-full shadow-lg flex items-center justify-center opacity-0 translate-y-10 pointer-events-none transition-all duration-300 hover:bg-amber-600 z-50">
                        <i class="fa-solid fa-arrow-up text-sm"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Lightbox Modal (Moved inside #app for better visibility) -->
    <div id="lightbox-modal" onclick="closeLightbox()" class="fixed inset-0 bg-black/95 z-[9999] hidden flex-col items-center justify-center p-4 cursor-zoom-out">
        <img id="lightbox-img" src="" class="max-w-[95vw] max-h-[90vh] object-contain shadow-2xl rounded-xl">
        <div class="mt-6 text-white/70 font-black uppercase tracking-[0.3em] text-sm">Click anywhere to close</div>
        <button class="absolute top-6 right-6 text-white/50 hover:text-white transition-colors">
            <i class="fa-solid fa-xmark text-4xl"></i>
        </button>
    </div>

    <!-- Quote Modal -->
    <div id="quote-modal" onclick="if(event.target === this) closeQuoteModal()" class="fixed inset-0 bg-black/80 backdrop-blur-xl z-[100] hidden overflow-y-auto cursor-pointer">
        <div onclick="if(event.target === this) closeQuoteModal()" class="min-h-screen flex justify-center items-start md:items-center p-4 md:p-10">
            <div class="bg-white w-full max-w-xl rounded-[2rem] md:rounded-[3rem] shadow-2xl animate-in zoom-in-95 duration-300 my-auto relative cursor-default">
                <!-- Close Button -->
                <button onclick="closeQuoteModal()" class="absolute top-8 right-10 text-gray-400 hover:text-gray-900 transition-colors z-50">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
                <div class="p-8 md:p-12">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-black uppercase tracking-tighter mb-4">Request Quote</h2>
                    <p class="text-[16px] text-gray-500 font-bold uppercase tracking-widest">Share your contact details to receive a pro-forma</p>
                </div>

                <form id="quote-form" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">First Name</label>
                            <input type="text" id="q-first-name" required minlength="2" placeholder="John" class="w-full bg-gray-50 border border-gray-100 rounded-3xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">Last Name</label>
                            <input type="text" id="q-last-name" required minlength="2" placeholder="Doe" class="w-full bg-gray-50 border border-gray-100 rounded-3xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500 shadow-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">Phone Number</label>
                            <input type="tel" id="q-phone" required minlength="7" placeholder="+1 234 567 890" class="w-full bg-gray-50 border border-gray-100 rounded-3xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-[14px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">Email Address</label>
                            <input type="email" id="q-email" required placeholder="john@example.com" class="w-full bg-gray-50 border border-gray-100 rounded-3xl px-8 py-5 text-[16px] focus:outline-none focus:border-amber-500 shadow-sm">
                        </div>
                    </div>

                    <div class="bg-amber-50/50 p-8 rounded-[2.5rem] border border-amber-100">
                        <p class="text-[14px] font-black text-amber-700 uppercase tracking-widest mb-6">You have selected:</p>
                        <ul class="space-y-3">
                            <li class="text-[16px] font-bold text-gray-700 flex justify-between">
                                <span class="uppercase">Frame:</span> <span id="m-frame">---</span>
                            </li>
                            <li class="text-[16px] font-bold text-gray-700 flex justify-between">
                                <span class="uppercase">Liner:</span> <span id="m-liner">---</span>
                            </li>
                            <li class="text-[16px] font-bold text-gray-700 flex justify-between border-t border-amber-200 pt-4 mt-4">
                                <span class="uppercase">Artwork:</span> <span id="m-art">---</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex items-center gap-6 pt-6">
                        <button type="button" onclick="closeQuoteModal()" class="flex-1 py-6 rounded-2xl text-[16px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 transition-colors">Cancel</button>
                        <button type="submit" class="flex-[2] bg-gray-900 text-white py-6 rounded-2xl text-[16px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-amber-600 transition-all">Submit Inquiry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        const FRAMES = <?php echo json_encode($frames); ?>;
        const LINERS = <?php echo json_encode($liners); ?>;
        const ARTS = <?php echo json_encode($arts); ?>;

        let state = {
            frame: <?php echo $defFrame; ?>,
            liner: <?php echo $defLiner; ?>,
            art: <?php echo $defArt; ?>,
            activeTab: 'frame'
        };

        function updateUI() {
            // Update Visualization
            if (state.frame) {
                document.getElementById('v-frame-img').src = state.frame.image_path;
                
                // Update Thumbnail
                const thumb = document.getElementById('s-frame-thumb');
                thumb.src = state.frame.detail_image_path ? state.frame.detail_image_path : state.frame.image_path;
                document.getElementById('s-frame-preview').classList.remove('hidden');
            }

            if (state.liner) {
                document.getElementById('v-liner-img').src = state.liner.image_path;
                document.getElementById('v-liner-img').classList.remove('hidden');
                document.getElementById('v-liner-empty').classList.add('hidden');
                
                // Update Thumbnail
                const thumb = document.getElementById('s-liner-thumb');
                thumb.src = state.liner.detail_image_path ? state.liner.detail_image_path : state.liner.image_path;
                document.getElementById('s-liner-preview').classList.remove('hidden');
                
                // Adaptive Scale
                document.getElementById('v-art-container').style.width = '86%';
                document.getElementById('v-art-container').style.height = '76%';
            } else {
                document.getElementById('v-liner-img').classList.add('hidden');
                document.getElementById('v-liner-empty').classList.remove('hidden');
                document.getElementById('s-liner-preview').classList.add('hidden');
                
                // Full scale if no liner at all
                document.getElementById('v-art-container').style.width = '91.5%';
                document.getElementById('v-art-container').style.height = '85%';
            }

            if (state.art) {
                document.getElementById('v-art-img').src = state.art.image_path;
                
                // Update Thumbnail
                const thumb = document.getElementById('s-art-thumb');
                thumb.src = state.art.image_path;
                document.getElementById('s-art-preview').classList.remove('hidden');
            }

            renderGrid();
        }

        function handleLightbox(type) {
            let item = state[type];
            if (!item) return;
            let src = (type === 'art') ? item.image_path : (item.detail_image_path || item.image_path);
            openLightbox(src);
        }

        function openLightbox(src) {
            if (!src) return;
            const modal = document.getElementById('lightbox-modal');
            const img = document.getElementById('lightbox-img');
            img.src = src;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const modal = document.getElementById('lightbox-modal');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function switchTab(tab) {
            state.activeTab = tab;
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('bg-gray-900', 'text-white');
                b.classList.add('text-gray-400');
            });
            document.getElementById('tab-' + tab).classList.add('bg-gray-900', 'text-white');
            document.getElementById('tab-' + tab).classList.remove('text-gray-400');
            renderGrid();
        }

        function selectItem(id) {
            let list = state.activeTab === 'frame' ? FRAMES : (state.activeTab === 'liner' ? LINERS : ARTS);
            state[state.activeTab] = list.find(i => i.id == id || i.internal_id == id);
            updateUI();
        }

        function renderGrid() {
            const container = document.getElementById('selector-container');
            container.innerHTML = '';
            let list = state.activeTab === 'frame' ? FRAMES : (state.activeTab === 'liner' ? LINERS : ARTS);

            list.forEach(item => {
                let isSelected = state[state.activeTab] && (state[state.activeTab].id === item.id);
                let btn = document.createElement('button');
                btn.className = `group relative flex flex-col p-3 rounded-[1.5rem] border-2 transition-all ${isSelected ? 'border-amber-600 bg-amber-50 shadow-md' : 'border-gray-100 hover:border-gray-200 bg-white'}`;
                btn.onclick = () => selectItem(item.id);

                let imgHtml = '';
                if (item.image_path) {
                    // Zoom logic for frames/liners
                    let zoomClass = (state.activeTab !== 'art') ? 'zoom-thumbnail' : 'w-full h-full object-cover';
                    imgHtml = `<img src="${item.image_path}" class="${zoomClass}" loading="lazy">`;
                } else {
                    imgHtml = `<div class="w-full h-full flex items-center justify-center text-[16px] font-black uppercase text-gray-300">None</div>`;
                }

                btn.innerHTML = `
                    <div class="w-full aspect-square rounded-xl overflow-hidden mb-4 bg-gray-50 relative">
                        ${imgHtml}
                    </div>
                    <span class="text-[16px] font-black text-gray-800 text-center uppercase truncate w-full mb-1">${item.name}</span>
                    ${isSelected ? '<div class="absolute top-4 right-4 bg-amber-600 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-lg"><i class="fa-solid fa-check text-[10px]"></i></div>' : ''}
                `;
                container.appendChild(btn);
            });
        }

        function openQuoteModal() {
            document.getElementById('m-frame').innerText = state.frame ? state.frame.name : 'N/A';
            document.getElementById('m-liner').innerText = state.liner ? state.liner.name : 'None';
            document.getElementById('m-art').innerText = state.art ? state.art.name : 'N/A';
            const modal = document.getElementById('quote-modal');
            modal.style.display = 'block';
            modal.scrollTo(0, 0); // Ensure it starts at top
        }

        function closeQuoteModal() {
            document.getElementById('quote-modal').style.display = 'none';
        }

        document.getElementById('quote-form').onsubmit = function(e) {
            e.preventDefault();
            
            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }

            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = 'Sending...';
            btn.disabled = true;

            const payload = {
                firstName: document.getElementById('q-first-name').value,
                lastName: document.getElementById('q-last-name').value,
                phone: document.getElementById('q-phone').value,
                email: document.getElementById('q-email').value,
                selection: {
                    frame_id: state.frame ? state.frame.internal_id : null,
                    frame_name: state.frame ? state.frame.name : null,
                    liner_id: state.liner ? state.liner.internal_id : null,
                    liner_name: state.liner ? state.liner.name : null,
                    art_id: state.art ? state.art.internal_id : null,
                    art_name: state.art ? state.art.name : null
                }
            };

            fetch('api/quote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeQuoteModal();
                    e.target.reset();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                alert('Connection error. Please try again.');
            })
            .finally(() => {
                btn.innerText = originalText;
                btn.disabled = false;
            });
        };

        // Init
        updateUI();

        // Sidebar Back to Top Logic
        const sidebarScroll = document.querySelector('.custom-scrollbar');
        const sidebarBtn = document.getElementById('sidebarToTop');

        if (sidebarScroll && sidebarBtn) {
            sidebarScroll.addEventListener('scroll', () => {
                if (sidebarScroll.scrollTop > 300) {
                    sidebarBtn.classList.remove('opacity-0', 'translate-y-10', 'pointer-events-none');
                    sidebarBtn.classList.add('opacity-100', 'translate-y-0');
                } else {
                    sidebarBtn.classList.add('opacity-0', 'translate-y-10', 'pointer-events-none');
                    sidebarBtn.classList.remove('opacity-100', 'translate-y-0');
                }
            });
        }

        function scrollSidebarTop() {
            sidebarScroll.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
