<?php include('server.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SHUL - Admin</title>

    <link rel="shortcut icon" type="image/png" href="image/logo.png">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(
                135deg,
                #2858d3 0%,
                #5265df 45%,
                #793cf0 100%
            );
            min-height: 100vh;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 20px;
        }

        /* Logo */
        .logo-area {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-circle {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #fff;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 6px rgba(255,255,255,0.13);
            overflow: hidden;
        }

        .logo-circle img {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }

        .company-name {
            color: #fff;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        /* Login Card */
        .login-card {
            width: 100%;
            max-width: 430px;
            background: #fff;
            border-radius: 19px;
            padding: 31px 30px 30px;
            box-shadow: 0 15px 35px rgba(27, 35, 100, 0.15);
        }

        .welcome {
            text-align: center;
            margin-bottom: 22px;
        }

        .welcome h2 {
            color: #10294a;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .welcome p {
            color: #63718a;
            font-size: 12px;
            font-weight: 400;
        }

        /* Error */
        .errors {
            background: #fff0f0;
            color: #d93025;
            border: 1px solid #ffd1d1;
            border-radius: 7px;
            padding: 9px 12px;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: #152c4b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .input-box {
            position: relative;
        }

        .input-box input {
            width: 100%;
            height: 45px;
            border: 1px solid #ccd5e3;
            border-radius: 9px;
            background: #f8fafc;
            padding: 0 14px;
            color: #26364d;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
            transition: all 0.2s ease;
        }

        .input-box input::placeholder {
            color: #8793a5;
        }

        .input-box input:focus {
            background: #fff;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.10);
        }

        .password-input {
            padding-right: 45px !important;
        }

        .password-icon {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 17px;
            cursor: pointer;
            opacity: 0.8;
        }

        /* Button */
        .login-btn {
            width: 100%;
            height: 46px;
            border: none;
            border-radius: 9px;
            background: #6364ed;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(99, 100, 237, 0.25);
            transition: all 0.2s ease;
            margin-top: 1px;
        }

        .login-btn:hover {
            background: #5556df;
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(99, 100, 237, 0.32);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Footer */
        .footer {
            color: rgba(255,255,255,0.92);
            font-size: 11px;
            text-align: center;
            margin-top: 20px;
        }

        /* Mobile */
        @media (max-width: 500px) {

            .login-page {
                padding: 20px 15px;
            }

            .logo-circle {
                width: 68px;
                height: 68px;
            }

            .logo-circle img {
                width: 90px;
                height: 90px;
            }

            .company-name {
                font-size: 21px;
            }

            .login-card {
                max-width: 100%;
                padding: 27px 22px 25px;
                border-radius: 17px;
            }

            .welcome h2 {
                font-size: 20px;
            }

            .footer {
                margin-top: 16px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <!-- Logo + Company Name -->
    <div class="logo-area">

        <div class="logo-circle">
            <img src="image/logo.png" alt="SHUL Ventures">
        </div>

        <div class="company-name">
            SHUL VENTURES
        </div>

    </div>


    <!-- Login Card -->
    <div class="login-card">

        <div class="welcome">
            <h2>Welcome Back! 👋</h2>
            <p>Sign in to your admin account</p>
        </div>


        <!-- Existing PHP Errors -->
        <?php if (!empty($errors)) { ?>
            <div class="errors">
                <?php include('errors.php'); ?>
            </div>
        <?php } ?>


        <form action="login.php" method="post">

            <!-- Email -->
            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <div class="input-box">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter email address"
                        autocomplete="email"
                        required
                    >
                </div>

            </div>


            <!-- Password -->
            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="input-box">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter password"
                        class="password-input"
                        autocomplete="current-password"
                        required
                    >

                    <span
                        class="password-icon"
                        onclick="togglePassword()"
                        id="passwordIcon"
                    >🙈</span>

                </div>

            </div>


            <!-- Login Button -->
            <button
                type="submit"
                class="login-btn"
                name="login_user"
            >
                🚀 &nbsp;Sign In
            </button>

        </form>

    </div>


    <!-- Footer -->
    <div class="footer">
        © 2026 Shul Ventures Pvt. Ltd. · All rights reserved
    </div>

</div>


<script>
function togglePassword() {

    const password = document.getElementById("password");
    const icon = document.getElementById("passwordIcon");

    if (password.type === "password") {
        password.type = "text";
        icon.textContent = "👁️";
    } else {
        password.type = "password";
        icon.textContent = "🙈";
    }
}
</script>

</body>
</html>
