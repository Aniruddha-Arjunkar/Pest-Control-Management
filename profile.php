<?php
/* =========================================================
   MY PROFILE PAGE
========================================================= */

include_once __DIR__ . '/header.php';

/* ---------------------------
   SESSION USER DATA
---------------------------- */
$profile_name   = $_SESSION['name']   ?? '';
$profile_email  = $_SESSION['email']  ?? '';
$profile_role   = $_SESSION['role']   ?? '';
$profile_branch = $_SESSION['branch'] ?? '';

if ($profile_name === '' && $profile_email !== '') {
    $profile_name = ucfirst(strtok($profile_email, '@'));
}

if ($profile_name === '') {
    $profile_name = 'Admin';
}

$profile_avatar_letter = strtoupper(substr($profile_name, 0, 1));

/* ---------------------------
   EXTRA DETAILS FROM DB
   (optional — only if 'users' table
   has these columns; safe fallback
   to '-' if query/columns don't exist)
---------------------------- */
$profile_phone      = '-';
$profile_created_at = '-';

if (isset($conn) && $conn instanceof mysqli && $profile_email !== '') {

    $email_safe = mysqli_real_escape_string($conn, $profile_email);

    $user_result = @mysqli_query(
        $conn,
        "SELECT * FROM users WHERE email = '$email_safe' LIMIT 1"
    );

    if ($user_result) {

        $user_row = mysqli_fetch_assoc($user_result);

        if ($user_row) {

            if (!empty($user_row['phone'])) {
                $profile_phone = htmlspecialchars($user_row['phone']);
            }

            if (!empty($user_row['created_at'])) {
                $profile_created_at = date(
                    'd M Y',
                    strtotime($user_row['created_at'])
                );
            }
        }
    }
}

/* ---------------------------
   INFO CARDS DATA
---------------------------- */
$profile_info = [
    ['label' => 'Full Name',      'value' => $profile_name],
    ['label' => 'Email Address',  'value' => $profile_email],
    ['label' => 'Role',           'value' => $profile_role ?: '-'],
    ['label' => 'Branch',         'value' => $profile_branch ?: '-'],
    ['label' => 'Phone',          'value' => $profile_phone],
    ['label' => 'Member Since',   'value' => $profile_created_at],
    ['label' => 'Account Status', 'value' => 'Active'],
];
?>

<div style="padding:24px;">

    <!-- HEADER -->
    <div style="margin-bottom:20px;">
        <h1 style="margin:0;font-size:24px;color:#1e293b;">
            👤 My Profile
        </h1>
        <p style="margin:6px 0 0;color:#64748b;font-size:14px;">
            View your account details and information
        </p>
    </div>

    <!-- PROFILE HERO -->
    <div style="background:#fff;border-radius:16px;padding:24px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);">

        <div style="background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:16px;padding:30px;display:flex;align-items:center;gap:25px;color:#fff;flex-wrap:wrap;">

            <div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;font-size:38px;font-weight:700;border:4px solid rgba(255,255,255,.4);">
                <?= htmlspecialchars($profile_avatar_letter) ?>
            </div>

            <div>
                <h2 style="margin:0;font-size:26px;">
                    <?= htmlspecialchars($profile_name) ?>
                </h2>

                <p style="margin:8px 0;opacity:0.85;">
                    <?= htmlspecialchars($profile_email) ?>
                </p>

                <div style="display:flex;gap:10px;flex-wrap:wrap;">

                    <span style="background:rgba(255,255,255,.25);padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;">
                        🔐 <?= htmlspecialchars($profile_role ?: 'Admin') ?>
                    </span>

                    <?php if ($profile_branch !== ''): ?>
                    <span style="background:rgba(255,255,255,.25);padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;">
                        📍 <?= htmlspecialchars($profile_branch) ?>
                    </span>
                    <?php endif; ?>

                </div>
            </div>

        </div>

    </div>

    <!-- INFO CARDS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">

        <?php foreach ($profile_info as $item): ?>

            <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);">

                <div style="font-size:12px;color:#64748b;font-weight:600;margin-bottom:8px;text-transform:uppercase;">
                    <?= htmlspecialchars($item['label']) ?>
                </div>

                <div style="font-size:18px;font-weight:700;color:#1e293b;">

                    <?php if ($item['label'] === 'Account Status'): ?>

                        <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:12px;font-size:13px;">
                            ✅ <?= htmlspecialchars($item['value']) ?>
                        </span>

                    <?php elseif ($item['label'] === 'Role'): ?>

                        <span style="background:#e0e7ff;color:#4338ca;padding:3px 10px;border-radius:12px;font-size:13px;">
                            <?= htmlspecialchars($item['value']) ?>
                        </span>

                    <?php else: ?>

                        <?= htmlspecialchars($item['value']) ?>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<?php
/* =========================================================
   CLOSE LAYOUT (matches header.php's opened tags)
========================================================= */
?>
</div><!-- /#shul-main-content -->
</div><!-- /#shul-app -->
</body>
</html>