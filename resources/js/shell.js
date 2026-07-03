const SIDEBAR_COOKIE = 'sidebar_state';
const SIDEBAR_MAX_AGE = 60 * 60 * 24 * 7;

function readSidebarOpen() {
    try {
        const fromStorage = localStorage.getItem(SIDEBAR_COOKIE);
        if (fromStorage === 'true' || fromStorage === 'false') {
            return fromStorage === 'true';
        }
        const match = document.cookie.match(new RegExp(`(?:^|; )${SIDEBAR_COOKIE}=(true|false)`));
        if (match) {
            return match[1] === 'true';
        }
    } catch {
        // ignore
    }

    return true;
}

function persistSidebarOpen(open) {
    try {
        localStorage.setItem(SIDEBAR_COOKIE, String(open));
        document.cookie = `${SIDEBAR_COOKIE}=${open}; path=/; max-age=${SIDEBAR_MAX_AGE}`;
        document.documentElement.dataset.sidebar = open ? 'expanded' : 'collapsed';
    } catch {
        // ignore
    }
}

export function registerNttuShell(Alpine) {
    Alpine.data('nttuShell', (initialOpenMenu = null) => ({
        sidebarOpen: readSidebarOpen(),
        openMenuSection: initialOpenMenu || null,

        init() {
            persistSidebarOpen(this.sidebarOpen);
            if (initialOpenMenu) {
                this.openMenuSection = initialOpenMenu;
            }

            window.addEventListener('keydown', (event) => {
                if (event.key === 'b' && (event.metaKey || event.ctrlKey)) {
                    event.preventDefault();
                    this.toggleSidebar();
                }
            });
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            persistSidebarOpen(this.sidebarOpen);
        },

        expandSidebar() {
            if (!this.sidebarOpen) {
                this.sidebarOpen = true;
                persistSidebarOpen(true);
            }
        },

        toggleMenu(section) {
            if (!this.sidebarOpen) {
                this.expandSidebar();
                this.openMenuSection = section;
                return;
            }

            this.openMenuSection = this.openMenuSection === section ? null : section;
        },

        isMenuOpen(section) {
            return this.sidebarOpen && this.openMenuSection === section;
        },
    }));
}
