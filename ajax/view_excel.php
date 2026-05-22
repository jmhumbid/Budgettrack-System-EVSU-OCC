<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo 'File parameter required';
    exit;
}

$relativePath = urldecode($_GET['file']);
// Normalize path - remove any directory traversal attempts
$relativePath = str_replace('..', '', $relativePath);
$relativePath = ltrim($relativePath, '/\\');

// Normalize backslashes to forward slashes for consistency
$relativePath = str_replace('\\', '/', $relativePath);

// Security check - ensure file path starts with uploads/ (allow any subdirectory)
// Check both forward and backslash versions
if (strpos($relativePath, 'uploads/') !== 0 && strpos($relativePath, 'uploads\\') !== 0) {
    http_response_code(403);
    echo 'Invalid file path. File must be in uploads/ directory. Received: ' . htmlspecialchars($relativePath);
    exit;
}

$filePath = __DIR__ . '/../' . $relativePath;
// Normalize the path
$filePath = realpath($filePath);

if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found: ' . htmlspecialchars($relativePath);
    exit;
}

// Additional security check - ensure resolved path is still in uploads directory
$uploadsDir = realpath(__DIR__ . '/../uploads/');
if (!$uploadsDir || strpos($filePath, $uploadsDir) !== 0) {
    http_response_code(403);
    echo 'Invalid file path';
    exit;
}

$fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

if (!in_array($fileExt, ['xls', 'xlsx', 'csv'])) {
    http_response_code(400);
    echo 'Invalid file type';
    exit;
}

// For CSV files, read directly
if ($fileExt === 'csv') {
    function formatCSVNumber($value) {
        // Remove any existing formatting
        $cleaned = preg_replace('/[,\s]/', '', $value);
        // Check if it's a valid number
        if (is_numeric($cleaned)) {
            return number_format((float)$cleaned, 2, '.', ',');
        }
        return $value;
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CSV Viewer</title>
        <style>
            body {
                margin: 0;
                padding: 10px;
                background-color: #f9fafb;
                font-family: Arial, sans-serif;
            }
            table {
                border-collapse: collapse;
                width: 100%;
                background-color: white;
                border: 1px solid #d1d5db;
            }
            th, td {
                border: 1px solid #e5e7eb;
                padding: 4px 8px;
                text-align: left;
            }
            th {
                background-color: #f3f4f6;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <table>
        <?php
        $handle = fopen($filePath, 'r');
        $rowNum = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            echo '<tr>';
            foreach ($data as $cell) {
                $cell = htmlspecialchars($cell);
                // Format numbers in data rows (not header)
                if ($rowNum !== 1) {
                    $cell = formatCSVNumber($cell);
                }
                if ($rowNum === 1) {
                    echo '<th>' . $cell . '</th>';
                } else {
                    echo '<td>' . $cell . '</td>';
                }
            }
            echo '</tr>';
        }
        fclose($handle);
        ?>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// For Excel files, use JavaScript with SheetJS
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Viewer</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            padding: 18px;
            background: radial-gradient(circle at top, #f8fafc 0%, #fee2e2 60%, #fecaca 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: #0f172a;
        }
        #excel-content {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.4);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            min-height: 520px;
        }
        .viewer-shell {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .viewer-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #800000, #dc2626);
            color: #fff;
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }
        .viewer-title {
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
        }
        .viewer-meta {
            font-size: 0.85rem;
            opacity: 0.85;
        }
        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toolbar-button {
            border: none;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #0f172a;
            background: #e0f2fe;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }
        .toolbar-button svg {
            width: 16px;
            height: 16px;
        }
        .toolbar-button.primary {
            background: #fff;
            color: #dc2626;
            box-shadow: 0 8px 16px rgba(220, 38, 38, 0.25);
        }
        .toolbar-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(15, 23, 42, 0.08);
        }
        .sheet-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px;
            background: linear-gradient(120deg, #f1f5f9, #e2e8f0);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }
        .sheet-tab {
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            background: #fff;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
            transition: transform 0.15s ease, color 0.2s ease, box-shadow 0.2s ease;
        }
        .sheet-tab.active {
            background: linear-gradient(135deg, #dc2626, #800000);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 12px 25px rgba(128, 0, 0, 0.35);
        }
        .sheet-panels {
            position: relative;
        }
        .sheet-panel {
            display: none;
            animation: fadeIn 0.25s ease;
        }
        .sheet-panel.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .excel-wrapper {
            width: 100%;
            overflow: auto;
            border: 1px solid rgba(203, 213, 225, 0.9);
            border-radius: 14px;
            background: #fff;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 12px 25px rgba(15,23,42,0.08);
        }
        #excel-table,
        .excel-grid {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            min-width: 100%;
            table-layout: fixed;
            font-size: 11pt;
        }
        .excel-grid th,
        .excel-grid td {
            min-width: 60px;
            max-width: none;
        }
        .column-header-row th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: linear-gradient(180deg, #f8fafc, #e2e8f0);
        }
        .corner-cell {
            background: #e2e8f0;
            color: #0f172a;
            text-align: center;
            font-weight: 700;
            border: 1px solid #cbd5f5;
        }
        .col-header, .row-header {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
            text-align: center;
            border: 1px solid #d1d5db;
            letter-spacing: 0.02em;
        }
        .row-header {
            position: sticky;
            left: 0;
            z-index: 2;
            width: 55px;
            background: linear-gradient(180deg, #f8fafc, #e2e8f0);
        }
        .excel-grid-body td, .excel-grid-body th {
            position: relative;
        }
        .excel-grid-body td {
            background: linear-gradient(180deg, rgba(248,250,252,0.8), rgba(255,255,255,0.95));
            color: #000000; /* Default black text color for visibility */
        }
        .excel-grid-body tr:nth-child(even) td {
            background: rgba(248, 250, 252, 0.95);
            color: #000000; /* Default black text color for visibility */
        }
        .editable-cell {
            transition: background-color 0.2s, outline 0.2s;
            cursor: text;
        }
        .editable-cell:hover:not(.editing) {
            background-color: #f0f9ff !important;
        }
        .editable-cell.editing {
            background-color: #fff !important;
            outline: 2px solid #3b82f6;
            outline-offset: -2px;
        }
        .editable-cell.modified {
            border-left: 3px solid #10b981;
        }
        .col-resizer {
            transition: background-color 0.2s;
        }
        .col-resizer:hover {
            background-color: rgba(220, 38, 38, 0.35) !important;
        }
        .loading {
            text-align: center;
            padding: 60px;
            color: #475569;
            font-weight: 500;
        }
        .error {
            color: #dc2626;
            padding: 20px;
            text-align: center;
        }
        .empty-sheet {
            padding: 60px 30px;
            text-align: center;
            color: #94a3b8;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div id="excel-content" class="loading">
        <p>Loading Excel file...</p>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
    <script>
    (function() {
        const fileUrl = "<?php echo htmlspecialchars('../' . $relativePath, ENT_QUOTES, 'UTF-8'); ?>";
        const contentDiv = document.getElementById("excel-content");
        
        // Extract filename from path for display
        const pathParts = fileUrl.split('/');
        const fileName = pathParts[pathParts.length - 1] || 'Workbook';
        const displayName = fileName.replace(/\.[^/.]+$/, ''); // Remove extension

        // Store original workbook and file path for saving
        let originalWorkbook = null;
        const filePathForSave = "<?php echo htmlspecialchars($relativePath, ENT_QUOTES, 'UTF-8'); ?>";
        const canEdit = <?php echo (in_array($_SESSION['user_role'] ?? '', ['budget', 'school_admin'])) ? 'true' : 'false'; ?>;

        fetch(fileUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch file: ' + response.status + ' ' + response.statusText);
                }
                return response.arrayBuffer();
            })
            .then(async (data) => {
                originalWorkbook = new ExcelJS.Workbook();
                await originalWorkbook.xlsx.load(data);

                const renderedSheets = originalWorkbook.worksheets
                    .map((worksheet, index) => ({
                        name: worksheet.name || `Sheet ${index + 1}`,
                        worksheet: worksheet,
                        html: renderWorksheet(worksheet, index)
                    }))
                    .filter(sheet => !!sheet.html);

                if (!renderedSheets.length) {
                    contentDiv.innerHTML = '<div class="empty-sheet">This workbook does not contain any visible data.</div>';
                    return;
                }

                const sheetCountLabel = renderedSheets.length === 1 ? '1 sheet' : `${renderedSheets.length} sheets`;
                contentDiv.innerHTML = `
                    <div class="viewer-shell">
                        <div class="viewer-toolbar">
                            <div>
                                <div class="viewer-title">${displayName} Preview</div>
                                <div class="viewer-meta"><span id="activeSheetLabel">${renderedSheets[0].name}</span> · ${sheetCountLabel}</div>
                            </div>
                            <div class="toolbar-actions">
                                ${canEdit ? `
                                <button type="button" class="toolbar-button" id="saveWorkbook" title="Save changes">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                        <polyline points="7 3 7 8 15 8"></polyline>
                                    </svg>
                                    <span id="saveButtonText">Save</span>
                                </button>
                                ` : ''}
                                <button type="button" class="toolbar-button" id="refreshWorkbook">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4v6h6M20 20v-6h-6"></path>
                                        <path d="M5.63 18.37A9 9 0 0 0 18 19.36M18.37 5.63A9 9 0 0 0 6 4.64"></path>
                                    </svg>
                                    Refresh
                                </button>
                                <button type="button" class="toolbar-button primary" id="fullscreenWorkbook">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 8V4h4M4 4l5 5m7-5h4v4m0-4l-5 5m5 7v4h-4m4 0-5-5m-7 5H4v-4m0 4 5-5"></path>
                                    </svg>
                                    <span id="fullscreenButtonText">Fullscreen</span>
                                </button>
                            </div>
                        </div>
                        <div class="sheet-tabs" role="tablist">
                            ${renderedSheets.map((sheet, index) => `
                                <button type="button" class="sheet-tab ${index === 0 ? 'active' : ''}" data-sheet-index="${index}">
                                    ${sheet.name}
                                </button>
                            `).join('')}
                        </div>
                        <div class="sheet-panels">
                            ${renderedSheets.map((sheet, index) => `
                                <div class="sheet-panel ${index === 0 ? 'active' : ''}" data-sheet-index="${index}">
                                    ${sheet.html}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;

                registerSheetTabs();
                registerToolbarActions();

                contentDiv.querySelectorAll('.excel-grid').forEach((table, index) => {
                    enableColumnResizing(table);
                    if (canEdit) {
                        makeTableEditable(table, index);
                    }
                });
            })
            .catch(error => {
                contentDiv.className = "error";
                contentDiv.innerHTML = 
                    "<p><strong>Error loading file</strong></p>" +
                    "<p>" + error.message + "</p>" +
                    "<p style='font-size: 12px; color: #6b7280; margin-top: 10px;'>File URL: " + fileUrl + "</p>";
                console.error('Excel loading error:', error);
            });

        function registerSheetTabs() {
            const tabs = contentDiv.querySelectorAll('.sheet-tab');
            const panels = contentDiv.querySelectorAll('.sheet-panel');
            const sheetLabel = document.getElementById('activeSheetLabel');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const targetIndex = tab.dataset.sheetIndex;

                    tabs.forEach(btn => btn.classList.remove('active'));
                    tab.classList.add('active');

                    panels.forEach(panel => {
                        panel.classList.toggle('active', panel.dataset.sheetIndex === targetIndex);
                    });

                    if (sheetLabel) {
                        sheetLabel.textContent = tab.textContent.trim();
                    }
                });
            });
        }

        function registerToolbarActions() {
            const refreshBtn = document.getElementById('refreshWorkbook');
            const fullscreenBtn = document.getElementById('fullscreenWorkbook');

            if (refreshBtn) {
                refreshBtn.addEventListener('click', async () => {
                    if (canEdit) {
                        // Automatically save changes if any exist, then reload
                        const hasChanges = document.querySelector('.modified');
                        if (hasChanges) {
                            try {
                                await saveWorkbook();
                                // Small delay to show "Saved!" message
                                await new Promise(resolve => setTimeout(resolve, 500));
                            } catch (error) {
                                console.error('Error saving before refresh:', error);
                                if (!confirm('Error saving changes. Refresh anyway?')) {
                                    return; // User cancelled refresh
                                }
                            }
                        }
                    }
                    window.location.reload();
                });
            }
            
            // Save button functionality
            const saveBtn = document.getElementById('saveWorkbook');
            if (saveBtn && canEdit) {
                saveBtn.addEventListener('click', async () => {
                    await saveWorkbook();
                });
            }
            
            async function saveWorkbook() {
                if (!originalWorkbook) {
                    alert('No workbook loaded');
                    return;
                }
                
                const saveBtn = document.getElementById('saveWorkbook');
                const saveBtnText = document.getElementById('saveButtonText');
                
                try {
                    saveBtn.disabled = true;
                    if (saveBtnText) saveBtnText.textContent = 'Saving...';
                    
                    // Collect all cell values from editable tables
                    const tables = contentDiv.querySelectorAll('.excel-grid');
                    tables.forEach((table, sheetIndex) => {
                        const worksheet = originalWorkbook.worksheets[sheetIndex];
                        if (!worksheet) return;
                        
                        const cells = table.querySelectorAll('tbody.excel-grid-body td[contenteditable="true"]');
                        cells.forEach(cell => {
                            const cellRef = cell.dataset.cellRef;
                            if (cellRef) {
                                const newValue = cell.textContent.trim();
                                try {
                                    const excelCell = worksheet.getCell(cellRef);
                                    // Try to parse as number if it's numeric
                                    if (newValue !== '' && !isNaN(newValue) && !isNaN(parseFloat(newValue))) {
                                        excelCell.value = parseFloat(newValue);
                                    } else {
                                        excelCell.value = newValue;
                                    }
                                } catch (e) {
                                    console.warn('Could not update cell ' + cellRef, e);
                                }
                            }
                        });
                    });
                    
                    // Generate Excel file as blob
                    const buffer = await originalWorkbook.xlsx.writeBuffer();
                    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    
                    // Send to server
                    const formData = new FormData();
                    formData.append('file', blob, fileName);
                    formData.append('file_path', filePathForSave);
                    
                    const response = await fetch('../ajax/save_excel_file.php?file=' + encodeURIComponent(filePathForSave), {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Remove modified indicators
                        document.querySelectorAll('.modified').forEach(cell => {
                            cell.classList.remove('modified');
                            cell.style.borderLeft = '';
                            cell.dataset.originalValue = cell.textContent.trim();
                        });
                        
                        if (saveBtnText) saveBtnText.textContent = 'Saved!';
                        setTimeout(() => {
                            if (saveBtnText) saveBtnText.textContent = 'Save';
                        }, 2000);
                    } else {
                        alert('Error saving file: ' + (result.message || 'Unknown error'));
                        if (saveBtnText) saveBtnText.textContent = 'Save';
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    alert('Error saving file: ' + error.message);
                    if (saveBtnText) saveBtnText.textContent = 'Save';
                } finally {
                    saveBtn.disabled = false;
                }
            }

            if (fullscreenBtn) {
                const updateFullscreenButton = () => {
                    const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement || 
                                         (window.parent && (window.parent.document.fullscreenElement || window.parent.document.webkitFullscreenElement || window.parent.document.msFullscreenElement));
                    const buttonText = document.getElementById('fullscreenButtonText');
                    if (buttonText) {
                        buttonText.textContent = isFullscreen ? 'Minimize' : 'Fullscreen';
                    }
                };

                fullscreenBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const iframeEl = window.frameElement;
                    const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement || 
                                         (window.parent && (window.parent.document.fullscreenElement || window.parent.document.webkitFullscreenElement || window.parent.document.msFullscreenElement));
                    
                    if (isFullscreen) {
                        // Exit fullscreen - try parent first, then current document
                        if (window.parent && window.parent.document.exitFullscreen) {
                            window.parent.document.exitFullscreen().catch(() => {
                                if (document.exitFullscreen) {
                                    document.exitFullscreen();
                                } else if (document.webkitExitFullscreen) {
                                    document.webkitExitFullscreen();
                                } else if (document.msExitFullscreen) {
                                    document.msExitFullscreen();
                                }
                            });
                        } else if (window.parent && window.parent.document.webkitExitFullscreen) {
                            window.parent.document.webkitExitFullscreen();
                        } else if (window.parent && window.parent.document.msExitFullscreen) {
                            window.parent.document.msExitFullscreen();
                        } else if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        } else if (document.msExitFullscreen) {
                            document.msExitFullscreen();
                        }
                    } else {
                        // Enter fullscreen - try iframe first, then parent, then current document
                        if (iframeEl && iframeEl.requestFullscreen) {
                            iframeEl.requestFullscreen().catch(() => {
                                // Fallback to parent document
                                if (window.parent && window.parent.document.documentElement.requestFullscreen) {
                                    window.parent.document.documentElement.requestFullscreen().catch(() => {
                                        // Final fallback to current document
                                        if (document.documentElement.requestFullscreen) {
                                            document.documentElement.requestFullscreen();
                                        }
                                    });
                                } else if (document.documentElement.requestFullscreen) {
                                    document.documentElement.requestFullscreen();
                                }
                            });
                        } else if (iframeEl && iframeEl.webkitRequestFullscreen) {
                            iframeEl.webkitRequestFullscreen();
                        } else if (iframeEl && iframeEl.msRequestFullscreen) {
                            iframeEl.msRequestFullscreen();
                        } else if (window.parent && window.parent.document.documentElement.requestFullscreen) {
                            window.parent.document.documentElement.requestFullscreen().catch(() => {
                                if (document.documentElement.requestFullscreen) {
                                    document.documentElement.requestFullscreen();
                                }
                            });
                        } else if (document.documentElement.requestFullscreen) {
                            document.documentElement.requestFullscreen();
                        } else if (document.documentElement.webkitRequestFullscreen) {
                            document.documentElement.webkitRequestFullscreen();
                        } else if (document.documentElement.msRequestFullscreen) {
                            document.documentElement.msRequestFullscreen();
                        }
                    }
                    
                    // Update button text immediately
                    setTimeout(updateFullscreenButton, 100);
                });

                // Listen for fullscreen changes in both current and parent document
                const handleFullscreenChange = () => {
                    updateFullscreenButton();
                };
                
                document.addEventListener('fullscreenchange', handleFullscreenChange);
                document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
                document.addEventListener('msfullscreenchange', handleFullscreenChange);
                
                // Also listen to parent window if available
                if (window.parent && window.parent !== window) {
                    try {
                        window.parent.document.addEventListener('fullscreenchange', handleFullscreenChange);
                        window.parent.document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
                        window.parent.document.addEventListener('msfullscreenchange', handleFullscreenChange);
                    } catch (e) {
                        // Cross-origin or other error, ignore
                    }
                }
                
                // Initial update
                updateFullscreenButton();
            }
        }

        function colorToHex(color) {
            if (!color) return null;

            if (typeof color === 'string') {
                if (color.startsWith('#')) return color;
                if (color.length === 8) return '#' + color.substring(2);
                if (color.length === 6) return '#' + color;
            }

            if (typeof color === 'object') {
                if (color.toString && typeof color.toString === 'function') {
                    try {
                        const str = color.toString();
                        if (str && str.startsWith('#')) {
                            return str;
                        }
                    } catch (_) {}
                }

                if (color.argb) {
                    const hex = String(color.argb);
                    if (hex.length === 8) {
                        return '#' + hex.substring(2);
                    } else if (hex.length === 6) {
                        return '#' + hex;
                    }
                }

                if (color.rgb) {
                    const rgb = String(color.rgb);
                    return rgb.startsWith('#') ? rgb : '#' + rgb;
                }

                if (color.theme !== undefined) {
                    try {
                        if (color.toString && typeof color.toString === 'function') {
                            const resolved = color.toString();
                            if (resolved && (resolved.startsWith('#') || /^[0-9A-Fa-f]{6}$/.test(resolved))) {
                                return resolved.startsWith('#') ? resolved : '#' + resolved;
                            }
                        }

                        const themeMap = {
                            0: '#000000',
                            1: '#FFFFFF',
                            2: '#000000',
                            3: '#EEECE1',
                            4: '#1F497D',
                            5: '#4F81BD',
                            6: '#9BBB59',
                            7: '#8064A2',
                            8: '#4BACC6',
                            9: '#F79646',
                        };

                        if (themeMap[color.theme] !== undefined) {
                            let baseColor = themeMap[color.theme];
                            if (color.tint !== undefined && color.tint !== 0) {
                                const hex = baseColor.substring(1);
                                let r = parseInt(hex.substring(0, 2), 16);
                                let g = parseInt(hex.substring(2, 4), 16);
                                let b = parseInt(hex.substring(4, 6), 16);

                                if (color.tint > 0) {
                                    r = Math.round(r + (255 - r) * color.tint);
                                    g = Math.round(g + (255 - g) * color.tint);
                                    b = Math.round(b + (255 - b) * color.tint);
                                } else if (color.tint < 0) {
                                    r = Math.round(r * (1 + color.tint));
                                    g = Math.round(g * (1 + color.tint));
                                    b = Math.round(b * (1 + color.tint));
                                }

                                baseColor = '#' + [r, g, b].map(x => {
                                    const channel = x.toString(16);
                                    return channel.length === 1 ? '0' + channel : channel;
                                }).join('');
                            }
                            return baseColor;
                        }
                    } catch (_) {}
                }
            }

            return null;
        }

        function columnLetterToIndex(letter) {
            let index = 0;
            for (let i = 0; i < letter.length; i++) {
                index = index * 26 + (letter.charCodeAt(i) - 64);
            }
            return index;
        }

        function columnIndexToLetter(index) {
            let letter = '';
            let temp = index;
            while (temp > 0) {
                const mod = (temp - 1) % 26;
                letter = String.fromCharCode(65 + mod) + letter;
                temp = Math.floor((temp - 1) / 26);
            }
            return letter;
        }

        function cellRefToCoordinates(ref) {
            if (!ref) return null;
            const match = ref.match(/^([A-Z]+)(\d+)$/i);
            if (!match) return null;
            return {
                col: columnLetterToIndex(match[1].toUpperCase()),
                row: parseInt(match[2], 10)
            };
        }

        function parseMergeRange(range) {
            if (!range) return null;
            if (typeof range === 'string') {
                const parts = range.split(':');
                const start = cellRefToCoordinates(parts[0]);
                const end = cellRefToCoordinates(parts[1] || parts[0]);
                if (!start || !end) return null;
                return {
                    top: Math.min(start.row, end.row),
                    left: Math.min(start.col, end.col),
                    bottom: Math.max(start.row, end.row),
                    right: Math.max(start.col, end.col)
                };
            }
            if (typeof range === 'object' && range.top !== undefined) {
                return {
                    top: range.top,
                    left: range.left,
                    bottom: range.bottom,
                    right: range.right
                };
            }
            return null;
        }

        // Store worksheet references for editing
        const worksheetRefs = [];
        
        function makeTableEditable(table, sheetIndex) {
            const tbody = table.querySelector('tbody.excel-grid-body');
            if (!tbody) return;
            
            const cells = tbody.querySelectorAll('td');
            cells.forEach(cell => {
                // Skip if cell is part of a merged range (already handled)
                if (cell.hasAttribute('colspan') && parseInt(cell.getAttribute('colspan')) > 1) {
                    return;
                }
                if (cell.hasAttribute('rowspan') && parseInt(cell.getAttribute('rowspan')) > 1) {
                    return;
                }
                
                // Make cell editable
                cell.setAttribute('contenteditable', 'true');
                cell.style.cursor = 'text';
                cell.classList.add('editable-cell');
                
                // Add visual indicator on hover
                cell.addEventListener('mouseenter', () => {
                    if (!cell.classList.contains('editing')) {
                        cell.style.backgroundColor = '#f0f9ff';
                    }
                });
                cell.addEventListener('mouseleave', () => {
                    if (!cell.classList.contains('editing')) {
                        cell.style.backgroundColor = '';
                    }
                });
                
                // Track changes
                cell.addEventListener('focus', () => {
                    cell.classList.add('editing');
                    cell.style.backgroundColor = '#fff';
                    cell.style.outline = '2px solid #3b82f6';
                });
                
                cell.addEventListener('blur', () => {
                    cell.classList.remove('editing');
                    cell.style.backgroundColor = '';
                    cell.style.outline = '';
                    
                    // Mark as modified
                    if (cell.textContent !== cell.dataset.originalValue) {
                        cell.classList.add('modified');
                        cell.style.borderLeft = '3px solid #10b981';
                    } else {
                        cell.classList.remove('modified');
                        cell.style.borderLeft = '';
                    }
                });
                
                // Store original value
                cell.dataset.originalValue = cell.textContent.trim();
            });
        }
        
        function renderWorksheet(worksheet, sheetIndex) {
            const dimensions = worksheet.dimensions;
            if (!dimensions) {
                return '<div class="empty-sheet">This sheet is currently empty.</div>';
            }

            const startRow = dimensions.top;
            const endRow = dimensions.bottom;
            const startCol = dimensions.left;
            const endCol = dimensions.right;

            const rowCount = endRow - startRow + 1;
            const columnCount = endCol - startCol + 1;

            if (rowCount <= 0 || columnCount <= 0) {
                return '<div class="empty-sheet">This sheet is currently empty.</div>';
            }

            const cellGrid = Array.from({ length: rowCount }, () => Array(columnCount).fill(null));
            const mergedRanges = [];

            try {
                if (worksheet.model && worksheet.model.merges) {
                    worksheet.model.merges.forEach(merge => {
                        const range = parseMergeRange(merge);
                        if (!range) return;
                        const { top, left, bottom, right } = range;

                        mergedRanges.push({
                            top,
                            left,
                            bottom,
                            right,
                            colspan: right - left + 1,
                            rowspan: bottom - top + 1
                        });

                        for (let r = top; r <= bottom; r++) {
                            for (let c = left; c <= right; c++) {
                                const gridRow = r - startRow;
                                const gridCol = c - startCol;
                                if (gridRow >= 0 && gridRow < rowCount && gridCol >= 0 && gridCol < columnCount) {
                                    if (r !== top || c !== left) {
                                        cellGrid[gridRow][gridCol] = { merged: true, skip: true };
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (err) {
                console.warn('Could not parse merged cells:', err);
            }

            const cellAddressMap = new Map();
            for (let rowNum = startRow; rowNum <= endRow; rowNum++) {
                const row = worksheet.getRow(rowNum);
                const gridRow = rowNum - startRow;

                row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                    const gridCol = colNumber - startCol;
                    if (gridCol < 0 || gridCol >= columnCount) return;
                    if (cellGrid[gridRow][gridCol] && cellGrid[gridRow][gridCol].merged) return;

                    const cellAddress = cell.address || `${rowNum},${colNumber}`;
                    if (cellAddressMap.has(cellAddress)) {
                        return;
                    }
                    cellAddressMap.set(cellAddress, true);

                    const cellData = processCell(cell, rowNum, colNumber, mergedRanges);
                    if (cellData) {
                        cellGrid[gridRow][gridCol] = cellData;
                    }
                });
            }

            for (let r = 0; r < rowCount; r++) {
                for (let c = 0; c < columnCount; c++) {
                    if (cellGrid[r][c] && (cellGrid[r][c].merged || cellGrid[r][c].value !== undefined)) {
                        // Ensure value is not [object Object]
                        if (cellGrid[r][c].value && typeof cellGrid[r][c].value === 'object') {
                            cellGrid[r][c].value = '';
                        }
                        continue;
                    }

                    cellGrid[r][c] = {
                        value: '',
                        style: 'border: 1px solid #e5e7eb; padding: 4px 8px; white-space: nowrap;',
                        colspan: 1,
                        rowspan: 1
                    };
                }
            }

            const colWidths = [];
            for (let i = startCol; i <= endCol; i++) {
                const col = worksheet.getColumn(i);
                const width = col.width ? Math.max(60, col.width * 7) + 'px' : '100px';
                colWidths.push(width);
            }

            const columnLetters = [];
            for (let i = startCol; i <= endCol; i++) {
                columnLetters.push(columnIndexToLetter(i));
            }

            let html = '<div class="excel-wrapper"><table class="excel-grid">';
            html += '<colgroup>';
            html += '<col style="width: 55px; min-width: 55px;">';
            for (let i = 0; i < columnCount; i++) {
                html += '<col style="min-width: 60px; width: ' + colWidths[i] + ';">';
            }
            html += '</colgroup>';

            html += '<thead><tr class="column-header-row">';
            html += '<th class="corner-cell"></th>';
            columnLetters.forEach(letter => {
                html += '<th class="col-header">' + letter + '</th>';
            });
            html += '</tr></thead>';

            html += '<tbody class="excel-grid-body">';

            const renderedCells = new Set();
            for (let r = 0; r < rowCount; r++) {
                const row = worksheet.getRow(startRow + r);
                const rowHeight = row.height ? row.height + 'pt' : 'auto';
                const excelRowNumber = startRow + r;
                html += '<tr style="height: ' + rowHeight + ';">';
                html += '<th class="row-header">' + excelRowNumber + '</th>';

                for (let c = 0; c < columnCount; c++) {
                    const cellData = cellGrid[r][c];

                    if (!cellData || cellData.merged || cellData.skip) {
                        continue;
                    }

                    const cellKey = `${r},${c}`;
                    if (renderedCells.has(cellKey)) {
                        continue;
                    }
                    renderedCells.add(cellKey);

                    // Handle cell value - ensure it's not an object
                    let cellValueStr = '';
                    if (cellData.value !== null && cellData.value !== undefined) {
                        if (typeof cellData.value === 'object') {
                            cellValueStr = '';
                        } else {
                            cellValueStr = String(cellData.value);
                        }
                    }
                    const cellValue = cellValueStr
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/\[object\s+Object\]/gi, '');

                    // Calculate Excel cell reference (e.g., A1, B2)
                    const excelCol = columnIndexToLetter(startCol + c);
                    const excelRow = startRow + r;
                    const cellRef = excelCol + excelRow;
                    
                    html += '<td style="' + (cellData.style || '') + '"';
                    if (cellData.colspan > 1) html += ' colspan="' + cellData.colspan + '"';
                    if (cellData.rowspan > 1) html += ' rowspan="' + cellData.rowspan + '"';
                    html += ' data-row="' + excelRow + '" data-col="' + excelCol + '"';
                    html += ' data-cell-ref="' + cellRef + '"';
                    html += '>' + cellValue + '</td>';
                }

                html += '</tr>';
            }

            html += '</tbody></table></div>';
            return html;
        }

        function formatNumber(value) {
            // Handle null, undefined, or empty values
            if (value === null || value === undefined || value === '') {
                return '';
            }
            
            // Handle objects - return empty string to avoid [object Object]
            if (typeof value === 'object') {
                return '';
            }
            
            // If value is already a string, check if it's a number
            if (typeof value === 'string') {
                // Skip if it contains [object Object] or similar
                if (value.includes('[object') || value.trim() === '') {
                    return '';
                }
                // Remove any existing formatting (commas, spaces)
                const cleaned = value.replace(/,/g, '').replace(/\s/g, '').trim();
                // Skip if empty after cleaning
                if (cleaned === '') {
                    return '';
                }
                const numValue = parseFloat(cleaned);
                // Only format if it's a valid number and the original string was numeric
                if (!isNaN(numValue) && isFinite(numValue) && /^-?\d*\.?\d+$/.test(cleaned)) {
                    return numValue.toLocaleString('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    });
                }
                return value;
            }
            // If value is a number, format it
            if (typeof value === 'number') {
                if (isNaN(value) || !isFinite(value)) {
                    return '';
                }
                return value.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }
            return '';
        }

        function processCell(cell, rowNum, colNum, mergedRanges) {
            if (!cell) return null;

            let cellValue = '';
            if (cell.value !== null && cell.value !== undefined) {
                if (typeof cell.value === 'object' && cell.value.richText) {
                    cell.value.richText.forEach(rt => {
                        cellValue += rt.text || '';
                    });
                    // Format the combined rich text if it's a number
                    cellValue = formatNumber(cellValue);
                } else if (typeof cell.value === 'object' && cell.value.text) {
                    cellValue = cell.value.text;
                    // Format if it's a number
                    cellValue = formatNumber(cellValue);
                } else if (typeof cell.value === 'object' && cell.value.formula) {
                    cellValue = cell.text || cell.result || '';
                    // Format if it's a number
                    cellValue = formatNumber(cellValue);
                } else if (typeof cell.value === 'object') {
                    // Handle other object types - try to get text representation
                    if (cell.text !== null && cell.text !== undefined) {
                        cellValue = String(cell.text);
                    } else if (cell.result !== null && cell.result !== undefined) {
                        cellValue = String(cell.result);
                    } else {
                        // Skip objects that can't be converted
                        cellValue = '';
                    }
                    // Format if it's a number
                    cellValue = formatNumber(cellValue);
                } else {
                    // Prefer cell.value if it's a number, otherwise use cell.text
                    const rawValue = (typeof cell.value === 'number') ? cell.value : (cell.text || cell.value || '');
                    // Format numbers with commas
                    cellValue = formatNumber(rawValue);
                }
            } else if (cell.text !== null && cell.text !== undefined) {
                // If value is null/undefined but text exists, use text
                cellValue = formatNumber(cell.text);
            }

            const style = cell.style || {};
            let cellStyle = 'border: 1px solid #e5e7eb; padding: 4px 8px; white-space: nowrap;';

            if (style.fill) {
                const patternType = (style.fill.patternType || '').toLowerCase();
                if (!patternType || patternType === 'solid') {
                    let bgColor = null;
                    if (style.fill.fgColor) {
                        bgColor = colorToHex(style.fill.fgColor);
                    }
                    if (!bgColor && style.fill.bgColor) {
                        bgColor = colorToHex(style.fill.bgColor);
                    }
                    if (!bgColor && style.fill.patternFill && style.fill.patternFill.fgColor) {
                        bgColor = colorToHex(style.fill.patternFill.fgColor);
                    }
                    if (!bgColor && style.fill.color) {
                        bgColor = colorToHex(style.fill.color);
                    }
                    if (bgColor) {
                        const normalizedBg = bgColor.replace('#', '').toUpperCase();
                        if (normalizedBg !== '000000' && normalizedBg !== '00000000') {
                        cellStyle += 'background-color: ' + bgColor + ';';
                        }
                    }
                }
            }

            if (style.font) {
                if (style.font.color) {
                    const fontColor = colorToHex(style.font.color);
                    if (fontColor) {
                        // Check if color is white or very light (invisible on white background)
                        const hex = fontColor.replace('#', '');
                        const r = parseInt(hex.substring(0, 2), 16);
                        const g = parseInt(hex.substring(2, 4), 16);
                        const b = parseInt(hex.substring(4, 6), 16);
                        // If color is white or very light (RGB > 240), use black instead
                        if (r > 240 && g > 240 && b > 240) {
                            cellStyle += 'color: #000000;';
                        } else {
                            cellStyle += 'color: ' + fontColor + ';';
                        }
                    } else {
                        // No valid color found, default to black
                        cellStyle += 'color: #000000;';
                    }
                } else {
                    // No font color specified, default to black for visibility
                    cellStyle += 'color: #000000;';
                }
                if (style.font.size) {
                    cellStyle += 'font-size: ' + style.font.size + 'pt;';
                }
                if (style.font.bold) {
                    cellStyle += 'font-weight: bold;';
                }
                if (style.font.italic) {
                    cellStyle += 'font-style: italic;';
                }
                if (style.font.underline) {
                    cellStyle += 'text-decoration: underline;';
                }
                if (style.font.strike) {
                    cellStyle += 'text-decoration: line-through;';
                }
            }

            if (style.alignment) {
                if (style.alignment.horizontal) {
                    cellStyle += 'text-align: ' + style.alignment.horizontal + ';';
                }
                if (style.alignment.vertical) {
                    cellStyle += 'vertical-align: ' + style.alignment.vertical + ';';
                }
                if (style.alignment.wrapText) {
                    cellStyle += 'white-space: normal; word-wrap: break-word;';
                }
            }

            if (style.border) {
                ['top', 'bottom', 'left', 'right'].forEach(side => {
                    const border = style.border[side];
                    if (border && border.style && border.style !== 'none') {
                        const borderColor = colorToHex(border.color) || '#000000';
                        let borderWidth = '1px';
                        const borderStyle = border.style;
                        if (borderStyle === 'thin') borderWidth = '1px';
                        else if (borderStyle === 'medium') borderWidth = '2px';
                        else if (borderStyle === 'thick') borderWidth = '3px';

                        let borderStyleCss = 'solid';
                        if (borderStyle === 'dashed') borderStyleCss = 'dashed';
                        else if (borderStyle === 'dotted') borderStyleCss = 'dotted';
                        else if (borderStyle === 'double') borderStyleCss = 'double';

                        cellStyle += 'border-' + side + ': ' + borderWidth + ' ' + borderStyleCss + ' ' + borderColor + ';';
                    }
                });
            }

            let colspan = 1, rowspan = 1;
            for (const merge of mergedRanges) {
                if (rowNum === merge.top && colNum === merge.left) {
                    colspan = merge.colspan;
                    rowspan = merge.rowspan;
                    break;
                }
            }

            return {
                value: cellValue,
                style: cellStyle,
                colspan,
                rowspan
            };
        }

        function enableColumnResizing(table) {
            if (!table) return;
            table.style.tableLayout = 'fixed';

            const cols = table.querySelectorAll('col');
            const headerRow = table.querySelector('.column-header-row');
            const headerCells = headerRow ? headerRow.querySelectorAll('.col-header') : [];

            cols.forEach((col, index) => {
                if (index === 0 || index >= cols.length - 1) {
                    return;
                }

                const headerCell = headerCells[index - 1];
                if (!headerCell) return;

                const existingResizer = headerCell.querySelector('.col-resizer');
                if (existingResizer) {
                    existingResizer.remove();
                }

                const resizer = document.createElement('div');
                resizer.className = 'col-resizer';
                resizer.style.cssText = 'position: absolute; top: 0; right: -2.5px; width: 5px; height: 100%; cursor: col-resize; user-select: none; z-index: 10; background: transparent;';
                headerCell.style.position = 'relative';
                headerCell.appendChild(resizer);

                let isResizing = false;
                let startX = 0;
                let startWidth = 0;

                resizer.addEventListener('mousedown', (e) => {
                    isResizing = true;
                    startX = e.pageX;
                    startWidth = parseInt(col.style.width) || col.offsetWidth;
                    document.body.style.cursor = 'col-resize';
                    document.body.style.userSelect = 'none';
                    e.preventDefault();
                    e.stopPropagation();
                });

                const handleMouseMove = (e) => {
                    if (!isResizing) return;
                    const diff = e.pageX - startX;
                    const newWidth = Math.max(40, startWidth + diff);
                    col.style.width = newWidth + 'px';
                    col.style.minWidth = newWidth + 'px';
                    table.style.width = 'auto';
                };

                const handleMouseUp = () => {
                    if (isResizing) {
                        isResizing = false;
                        document.body.style.cursor = '';
                        document.body.style.userSelect = '';
                    }
                };

                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
            });
        }
    })();
    </script>
</body>
</html>
<?php

