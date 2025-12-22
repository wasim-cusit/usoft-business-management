<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'براہ کرم یوزرنیم اور پاس ورڈ درج کریں';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username, password, full_name, user_type, status FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 'active') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    header('Location: ' . BASE_URL . 'index.php');
                    exit;
                } else {
                    $error = getLang() == 'ur' ? 'آپ کا اکاؤنٹ غیر فعال ہے۔ براہ کرم ایڈمن سے رابطہ کریں۔' : 'Your account is inactive. Please contact admin.';
                }
            } else {
                $error = getLang() == 'ur' ? 'غلط یوزرنیم یا پاس ورڈ' : 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = getLang() == 'ur' ? 'لاگ ان ناکام ہوا۔ براہ کرم دوبارہ کوشش کریں۔' : 'Login failed. Please try again.';
        }
    }
}
?>
<?php
require_once 'config/config.php';
// Get language from session or default
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'ur';
}
$lang = $_SESSION['language'] ?? 'ur';
require_once 'config/language.php';
?>
<!DOCTYPE html>
<html lang="<?php echo getLang() == 'ur' ? 'ur' : 'en'; ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo t('login'); ?> - <?php echo t('app_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-rtl@3.4.0/dist/css/bootstrap-rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;500;600;700&family=Almarai:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: <?php echo getLang() == 'ur' ? "'Almarai', 'Noto Nastaliq Urdu', 'Arial'" : "'Inter', 'Arial'"; ?>, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: <?php echo getDir(); ?>;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .login-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        .login-header p {
            margin: 15px 0 0 0;
            opacity: 0.95;
            font-size: 18px;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            padding: 15px 20px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            font-size: 16px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            width: 100%;
            font-size: 18px;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .input-group .form-control {
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-store"></i> <?php echo t('app_name'); ?></h1>
            <p><?php echo t('business_management_system'); ?></p>
            <div class="mt-3">
                <a href="?lang=ur" class="btn btn-sm btn-light me-2 <?php echo getLang() == 'ur' ? 'active' : ''; ?>">اردو</a>
                <a href="?lang=en" class="btn btn-sm btn-light <?php echo getLang() == 'en' ? 'active' : ''; ?>">English</a>
            </div>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label"><?php echo t('username'); ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="username" required autofocus placeholder="<?php echo t('enter_username'); ?>">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label"><?php echo t('password'); ?></label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" required placeholder="<?php echo t('enter_password'); ?>">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> <?php echo t('login'); ?>
                </button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
