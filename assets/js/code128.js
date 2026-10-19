/**
 * Code 128 Vector SVG Barcode Generator for Curtiss POS
 * High-resolution 203 DPI thermal printer optimized.
 * Pure standalone JavaScript, zero dependencies.
 */
(function(window) {
    'use strict';

    // Standard Code 128 Patterns (widths of alternating bars & spaces, each symbol has 6 elements summing to 11 modules)
    const CODE128_PATTERNS = [
        "212222", "222122", "222221", "121223", "121322", "131222", "122213", "122312", "132212", "221213", // 0-9
        "221312", "231212", "112232", "122132", "122231", "113222", "123122", "123221", "223211", "221132", // 10-19
        "221231", "213212", "223112", "312131", "311222", "321122", "321221", "312212", "322112", "322211", // 20-29
        "212123", "212321", "232121", "111323", "131123", "131321", "112313", "132113", "132311", "211313", // 30-39
        "231113", "231311", "112133", "112331", "132131", "113123", "113321", "133121", "313121", "211331", // 40-49
        "231131", "213113", "213311", "213131", "311123", "311321", "331121", "312113", "312311", "332111", // 50-59
        "314111", "221411", "431111", "111224", "111422", "121124", "121421", "141122", "141221", "112214", // 60-69
        "112412", "122114", "122411", "142112", "142211", "241211", "221114", "413111", "241112", "134111", // 70-79
        "111242", "121142", "121241", "114212", "124112", "124211", "411212", "421112", "421211", "212141", // 80-89
        "214121", "412121", "111143", "111341", "131141", "114113", "114311", "411113", "411311", "113141", // 90-99
        "114131", "311141", "411131", "211412", "211214", "211232", "2331112" // 100-106 (106 is STOP: 7 elements, 13 modules)
    ];

    const START_B = 104;
    const START_C = 105;
    const STOP = 106;

    /**
     * Encode a text string into Code 128 symbols
     */
    function encodeCode128(text) {
        if (!text || text.length === 0) return null;

        const isAllDigits = /^\d+$/.test(text);
        let symbols = [];

        if (isAllDigits && text.length >= 4 && (text.length % 2 === 0)) {
            // Pure Code 128 C: Even number of digits (cuts symbols in half, 100% universal scanner support)
            symbols.push(START_C);
            for (let i = 0; i < text.length; i += 2) {
                symbols.push(parseInt(text.substr(i, 2), 10));
            }
        } else if (isAllDigits && text.length >= 5 && (text.length % 2 !== 0)) {
            // Standard Code 128 B start + switch to Code C (symbol 99) for odd-length numeric barcodes (e.g. 13-digit retail numbers)
            // Cuts symbol count by ~35% so bars print significantly wider and scan reliably on 203 DPI printers
            symbols.push(START_B);
            symbols.push(text.charCodeAt(0) - 32);
            symbols.push(99); // Switch to Code C
            for (let i = 1; i < text.length; i += 2) {
                symbols.push(parseInt(text.substr(i, 2), 10));
            }
        } else {
            // Pure Code 128 B: Alphanumeric strings (e.g. CBRZB12)
            symbols.push(START_B);
            for (let i = 0; i < text.length; i++) {
                const code = text.charCodeAt(i);
                if (code >= 32 && code <= 126) {
                    symbols.push(code - 32);
                } else {
                    symbols.push(0); // Fallback to space
                }
            }
        }

        // Calculate checksum: (start + sum(index * val)) % 103
        let sum = symbols[0];
        for (let i = 1; i < symbols.length; i++) {
            sum += i * symbols[i];
        }
        const checksum = sum % 103;
        symbols.push(checksum);
        symbols.push(STOP);

        return symbols;
    }

    /**
     * Generate SVG string for Code 128 Barcode
     * @param {string} text - Value to encode
     * @param {object} options - Sizing and appearance options
     * @returns {string} SVG HTML markup
     */
    function generateSVG(text, options) {
        options = options || {};
        const safeText = String(text || '').trim();
        if (!safeText) return '';

        const width = options.width || 1.1; // module width in pixels
        const height = options.height || 34; // barcode height in pixels
        const showText = options.showText || false;
        const fontSize = options.fontSize || 10;
        const quietZone = (options.quietZone !== undefined) ? options.quietZone : 4;
        const preserveAspect = options.preserveAspectRatio || 'none';

        // 1. Primary: Use standard JsBarcode library if available in runtime
        if (typeof window.JsBarcode === 'function') {
            try {
                const svgNode = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                window.JsBarcode(svgNode, safeText, {
                    format: "CODE128",
                    width: width,
                    height: height,
                    displayValue: showText,
                    fontSize: fontSize,
                    margin: quietZone,
                    background: "#ffffff",
                    lineColor: "#000000"
                });
                svgNode.setAttribute("preserveAspectRatio", preserveAspect);
                svgNode.setAttribute("shape-rendering", "crispEdges");
                svgNode.style.width = "100%";
                svgNode.style.height = "100%";
                svgNode.style.display = "block";
                svgNode.style.margin = "0 auto";
                return svgNode.outerHTML;
            } catch (e) {
                console.warn('JsBarcode render failed, falling back to standalone generator:', e);
            }
        }

        // 2. Standalone Code 128 vector generator (Native fallback)
        const symbols = encodeCode128(safeText);
        if (!symbols) return '';

        let x = quietZone;
        let rects = [];

        for (let s = 0; s < symbols.length; s++) {
            const sym = symbols[s];
            const pattern = CODE128_PATTERNS[sym];
            if (!pattern) continue;

            let isBar = true;
            for (let p = 0; p < pattern.length; p++) {
                const w = parseInt(pattern[p], 10) * width;
                if (isBar) {
                    rects.push(`<rect x="${x.toFixed(2)}" y="0" width="${w.toFixed(2)}" height="${height}" fill="#000000" />`);
                }
                x += w;
                isBar = !isBar;
            }
        }

        const totalWidth = x + quietZone;
        const totalHeight = showText ? (height + fontSize + 3) : height;

        let textSvg = '';
        if (showText) {
            const textY = height + fontSize;
            textSvg = `<text x="${(totalWidth / 2).toFixed(2)}" y="${textY}" text-anchor="middle" font-family="'Courier New', Courier, monospace" font-size="${fontSize}px" font-weight="700" fill="#000000" letter-spacing="1px">${escapeXml(text)}</text>`;
        }

        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${totalWidth.toFixed(2)} ${totalHeight}" preserveAspectRatio="${preserveAspect}" shape-rendering="crispEdges" width="100%" height="100%" style="display:block; margin:0 auto; overflow:visible;">
            ${rects.join('\n            ')}
            ${textSvg}
        </svg>`;
    }

    function escapeXml(unsafe) {
        return String(unsafe).replace(/[<>&'"]/g, function (c) {
            switch (c) {
                case '<': return '&lt;';
                case '>': return '&gt;';
                case '&': return '&amp;';
                case '\'': return '&apos;';
                case '"': return '&quot;';
            }
        });
    }

    window.CurtissCode128 = {
        encode: encodeCode128,
        generateSVG: generateSVG
    };

})(window);
