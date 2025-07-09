<?php
require 'config1.php';

// Handle filter and pagination
$username_filter = $_GET['username'] ?? '';
$limit_options = [10, 25, 50, 'all'];
$selected_limit = $_GET['limit'] ?? 10;
$selected_limit = in_array($selected_limit, ['10', '25', '50', 'all']) ? $selected_limit : 10;

$page = max(1, intval($_GET['page'] ?? 1));
$offset = 0;
$limit = null;

if ($selected_limit !== 'all') {
    $limit = intval($selected_limit);
    $offset = ($page - 1) * $limit;
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM checked_result";
$count_params = [];

if ($username_filter !== '') {
    $count_query .= " WHERE username = ?";
    $count_params[] = $username_filter;
}
$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_records = $stmt->fetchColumn();
$total_pages = ($selected_limit === 'all') ? 1 : ceil($total_records / $limit);

// Fetch usernames for dropdown
$usernames = $pdo->query("SELECT DISTINCT username FROM checked_result ORDER BY username ASC")->fetchAll();

// Build the main query
$query = "SELECT * FROM checked_result";
$params = [];

if ($username_filter !== '') {
    $query .= " WHERE username = ?";
    $params[] = $username_filter;
}

if ($selected_limit !== 'all') {
    $query .= " LIMIT $limit OFFSET $offset";
}


$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checked Results</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
        th { background: #007bff; color: #fff; }
        form { background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        select, button { padding: 8px 12px; margin-right: 10px; }
        .pagination { margin-top: 20px; }
        .pagination a {
            margin: 0 3px;
            padding: 6px 10px;
            background: #eee;
            text-decoration: none;
            border-radius: 4px;
            color: #007bff;
        }
        .pagination a.active {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>

<h2>Checked Results</h2>

<form method="GET">
    <label for="username">Filter by Username:</label>
    <select name="username" id="username" class="searchable-select">
        <option value="">-- All Users --</option>
        <?php foreach ($usernames as $user): ?>
            <option value="<?= htmlspecialchars($user['username']) ?>" <?= $username_filter === $user['username'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['username']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="limit">Records per page:</label>
    <select name="limit" id="limit">
        <?php foreach ($limit_options as $opt): ?>
            <option value="<?= $opt ?>" <?= $selected_limit == $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Apply</button>
</form>

<script>
    $(document).ready(function() {
        $('.searchable-select').select2({
            placeholder: "-- All Users --",
            allowClear: true
        });
    });
</script>

<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>App/Service/Website</th>
            <th>Availability</th>
            <th>Status Detail</th>
            <th>Last Checked At</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($results) > 0): ?>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['app_or_service_name']) ?></td>
                    <td><?= htmlspecialchars($row['availability']) ?></td>
                    <td><?= htmlspecialchars($row['status_detail']) ?></td>
                    <td><?= htmlspecialchars($row['checked_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($selected_limit !== 'all' && $total_pages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?username=<?= urlencode($username_filter) ?>&limit=<?= $selected_limit ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

</body>
</html>
