<?php
require 'config1.php';

// Handle App insert
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_app'])) {
    $app = $_POST['app_name'];
    $stmt = $pdo->prepare("INSERT INTO app_table (app_name) VALUES (?)");
    $stmt->execute([$app]);
}

// Handle Service insert
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_service'])) {
    $service = $_POST['service_name'];
    $stmt = $pdo->prepare("INSERT INTO service_table (service_name) VALUES (?)");
    $stmt->execute([$service]);
}
// Handle Website insert
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_website'])) {
    $website = trim($_POST['website_url']);
    if (!empty($website)) {
        $stmt = $pdo->prepare("INSERT INTO website_table (website_url) VALUES (?)");
        $stmt->execute([$website]);
    }
}

// Handle App delete
if (isset($_GET['delete_app'])) {
    $id = $_GET['delete_app'];
    $stmt = $pdo->prepare("DELETE FROM app_table WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage.php");
    exit;
}

// Handle Service delete
if (isset($_GET['delete_service'])) {
    $id = $_GET['delete_service'];
    $stmt = $pdo->prepare("DELETE FROM service_table WHERE service_id = ?");
    $stmt->execute([$id]);
    header("Location: manage.php");
    exit;
}



// Handle Website delete
if (isset($_GET['delete_website'])) {
    $id = $_GET['delete_website'];
    $stmt = $pdo->prepare("DELETE FROM website_table WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage.php");
    exit;
}

// Fetch all website entries
$websites = $pdo->query("SELECT * FROM website_table")->fetchAll();

// Fetch all entries
$apps = $pdo->query("SELECT * FROM app_table")->fetchAll();
$services = $pdo->query("SELECT * FROM service_table")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Apps & Services</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h2 {
            color: #2b7cd3;
        }
        form, table {
            background: #fff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        input[type="text"], button {
            padding: 8px 12px;
            font-size: 14px;
            margin-right: 10px;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
        }
        button:hover {
            background: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #2b7cd3;
            color: white;
        }
        a {
            color: #d33;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr { display: block; }
            th { display: none; }
            td {
                position: relative;
                padding-left: 50%;
            }
            td::before {
                position: absolute;
                left: 10px;
                font-weight: bold;
            }
        }
    </style>
</head>
<body>

<h2>Add App</h2>
<form method="POST">
    <input type="text" name="app_name" placeholder="Enter App Name" required>
    <button type="submit" name="add_app">Add App</button>
</form>

<h2>App List</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>App Name</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($apps as $app): ?>
            <tr>
                <td><?= $app['id'] ?></td>
                <td><?= htmlspecialchars($app['app_name']) ?></td>
                <td><a href="?delete_app=<?= $app['id'] ?>" onclick="return confirm('Delete this app?')">Delete</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- <h2>Add Service</h2>
<form method="POST">
    <input type="text" name="service_name" placeholder="Enter Service Name" required>
    <button type="submit" name="add_service">Add Service</button>
</form>

<h2>Service List</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Service Name</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($services as $svc): ?>
            <tr>
                <td><?= $svc['service_id'] ?></td>
                <td><?= htmlspecialchars($svc['service_name']) ?></td>
                <td><a href="?delete_service=<?= $svc['service_id'] ?>" onclick="return confirm('Delete this service?')">Delete</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table> -->
<h2>Add Website</h2>
<form method="POST">
    <input type="text" name="website_url" placeholder="https://example.com" required>
    <button type="submit" name="add_website">Add Website</button>
</form>

<h2>Website List</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Website URL</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($websites as $site): ?>
            <tr>
                <td><?= $site['id'] ?></td>
                <td><?= htmlspecialchars($site['website_url']) ?></td>
                <td><a href="?delete_website=<?= $site['id'] ?>" onclick="return confirm('Delete this website?')">Delete</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>