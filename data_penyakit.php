<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$penyakit_list = $conn->query("SELECT * FROM penyakit ORDER BY kode_penyakit");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penyakit - Sistem Pakar Anggrek</title>
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
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
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
            color: #ec4899;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .header-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header-card h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header-card p {
            color: #666;
            font-size: 16px;
        }
        
        .disease-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .disease-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .disease-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #ec4899;
        }
        
        .disease-header {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
            color: white;
            padding: 20px;
        }
        
        .disease-code {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .disease-name {
            font-size: 22px;
            font-weight: bold;
        }
        
        .disease-body {
            padding: 20px;
        }
        
        .section {
            margin-bottom: 15px;
        }
        
        .section-title {
            font-size: 14px;
            color: #ec4899;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .section-content {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .gejala-tag {
            display: inline-block;
            background: #fce7f3;
            color: #be185d;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin: 3px;
        }
        
        .solution-box {
            background: #f0fdf4;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #10b981;
        }
        
        .stats-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #ec4899;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }
        
        .view-toggle {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .toggle-btn {
            padding: 10px 25px;
            border: 2px solid #ec4899;
            background: white;
            color: #ec4899;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .toggle-btn.active {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
            color: white;
        }
        
        .toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.3);
        }
        
        .table-view {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: none;
        }
        
        .table-view.active {
            display: block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        
        tr:hover {
            background: #fef2f8;
        }
        
        .table-disease-name {
            font-weight: bold;
            color: #ec4899;
            font-size: 16px;
        }
        
        .table-code {
            background: #fce7f3;
            color: #be185d;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 5px;
        }
        
        .gejala-list-compact {
            font-size: 13px;
            color: #555;
        }
        
        .gejala-list-compact span {
            background: #f8f9fa;
            padding: 2px 8px;
            border-radius: 10px;
            margin: 2px;
            display: inline-block;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">📚 Database Penyakit Anggrek</div>
        <a href="index.php" class="btn-back">← Kembali</a>
    </div>
    
    <div class="container">
        <div class="header-card">
            <h1>🌸 Database Penyakit Tanaman Anggrek</h1>
            <p>Informasi lengkap tentang penyakit anggrek, gejala, dan solusi penanganannya</p>
        </div>
        
        <div class="view-toggle">
            <button class="toggle-btn active" onclick="switchView('card')">📱 Tampilan Card</button>
            <button class="toggle-btn" onclick="switchView('table')">📋 Tampilan List</button>
        </div>
        
        <!-- Table View -->
        <div class="table-view" id="tableView">
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%">Kode</th>
                        <th style="width: 20%">Nama Penyakit</th>
                        <th style="width: 30%">Deskripsi</th>
                        <th style="width: 20%">Gejala Utama</th>
                        <th style="width: 20%">Solusi</th>
                     
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $penyakit_list->data_seek(0); // Reset pointer
                    while ($penyakit = $penyakit_list->fetch_assoc()): 
                        $penyakit_id = $penyakit['id'];
                        $gejala_query = "SELECT g.kode_gejala, g.nama_gejala FROM gejala g 
                                        JOIN penyakit_gejala pg ON g.id = pg.gejala_id 
                                        WHERE pg.penyakit_id = $penyakit_id 
                                        ORDER BY pg.probabilitas DESC 
                                        LIMIT 5";
                        $gejala_result = $conn->query($gejala_query);
                    ?>
                        <tr>
                            <td>
                                <span class="table-code"><?php echo $penyakit['kode_penyakit']; ?></span>
                            </td>
                            <td>
                                <div class="table-disease-name"><?php echo htmlspecialchars($penyakit['nama_penyakit']); ?></div>
                            </td>
                            <td style="font-size: 13px; color: #555;">
                                <?php echo htmlspecialchars($penyakit['deskripsi']); ?>
                            </td>
                            <td>
                                <div class="gejala-list-compact">
                                    <?php while ($g = $gejala_result->fetch_assoc()): ?>
                                        <span><?php echo $g['kode_gejala']; ?></span>
                                    <?php endwhile; ?>
                                </div>
                            </td>
                            <td style="font-size: 13px; color: #555;">
                                <?php 
                                    $solusi = $penyakit['solusi'];
                                    echo strlen($solusi) > 100 ? substr($solusi, 0, 100) . '...' : $solusi;
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Card View -->
        <div class="disease-grid" id="cardView">
            <?php 
            $penyakit_list2 = $conn->query("SELECT * FROM penyakit ORDER BY kode_penyakit");
            while ($penyakit = $penyakit_list2->fetch_assoc()): 
            ?>
                <?php
                // Get gejala for this disease
                $penyakit_id = $penyakit['id'];
                $gejala_query = "SELECT g.* FROM gejala g 
                                JOIN penyakit_gejala pg ON g.id = pg.gejala_id 
                                WHERE pg.penyakit_id = $penyakit_id 
                                ORDER BY pg.probabilitas DESC 
                                LIMIT 5";
                $gejala_result = $conn->query($gejala_query);
                
                // Get stats
                $jumlah_gejala = $conn->query("SELECT COUNT(*) as cnt FROM penyakit_gejala WHERE penyakit_id = $penyakit_id")->fetch_assoc()['cnt'];
                $jumlah_kasus = $conn->query("SELECT COUNT(*) as cnt FROM pencatatan WHERE hasil_penyakit_id = $penyakit_id")->fetch_assoc()['cnt'];
                ?>
                
                <div class="disease-card">
                    <div class="disease-header">
                        <div class="disease-code"><?php echo $penyakit['kode_penyakit']; ?></div>
                        <div class="disease-name"><?php echo htmlspecialchars($penyakit['nama_penyakit']); ?></div>
                    </div>
                    
                    <div class="disease-body">
                        <div class="section">
                            <div class="section-title">📝 Deskripsi</div>
                            <div class="section-content">
                                <?php echo htmlspecialchars($penyakit['deskripsi']); ?>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">🔍 Gejala Utama</div>
                            <div class="section-content">
                                <?php while ($gejala = $gejala_result->fetch_assoc()): ?>
                                    <span class="gejala-tag"><?php echo $gejala['kode_gejala']; ?></span>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">💡 Solusi & Penanganan</div>
                            <div class="solution-box">
                                <?php echo htmlspecialchars($penyakit['solusi']); ?>
                            </div>
                        </div>
                        
                        <div class="stats-box">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $jumlah_gejala; ?></div>
                                <div class="stat-label">Gejala</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $jumlah_kasus; ?></div>
                                <div class="stat-label">Kasus Terdeteksi</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <script>
        function switchView(view) {
            const cardView = document.getElementById('cardView');
            const tableView = document.getElementById('tableView');
            const buttons = document.querySelectorAll('.toggle-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            
            if (view === 'card') {
                cardView.style.display = 'grid';
                tableView.classList.remove('active');
                buttons[0].classList.add('active');
            } else {
                cardView.style.display = 'none';
                tableView.classList.add('active');
                buttons[1].classList.add('active');
            }
        }
    </script>
</body>
</html>
