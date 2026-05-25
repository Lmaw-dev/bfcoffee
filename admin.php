<?php
session_start();

$defaultResetPassword = '12345678';
$message = '';
$error = '';

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.html');
    exit();
}

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if (!$isAdmin) {
    header('Location: index.html');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'bfs');
if ($conn->connect_error) {
    die('Connection Failed : ' . $conn->connect_error);      
}

if (isset($_POST['admin_action']) && $_POST['admin_action'] === 'reset_password') {
    $email = trim($_POST['email'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    if ($newPassword === '') {
        $newPassword = $defaultResetPassword;
    }

    if ($email !== '') {
        $stmt = $conn->prepare('UPDATE registration SET password = ? WHERE email = ? LIMIT 1');
        $stmt->bind_param('ss', $newPassword, $email);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = 'Password reset successful for ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '.';
        } else {
            $error = 'No account updated. The email may not exist.';
        }
        $stmt->close();
    }
}

if (isset($_POST['admin_action']) && $_POST['admin_action'] === 'delete_account') {
    $email = trim($_POST['email'] ?? '');
    if ($email !== '') {
        $stmt = $conn->prepare('DELETE FROM registration WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = 'Account deleted for ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '.';
        } else {
            $error = 'No account deleted. The email may not exist.';
        }
        $stmt->close();
    }
}

$users = [];
$query = 'SELECT firstname, lastname, email, address, sex FROM registration ORDER BY firstname, lastname';
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Mode - Account Manager</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --primary: #1f6feb;
            --danger: #c62828;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #dbe2ea;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #eef3ff 0%, #f9fbff 45%, #f3fff8 100%);
            color: var(--text);
            min-height: 100vh;
            padding: 24px;
        }

        .shell {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 14px 28px rgba(17, 24, 39, 0.08);
            overflow: hidden;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            background: #ffffff;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 0.3px;
        }

        .badge {
            background: #e6f0ff;
            color: #18489b;
            border: 1px solid #c6d9ff;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .content {
            padding: 20px 24px 28px;
        }

        .message {
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
        }

        .message.success {
            background: #e8f7ee;
            border: 1px solid #bce3cb;
            color: #1f6f44;
        }

        .message.error {
            background: #fdecec;
            border: 1px solid #f5c2c2;
            color: #9f2d2d;
        }

        .admin-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            opacity: 0.92;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-logout {
            background: #111827;
            color: #fff;
        }

        .login-wrap {
            max-width: 420px;
            margin: 60px auto;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 12px 24px rgba(31, 41, 55, 0.08);
            padding: 22px;
        }

        .login-wrap h2 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .hint {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 14px;
        }

        .field {
            margin-bottom: 12px;
        }

        .field label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
            color: #374151;
        }

        .field input {
            width: 100%;
            border: 1px solid #cfd8e3;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
            background: #fff;
        }

        th,
        td {
            border-bottom: 1px solid #edf1f7;
            padding: 10px;
            text-align: left;
            font-size: 14px;
            vertical-align: middle;
        }

        th {
            background: #f7faff;
            color: #334155;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        tr:hover td {
            background: #fbfdff;
        }

        .actions-cell {
            min-width: 290px;
        }

        .inline-form {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .inline-form input[type="text"] {
            border: 1px solid #d3dce8;
            border-radius: 8px;
            padding: 8px 10px;
            width: 160px;
        }

        .small {
            font-size: 12px;
            color: var(--muted);
        }

        @media (max-width: 900px) {
            body {
                padding: 14px;
            }

            .header {
                padding: 14px;
            }

            .content {
                padding: 14px;
            }

            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="header">
            <div>
                <h1>Account Management</h1>
                <div class="small">Manage registered users in the <code>registration</code> table.</div>
            </div>
            <div class="admin-actions">
                <span class="badge">Admin Mode</span>
                <a class="btn btn-logout" href="admin.php?logout=1">Logout</a>
            </div>
        </div>

        <div class="content">
            <?php if ($message !== ''): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Sex</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($users) === 0): ?>
                        <tr>
                            <td colspan="6">No registered accounts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['firstname'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['lastname'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['sex'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="actions-cell">
                                    <form class="inline-form" method="POST" action="admin.php">
                                        <input type="hidden" name="admin_action" value="reset_password">
                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="new_password" placeholder="New password (optional)">
                                        <button class="btn btn-primary" type="submit">Reset Password</button>
                                    </form>

                                    <form class="inline-form" method="POST" action="admin.php" onsubmit="return confirm('Delete this account?');">
                                        <input type="hidden" name="admin_action" value="delete_account">
                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="btn btn-danger" type="submit">Delete Account</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
