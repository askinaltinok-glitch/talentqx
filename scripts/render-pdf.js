#!/usr/bin/env node
/**
 * PDF Renderer using Playwright
 *
 * Usage: node render-pdf.js --input <html-file> --output <pdf-file> [--locale tr|en] [--format A4|Letter]
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Parse command line arguments
const args = process.argv.slice(2);
const options = {
    input: null,
    output: null,
    locale: 'tr',
    format: 'A4',
};

for (let i = 0; i < args.length; i++) {
    switch (args[i]) {
        case '--input':
            options.input = args[++i];
            break;
        case '--output':
            options.output = args[++i];
            break;
        case '--locale':
            options.locale = args[++i];
            break;
        case '--format':
            options.format = args[++i];
            break;
    }
}

// Validate required arguments
if (!options.input || !options.output) {
    console.error('Usage: node render-pdf.js --input <html-file> --output <pdf-file>');
    process.exit(1);
}

// Check input file exists
if (!fs.existsSync(options.input)) {
    console.error(`Input file not found: ${options.input}`);
    process.exit(1);
}

async function renderPdf() {
    let browser;

    try {
        // Launch browser
        browser = await chromium.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
        });

        const context = await browser.newContext({
            locale: options.locale === 'en' ? 'en-US' : 'tr-TR',
        });

        const page = await context.newPage();

        // Read HTML content
        const htmlContent = fs.readFileSync(options.input, 'utf8');

        // Set content with base URL for relative paths
        const baseDir = path.dirname(path.resolve(options.input));
        await page.setContent(htmlContent, {
            waitUntil: 'networkidle',
        });

        // Wait for fonts and images to load
        await page.waitForTimeout(1000);

        // Generate PDF
        await page.pdf({
            path: options.output,
            format: options.format,
            printBackground: true,
            margin: {
                top: '20mm',
                right: '15mm',
                bottom: '20mm',
                left: '15mm',
            },
            displayHeaderFooter: false,
        });

        console.log(`PDF generated: ${options.output}`);
        process.exit(0);

    } catch (error) {
        console.error(`PDF generation failed: ${error.message}`);
        process.exit(1);
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

renderPdf();
