<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'يرجى تسجيل الدخول أولاً.']);
        exit();
    }
    header("Location: login.html?auth_required=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $pic_path = null;

    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'يرجى ملء جميع الحقول المطلوبة.']);
            exit();
        }
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'البريد الإلكتروني غير صالح.']);
            exit();
        }
    } else {
        // فحص ورفع الصورة
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $tmp = $_FILES['profile_picture']['tmp_name'];
            $size = $_FILES['profile_picture']['size'];
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            // فحص النوع الفعلي للصورة
            $img_info = @getimagesize($tmp);
            $real_mime = $img_info ? $img_info['mime'] : '';

            if ($size > 2 * 1024 * 1024) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => 'حجم الصورة يجب أن يكون أقل من 2MB.']);
                    exit();
                }
            } elseif (!in_array($ext, $allowed_exts) || !in_array($real_mime, $allowed_mimes)) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => 'الملف المرفوع ليس صورة صالحة.']);
                    exit();
                }
            } else {
                $new_name = 'uploads/' . uniqid('img_') . '.' . $ext;
                if (move_uploaded_file($tmp, $new_name)) {
                    $pic_path = $new_name;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO students (name, email, phone, address, profile_picture) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $address, $pic_path]);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'تمت إضافة الطالب بنجاح!']);
            exit();
        } else {
            header("Location: view_students.php?added=1");
            exit();
        }
    }
}

$adminName = isset($_SESSION['username']) ? $_SESSION['username'] : 'مدير النظام';
$firstLetter = mb_substr($adminName, 0, 1, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة طالب - طلابي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="protected-page">

    <div class="page-wrapper">
        <aside class="sidebar">
            <div class="logo-area">
                <div class="logo-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="logo-text">
                    <h4 data-i18n="app_name">طلابي</h4>
                    <span data-i18n="app_subtitle">نظام إدارة الطلاب</span>
                </div>
            </div>

            <div class="menu-label" data-i18n="main_menu">القائمة الرئيسية</div>
            <nav class="side-menu">
                <a href="index.html" class="side-link">
                    <i class="bi bi-grid"></i>
                    <span data-i18n="nav_dashboard">لوحة التحكم</span>
                </a>
                <a href="view_students.php" class="side-link">
                    <i class="bi bi-people"></i>
                    <span data-i18n="nav_students">الطلاب</span>
                </a>
                <a href="add_student.php" class="side-link active">
                    <i class="bi bi-person-plus"></i>
                    <span data-i18n="nav_add_student">إضافة طالب</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="admin-box">
                    <div class="admin-info">
                        <div class="admin-circle" id="currentUserAvatar"><?= htmlspecialchars($firstLetter) ?></div>
                        <div class="admin-name">
                            <h6 id="currentUserName"><?= htmlspecialchars($adminName) ?></h6>
                            <small data-i18n="role_admin">مدير النظام</small>
                        </div>
                    </div>
                    <a href="logout.php" id="logoutBtn" class="logout-btn" title="تسجيل الخروج">
                        <i class="bi bi-box-arrow-left"></i>
                    </a>
                </div>
            </div>
        </aside>

        <main class="content-area">
            <header class="top-bar">
                <button type="button" id="themeToggleBtn" class="top-btn">
                    <i class="bi bi-sun-fill text-warning" id="themeIcon"></i>
                    <span id="themeText" data-i18n="theme_light">الوضع النهاري</span>
                </button>
                <button type="button" id="langToggleBtn" class="top-btn">
                    <img src="https://flagcdn.com/w40/us.png" alt="Flag" class="flag-icon" id="langFlag">
                    <span id="langText">English</span>
                </button>
            </header>

            <div class="inner-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-1" data-i18n="nav_add_student">إضافة طالب جديد</h3>
                        <p class="text-secondary mb-0" data-i18n="qa_add_desc">إدخال بيانات الطالب مع التحقق الفوري عبر AJAX</p>
                    </div>
                    <a href="view_students.php" class="btn btn-outline-secondary" data-i18n="nav_students">الطلاب</a>
                </div>

                <div class="my-card">
                    <div id="ajaxAlert" class="alert d-none" role="alert"></div>

                    <form id="addStudentForm" action="add_student.php" method="POST" enctype="multipart/form-data" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label" data-i18n="lbl_std_name">اسم الطالب الكامل</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="أدخل اسم الطالب">
                                <div class="invalid-feedback">حقل الاسم مطلوب.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label" data-i18n="lbl_email">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="student@svuonline.org">
                                <div class="invalid-feedback">يرجى إدخال بريد إلكتروني صحيح.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label" data-i18n="lbl_phone">رقم الهاتف</label>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="09xxxxxxxx">
                                <div class="invalid-feedback">يرجى إدخال رقم هاتف صحيح.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="profile_picture" class="form-label" data-i18n="lbl_pic">الصورة الشخصية</label>
                                <input class="form-control" type="file" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label" data-i18n="lbl_address">العنوان بالتفصيل</label>
                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="المحافظة - المنطقة - الشارع"></textarea>
                            <div class="invalid-feedback">حقل العنوان مطلوب.</div>
                        </div>

                        <button type="submit" class="btn btn-gold px-5 py-2">
                            <i class="bi bi-check2-circle me-1"></i> <span data-i18n="btn_save">حفظ بيانات الطالب</span>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="js/script.js"></script>
</body>
</html>