<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT p.*, py.* FROM pencatatan p 
          LEFT JOIN penyakit py ON p.hasil_penyakit_id = py.id 
          WHERE p.id = $id AND p.user_id = $user_id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    header("Location: laporan.php");
    exit();
}

$record = $result->fetch_assoc();
$gejala_codes = json_decode($record['gejala_dipilih'], true);

// Get gejala details
$gejala_details = [];
if ($gejala_codes) {
    $gejala_in = "'" . implode("','", $gejala_codes) . "'";
    $gejala_query = "SELECT * FROM gejala WHERE kode_gejala IN ($gejala_in)";
    $gejala_result = $conn->query($gejala_query);
    while ($g = $gejala_result->fetch_assoc()) {
        $gejala_details[] = $g;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pencatatan - Sistem Pakar Anggrek</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
        }
        
        .navbar-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn-back, .btn-print {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid white;
            cursor: pointer;
        }
        
        .btn-back:hover, .btn-print:hover {
            background: white;
            color: #667eea;
        }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .header-section {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 30px;
        }
        
        .header-section h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .badge-naive {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-tree {
            background: #d1fae5;
            color: #065f46;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .section-title {
            font-size: 20px;
            color: #333;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .gejala-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 10px;
        }
        
        .gejala-item {
            background: #f0f7ff;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .gejala-code {
            font-weight: bold;
            color: #667eea;
        }
        
        .result-box {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
        }
        
        .result-disease {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .result-confidence {
            font-size: 18px;
            opacity: 0.95;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: white;
        }
        
        .description-box, .solution-box {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .notes-box {
            background: #fff9e6;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
            margin-top: 15px;
        }
        
        @media print {
            .navbar, .btn-print, .btn-back {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">📄 Detail Pencatatan</div>
        <div class="navbar-actions">
            <button onclick="window.print()" class="btn-print">🖨️ Cetak</button>
            <a href="laporan.php" class="btn-back">← Kembali</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="header-section">
                <h1>Detail Diagnosa Tanaman Anggrek</h1>
                <span class="badge <?php echo $record['metode_diagnosa'] == 'naive_bayes' ? 'badge-naive' : 'badge-tree'; ?>">
                    Metode: <?php echo $record['metode_diagnosa'] == 'naive_bayes' ? 'Naive Bayes' : 'Decision Tree'; ?>
                </span>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nama Tanaman</div>
                    <div class="info-value"><?php echo htmlspecialchars($record['nama_tanaman']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Lokasi</div>
                    <div class="info-value"><?php echo htmlspecialchars($record['lokasi']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tanggal Pencatatan</div>
                    <div class="info-value"><?php echo date('d F Y', strtotime($record['tanggal_pencatatan'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Waktu Diagnosa</div>
                    <div class="info-value"><?php echo date('d F Y H:i', strtotime($record['created_at'])); ?></div>
                </div>
            </div>
            
            <h3 class="section-title">🔍 Gejala yang Terdeteksi</h3>
            <div class="gejala-list">
                <?php foreach ($gejala_details as $gejala): ?>
                    <div class="gejala-item">
                        <span class="gejala-code"><?php echo $gejala['kode_gejala']; ?>:</span>
                        <?php echo htmlspecialchars($gejala['nama_gejala']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <h3 class="section-title">📊 Hasil Diagnosa</h3>
            <div class="result-box">
                <div class="result-disease">
                    <?php echo htmlspecialchars($record['nama_penyakit']); ?>
                </div>
                <div class="result-confidence">
                    Tingkat Kepercayaan: <?php echo number_format($record['tingkat_kepercayaan'], 2); ?>%
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($record['tingkat_kepercayaan'], 100); ?>%"></div>
                </div>
                
                <div class="description-box">
                    <strong>📝 Deskripsi Penyakit:</strong><br>
                    <?php echo htmlspecialchars($record['deskripsi']); ?>
                </div>
                
                <div class="solution-box">
                    <strong>💊 Solusi & Penanganan:</strong><br>
                    <?php echo nl2br(htmlspecialchars($record['solusi'])); ?>
                </div>
            </div>
            
            <?php if ($record['catatan']): ?>
                <h3 class="section-title">📝 Catatan Tambahan</h3>
                <div class="notes-box">
                    <?php echo nl2br(htmlspecialchars($record['catatan'])); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
