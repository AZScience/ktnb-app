const fs = require('fs');
const path = require('path');

function processFiles(dir, ext, callback) {
    fs.readdirSync(dir).forEach(file => {
        let p = path.join(dir, file);
        if (fs.statSync(p).isDirectory()) {
            processFiles(p, ext, callback);
        } else if (p.endsWith(ext)) {
            let content = fs.readFileSync(p, 'utf8');
            let newContent = callback(content, p);
            if (content !== newContent) {
                fs.writeFileSync(p, newContent, 'utf8');
                console.log('Updated JS: ' + p);
            }
        }
    });
}

// Update JS timeout
processFiles('resources/js', '.js', (content) => {
    // This matches: setTimeout(() => { this.toast = null; }, 3000);
    content = content.replace(/setTimeout\(\(\) => \{\s*this\.toast = null;?\s*\}, \d+\);/g, 
                           'setTimeout(() => { this.toast = null; }, 30000);');
    
    // This matches: setTimeout(() => { if (this.toast?.message === translated) { this.toast = null; } }, 3000);
    content = content.replace(/setTimeout\(\(\) => \{\s*if \(this\.toast\?\.message === translated\) \{\s*this\.toast = null;\s*\}\s*\}, \d+\);/g, 
                           'setTimeout(() => {\n                if (this.toast?.message === translated) {\n                    this.toast = null;\n                }\n            }, 30000);');
    return content;
});
