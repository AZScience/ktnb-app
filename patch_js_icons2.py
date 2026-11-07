import re

path = 'resources/js/interactive-report.js'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

target = '''
        columnIconHtml(iconOrCol) {
            const path = typeof iconOrCol === 'object' && iconOrCol !== null
                ? this.columnHeaderIconPath(iconOrCol)
                : iconPathForName(iconOrCol);
            if (!path) {
                return '';
            }
            return <svg class="h-3.5 w-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d=""></path></svg>;
        },
'''

replacement = '''
        iconToneForName(name) {
            const map = {
                'id-card': 'text-amber-500',
                'calendar': 'text-sky-500',
                'file-text': 'text-blue-500',
                'hash': 'text-gray-500',
                'user': 'text-purple-500',
                'users': 'text-indigo-500',
                'shield-check': 'text-emerald-500',
                'building': 'text-rose-500',
                'map-pin': 'text-red-500',
                'library': 'text-teal-500',
                'landmark': 'text-cyan-500',
                'note': 'text-fuchsia-500',
                'activity': 'text-yellow-600',
                'clock': 'text-sky-500',
                'check': 'text-green-500',
                'x': 'text-red-500',
            };
            return map[name] || 'text-[var(--nttu-primary)]';
        },

        columnIconHtml(iconOrCol) {
            const name = typeof iconOrCol === 'object' && iconOrCol !== null ? iconOrCol.icon : iconOrCol;
            const path = typeof iconOrCol === 'object' && iconOrCol !== null
                ? this.columnHeaderIconPath(iconOrCol)
                : iconPathForName(iconOrCol);
            if (!path) {
                return '';
            }
            const colorClass = this.iconToneForName(name);
            return <svg class="h-4 w-4 shrink-0 " fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d=""></path></svg>;
        },
'''

content = content.replace(target.strip(), replacement.strip())

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated JS successfully using replace")
