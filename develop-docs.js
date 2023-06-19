const fs = require('fs');
const path = require('path');
const markdownIt = require('markdown-it');
const { JSDOM } = require('jsdom');
const util = require('util');
const exec = util.promisify(require('child_process').exec);
const Prism = require('prismjs');
const browserSync = require('browser-sync').create();
const historyApiFallback = require('connect-history-api-fallback');

// CSS Links
const tailwindCSSLink = `<link href="styles.css" rel="stylesheet">`;
const prismJSLink = `<link href="../prism.css" rel="stylesheet">
<script src="../prism.js"></script>`;

// Input and output directories
const inputDir = path.join(__dirname, 'content');
const outputDir = path.join(__dirname, 'docs');

// Set the footer and title text
const footerText = '';
const titleText = 'Documentation';

// GitHub URL
const githubURL = 'https://github.com/generateapps/generateclient';

// Create the output directory if it doesn't exist
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir);
}

// Build CSS files
exec('npm run docs:css', function (error, stdOut, stdErr) {});

// Read the Markdown files from the input directory
fs.readdir(inputDir, (err, files) => {
  if (err) {
    console.error(err);
    process.exit(1);
  }

  // Get the list of existing files in the output directory
  const existingFiles = fs.readdirSync(outputDir);

  // Process each Markdown file
  files.forEach((file) => {
    const inputPath = path.join(inputDir, file);
    const outputPath = path.join(outputDir, file.replace('.md', '.html'));

    // Read the content of the Markdown file
    const markdown = fs.readFileSync(inputPath, 'utf8');

    // Convert Markdown to HTML using markdown-it
    const md = new markdownIt();
    const html = md.render(markdown);

    // Create a DOM and set the HTML content
    const dom = new JSDOM(`<!DOCTYPE html><html><head><title>${titleText}</title>${tailwindCSSLink}${prismJSLink}</head><body>
    <div class="flex">
      <div class="w-1/4 bg-gray-100 sticky top-0">
        <div class="h-screen flex flex-col justify-between p-4">
          <div>
            <h2 class="text-xl font-bold mb-4">${titleText}</h2>
            <ul class="space-y-2">
              ${getSidebarItems(files, file)}
            </ul>
          </div>
        </div>
      </div>
      <div class="w-3/4 prose lg:prose-xl bg-white p-8">
        ${html}
      </div>
    </div>
    </body></html>`);
    const document = dom.window.document;

    // Apply syntax highlighting to code blocks
    const codeBlocks = document.querySelectorAll('pre > code');
    codeBlocks.forEach((codeBlock) => {
      const language = codeBlock.className.replace('language-', '');
      if (Prism.languages[language]) {
        const code = codeBlock.textContent;
        const highlightedCode = Prism.highlight(code, Prism.languages[language], language);
        codeBlock.innerHTML = highlightedCode;
      }
    });

    // Apply Tailwind CSS classes to Markdown elements
    const markdownElements = document.querySelectorAll(
      'p, h1, h2, h3, h4, h5, h6, ul, ol, li, blockquote, table, th, td, code'
    );
    markdownElements.forEach((element) => {
      element.classList.add('markdown');
    });

    // Write the final HTML file
    fs.writeFileSync(outputPath, document.documentElement.outerHTML, 'utf8');

    // Remove the file from the existingFiles array
    const index = existingFiles.indexOf(path.parse(outputPath).base);
    if (index > -1) {
      existingFiles.splice(index, 1);
    }
  });

  // Delete the files from the output directory that don't exist in the input directory
  existingFiles.forEach((file) => {
    const filePath = path.join(outputDir, file);
    fs.unlinkSync(filePath);
  });

  console.log('Docs built');

  // Configure Lite Server with BrowserSync
  browserSync.init({
    server: outputDir,
    files: [inputDir, __filename],
    middleware: [historyApiFallback()],
    serveStatic: [
      { route: '/prism.css', dir: path.join(__dirname, 'prism.css'), options: { extensions: ['css'] } },
      { route: '/prism.js', dir: path.join(__dirname, 'prism.js'), options: { extensions: ['js'] } },
    ],
  });
});

function getSidebarItems(files, currentFile) {
  return files
    .map((file) => {
      const fileName = path.parse(file).name;
      const capitalizedFileName = fileName
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
      const fileLink = `${fileName}.html`;
      const isActive = fileName === path.parse(currentFile).name ? 'text-blue-600' : '';
      return `<li><a href="${fileLink}" class="hover:text-blue-600 ${isActive}">${capitalizedFileName}</a></li>`;
    })
    .join('');
}
