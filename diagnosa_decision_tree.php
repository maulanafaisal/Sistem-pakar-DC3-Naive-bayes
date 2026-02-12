<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$gejala_list = $conn->query("SELECT * FROM gejala ORDER BY kode_gejala");

$hasil_diagnosa = null;
$gejala_dipilih = [];
$rules_matched = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_tanaman = $_POST['nama_tanaman'];
    $lokasi = $_POST['lokasi'];
    $tanggal = $_POST['tanggal'];
    $gejala_dipilih = isset($_POST['gejala']) ? $_POST['gejala'] : [];
    $catatan = $_POST['catatan'];
    
    if (count($gejala_dipilih) > 0) {
        // DECISION TREE ALGORITHM - Rule Based
        $rules = $conn->query("SELECT dr.*, p.* FROM decision_rules dr 
            JOIN penyakit p ON dr.penyakit_id = p.id 
            ORDER BY dr.priority ASC");
        
        $hasil_matches = [];
        
        while ($rule = $rules->fetch_assoc()) {
            $conditions = json_decode($rule['conditions'], true);
            
            // Check if all conditions in the rule are met
            $matched_conditions = 0;
            foreach ($conditions as $required_gejala) {
                if (in_array($required_gejala, $gejala_dipilih)) {
                    $matched_conditions++;
                }
            }
            
            // Calculate match percentage
            $match_percentage = ($matched_conditions / count($conditions)) * 100;
            
            // If all conditions met (100% match)
            if ($match_percentage >= 100) {
                $penyakit_id = $rule['penyakit_id'];
                
                // Check if we already have this disease
                if (!isset($hasil_matches[$penyakit_id])) {
                    $hasil_matches[$penyakit_id] = [
                        'penyakit' => [
                            'id' => $rule['id'],
                            'kode_penyakit' => $rule['kode_penyakit'],
                            'nama_penyakit' => $rule['nama_penyakit'],
                            'deskripsi' => $rule['deskripsi'],
                            'solusi' => $rule['solusi']
                        ],
                        'rules_matched' => [],
                        'confidence' => 0,
                        'total_matched' => 0
                    ];
                }
                
                $hasil_matches[$penyakit_id]['rules_matched'][] = [
                    'rule_name' => $rule['rule_name'],
                    'conditions' => $conditions,
                    'matched' => $matched_conditions,
                    'priority' => $rule['priority']
                ];
                $hasil_matches[$penyakit_id]['total_matched']++;
            }
        }
        
        // Calculate confidence for each disease based on matched rules
        foreach ($hasil_matches as $pid => &$match) {
            $total_rules_for_disease = $conn->query("SELECT COUNT(*) as cnt FROM decision_rules WHERE penyakit_id = " . $match['penyakit']['id'])->fetch_assoc()['cnt'];
            
            // Confidence based on: rules matched, priority, and symptom coverage
            $base_confidence = ($match['total_matched'] / max($total_rules_for_disease, 1)) * 100;
            
            // Bonus for matching higher priority rules
            $priority_bonus = 0;
            foreach ($match['rules_matched'] as $rm) {
                if ($rm['priority'] == 1) {
                    $priority_bonus += 20;
                }
            }
            
            $match['confidence'] = min($base_confidence + $priority_bonus, 99.9);
        }
        
        // Sort by confidence
        usort($hasil_matches, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        $hasil_diagnosa = $hasil_matches;
        
        // Save to database
        if (count($hasil_diagnosa) > 0) {
            $top_result = $hasil_diagnosa[0];
            $penyakit_id = $top_result['penyakit']['id'];
            $confidence = round($top_result['confidence'], 2);
            $gejala_json = json_encode($gejala_dipilih);
            $user_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("INSERT INTO pencatatan (user_id, nama_tanaman, lokasi, tanggal_pencatatan, gejala_dipilih, metode_diagnosa, hasil_penyakit_id, tingkat_kepercayaan, catatan) VALUES (?, ?, ?, ?, ?, 'decision_tree', ?, ?, ?)");
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
    <title>Diagnosa Decision Tree - Sistem Pakar Anggrek</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            color: #10b981;
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
            border-color: #10b981;
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            box-shadow: 0 5px 20px rgba(16, 185, 129, 0.4);
        }
        
        .result-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        
        .rules-info {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .rule-item {
            background: rgba(255,255,255,0.15);
            padding: 8px 12px;
            border-radius: 6px;
            margin: 5px 0;
            font-size: 14px;
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
        
        .no-result {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🌳 Diagnosa Decision Tree</div>
        <a href="index.php" class="btn-back">← Kembali</a>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🌳</div>
                <div>
                    <h2 class="card-title">Diagnosa Penyakit dengan Decision Tree</h2>
                    <p style="color: #666; margin-top: 5px;">Metode berbasis aturan (rules) untuk menentukan penyakit secara sistematis</p>
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
                        <?php 
                        $gejala_list->data_seek(0); // Reset pointer
                        while ($gejala = $gejala_list->fetch_assoc()): 
                        ?>
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
        
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <?php if ($hasil_diagnosa && count($hasil_diagnosa) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">📋</div>
                        <div>
                            <h2 class="card-title">Hasil Diagnosa</h2>
                            <p style="color: #666; margin-top: 5px;">Berdasarkan <?php echo count($gejala_dipilih); ?> gejala yang cocok dengan rules</p>
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
                                (<?php echo $hasil['total_matched']; ?> rules terpenuhi)
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($hasil['confidence'], 100); ?>%"></div>
                            </div>
                            
                            <div class="rules-info">
                                <strong>📌 Rules yang Cocok:</strong>
                                <?php foreach ($hasil['rules_matched'] as $rm): ?>
                                    <div class="rule-item">
                                        <?php echo $rm['rule_name']; ?>: 
                                        <?php echo implode(' + ', $rm['conditions']); ?>
                                        <?php echo $rm['priority'] == 1 ? '(Priority High)' : ''; ?>
                                    </div>
                                <?php endforeach; ?>
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
            <?php else: ?>
                <div class="card">
                    <div class="no-result">
                        <h3>⚠️ Tidak Ada Penyakit yang Terdeteksi</h3>
                        <p style="margin-top: 10px;">
                            Kombinasi gejala yang Anda pilih tidak cocok dengan rules yang ada dalam sistem. 
                            Kemungkinan:
                        </p>
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <li>Gejala yang dipilih belum cukup untuk membuat diagnosa</li>
                            <li>Kombinasi gejala tidak sesuai dengan pola penyakit yang ada</li>
                            <li>Perlu menambah gejala lain untuk diagnosa yang lebih akurat</li>
                        </ul>
                        <p style="margin-top: 10px;">
                            <strong>Saran:</strong> Coba pilih kombinasi gejala yang lebih lengkap atau gunakan metode Naive Bayes untuk pendekatan probabilistik.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
