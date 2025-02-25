<?php
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $company = $_POST['company'];
    $domain = $_POST['domain'];
    $city = $_POST['city'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $pdo->prepare("INSERT INTO customers (name, company, domain, city, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $company, $domain, $city, $email, $phone]);
}

$customers = $pdo->query("SELECT * FROM customers")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
        }

        .editor-header {
            background: #f4f4f9;
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .editor-title {
            margin: 0 0 8px 0;
            padding: 0;
            color: #0056b3;
            margin-top: 20px;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        .button-group {
            padding: 10px 17px;
            display: flex;
            gap: 10px;
        }

        .command-button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        .command-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="editor-view">
        <div class="editor" id="editorSection">
            <div class="editor-header">
                <div class="header-top">
                    <h1 class="editor-title" style="padding-left: 15px;">NetBound Tools: Customers</h1>
                </div>
                <div class="persistent-status-bar" id="statusBar"></div>
                <div class="button-controls">
                    <a href="invoice.php" class="command-button">Back to Invoice Tool</a>
                </div>
            </div>
            <div class="preview-area" id="previewArea">
                <div class="button-group">
                    <form method="POST">
                        <input type="text" name="name" placeholder="Customer Name" required>
                        <input type="text" name="company" placeholder="Company" required>
                        <input type="text" name="domain" placeholder="Domain" required>
                        <input type="text" name="city" placeholder="City" required>
                        <input type="email" name="email" placeholder="Customer Email" required>
                        <input type="text" name="phone" placeholder="Phone" required>
                        <button type="submit" class="command-button">Add</button>
                    </form>
                </div>
                <div class="button-group">
                    <h2>Customer List</h2>
                    <ul>
                        <?php foreach ($customers as $customer): ?>
                            <li><?php echo htmlspecialchars($customer['name']) . ' - ' . htmlspecialchars($customer['company']) . ' - ' . htmlspecialchars($customer['domain']) . ' - ' . htmlspecialchars($customer['city']) . ' - ' . htmlspecialchars($customer['email']) . ' - ' . htmlspecialchars($customer['phone']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
