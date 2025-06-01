<?php
// URL API Golang
$apiBalance = 'http://localhost:8080/balance';
$apiIncome = 'http://localhost:8080/income';
$apiExpense = 'http://localhost:8080/expense';

$message = '';
$balance = 0;

// Pagination settings
$perPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Get balance from API
$balanceJson = file_get_contents($apiBalance);
if ($balanceJson !== false) {
    $data = json_decode($balanceJson, true);
    if (isset($data['balance'])) {
        $balance = $data['balance'];
    }
}

// Get transaction history from API
$allHistory = json_decode(file_get_contents("http://localhost:8080/history"), true);

// Process income/expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['income_amount'])) {
        $amount = floatval($_POST['income_amount']);
        if ($amount > 0) {
            $postData = json_encode(['amount' => $amount]);
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => $postData
                ]
            ];
            $context = stream_context_create($opts);
            $result = file_get_contents($apiIncome, false, $context);
            if ($result !== false) {
                $resp = json_decode($result, true);
                if (isset($resp['balance'])) {
                    $balance = $resp['balance'];
                    $message = $resp['message'] ?? 'Pemasukan berhasil ditambahkan.';
                }
            }
        }
    } elseif (isset($_POST['expense_amount'])) {
        $amount = floatval($_POST['expense_amount']);
        if ($amount > 0) {
            $postData = json_encode(['amount' => $amount]);
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => $postData
                ]
            ];
            $context = stream_context_create($opts);
            $result = @file_get_contents($apiExpense, false, $context);
            if ($result !== false) {
                $resp = json_decode($result, true);
                if (isset($resp['balance'])) {
                    $balance = $resp['balance'];
                    $message = $resp['message'] ?? 'Pengeluaran berhasil ditambahkan.';
                }
            }
        }
    }
    // Refresh history after submission
    header("Location: ".strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Paginate history
$totalItems = count($allHistory);
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;
$history = array_slice($allHistory, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uang Kas - Money Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --pastel-pink: #FFD6E0;
            --pastel-green: #C1FBA4;
            --pastel-blue: #B5EAD7;
            --pastel-yellow: #FFEAC9;
            --pastel-purple: #E2D1F9;
            --pastel-orange: #FFC8A2;
            --text-dark: #4A4A4A;
            --text-light: #6C6C6C;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #FFF9FB;
            color: var(--text-dark);
            min-height: 100vh;
            padding: 2rem;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        h1 {
            font-size: 2.5rem;
            color: #FF85A2;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
        }
        
        .balance-display {
            text-align: center;
            margin: 2rem 0 3rem;
            background: var(--pastel-purple);
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }
        
        .balance-amount {
            font-size: 2.5rem;
            font-weight: 700;
            color: #8A4FFF;
            margin: 0.5rem 0;
        }
        
        .main-content {
            display: flex;
            gap: 2rem;
        }
        
        .form-section {
            flex: 1;
        }
        
        .history-section {
            flex: 1;
        }
        
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            background: var(--pastel-yellow);
        }
        
        h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #F0F0F0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
        }
        
        button {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
        }
        
        .btn-income {
            background: var(--pastel-green);
            color: #2E7D32;
        }
        
        .btn-income:hover {
            background: #A5D6A7;
            transform: translateY(-2px);
        }
        
        .btn-expense {
            background: var(--pastel-pink);
            color: #C2185B;
        }
        
        .btn-expense:hover {
            background: #F8BBD0;
            transform: translateY(-2px);
        }
        
        .message {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
        
        .success {
            background: var(--pastel-green);
            color: #2E7D32;
        }
        
        .error {
            background: var(--pastel-pink);
            color: #C2185B;
        }
        
        .history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }
        
        .history-table th, 
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #F0F0F0;
        }
        
        .history-table th {
            background: var(--pastel-blue);
            color: var(--text-dark);
            font-weight: 600;
        }
        
        .history-table tr:last-child td {
            border-bottom: none;
        }
        
        .history-table tr:hover {
            background: rgba(0, 0, 0, 0.02);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .pagination a, 
        .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
        
        .pagination a {
            background: var(--pastel-blue);
            color: var(--text-dark);
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #80CBC4;
            transform: translateY(-2px);
        }
        
        .pagination .current {
            background: var(--pastel-purple);
            color: white;
        }
        
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Uang Kas Tracker</h1>
            <p class="subtitle">Kelola uang dengan semangat dan senyuman!</p>
        </header>

        <div class="balance-display">
            <h2>Saldo Saat Ini</h2>
            <div class="balance-amount">Rp <?= number_format($balance, 0, ',', '.') ?></div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'berhasil') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="main-content">
            <div class="form-section">
                <div class="form-card">
                    <h2>💵 Tambah Pemasukan</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="income_amount">Jumlah (Rp)</label>
                            <input type="number" name="income_amount" id="income_amount" step="0.01" required>
                        </div>
                        <button type="submit" class="btn-income">➕ Tambah Pemasukan</button>
                    </form>
                </div>

                <div class="form-card">
                    <h2>💸 Tambah Pengeluaran</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="expense_amount">Jumlah (Rp)</label>
                            <input type="number" name="expense_amount" id="expense_amount" step="0.01" required>
                        </div>
                        <button type="submit" class="btn-expense">➖ Tambah Pengeluaran</button>
                    </form>
                </div>
            </div>

            <div class="history-section">
                <h2>📜 Riwayat Transaksi</h2>
                <?php if (!empty($history)): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?= $row['created_at'] ?></td>
                                    <td><?= $row['type'] ?></td>
                                    <td><?= number_format($row['amount'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>">« Prev</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="background: var(--pastel-yellow); padding: 1rem; border-radius: 12px;">Belum ada transaksi</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>