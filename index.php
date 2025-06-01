<?php
// URL API Golang
$apiBalance = 'http://localhost:8080/balance';
$apiIncome = 'http://localhost:8080/income';
$apiExpense = 'http://localhost:8080/expense';

$message = '';
$balance = 0;

// Get balance from API
$balanceJson = file_get_contents($apiBalance);
if ($balanceJson !== false) {
    $data = json_decode($balanceJson, true);
    if (isset($data['balance'])) {
        $balance = $data['balance'];
    }
}

// Get transaction history from API
$history = json_decode(file_get_contents("http://localhost:8080/history"), true);

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
    $history = json_decode(file_get_contents("http://localhost:8080/history"), true);
}
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
            --primary: #FFD166;
            --secondary: #06D6A0;
            --danger: #EF476F;
            --light: #F8F9FA;
            --dark: #212529;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
            color: var(--dark);
            min-height: 100vh;
            padding: 2rem;
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
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: #6c757d;
            font-size: 1.1rem;
        }
        .balance-display {
            text-align: center;
            margin: 2rem 0 3rem 0;
        }
        .balance-amount {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary);
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
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-income {
            background: var(--secondary);
            color: white;
        }
        .btn-income:hover {
            background: #05b388;
        }
        .btn-expense {
            background: var(--danger);
            color: white;
        }
        .btn-expense:hover {
            background: #d63a5f;
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .history-table th, 
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .history-table th {
            background: var(--primary);
            color: var(--dark);
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Uang Kas Tracker</h1>
            <p class="subtitle">Kelola pemasukan & pengeluaran dengan mudah!</p>
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
                    <h2>Tambah Pemasukan</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="income_amount">Jumlah (Rp)</label>
                            <input type="number" name="income_amount" id="income_amount" step="0.01" required>
                        </div>
                        <button type="submit" class="btn-income">➕ Tambah Pemasukan</button>
                    </form>
                </div>

                <div class="form-card">
                    <h2>Tambah Pengeluaran</h2>
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
                <?php else: ?>
                    <p>Belum ada transaksi</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>