const SIDEBAR_COOKIE = 'sidebar_state';
const SIDEBAR_MAX_AGE = 60 * 60 * 24 * 7;

function isMobile() {
    return typeof window !== 'undefined' && window.innerWidth < 768;
}

function readSidebarOpen() {
    if (isMobile()) {
        return false;
    }
    return true;
}

function persistSidebarOpen(open) {
    if (isMobile()) {
        return;
    }
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
            if (isMobile()) {
                this.sidebarOpen = false;
            } else {
                this.sidebarOpen = true;
                persistSidebarOpen(true);
            }

            if (initialOpenMenu) {
                this.openMenuSection = initialOpenMenu;
            }

            window.addEventListener('resize', () => {
                if (isMobile() && this.sidebarOpen) {
                    this.sidebarOpen = false;
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'b' && (event.metaKey || event.ctrlKey)) {
                    event.preventDefault();
                    this.toggleSidebar();
                }
            });
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            if (!isMobile()) {
                persistSidebarOpen(this.sidebarOpen);
            }
        },

        expandSidebar() {
            if (!this.sidebarOpen) {
                this.sidebarOpen = true;
                if (!isMobile()) {
                    persistSidebarOpen(true);
                }
            }
        },

        closeSidebar() {
            this.sidebarOpen = false;
            if (!isMobile()) {
                persistSidebarOpen(false);
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
