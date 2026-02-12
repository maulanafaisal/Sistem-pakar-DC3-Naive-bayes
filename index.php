<?php
require_once 'config.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Pakar Anggrek</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 1px solid white;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: white;
            color: #667eea;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome-card h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 16px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 2px solid transparent;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .menu-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .menu-card h3 {
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .menu-card p {
            color: #666;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            🌸 Sistem Pakar Anggrek
        </div>
        <div class="navbar-user">
            <span>👤 <?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h1>Selamat Datang, <?php echo htmlspecialchars($user['nama_lengkap']); ?>! 👋</h1>
            <p>Sistem diagnosa penyakit tanaman anggrek menggunakan metode Naive Bayes dan Decision Tree</p>
        </div>
        
        <?php
        $conn = getConnection();
        $user_id = $_SESSION['user_id'];
        
        // Get statistics
        $total_diagnosa = $conn->query("SELECT COUNT(*) as total FROM pencatatan WHERE user_id = $user_id")->fetch_assoc()['total'];
        $total_penyakit = $conn->query("SELECT COUNT(*) as total FROM penyakit")->fetch_assoc()['total'];
        $total_gejala = $conn->query("SELECT COUNT(*) as total FROM gejala")->fetch_assoc()['total'];
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_diagnosa; ?></div>
                <div class="stat-label">Total Diagnosa</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_penyakit; ?></div>
                <div class="stat-label">Jenis Penyakit</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_gejala; ?></div>
                <div class="stat-label">Data Gejala</div>
            </div>
        </div>
        
        <div class="menu-grid">
            <a href="diagnosa_naive_bayes.php" class="menu-card">
                <div class="menu-icon">🧠</div>
                <h3>Diagnosa Naive Bayes</h3>
                <p>Diagnosa penyakit menggunakan metode probabilitas Naive Bayes</p>
            </a>
            
            <a href="diagnosa_decision_tree.php" class="menu-card">
                <div class="menu-icon">🌳</div>
                <h3>Diagnosa Decision Tree</h3>
                <p>Diagnosa penyakit menggunakan metode Decision Tree dengan rules</p>
            </a>
            
            <a href="laporan.php" class="menu-card">
                <div class="menu-icon">📊</div>
                <h3>Laporan Pencatatan</h3>
                <p>Lihat riwayat dan laporan hasil diagnosa tanaman anggrek</p>
            </a>
            
            <a href="data_penyakit.php" class="menu-card">
                <div class="menu-icon">📚</div>
                <h3>Data Penyakit</h3>
                <p>Database penyakit anggrek dan solusi penanganannya</p>
            </a>

            <a href="data_penyakit.php" class="menu-card">
                <div class="menu-icon">📚</div>
                <h3>Data gejala</h3>
                <p>Database penyakit anggrek dan solusi penanganannya</p>
            </a>
        </div>
    </div>
</body>
</html>
