<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter
$metode_filter = isset($_GET['metode']) ? $_GET['metode'] : '';
$where_metode = $metode_filter ? "AND p.metode_diagnosa = '$metode_filter'" : '';

// Get total records
$total_query = "SELECT COUNT(*) as total FROM pencatatan WHERE user_id = $user_id";
$total_result = $conn->query($total_query);
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get records
$query = "SELECT p.*, py.nama_penyakit, py.kode_penyakit 
          FROM pencatatan p 
          LEFT JOIN penyakit py ON p.hasil_penyakit_id = py.id 
          WHERE p.user_id = $user_id $where_metode
          ORDER BY p.created_at DESC 
          LIMIT $limit OFFSET $offset";
$records = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pencatatan - Sistem Pakar Anggrek</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
            color: #f59e0b;
        }
        
        .container {
            max-width: 1200px;
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
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 24px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-naive {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-tree {
            background: #d1fae5;
            color: #065f46;
        }
        
        .confidence {
            font-weight: 600;
        }
        
        .confidence-high {
            color: #10b981;
        }
        
        .confidence-medium {
            color: #f59e0b;
        }
        
        .confidence-low {
            color: #ef4444;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .page-link {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-decoration: none;
            color: #555;
            transition: all 0.3s;
        }
        
        .page-link:hover, .page-link.active {
            background: #f59e0b;
            color: white;
            border-color: #f59e0b;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .btn-detail {
            background: #667eea;
            color: white;
            padding: 6px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .btn-detail:hover {
            background: #5568d3;
        }
        
        @media print {
            .navbar, .filter-section, .pagination, .btn-detail {
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
        <div class="navbar-brand">📊 Laporan Pencatatan</div>
        <div class="navbar-actions">
            <button onclick="window.print()" class="btn-print">🖨️ Cetak</button>
            <a href="index.php" class="btn-back">← Kembali</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <span>📋</span>
                    Riwayat Diagnosa Tanaman Anggrek
                </h2>
            </div>
            
            <?php
            // Get statistics
            $stats = [
                'total' => $conn->query("SELECT COUNT(*) as cnt FROM pencatatan WHERE user_id = $user_id")->fetch_assoc()['cnt'],
                'naive_bayes' => $conn->query("SELECT COUNT(*) as cnt FROM pencatatan WHERE user_id = $user_id AND metode_diagnosa = 'naive_bayes'")->fetch_assoc()['cnt'],
                'decision_tree' => $conn->query("SELECT COUNT(*) as cnt FROM pencatatan WHERE user_id = $user_id AND metode_diagnosa = 'decision_tree'")->fetch_assoc()['cnt']
            ];
            ?>
            
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Diagnosa</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['naive_bayes']; ?></div>
                    <div class="stat-label">Naive Bayes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $stats['decision_tree']; ?></div>
                    <div class="stat-label">Decision Tree</div>
                </div>
            </div>
            
            <div class="filter-section">
                <label>Filter Metode:</label>
                <select onchange="window.location.href='?metode=' + this.value">
                    <option value="">Semua Metode</option>
                    <option value="naive_bayes" <?php echo $metode_filter == 'naive_bayes' ? 'selected' : ''; ?>>Naive Bayes</option>
                    <option value="decision_tree" <?php echo $metode_filter == 'decision_tree' ? 'selected' : ''; ?>>Decision Tree</option>
                </select>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nama Tanaman</th>
                            <th>Lokasi</th>
                            <th>Metode</th>
                            <th>Hasil Diagnosa</th>
                            <th>Kepercayaan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($records->num_rows > 0): ?>
                            <?php 
                            $no = $offset + 1;
                            while ($row = $records->fetch_assoc()): 
                                $confidence = floatval($row['tingkat_kepercayaan']);
                                $confidence_class = $confidence >= 70 ? 'confidence-high' : ($confidence >= 50 ? 'confidence-medium' : 'confidence-low');
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_pencatatan'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_tanaman']); ?></td>
                                    <td><?php echo htmlspecialchars($row['lokasi']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['metode_diagnosa'] == 'naive_bayes' ? 'badge-naive' : 'badge-tree'; ?>">
                                            <?php echo $row['metode_diagnosa'] == 'naive_bayes' ? 'Naive Bayes' : 'Decision Tree'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nama_penyakit']); ?></strong>
                                        <br><small><?php echo $row['kode_penyakit']; ?></small>
                                    </td>
                                    <td>
                                        <span class="confidence <?php echo $confidence_class; ?>">
                                            <?php echo number_format($confidence, 1); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <a href="detail_pencatatan.php?id=<?php echo $row['id']; ?>" class="btn-detail">Detail</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-data">
                                    <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                    Belum ada data pencatatan diagnosa
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&metode=<?php echo $metode_filter; ?>" class="page-link">← Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&metode=<?php echo $metode_filter; ?>" 
                           class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&metode=<?php echo $metode_filter; ?>" class="page-link">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
