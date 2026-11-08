// assets/js/app.js - Interaktivitas Frontend SIMRS Siloam Peduli

function initIcons() {
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    }
}

function toggleSidebar() {
    const app = document.getElementById('app');
    if (!app) return;

    if (window.innerWidth <= 900) {
        app.classList.toggle('open');
    } else {
        const isClosed = app.classList.toggle('sidebar-closed');
        try {
            localStorage.setItem('simrs_sidebar_closed', isClosed ? '1' : '0');
        } catch (e) {}
    }
}

// Tutup flash alert
function closeAlert(btn) {
    if (btn && btn.parentElement) {
        btn.parentElement.style.opacity = '0';
        setTimeout(() => btn.parentElement.remove(), 200);
    }
}

// Notification Panel Handlers
function toggleNotificationPanel(e) {
    if (e) e.stopPropagation();
    const panel = document.getElementById('notificationPanel');
    if (!panel) return;
    const isHidden = panel.classList.toggle('hidden');
    if (!isHidden) {
        initIcons();
    }
}

function markAllNotificationsRead(e) {
    if (e) e.stopPropagation();
    const bellBadge = document.getElementById('bellN');
    const notifSub = document.getElementById('notifSub');
    const items = document.querySelectorAll('.notif-item');

    if (bellBadge) {
        bellBadge.style.display = 'none';
        bellBadge.textContent = '0';
    }
    if (notifSub) {
        notifSub.innerHTML = '0 belum dibaca &bull; Seluruh pengingat telah ditandai';
    }
    items.forEach(item => {
        item.classList.add('read');
    });

    const uid = window.__CURRENT_USER_ID || '0';
    try {
        localStorage.setItem('simrs_notifs_read_' + uid, '1');
        document.cookie = 'simrs_notifs_read_' + uid + '=1; path=/; max-age=86400';
        document.documentElement.classList.add('notifs-read');
    } catch (err) {}
}

function handleNotifItemClick(e) {
    const badge = document.getElementById('bellN');
    if (badge) {
        badge.style.display = 'none';
    }
    const uid = window.__CURRENT_USER_ID || '0';
    try {
        localStorage.setItem('simrs_notifs_read_' + uid, '1');
        document.cookie = 'simrs_notifs_read_' + uid + '=1; path=/; max-age=86400';
        document.documentElement.classList.add('notifs-read');
    } catch (err) {}
}

// Logout Confirmation Dialog Handlers
function askLogout(e) {
    if (e) e.preventDefault();
    const dlg = document.getElementById('dlg-logout');
    if (dlg) {
        dlg.classList.remove('hidden');
        initIcons();
    }
}

function closeLogoutDialog() {
    const dlg = document.getElementById('dlg-logout');
    if (dlg) {
        dlg.classList.add('hidden');
    }
}

// Global click outside listener
document.addEventListener('click', (e) => {
    const panel = document.getElementById('notificationPanel');
    const bell = document.getElementById('bell');
    if (panel && !panel.classList.contains('hidden')) {
        if (!panel.contains(e.target) && (!bell || !bell.contains(e.target))) {
            panel.classList.add('hidden');
        }
    }
});

// Modal dialog helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        initIcons();
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeLogoutDialog();
        const notifPanel = document.getElementById('notificationPanel');
        if (notifPanel) notifPanel.classList.add('hidden');
        document.querySelectorAll('.modal-backdrop').forEach(m => {
            m.classList.add('hidden');
            m.style.display = 'none';
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initIcons();

    // Pulihkan status notifikasi jika sudah dibaca
    const uid = window.__CURRENT_USER_ID || '0';
    try {
        if (localStorage.getItem('simrs_notifs_read_' + uid) === '1' || document.cookie.indexOf('simrs_notifs_read_' + uid + '=1') !== -1) {
            const bellBadge = document.getElementById('bellN');
            const notifSub = document.getElementById('notifSub');
            if (bellBadge) {
                bellBadge.style.display = 'none';
                bellBadge.textContent = '0';
            }
            if (notifSub) {
                notifSub.innerHTML = '0 belum dibaca &bull; Seluruh pengingat telah ditandai';
            }
            document.querySelectorAll('.notif-item').forEach(item => {
                item.classList.add('read');
            });
            document.documentElement.classList.add('notifs-read');
        }
    } catch (e) {}

    // Handler buka / tutup sidebar
    const menuBtn = document.getElementById('menuBtn');
    if (menuBtn) {
        menuBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
    }

    // Pulihkan preferensi sidebar desktop dari localStorage
    if (window.innerWidth > 900) {
        try {
            if (localStorage.getItem('simrs_sidebar_closed') === '1') {
                const app = document.getElementById('app');
                if (app) {
                    app.classList.add('sidebar-closed');
                }
            }
        } catch (e) {}
    }

    // Klik overlay untuk menutup drawer pada mobile
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            const app = document.getElementById('app');
            if (app) {
                app.classList.remove('open');
            }
        });
    }
});
