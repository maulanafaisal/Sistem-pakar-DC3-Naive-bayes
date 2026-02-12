<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$gejala_list = $conn->query("SELECT * FROM gejala ORDER BY kode_gejala");

$hasil_diagnosa = null;
$gejala_dipilih = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_tanaman = $_POST['nama_tanaman'];
    $lokasi = $_POST['lokasi'];
    $tanggal = $_POST['tanggal'];
    $gejala_dipilih = isset($_POST['gejala']) ? $_POST['gejala'] : [];
    $catatan = $_POST['catatan'];
    
    if (count($gejala_dipilih) > 0) {
        // NAIVE BAYES ALGORITHM
        $penyakit_list = $conn->query("SELECT * FROM penyakit");
        $hasil_probabilitas = [];
        
        while ($penyakit = $penyakit_list->fetch_assoc()) {
            $penyakit_id = $penyakit['id'];
            
            // Get all symptoms for this disease
            $gejala_penyakit = $conn->query("SELECT g.kode_gejala, pg.probabilitas 
                FROM penyakit_gejala pg 
                JOIN gejala g ON pg.gejala_id = g.id 
                WHERE pg.penyakit_id = $penyakit_id");
            
            $prob_map = [];
            while ($row = $gejala_penyakit->fetch_assoc()) {
                $prob_map[$row['kode_gejala']] = floatval($row['probabilitas']);
            }
            
            // Calculate probability using Naive Bayes
            $prob_total = 1.0;
            $matched_symptoms = 0;
            
            foreach ($gejala_dipilih as $gejala_kode) {
                if (isset($prob_map[$gejala_kode])) {
                    $prob_total *= $prob_map[$gejala_kode];
                    $matched_symptoms++;
                } else {
                    // Penalty for symptoms not associated with this disease
                    $prob_total *= 0.1;
                }
            }
            
            // Normalize by number of selected symptoms
            if ($matched_symptoms > 0) {
                $confidence = ($prob_total * 100) / count($gejala_dipilih);
                $hasil_probabilitas[] = [
                    'penyakit' => $penyakit,
                    'probabilitas' => $prob_total,
                    'confidence' => min($confidence * $matched_symptoms, 99.9),
                    'matched' => $matched_symptoms
                ];
            }
        }
        
        // Sort by confidence
        usort($hasil_probabilitas, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        $hasil_diagnosa = $hasil_probabilitas;
        
        // Save to database
        if (count($hasil_diagnosa) > 0) {
            $top_result = $hasil_diagnosa[0];
            $penyakit_id = $top_result['penyakit']['id'];
            $confidence = round($top_result['confidence'], 2);
            $gejala_json = json_encode($gejala_dipilih);
            $user_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("INSERT INTO pencatatan (user_id, nama_tanaman, lokasi, tanggal_pencatatan, gejala_dipilih, metode_diagnosa, hasil_penyakit_id, tingkat_kepercayaan, catatan) VALUES (?, ?, ?, ?, ?, 'naive_bayes', ?, ?, ?)");
            $stmt->bind_param("issssids", $user_id, $nama_tanaman, $lokasi, $tanggal, $gejala_json, $penyakit_id, $confidence, $catatan);
            $stmt->execute();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosa Naive Bayes - Sistem Pakar Anggrek</title>
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
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: white;
            color: #667eea;
        }
        
        .container {
            max-width: 1000px;
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
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-icon {
            font-size: 40px;
        }
        
        .card-title {
            font-size: 24px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .gejala-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .checkbox-item:hover {
            background: #e9ecef;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
        }
        
        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            flex: 1;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .result-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 15px;
        }
        
        .result-rank {
            font-size: 18px;
            font-weight: bold;
            opacity: 0.9;
        }
        
        .result-disease {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .result-confidence {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .result-description {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.3);
        }
        
        .result-solution {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .secondary-result {
            background: white;
            color: #333;
            border: 2px solid #e0e0e0;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: white;
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🧠 Diagnosa Naive Bayes</div>
        <a href="index.php" class="btn-back">← Kembali</a>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🧠</div>
                <div>
                    <h2 class="card-title">Diagnosa Penyakit dengan Naive Bayes</h2>
                    <p style="color: #666; margin-top: 5px;">Metode probabilistik untuk menentukan penyakit berdasarkan gejala yang dipilih</p>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nama Tanaman</label>
                    <input type="text" name="nama_tanaman" required value="<?php echo isset($_POST['nama_tanaman']) ? htmlspecialchars($_POST['nama_tanaman']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="lokasi" required value="<?php echo isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Tanggal Pencatatan</label>
                    <input type="date" name="tanggal" required value="<?php echo isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Pilih Gejala yang Dialami (minimal 1):</label>
                    <div class="gejala-grid">
                        <?php while ($gejala = $gejala_list->fetch_assoc()): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" 
                                       name="gejala[]" 
                                       value="<?php echo $gejala['kode_gejala']; ?>" 
                                       id="g_<?php echo $gejala['id']; ?>"
                                       <?php echo in_array($gejala['kode_gejala'], $gejala_dipilih) ? 'checked' : ''; ?>>
                                <label for="g_<?php echo $gejala['id']; ?>">
                                    <strong><?php echo $gejala['kode_gejala']; ?>:</strong> <?php echo $gejala['nama_gejala']; ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Catatan Tambahan (Opsional)</label>
                    <textarea name="catatan" rows="3"><?php echo isset($_POST['catatan']) ? htmlspecialchars($_POST['catatan']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">🔍 Diagnosa Sekarang</button>
            </form>
        </div>
        
        <?php if ($hasil_diagnosa): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">📋</div>
                    <div>
                        <h2 class="card-title">Hasil Diagnosa</h2>
                        <p style="color: #666; margin-top: 5px;">Berdasarkan <?php echo count($gejala_dipilih); ?> gejala yang dipilih</p>
                    </div>
                </div>
                
                <?php foreach ($hasil_diagnosa as $index => $hasil): ?>
                    <div class="<?php echo $index == 0 ? 'result-card' : 'secondary-result'; ?> result-card">
                        <div class="result-rank">
                            <?php echo $index == 0 ? '⭐ Diagnosa Utama' : '#' . ($index + 1) . ' Kemungkinan Lain'; ?>
                        </div>
                        <div class="result-disease">
                            <?php echo htmlspecialchars($hasil['penyakit']['nama_penyakit']); ?>
                        </div>
                        <div class="result-confidence">
                            Tingkat Keyakinan: <?php echo round($hasil['confidence'], 2); ?>%
                            (<?php echo $hasil['matched']; ?>/<?php echo count($gejala_dipilih); ?> gejala cocok)
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($hasil['confidence'], 100); ?>%"></div>
                        </div>
                        
                        <div class="result-description">
                            <strong>Deskripsi:</strong><br>
                            <?php echo htmlspecialchars($hasil['penyakit']['deskripsi']); ?>
                        </div>
                        
                        <?php if ($index == 0): ?>
                            <div class="result-solution">
                                <strong>💡 Solusi Penanganan:</strong><br>
                                <?php echo htmlspecialchars($hasil['penyakit']['solusi']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
