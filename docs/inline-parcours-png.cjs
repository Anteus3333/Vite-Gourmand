const fs = require('fs');
const path = require('path');

const docsDir = __dirname;
const htmlPath = path.join(docsDir, 'parcours-export.html');
const printHtmlPath = path.join(docsDir, 'parcours-export-print.html');
const pngDir = path.join(docsDir, 'parcours-diagrams', 'png');

let html = fs.readFileSync(htmlPath, 'utf8');
html = html.replace(/src="parcours-diagrams\/png\/([^"]+\.png)"/g, (_match, file) => {
    const pngPath = path.join(pngDir, file);
    if (!fs.existsSync(pngPath)) {
        throw new Error(`PNG manquant : ${pngPath}`);
    }
    const encoded = fs.readFileSync(pngPath).toString('base64');
    return `src="data:image/png;base64,${encoded}"`;
});

fs.writeFileSync(printHtmlPath, html, 'utf8');
console.log('HTML pret : ' + printHtmlPath);
