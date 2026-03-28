        </main>
        <footer class="px-4 lg:px-8 py-4 border-t border-white/[0.06]">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-600">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(Settings::get('platform_name', 'Amani Escrow')) ?>. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <a href="#" class="hover:text-gray-400 transition-colors">Privacy</a>
                    <a href="#" class="hover:text-gray-400 transition-colors">Terms</a>
                    <a href="#" class="hover:text-gray-400 transition-colors">Support</a>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- PWA Install Banner -->
<div id="pwaInstall" class="hidden fixed bottom-4 left-4 right-4 lg:left-auto lg:right-8 lg:w-96 z-50 glass-card rounded-2xl p-4 shadow-2xl glow-accent">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-accent/20 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-bold text-white">Install <?= htmlspecialchars(Settings::get('pwa_short_name', 'Amani')) ?></p>
            <p class="text-xs text-gray-400 mt-0.5">Add to homescreen for the best experience</p>
        </div>
        <button onclick="installPWA()" class="px-4 py-2 bg-accent text-surface text-sm font-bold rounded-xl hover:bg-accent-dark transition-colors">Install</button>
        <button onclick="dismissPWA()" class="p-1 text-gray-500 hover:text-white"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
</div>

<!-- Chat Support Widget -->
<div class="chat-widget" id="chatWidget">
    <div class="chat-panel glass-card" id="chatPanel">
        <div class="flex items-center gap-3 px-5 py-4 border-b border-white/[0.06]" style="background:rgba(19,24,37,.95)">
            <div class="w-9 h-9 rounded-full bg-accent flex items-center justify-center shrink-0">
                <?php if ($logoUrl): ?>
                    <img src="<?= htmlspecialchars($logoUrl) ?>" alt="" class="w-6 h-6 object-contain">
                <?php else: ?>
                    <svg class="w-5 h-5 text-surface" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-white"><?= htmlspecialchars($platformName) ?> Support</p>
                <p class="text-[11px] text-emerald-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span> Online</p>
            </div>
            <button onclick="toggleChat()" class="p-1 text-gray-500 hover:text-white"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-5 space-y-3 max-h-[320px] overflow-y-auto" id="chatMessages" style="background:#0B0F19">
            <div class="flex gap-2.5">
                <div class="w-7 h-7 rounded-full bg-accent/20 flex items-center justify-center shrink-0 mt-0.5"><svg class="w-3.5 h-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></div>
                <div class="bg-[#131825] border border-white/[0.06] rounded-2xl rounded-tl-md px-4 py-3 max-w-[240px]">
                    <p class="text-sm text-gray-300">Hi there! 👋 How can we help you today?</p>
                    <p class="text-[10px] text-gray-600 mt-1">Just now</p>
                </div>
            </div>
            <div class="space-y-2 pt-1">
                <button onclick="chatQuick('How do escrow transactions work?')" class="block w-full text-left px-3 py-2 rounded-xl border border-white/[0.06] text-xs text-gray-400 hover:text-white hover:bg-white/[0.03] transition-all">💡 How does escrow work?</button>
                <button onclick="chatQuick('I need help with a dispute')" class="block w-full text-left px-3 py-2 rounded-xl border border-white/[0.06] text-xs text-gray-400 hover:text-white hover:bg-white/[0.03] transition-all">⚠️ Help with a dispute</button>
                <button onclick="chatQuick('How do I deposit funds?')" class="block w-full text-left px-3 py-2 rounded-xl border border-white/[0.06] text-xs text-gray-400 hover:text-white hover:bg-white/[0.03] transition-all">💰 How to deposit funds</button>
                <button onclick="chatQuick('I want to contact support')" class="block w-full text-left px-3 py-2 rounded-xl border border-white/[0.06] text-xs text-gray-400 hover:text-white hover:bg-white/[0.03] transition-all">📧 Contact support team</button>
            </div>
        </div>
        <div class="px-4 py-3 border-t border-white/[0.06]" style="background:rgba(19,24,37,.95)">
            <form onsubmit="return chatSend(event)" class="flex gap-2">
                <input type="text" id="chatInput" placeholder="Type a message..." class="flex-1 px-3 py-2 bg-[#0B0F19] border border-white/[0.08] rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-1 focus:ring-accent/30">
                <button type="submit" class="px-3 py-2 bg-accent rounded-xl hover:opacity-90 transition-all"><svg class="w-4 h-4 text-surface" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg></button>
            </form>
        </div>
    </div>
    <div class="chat-bubble bg-accent" onclick="toggleChat()">
        <svg id="chatIconOpen" class="w-6 h-6 text-surface" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <svg id="chatIconClose" class="w-6 h-6 text-surface hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </div>
</div>

<script>
// Custom confirm modal — replaces browser confirm()
window._origConfirm = window.confirm;
window.confirm = function(msg) {
    return new Promise(function(){});
};
function amaniConfirm(msg, onYes, opts={}) {
    const o = document.createElement('div');
    o.className = 'confirm-overlay';
    o.innerHTML = `<div class="confirm-box">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl ${opts.danger ? 'bg-red-500/10' : 'bg-accent/10'} flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 ${opts.danger ? 'text-red-400' : 'text-accent'}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="${opts.icon || 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'}"/></svg>
            </div>
            <div><p class="text-base font-bold text-white">${opts.title || 'Confirm Action'}</p></div>
        </div>
        <p class="text-sm text-gray-400 leading-relaxed mb-6">${msg}</p>
        <div class="flex gap-3">
            <button onclick="this.closest('.confirm-overlay').remove()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-300 border border-white/10 hover:bg-white/[0.04] transition-all">Cancel</button>
            <button id="confirmYes" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold transition-all ${opts.danger ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-accent text-surface hover:opacity-90'}">${opts.confirmText || 'Confirm'}</button>
        </div>
    </div>`;
    document.body.appendChild(o);
    o.querySelector('#confirmYes').onclick = function(){ o.remove(); if(onYes) onYes(); };
    o.addEventListener('click', function(e){ if(e.target===o) o.remove(); });
}

// Override all onclick="return confirm(...)" forms
document.addEventListener('click', function(e) {
    const btn = e.target.closest('button[onclick*="confirm("], a[onclick*="confirm("]');
    if (!btn) return;
    const match = btn.getAttribute('onclick').match(/confirm\(['"](.*?)['"]\)/);
    if (!match) return;
    e.preventDefault(); e.stopPropagation();
    const msg = match[1];
    const form = btn.closest('form');
    const isDestructive = msg.toLowerCase().includes('cancel') || msg.toLowerCase().includes('reject') || msg.toLowerCase().includes('remove') || msg.toLowerCase().includes('delete');
    btn.removeAttribute('onclick');
    amaniConfirm(msg, function(){
        if (form) form.submit();
        else if (btn.tagName === 'A') window.location = btn.href;
    }, {
        danger: isDestructive,
        title: isDestructive ? 'Are you sure?' : 'Confirm Action',
        confirmText: isDestructive ? 'Yes, proceed' : 'Confirm',
        icon: isDestructive ? 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    });
}, true);

// Chat widget
function toggleChat(){
    const p=document.getElementById('chatPanel');
    const i1=document.getElementById('chatIconOpen');
    const i2=document.getElementById('chatIconClose');
    p.classList.toggle('open');
    i1.classList.toggle('hidden');
    i2.classList.toggle('hidden');
}
function chatQuick(msg){
    const c=document.getElementById('chatMessages');
    c.innerHTML+=`<div class="flex gap-2.5 justify-end"><div class="bg-accent/10 border border-accent/20 rounded-2xl rounded-tr-md px-4 py-3 max-w-[240px]"><p class="text-sm text-white">${msg}</p></div></div>`;
    setTimeout(()=>{c.innerHTML+=`<div class="flex gap-2.5"><div class="w-7 h-7 rounded-full bg-accent/20 flex items-center justify-center shrink-0 mt-0.5"><svg class="w-3.5 h-3.5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></div><div class="bg-[#131825] border border-white/[0.06] rounded-2xl rounded-tl-md px-4 py-3 max-w-[240px]"><p class="text-sm text-gray-300">Thanks for reaching out! Our team will get back to you shortly. You can also email us at support@${window.location.hostname}</p></div></div>`;c.scrollTop=c.scrollHeight;},800);
    c.scrollTop=c.scrollHeight;
}
function chatSend(e){e.preventDefault();const i=document.getElementById('chatInput');if(!i.value.trim())return false;chatQuick(i.value.trim());i.value='';return false;}

// Load notifications
fetch('<?= APP_URL ?>/api/notifications.php?limit=5').then(r=>r.json()).then(d=>{
    const l=document.getElementById('notifList');
    if(d.data&&d.data.length>0){l.innerHTML=d.data.map(n=>`<a href="${n.link||'#'}" class="flex items-start gap-3 px-4 py-3 hover:bg-white/[0.05] ${!n.is_read?'bg-accent/[0.04]':''}"><div class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center shrink-0 mt-0.5"><svg class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></div><div class="flex-1 min-w-0"><p class="text-sm font-medium text-white truncate">${n.title}</p><p class="text-xs text-gray-400 mt-0.5 line-clamp-2">${n.message}</p></div></a>`).join('')}else{l.innerHTML='<p class="px-4 py-8 text-sm text-gray-600 text-center">No notifications</p>'}
}).catch(()=>{document.getElementById('notifList').innerHTML='<p class="px-4 py-8 text-sm text-gray-600 text-center">No notifications</p>'});

// PWA
let deferredPrompt;
window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();deferredPrompt=e;if(!localStorage.getItem('pwa_dismissed'))document.getElementById('pwaInstall').classList.remove('hidden')});
function installPWA(){if(deferredPrompt){deferredPrompt.prompt();deferredPrompt.userChoice.then(r=>{document.getElementById('pwaInstall').classList.add('hidden');deferredPrompt=null})}}
function dismissPWA(){document.getElementById('pwaInstall').classList.add('hidden');localStorage.setItem('pwa_dismissed','1')}

// Service Worker
if('serviceWorker' in navigator && <?= Settings::get('pwa_enabled','1') === '1' ? 'true' : 'false' ?>){
    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js').catch(()=>{});
}
</script>
</body>
</html>
