const fs = require('fs');
const path = require('path');
const markdownIt = require('markdown-it');
const { JSDOM } = require('jsdom');
const util = require('util');
const exec = util.promisify(require('child_process').exec);
const Prism = require('prismjs');

// CSS Links
const tailwindCSSLink = `<link href="styles.css" rel="stylesheet">`;
const prismJSLink = `<link href="../prism.css" rel="stylesheet">
<script src="../prism.js"></script>`;

// Input and output directories
const inputDir = path.join(__dirname, 'content');
const outputDir = path.join(__dirname, 'docs');

// Set the footer and title text
const footerText = '';
const titleText = 'GenerateClient Documentation';

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
          <!--
          <div class="text-sm text-gray-500">${footerText}
            <div class="mt-4 sticky bottom-0">
              <a href="${githubURL}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center bg-gray-200 px-3 py-2 rounded-md text-gray-700 hover:bg-gray-300 transition-colors duration-300 ease-in-out">
                <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M12 0C5.373 0 0 5.373 0 12c0 5.285 3.438 9.752 8.205 11.32.6.111.82-.257.82-.574 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.386-1.333-1.755-1.333-1.755-1.09-.745.083-.73.083-.73 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.805 1.305 3.485.998.108-.775.42-1.305.763-1.605-2.665-.301-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.125-.302-.54-1.524.12-3.176 0 0 1.005-.322 3.3 1.23a11.532 11.532 0 0 1 3-.405 11.527 11.527 0 0 1 3 .405c2.29-1.552 3.297-1.23 3.297-1.23.66 1.652.245 2.874.12 3.176.77.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.43.36.81 1.095.81 2.22 0 1.604-.015 2.894-.015 3.286 0 .317.215.688.825.573C20.565 21.748 24 17.28 24 12c0-6.627-5.373-12-12-12z" />
                </svg>
                GitHub
              </a>
            </div>
          </div>
          -->
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