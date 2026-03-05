<?php
session_start();
require_once 'db.php';
 
function redirect($url) {
    header("Location: $url");
    exit;
}
 
function getPlaceholder($cat, $id) {
    $c = ['Fashion'=>'d1e8e2','Food'=>'fde2e4','Travel'=>'c1e6f5','Art'=>'f4f1de','DIY'=>'e2eafc'];
    $textColor = ['Fashion'=>'264653','Food'=>'c1121f','Travel'=>'1d3557','Art'=>'3d348b','DIY'=>'3a0ca3'];
    $bg = $c[$cat] ?? 'f8f9fa';
    $text = $textColor[$cat] ?? '555555';
    return "https://via.placeholder.com/300x400/$bg/$text?text=" . urlencode("$cat $id");
}
 
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('?');
}
 
if (isset($_GET['view_pin'])) {
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM pins p JOIN users u ON p.user_id=u.id WHERE p.id=?");
    $stmt->execute([(int)$_GET['view_pin']]);
    $pin = $stmt->fetch();
    if (!$pin) redirect('?');
}
 
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signup'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username,email,password) VALUES (?,?,?)");
            $stmt->execute([$_POST['username'], $_POST['email'], password_hash($_POST['password'], PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            redirect('?');
        } catch (PDOException $e) {
            $error = "Username or email already exists.";
        }
    } elseif (isset($_POST['login'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$_POST['email']]);
        $u = $stmt->fetch();
        if ($u && password_verify($_POST['password'], $u['password'])) {
            $_SESSION['user_id'] = $u['id'];
            redirect('?');
        } else {
            $error = "Invalid email or password.";
        }
    } elseif (isset($_POST['create_pin']) && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO pins (user_id,title,description,image,category) VALUES (?,?,?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $_POST['title'], $_POST['description'], 'placeholder', $_POST['category']]);
        redirect('?');
    } elseif (isset($_POST['create_board']) && !empty($_SESSION['user_id'])) {
        if (!empty($_POST['board_name'])) {
            $stmt = $pdo->prepare("INSERT INTO boards (user_id,name) VALUES (?,?)");
            $stmt->execute([$_SESSION['user_id'], $_POST['board_name']]);
            redirect('?tab=boards');
        }
    } elseif (isset($_POST['save_to_board']) && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM boards WHERE id=? AND user_id=?");
        $stmt->execute([(int)$_POST['board_id'], $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            try {
                $stmt = $pdo->prepare("INSERT INTO board_pins (board_id,pin_id) VALUES (?,?)");
                $stmt->execute([(int)$_POST['board_id'], (int)$_POST['pin_id']]);
            } catch (PDOException $e) {}
        }
        redirect('?');
    }
}
 
$user = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
 
$stmt = $pdo->prepare("SELECT p.*,u.username FROM pins p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC LIMIT 30");
$stmt->execute();
$pins = $stmt->fetchAll();
 
$boards = [];
if ($user) {
    $stmt = $pdo->prepare("SELECT * FROM boards WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $boards = $stmt->fetchAll();
 
    if (empty($boards)) {
        $stmt = $pdo->prepare("INSERT INTO boards (user_id, name) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], 'My Favorites']);
        $boards = [['id' => $pdo->lastInsertId(), 'name' => 'My Favorites', 'created_at' => date('Y-m-d H:i:s')]];
    }
}
 
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
if ($search || $category) {
    $sql = "SELECT p.*, u.username FROM pins p JOIN users u ON p.user_id = u.id WHERE 1=1";
    $params = [];
    if ($search) {
        $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($category) {
        $sql .= " AND p.category = ?";
        $params[] = $category;
    }
    $sql .= " ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pins = $stmt->fetchAll();
}
 
$tab = $_GET['tab'] ?? 'home';
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>❄️ Winter Pinterest Clone</title>
    <style>
        /* === ULTIMATE WINTER CHRISTMAS THEME WITH FROSTY === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e6f7ff 0%, #f0f8ff 100%);
            color: #2c3e50;
            line-height: 1.5;
            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
        }
 
        /* Faux snow ground */
        body::after {
            content: "";
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40px;
            background: url("image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120'%3E%3Cpath fill='%23ffffff' d='M0,60 C300,120 900,0 1200,60 L1200,120 L0,120 Z'/%3E%3C/svg%3E");
            background-size: cover;
            z-index: 10;
        }
 
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 12px;
            position: relative;
            z-index: 5;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            margin-bottom: 16px;
            position: relative;
            z-index: 10;
        }
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #c1121f;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-family: 'Arial', sans-serif;
        }
 
        /* Twinkling fairy lights (top border) */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: repeating-linear-gradient(
                90deg,
                #ff6b6b 0px,
                #ff6b6b 4px,
                #4ecdc4 4px,
                #4ecdc4 8px,
                #ffd166 8px,
                #ffd166 12px,
                #118ab2 12px,
                #118ab2 16px
            );
            box-shadow: 0 0 10px #ff6b6b, 0 0 15px #4ecdc4;
            animation: twinkle 3s infinite alternate;
            z-index: 100;
        }
 
        @keyframes twinkle {
            0% { opacity: 0.6; }
            100% { opacity: 1; box-shadow: 0 0 20px #ff6b6b, 0 0 25px #4ecdc4, 0 0 30px #ffd166; }
        }
 
        .auth-links a {
            margin-left: 16px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(4px);
            transition: all 0.2s;
        }
        .auth-links a:hover {
            background: rgba(255,255,255,0.9);
            color: #c1121f;
        }
        .tabs {
            display: flex;
            gap: 24px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(193, 18, 31, 0.2);
        }
        .tab {
            padding: 6px 16px;
            cursor: pointer;
            border-radius: 20px;
            font-weight: 600;
            color: #555;
            background: rgba(255,255,255,0.6);
            transition: all 0.2s;
        }
        .tab.active, .tab:hover {
            background: #c1121f;
            color: white;
            box-shadow: 0 4px 12px rgba(193, 18, 31, 0.3);
        }
        .form-box {
            background: rgba(255, 255, 255, 0.85);
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 24px;
            max-width: 560px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            position: relative;
        }
        .form-box::before {
            content: "🌰";
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 18px;
            opacity: 0.6;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 15px;
            background: rgba(255,255,255,0.9);
            transition: all 0.2s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c1121f;
            box-shadow: 0 0 0 3px rgba(193, 18, 31, 0.2);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(236px, 1fr));
            gap: 16px;
        }
        .pin {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            cursor: pointer;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.6);
            position: relative;
        }
        .pin::after {
            content: "";
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 0;
            background: linear-gradient(to bottom, transparent, #a8dadc);
            border-radius: 0 0 4px 4px;
            transition: height 0.4s ease;
            opacity: 0.7;
        }
        .pin:hover::after {
            height: 20px;
        }
        .pin:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        .pin img {
            width: 100%;
            height: auto;
            display: block;
        }
        .pin-info {
            padding: 16px;
        }
        .pin-title {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
            color: #2c3e50;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .pin-user {
            font-size: 13px;
            color: #7f8c8d;
        }
        .save-form select {
            margin-top: 10px;
            margin-bottom: 12px;
        }
        .error {
            color: #c1121f;
            margin: 12px 0;
            font-weight: 600;
        }
        button {
            background: #c1121f;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 24px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(193, 18, 31, 0.3);
        }
        button:hover {
            background: #a00f19;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(193, 18, 31, 0.4);
        }
        .view-pin-page {
            max-width: 800px;
            margin: 40px auto;
            text-align: center;
            position: relative;
        }
        .view-pin-page::before {
            content: "❄️";
            position: absolute;
            top: -30px;
            left: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        .view-pin-page::after {
            content: "🎄";
            position: absolute;
            top: -30px;
            right: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        .view-pin-image {
            width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 20px;
            margin-bottom: 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            background: white;
            position: relative;
        }
        .view-pin-title {
            font-size: 32px;
            margin: 16px 0;
            font-weight: 800;
            color: #c1121f;
        }
        .view-pin-desc {
            margin: 16px 0;
            color: #34495e;
            line-height: 1.7;
            font-size: 17px;
            background: rgba(255,255,255,0.7);
            padding: 16px;
            border-radius: 12px;
            backdrop-filter: blur(4px);
        }
        .view-pin-meta {
            color: #7f8c8d;
            margin: 16px 0;
            font-size: 15px;
        }
        .back-home {
            display: inline-block;
            margin-top: 24px;
            color: #c1121f;
            text-decoration: none;
            font-weight: 700;
            font-size: 17px;
            padding: 10px 20px;
            border-radius: 50px;
            background: rgba(255,255,255,0.7);
            transition: all 0.2s;
        }
        .back-home:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
 
        /* Falling Snowflakes */
        .snowflake {
            position: fixed;
            top: -20px;
            color: #fff;
            font-size: 20px;
            text-shadow: 0 0 5px rgba(255,255,255,0.9);
            animation: fall linear infinite;
            z-index: 1000;
            opacity: 0.85;
            user-select: none;
            pointer-events: none;
        }
        @keyframes fall {
            to { transform: translateY(100vh) rotate(360deg); }
        }
 
        /* === FROSTY THE SNOWMAN === */
        #frosty {
            position: fixed;
            bottom: 60px;
            right: 20px;
            width: 80px;
            height: 80px;
            z-index: 999;
            cursor: pointer;
            transition: transform 0.3s;
        }
        #frosty:hover {
            transform: translateY(-5px);
        }
        .frosty-head {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            position: relative;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .frosty-eye {
            position: absolute;
            width: 8px;
            height: 8px;
            background: black;
            border-radius: 50%;
            top: 20px;
        }
        .frosty-eye.left { left: 12px; }
        .frosty-eye.right { right: 12px; }
        .frosty-nose {
            position: absolute;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 16px solid #ff6b00;
            top: 28px;
            left: 50%;
            transform: translateX(-50%);
        }
        .frosty-smile {
            position: absolute;
            bottom: 14px;
            left: 50%;
            width: 24px;
            height: 10px;
            border-bottom: 3px solid black;
            border-radius: 0 0 50% 50%;
            transform: translateX(-50%);
            animation: smile 4s infinite;
        }
        @keyframes smile {
            0%, 100% { transform: translateX(-50%) scaleY(1); }
            50% { transform: translateX(-50%) scaleY(1.2); }
        }
 
        /* Responsive */
        @media (max-width: 768px) {
            .grid { grid-template-columns: repeat(2, 1fr); }
            .tabs { flex-wrap: wrap; }
            body::after { height: 30px; }
            #frosty { bottom: 50px; right: 15px; width: 70px; height: 70px; }
        }
        @media (max-width: 480px) {
            .grid { grid-template-columns: 1fr; }
            .logo { font-size: 24px; }
            body::after { height: 25px; }
            #frosty { bottom: 40px; right: 10px; width: 60px; height: 60px; }
        }
    </style>
</head>
<body>
 
<!-- Falling Snowflakes -->
<div class="snowflake" style="left:8%;animation-duration:12s;">❄</div>
<div class="snowflake" style="left:25%;animation-duration:9s;animation-delay:1s;">❄</div>
<div class="snowflake" style="left:45%;animation-duration:11s;animation-delay:0.5s;">❄</div>
<div class="snowflake" style="left:65%;animation-duration:10s;animation-delay:2s;">❄</div>
<div class="snowflake" style="left:85%;animation-duration:13s;animation-delay:1.5s;">❄</div>
 
<!-- Frosty the Snowman -->
<div id="frosty">
    <div class="frosty-head">
        <div class="frosty-eye left"></div>
        <div class="frosty-eye right"></div>
        <div class="frosty-nose"></div>
        <div class="frosty-smile"></div>
    </div>
</div>
 
<?php if (isset($_GET['view_pin']) && $pin): ?>
<div class="container">
    <header>
        <div class="logo">❄️ Winter Pinterest</div>
        <div class="auth-links">
            <?php if ($user): ?>
                <span><?= htmlspecialchars($user['username']) ?></span>
                <a href="?logout">Logout</a>
            <?php else: ?>
                <a href="?login=1">Log in</a>
                <a href="?signup=1">Sign up</a>
            <?php endif; ?>
        </div>
    </header>
 
    <div class="view-pin-page">
        <img src="<?= getPlaceholder($pin['category'], $pin['id']) ?>" alt="<?= htmlspecialchars($pin['title']) ?>" class="view-pin-image">
        <h1 class="view-pin-title"><?= htmlspecialchars($pin['title']) ?></h1>
        <p class="view-pin-desc"><?= nl2br(htmlspecialchars($pin['description'])) ?></p>
        <p class="view-pin-meta">
            Category: <strong><?= htmlspecialchars($pin['category']) ?></strong> • 
            By: <strong><?= htmlspecialchars($pin['username']) ?></strong>
        </p>
        <a href="?" class="back-home">← Back to Home</a>
    </div>
</div>
 
<?php else: ?>
<div class="container">
    <header>
        <div class="logo">❄️ Winter Pinterest</div>
        <div class="auth-links">
            <?php if ($user): ?>
                <span><?= htmlspecialchars($user['username']) ?></span>
                <a href="?logout">Logout</a>
            <?php else: ?>
                <a href="?login=1">Log in</a>
                <a href="?signup=1">Sign up</a>
            <?php endif; ?>
        </div>
    </header>
 
    <?php if (isset($_GET['login'])): ?>
        <div class="form-box">
            <h3 style="margin-bottom:20px;color:#c1121f;">Welcome Back! ❄️</h3>
            <?php if ($error) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login">Log in</button>
            </form>
        </div>
    <?php elseif (isset($_GET['signup'])): ?>
        <div class="form-box">
            <h3 style="margin-bottom:20px;color:#c1121f;">Join Our Winter Community! 🎄</h3>
            <?php if ($error) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="signup">Sign up</button>
            </form>
        </div>
    <?php elseif ($tab === 'create' && $user): ?>
        <div class="form-box">
            <h3 style="margin-bottom:20px;color:#c1121f;">Create a Winter Pin ❄️</h3>
            <?php if ($error) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="Fashion">Winter Fashion</option>
                        <option value="Food">Holiday Recipes</option>
                        <option value="Travel">Snowy Getaways</option>
                        <option value="Art">Festive Crafts</option>
                        <option value="DIY">Christmas DIY</option>
                    </select>
                </div>
                <button type="submit" name="create_pin">Create Pin</button>
            </form>
        </div>
    <?php elseif ($tab === 'boards' && $user): ?>
        <div class="form-box">
            <h3 style="margin-bottom:20px;color:#c1121f;">Create Your Holiday Board 🎁</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="board_name" placeholder="e.g. Christmas Decor Ideas" required>
                </div>
                <button type="submit" name="create_board">Create Board</button>
            </form>
        </div>
        <h3 style="margin:24px 0 16px;color:#c1121f;">Your Boards</h3>
        <div class="grid">
            <?php foreach ($boards as $b): ?>
                <div class="pin">
                    <div class="pin-info">
                        <div class="pin-title"><?= htmlspecialchars($b['name']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="tabs">
            <div class="tab <?= $tab==='home'?'active':'' ?>" onclick="location.href='?tab=home'">Home</div>
            <?php if ($user): ?>
                <div class="tab <?= $tab==='create'?'active':'' ?>" onclick="location.href='?tab=create'">Create Pin</div>
                <div class="tab <?= $tab==='boards'?'active':'' ?>" onclick="location.href='?tab=boards'">My Boards</div>
            <?php endif; ?>
        </div>
 
        <div class="grid">
            <?php if (empty($pins)): ?>
                <p style="grid-column:1/-1; text-align:center; padding:40px; color:#666;">No pins yet. Be the first to <a href="?tab=create" style="color:#c1121f; text-decoration:underline;">create one</a>!</p>
            <?php else: ?>
                <?php foreach ($pins as $p): ?>
                    <div class="pin" onclick="location.href='?view_pin=<?= $p['id'] ?>'">
                        <img src="<?= getPlaceholder($p['category'], $p['id']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                        <div class="pin-info">
                            <div class="pin-title"><?= htmlspecialchars($p['title']) ?></div>
                            <div class="pin-user">by <?= htmlspecialchars($p['username']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
 
<script>
// Optional: Make Frosty say hello!
document.getElementById('frosty').addEventListener('click', function() {
    alert('Ho ho ho! 🎅\nWelcome to Winter Pinterest!');
});
</script>
</body>
</html>
