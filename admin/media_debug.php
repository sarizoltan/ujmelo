<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Media Debug</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #eee; padding: 20px; }
        .section { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #d4869c; }
        .label { color: #d4869c; font-weight: bold; }
        .value { color: #fff; word-break: break-all; }
        .success { color: #51cf66; }
        .error { color: #ff6b6b; }
        pre { background: #1a1a1a; padding: 10px; overflow-x: auto; border-radius: 4px; }
        img { max-width: 300px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔍 Media Debug</h1>

    <div class="section">
        <h3>Constants</h3>
        <p><span class="label">BASE_URL:</span> <span class="value"><?= BASE_URL ?></span></p>
        <p><span class="label">UPLOAD_URL:</span> <span class="value"><?= UPLOAD_URL ?></span></p>
        <p><span class="label">UPLOAD_PATH:</span> <span class="value"><?= UPLOAD_PATH ?></span></p>
        <p><span class="label">BASE_PATH:</span> <span class="value"><?= BASE_PATH ?></span></p>
    </div>

    <div class="section">
        <h3>Media Files in Database</h3>
        <?php
        $stmt = $pdo->query("SELECT id, filename, filepath, filetype, filesize FROM media ORDER BY uploaded_at DESC LIMIT 10");
        $files = $stmt->fetchAll();
        ?>
        <?php if ($files): ?>
        <table style="width:100%; border-collapse: collapse; font-size: 12px;">
            <tr style="border-bottom: 1px solid #444;">
                <th style="padding: 8px; text-align: left;">ID</th>
                <th style="padding: 8px; text-align: left;">Filename</th>
                <th style="padding: 8px; text-align: left;">Filepath</th>
                <th style="padding: 8px; text-align: left;">Type</th>
                <th style="padding: 8px; text-align: left;">Size</th>
                <th style="padding: 8px; text-align: left;">Test URL</th>
            </tr>
            <?php foreach ($files as $f): ?>
            <tr style="border-bottom: 1px solid #333;">
                <td style="padding: 8px;"><?= $f['id'] ?></td>
                <td style="padding: 8px;"><?= e($f['filename']) ?></td>
                <td style="padding: 8px;"><?= e($f['filepath']) ?></td>
                <td style="padding: 8px;"><?= $f['filetype'] ?></td>
                <td style="padding: 8px;"><?= number_format($f['filesize']/1024, 1) ?> KB</td>
                <td style="padding: 8px;">
                    <a href="<?= UPLOAD_URL . e($f['filepath']) ?>" target="_blank" style="color: #d4869c;">
                        Test Link
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <p class="error">❌ Nincsenek fájlok az adatbázisban</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Test Image Display</h3>
        <?php if ($files): ?>
        <?php $first = $files[0]; ?>
        <p><span class="label">File:</span> <span class="value"><?= e($first['filename']) ?></span></p>
        <p><span class="label">Filepath:</span> <span class="value"><?= e($first['filepath']) ?></span></p>
        
        <p><span class="label">URL Method 1 (UPLOAD_URL):</span></p>
        <span class="value"><?= UPLOAD_URL . e($first['filepath']) ?></span>
        <img src="<?= UPLOAD_URL . e($first['filepath']) ?>" alt="Test">
        
        <p><span class="label">URL Method 2 (BASE_URL):</span></p>
        <span class="value"><?= BASE_URL . '/' . e($first['filepath']) ?></span>
        <img src="<?= BASE_URL . '/' . e($first['filepath']) ?>" alt="Test">
        
        <p><span class="label">File Exists Check:</span></p>
        <?php 
        $full_path = UPLOAD_PATH . $first['filepath'];
        $file_exists = file_exists($full_path);
        ?>
        <p class="<?= $file_exists ? 'success' : 'error' ?>">
            <?= $file_exists ? '✓ YES' : '✗ NO' ?> 
            — <?= $full_path ?>
        </p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Browser Console Test</h3>
        <p>Nyisd meg az F12 Developer Tools-ot és nézd meg a Network fülben, hogy az imageek URL-jei helyesen töltődnek-e be!</p>
        <p>Ha 404 hibát látsz, akkor a path rossz.</p>
    </div>

</body>
</html>