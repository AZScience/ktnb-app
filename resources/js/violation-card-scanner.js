import jsQR from 'jsqr';
import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';
import { Html5Qrcode, Html5QrcodeSupportedFormats as F } from 'html5-qrcode';

const ALL_FORMATS = [
    F.QR_CODE,
    F.CODE_128,
    F.CODE_39,
    F.CODABAR,
    F.EAN_13,
    F.EAN_8,
    F.UPC_A,
    F.UPC_E,
    F.ITF,
    F.PDF_417,
    F.DATA_MATRIX,
    F.AZTEC,
];

const NATIVE_QR_FORMATS = ['qr_code', 'data_matrix', 'aztec', 'pdf417'];

const NATIVE_BARCODE_FORMATS = [
    'code_128',
    'code_39',
    'ean_13',
    'ean_8',
    'upc_a',
    'upc_e',
    'itf',
    'codabar',
    'pdf417',
    'data_matrix',
    'aztec',
];

const ZXING_QR_FORMATS = [
    BarcodeFormat.QR_CODE,
    BarcodeFormat.DATA_MATRIX,
    BarcodeFormat.AZTEC,
    BarcodeFormat.PDF_417,
];

const ZXING_BARCODE_FORMATS = [
    BarcodeFormat.CODE_128,
    BarcodeFormat.CODE_39,
    BarcodeFormat.CODABAR,
    BarcodeFormat.EAN_13,
    BarcodeFormat.EAN_8,
    BarcodeFormat.UPC_A,
    BarcodeFormat.UPC_E,
    BarcodeFormat.ITF,
    BarcodeFormat.PDF_417,
];

const ZXING_ALL_FORMATS = [...ZXING_BARCODE_FORMATS, ...ZXING_QR_FORMATS];

const zxingReaders = new Map();

function getZxingReader(mode = 'barcode') {
    const key = mode === 'all' ? 'all' : (mode === 'qr' ? 'qr' : 'barcode');
    if (!zxingReaders.has(key)) {
        const hints = new Map();
        hints.set(
            DecodeHintType.POSSIBLE_FORMATS,
            key === 'all' ? ZXING_ALL_FORMATS : (key === 'qr' ? ZXING_QR_FORMATS : ZXING_BARCODE_FORMATS),
        );
        hints.set(DecodeHintType.TRY_HARDER, true);
        zxingReaders.set(key, new BrowserMultiFormatReader(hints));
    }

    return zxingReaders.get(key);
}

function nativeFormatsForMode(mode = 'barcode') {
    return mode === 'qr' ? NATIVE_QR_FORMATS : NATIVE_BARCODE_FORMATS;
}

function remapFocusBox(hit, crop, sourceCanvas) {
    if (!hit?.focusBox || crop.x === 0 && crop.y === 0) {
        return hit;
    }

    return {
        ...hit,
        focusBox: {
            x: crop.x / sourceCanvas.width + hit.focusBox.x * (crop.w / sourceCanvas.width),
            y: crop.y / sourceCanvas.height + hit.focusBox.y * (crop.h / sourceCanvas.height),
            w: hit.focusBox.w * (crop.w / sourceCanvas.width),
            h: hit.focusBox.h * (crop.h / sourceCanvas.height),
        },
    };
}

const FILE_DECODER_ID = 'violation-card-file-decoder';

export function violationParseScannedCode(raw) {
    const trimmed = String(raw || '').trim();
    const candidates = new Set();
    const add = (value) => {
        const normalized = String(value || '').trim().toLowerCase();
        if (normalized) {
            candidates.add(normalized);
        }
    };

    if (!trimmed) {
        return { displayCode: '', candidates: [], isCCCD: false, scannedName: '' };
    }

    if (trimmed.includes('|')) {
        const parts = trimmed.split('|');
        const citizenId = String(parts[0] || '').trim();
        const scannedName = String(parts[2] || parts[1] || '').trim();
        add(citizenId);
        return {
            displayCode: citizenId || trimmed,
            candidates: [...candidates],
            isCCCD: true,
            scannedName,
        };
    }

    add(trimmed);
    const compact = trimmed.replace(/\s+/g, '');
    add(compact);

    const digitsOnly = compact.replace(/\D/g, '');
    if (digitsOnly) {
        add(digitsOnly);
        add(digitsOnly.replace(/^0+/, ''));
    }

    const alnum = compact.replace(/[^a-zA-Z0-9]/g, '');
    if (alnum) {
        add(alnum);
    }

    return {
        displayCode: trimmed,
        candidates: [...candidates],
        isCCCD: false,
        scannedName: '',
    };
}

export function violationFindStudent(map, candidates) {
    for (const code of candidates) {
        if (map?.[code]) {
            return { student: map[code], matchedCode: code };
        }
    }

    const keys = Object.keys(map || {});
    for (const code of candidates) {
        if (code.length < 5) {
            continue;
        }

        const suffixHit = keys.find((key) => key.endsWith(code) || code.endsWith(key));
        if (suffixHit) {
            return { student: map[suffixHit], matchedCode: suffixHit };
        }
    }

    return null;
}

export function violationDescribeScanResult(raw) {
    const parsed = violationParseScannedCode(raw);
    if (!parsed.displayCode) {
        return '';
    }

    if (parsed.isCCCD) {
        const name = parsed.scannedName ? ` · ${parsed.scannedName}` : '';
        return `CCCD: ${parsed.displayCode}${name}`;
    }

    return `Mã: ${parsed.displayCode}`;
}

function toJpegFile(input) {
    if (input instanceof File) {
        return input;
    }

    const type = input?.type || 'image/jpeg';
    const name = input?.name || 'card-capture.jpg';
    return new File([input], name, { type });
}

async function loadImageToCanvas(file) {
    const url = URL.createObjectURL(file);
    try {
        const image = new Image();
        image.decoding = 'async';
        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = reject;
            image.src = url;
        });

        const maxSide = 1800;
        let { width, height } = image;
        if (Math.max(width, height) > maxSide) {
            const scale = maxSide / Math.max(width, height);
            width = Math.round(width * scale);
            height = Math.round(height * scale);
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx?.drawImage(image, 0, 0, width, height);
        return canvas;
    } finally {
        URL.revokeObjectURL(url);
    }
}

function getCropRegions(width, height) {
    const regions = [{ x: 0, y: 0, w: width, h: height }];

    const qrSize = Math.round(Math.min(width, height) * 0.88);
    regions.push({
        x: Math.round((width - qrSize) / 2),
        y: Math.round((height - qrSize) / 2),
        w: qrSize,
        h: qrSize,
    });

    const barW = Math.round(width * 0.94);
    const barH = Math.round(Math.min(height * 0.45, width * 0.35));
    [0.32, 0.5, 0.68].forEach((centerY) => {
        regions.push({
            x: Math.round((width - barW) / 2),
            y: Math.max(0, Math.round(height * centerY - barH / 2)),
            w: barW,
            h: Math.min(barH, height),
        });
    });

    return regions;
}

function scaleCanvas(sourceCanvas, scale) {
    if (scale === 1) {
        return sourceCanvas;
    }

    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(sourceCanvas.width * scale));
    canvas.height = Math.max(1, Math.round(sourceCanvas.height * scale));
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) {
        return sourceCanvas;
    }

    ctx.imageSmoothingEnabled = scale > 1;
    ctx.drawImage(sourceCanvas, 0, 0, canvas.width, canvas.height);
    return canvas;
}

function highContrastCanvas(canvas) {
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) {
        return canvas;
    }

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const { data } = imageData;
    let min = 255;
    let max = 0;

    for (let i = 0; i < data.length; i += 4) {
        const gray = (data[i] * 0.299) + (data[i + 1] * 0.587) + (data[i + 2] * 0.114);
        min = Math.min(min, gray);
        max = Math.max(max, gray);
    }

    const range = Math.max(1, max - min);
    for (let i = 0; i < data.length; i += 4) {
        const gray = (data[i] * 0.299) + (data[i + 1] * 0.587) + (data[i + 2] * 0.114);
        const stretched = ((gray - min) / range) * 255;
        const binary = stretched < 140 ? 0 : 255;
        data[i] = binary;
        data[i + 1] = binary;
        data[i + 2] = binary;
    }

    ctx.putImageData(imageData, 0, 0);
    return canvas;
}

function extractCrop(sourceCanvas, crop) {
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, crop.w);
    canvas.height = Math.max(1, crop.h);
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx?.drawImage(
        sourceCanvas,
        crop.x,
        crop.y,
        crop.w,
        crop.h,
        0,
        0,
        canvas.width,
        canvas.height,
    );
    return canvas;
}

function enhanceCanvas(canvas) {
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) {
        return canvas;
    }

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const { data } = imageData;

    for (let i = 0; i < data.length; i += 4) {
        const gray = (data[i] * 0.299) + (data[i + 1] * 0.587) + (data[i + 2] * 0.114);
        const enhanced = gray < 128
            ? Math.max(0, gray * 0.8)
            : Math.min(255, gray * 1.15);
        data[i] = enhanced;
        data[i + 1] = enhanced;
        data[i + 2] = enhanced;
    }

    ctx.putImageData(imageData, 0, 0);
    return canvas;
}

async function decodeWithNativeDetector(canvas, mode = 'barcode') {
    const hit = await detectWithNativeDetector(canvas, mode);
    return hit?.text || '';
}

function normalizeFocusBox(x, y, w, h, canvasW, canvasH) {
    const padX = w * 0.08;
    const padY = h * 0.08;

    return {
        x: Math.max(0, (x - padX) / canvasW),
        y: Math.max(0, (y - padY) / canvasH),
        w: Math.min(1, (w + padX * 2) / canvasW),
        h: Math.min(1, (h + padY * 2) / canvasH),
    };
}

async function detectWithNativeDetector(canvas, mode = 'barcode') {
    if (typeof window === 'undefined' || !('BarcodeDetector' in window)) {
        return null;
    }

    try {
        const detector = new window.BarcodeDetector({ formats: nativeFormatsForMode(mode) });
        const results = await detector.detect(canvas);
        const best = results?.[0];
        if (!best?.rawValue) {
            return null;
        }

        const box = best.boundingBox;
        if (!box) {
            return {
                text: String(best.rawValue).trim(),
                focusBox: null,
                format: best.format || '',
            };
        }

        return {
            text: String(best.rawValue).trim(),
            focusBox: normalizeFocusBox(box.x, box.y, box.width, box.height, canvas.width, canvas.height),
            format: best.format || '',
        };
    } catch {
        return null;
    }
}

function detectWithZxing(canvas, mode = 'barcode') {
    try {
        const result = getZxingReader(mode).decodeFromCanvas(canvas);
        const text = String(result?.getText?.() || '').trim();
        if (!text) {
            return null;
        }

        return {
            text,
            focusBox: null,
            format: String(result?.getBarcodeFormat?.() || ''),
        };
    } catch {
        return null;
    }
}

function detectWithJsQr(canvas, offsetX = 0, offsetY = 0, sourceW = canvas.width, sourceH = canvas.height) {
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) {
        return null;
    }

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const result = jsQR(imageData.data, imageData.width, imageData.height, {
        inversionAttempts: 'attemptBoth',
    });

    if (!result?.data) {
        return null;
    }

    let focusBox = null;
    if (result.location) {
        const { location } = result;
        const xs = [location.topLeftCorner.x, location.topRightCorner.x, location.bottomRightCorner.x, location.bottomLeftCorner.x];
        const ys = [location.topLeftCorner.y, location.topRightCorner.y, location.bottomRightCorner.y, location.bottomLeftCorner.y];
        const minX = Math.min(...xs) + offsetX;
        const minY = Math.min(...ys) + offsetY;
        const width = Math.max(...xs) - Math.min(...xs);
        const height = Math.max(...ys) - Math.min(...ys);
        focusBox = normalizeFocusBox(minX, minY, width, height, sourceW, sourceH);
    }

    return {
        text: String(result.data).trim(),
        focusBox,
        format: 'qr_code',
    };
}

async function detectCanvasMulti(sourceCanvas, mode = 'barcode') {
    const regions = getCropRegions(sourceCanvas.width, sourceCanvas.height);
    const scanOrder = mode === 'barcode'
        ? [regions[2], regions[3], regions[4], regions[0], regions[1]]
        : [regions[1], regions[0], regions[2], regions[3], regions[4]];

    for (const crop of scanOrder) {
        const isFullFrame = crop.x === 0 && crop.y === 0
            && crop.w === sourceCanvas.width
            && crop.h === sourceCanvas.height;
        const cropCanvas = isFullFrame
            ? sourceCanvas
            : enhanceCanvas(extractCrop(sourceCanvas, crop));

        const native = await detectWithNativeDetector(cropCanvas, mode);
        if (native?.text) {
            return remapFocusBox(native, crop, sourceCanvas);
        }

        const zxing = detectWithZxing(cropCanvas, mode);
        if (zxing?.text) {
            return zxing;
        }

        const zxingAll = detectWithZxing(cropCanvas, 'all');
        if (zxingAll?.text) {
            return zxingAll;
        }

        const qr = detectWithJsQr(
            cropCanvas,
            crop.x,
            crop.y,
            sourceCanvas.width,
            sourceCanvas.height,
        );
        if (qr?.text) {
            return qr;
        }
    }

    return null;
}

function canvasVariants(sourceCanvas) {
    const variants = [sourceCanvas];

    const enhanced = enhanceCanvas(extractCrop(sourceCanvas, {
        x: 0,
        y: 0,
        w: sourceCanvas.width,
        h: sourceCanvas.height,
    }));
    variants.push(enhanced);

    const contrast = highContrastCanvas(extractCrop(sourceCanvas, {
        x: 0,
        y: 0,
        w: sourceCanvas.width,
        h: sourceCanvas.height,
    }));
    variants.push(contrast);

    return variants;
}

function decodeModesForRequest(mode = 'auto') {
    if (mode === 'qr') {
        return ['qr', 'barcode'];
    }
    if (mode === 'barcode') {
        return ['barcode', 'qr'];
    }

    return ['barcode', 'qr'];
}

async function decodeCropCanvas(cropCanvas, crop, sourceCanvas, mode = 'barcode') {
    const native = await detectWithNativeDetector(cropCanvas, mode);
    if (native?.text) {
        return native.text;
    }

    const zxing = detectWithZxing(cropCanvas, mode);
    if (zxing?.text) {
        return zxing.text;
    }

    const zxingAll = detectWithZxing(cropCanvas, 'all');
    if (zxingAll?.text) {
        return zxingAll.text;
    }

    const qr = detectWithJsQr(
        cropCanvas,
        crop.x,
        crop.y,
        sourceCanvas.width,
        sourceCanvas.height,
    );
    if (qr?.text) {
        return qr.text;
    }

    return '';
}
async function decodeCanvasMulti(sourceCanvas, mode = 'barcode') {
    const regions = getCropRegions(sourceCanvas.width, sourceCanvas.height);
    const scanOrder = mode === 'barcode'
        ? [regions[2], regions[3], regions[4], regions[0], regions[1]]
        : [regions[1], regions[0], regions[2], regions[3], regions[4]];

    for (const crop of scanOrder) {
        const isFullFrame = crop.x === 0 && crop.y === 0
            && crop.w === sourceCanvas.width
            && crop.h === sourceCanvas.height;
        const cropCanvas = isFullFrame
            ? sourceCanvas
            : enhanceCanvas(extractCrop(sourceCanvas, crop));

        const decoded = await decodeCropCanvas(cropCanvas, crop, sourceCanvas, mode);
        if (decoded) {
            return decoded;
        }
    }

    return '';
}

async function decodeCanvasAggressive(sourceCanvas) {
    const scales = [1, 1.6, 0.8];

    for (const scale of scales) {
        const scaled = scaleCanvas(sourceCanvas, scale);
        const variants = canvasVariants(scaled);

        for (const variant of variants) {
            for (const mode of ['barcode', 'qr']) {
                const decoded = await decodeCanvasMulti(variant, mode);
                if (decoded) {
                    return decoded;
                }
            }
        }
    }

    return '';
}

async function decodeWithHtml5Qrcode(file) {
    let host = document.getElementById(FILE_DECODER_ID);
    if (!host) {
        host = document.createElement('div');
        host.id = FILE_DECODER_ID;
        host.className = 'sr-only';
        host.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:320px;height:240px;overflow:hidden;';
        document.body.appendChild(host);
    }

    const scanner = new Html5Qrcode(FILE_DECODER_ID, {
        formatsToSupport: ALL_FORMATS,
        useBarCodeDetectorIfSupported: true,
        verbose: false,
    });

    try {
        const direct = String(await scanner.scanFile(file, false) || '').trim();
        if (direct) {
            return direct;
        }
    } catch {
        // Fall through to canvas-based html5 decode.
    }

    try {
        const canvas = await loadImageToCanvas(file);
        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob((value) => {
                if (value) {
                    resolve(value);
                } else {
                    reject(new Error('Không tạo được ảnh để quét.'));
                }
            }, 'image/jpeg', 0.92);
        });
        return String(await scanner.scanFile(toJpegFile(blob), false) || '').trim();
    } finally {
        try {
            scanner.clear();
        } catch {
            // ignore
        }
    }
}

export async function scanViolationVideoFrame(video, mode = 'barcode') {
    if (!video?.videoWidth) {
        return null;
    }

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx?.drawImage(video, 0, 0, canvas.width, canvas.height);

    return detectCanvasMulti(canvas, mode);
}

export async function decodeViolationImage(input, mode = 'auto') {
    const file = toJpegFile(input);
    const canvas = await loadImageToCanvas(file);

    try {
        const html5 = await decodeWithHtml5Qrcode(file);
        if (html5) {
            return html5;
        }
    } catch {
        // Fall through to canvas decoders.
    }

    for (const scanMode of decodeModesForRequest(mode)) {
        const decoded = await decodeCanvasMulti(canvas, scanMode);
        if (decoded) {
            return decoded;
        }
    }

    return decodeCanvasAggressive(canvas);
}
