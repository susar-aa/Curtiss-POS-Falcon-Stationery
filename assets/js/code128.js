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
    /**
     * Encode a text string into Code 128 symbols with dynamic Set B <-> Set C switching
     */
    function encodeCode128(text) {
        if (!text || text.length === 0) return null;

        const isAllDigits = /^\d+$/.test(text);
        let symbols = [];

        if (isAllDigits && text.length >= 4 && (text.length % 2 === 0)) {
            // Pure Code 128 C: Even number of digits (cuts symbols in half)
            symbols.push(START_C);
            for (let i = 0; i < text.length; i += 2) {
                symbols.push(parseInt(text.substr(i, 2), 10));
            }
        } else if (isAllDigits && text.length >= 5 && (text.length % 2 !== 0)) {
            // Start B + 1st digit + Switch to C
            symbols.push(START_B);
            symbols.push(text.charCodeAt(0) - 32);
            symbols.push(99); // Switch to Code C
            for (let i = 1; i < text.length; i += 2) {
                symbols.push(parseInt(text.substr(i, 2), 10));
            }
        } else {
            // Alphanumeric with dynamic B -> C switching for numeric runs
            let initialDigits = 0;
            while (initialDigits < text.length && /\d/.test(text[initialDigits])) {
                initialDigits++;
            }

            let currentMode = 'B';
            let i = 0;

            if (initialDigits >= 4) {
                currentMode = 'C';
                symbols.push(START_C);
                const pairs = Math.floor(initialDigits / 2);
                for (let p = 0; p < pairs; p++) {
                    symbols.push(parseInt(text.substr(i, 2), 10));
                    i += 2;
                }
            } else {
                symbols.push(START_B);
                currentMode = 'B';
            }

            while (i < text.length) {
                if (currentMode === 'B') {
                    // Count consecutive digits starting at index i
                    let digCount = 0;
                    while ((i + digCount) < text.length && /\d/.test(text[i + digCount])) {
                        digCount++;
                    }

                    const isEnd = (i + digCount === text.length);
                    // Switch to C if 4+ digits, or at the end with 2+ digits
                    let canSwitchToC = (digCount >= 4) || (isEnd && digCount >= 2);

                    if (canSwitchToC) {
                        symbols.push(99); // Switch to Code C
                        currentMode = 'C';
                        const pairs = Math.floor(digCount / 2);
                        for (let p = 0; p < pairs; p++) {
                            symbols.push(parseInt(text.substr(i, 2), 10));
                            i += 2;
                        }
                        if (digCount % 2 !== 0) {
                            // Switch back to B for remaining single odd digit
                            symbols.push(100); // Switch to Code B
                            currentMode = 'B';
                            symbols.push(text.charCodeAt(i) - 32);
                            i++;
                        }
                    } else {
                        const code = text.charCodeAt(i);
                        symbols.push((code >= 32 && code <= 126) ? (code - 32) : 0);
                        i++;
                    }
                } else {
                    // In mode C, if next character is not a digit or only 1 digit remains
                    if (i >= text.length || !/\d/.test(text[i]) || (i + 1 < text.length && !/\d/.test(text[i + 1]))) {
                        symbols.push(100); // Switch to Code B
                        currentMode = 'B';
                    } else {
                        symbols.push(parseInt(text.substr(i, 2), 10));
                        i += 2;
                    }
                }
            }

            // Fallback check: if pure Set B produces fewer symbols, use pure Set B
            const pureBSymbols = [START_B];
            for (let j = 0; j < text.length; j++) {
                const c = text.charCodeAt(j);
                pureBSymbols.push((c >= 32 && c <= 126) ? (c - 32) : 0);
            }
            if (pureBSymbols.length < symbols.length) {
                symbols = pureBSymbols;
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

        const containerW = options.containerWidthMm || 28.5;
        const containerH = options.containerHeightMm || 8.5;
        const targetRatio = containerW / containerH; // ~3.35

        // Encode symbols with optimal Code 128 Set B <-> C switching
        const symbols = encodeCode128(safeText);
        if (!symbols) return '';

        // Stop is 13 modules, all other symbols are 11 modules
        const barModules = (symbols.length - 1) * 11 + 13;

        // 203 DPI thermal printer dot resolution (203 / 25.4 = 7.992 dots/mm)
        // 28.5mm available width = ~227 thermal dots max
        const dpi = options.dpi || 203;
        const maxAvailableDots = Math.floor(containerW * (dpi / 25.4)); // ~227 dots

        // Clean minimum quiet zone (7 modules) to maximize printable bar area
        const qzModules = (options.quietZoneModules !== undefined) ? options.quietZoneModules : 7;
        const totalModules = barModules + (qzModules * 2);

        // Strict integer dot widths: moduleWidth is strictly 2 if it fits, else strictly 1.
        // Never use fractional decimals like 1.5 or 1.3 on 203 DPI thermal heads!
        let moduleWidth = (options.width && options.autoWidth === false)
            ? Math.round(options.width)
            : ((totalModules * 2 <= maxAvailableDots) ? 2 : 1);
        if (moduleWidth < 1) moduleWidth = 1;

        // Preserve quiet zone on both sides
        const defaultQzPx = qzModules * moduleWidth;
        const marginLeft = (options.marginLeft !== undefined) ? Math.round(options.marginLeft) : defaultQzPx;
        const marginRight = (options.marginRight !== undefined) ? Math.round(options.marginRight) : defaultQzPx;

        const showText = options.showText || false;
        const fontSize = options.fontSize || 10;
        // preserveAspectRatio: 'none' stretches bars horizontally to fill 28.5mm, eliminating 1-dot dropouts
        const preserveAspect = options.preserveAspectRatio || 'none';

        const totalWidthPx = (barModules * moduleWidth) + marginLeft + marginRight;

        // Dynamic height: dynamically matches the aspect ratio of the label space to maximize bar height
        const dynamicBarHeight = Math.max(30, Math.round(totalWidthPx / targetRatio));
        // Use dynamic height unless explicit options.dynamicHeight === false with a positive options.height
        const height = (options.dynamicHeight === false && options.height) ? Math.round(options.height) : dynamicBarHeight;

        // 1. Primary: Use standard JsBarcode library if available in runtime
        if (typeof window.JsBarcode === 'function') {
            try {
                const svgNode = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                window.JsBarcode(svgNode, safeText, {
                    format: "CODE128",
                    width: moduleWidth, // strictly integer 1 or 2!
                    height: height,     // strictly integer!
                    displayValue: showText,
                    fontSize: fontSize,
                    marginTop: 0,
                    marginBottom: 0,
                    marginLeft: marginLeft,   // strictly integer!
                    marginRight: marginRight, // strictly integer!
                    background: "#ffffff",
                    lineColor: "#000000"
                });
                svgNode.setAttribute("preserveAspectRatio", preserveAspect);
                svgNode.setAttribute("shape-rendering", "crispEdges");
                svgNode.setAttribute("width", "100%");
                svgNode.setAttribute("height", "100%");
                svgNode.style.width = "100%";
                svgNode.style.height = "100%";
                svgNode.style.display = "block";
                svgNode.style.margin = "0 auto";
                svgNode.style.overflow = "visible";
                return svgNode.outerHTML;
            } catch (e) {
                console.warn('JsBarcode render failed, falling back to standalone generator:', e);
            }
        }

        // 2. Standalone Code 128 vector generator (Native fallback)
        let x = marginLeft;
        let rects = [];

        for (let s = 0; s < symbols.length; s++) {
            const sym = symbols[s];
            const pattern = CODE128_PATTERNS[sym];
            if (!pattern) continue;

            let isBar = true;
            for (let p = 0; p < pattern.length; p++) {
                const w = parseInt(pattern[p], 10) * moduleWidth;
                if (isBar) {
                    rects.push(`<rect x="${Math.round(x)}" y="0" width="${Math.round(w)}" height="${height}" fill="#000000" />`);
                }
                x += w;
                isBar = !isBar;
            }
        }

        const totalWidth = x + marginRight;
        const totalHeight = showText ? (height + fontSize + 3) : height;

        const bgRect = `<rect x="0" y="0" width="${Math.round(totalWidth)}" height="${totalHeight}" fill="#ffffff" />`;

        let textSvg = '';
        if (showText) {
            const textY = height + fontSize;
            textSvg = `<text x="${Math.round(totalWidth / 2)}" y="${textY}" text-anchor="middle" font-family="'Courier New', Courier, monospace" font-size="${fontSize}px" font-weight="700" fill="#000000" letter-spacing="1px">${escapeXml(safeText)}</text>`;
        }

        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${Math.round(totalWidth)} ${totalHeight}" preserveAspectRatio="${preserveAspect}" shape-rendering="crispEdges" width="100%" height="100%" style="display:block; margin:0 auto; overflow:visible;">
            ${bgRect}
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
