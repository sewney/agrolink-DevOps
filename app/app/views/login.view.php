<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - AgroLink</title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/style1.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/components.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet" />
</head>

<body>
    <div id="floatingAlerts" style="position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;"></div>
    <div class="split-container">
        <!-- Left: Quote & Image -->
        <div
            class="split-left"
            style="
                background: url('<?= ROOT ?>/assets/imgs/registerpage/register4.jpg') center
                center/cover no-repeat;
            ">
            <span class="quote-icon">&ldquo;</span>
            <div class="split-left-content">
                <p>
                    Welcome back to AgroLink.<br />
                    Your gateway to Sri Lanka's agricultural marketplace.
                </p>
            </div>
        </div>

        <!-- Right: Login Form -->
        <div class="split-right">
            <div class="form-box">
                <?php if (!empty($errors)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showFloatingAlert('<?= implode("<br>", $errors) ?>', 'error');
                        });
                    </script>
                <?php endif ?>

                <h1>Welcome Back</h1>
                <div class="subtitle">
                    Sign in to access your dashboard
                </div>

                <form id="loginForm" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            required
                            placeholder="you@example.com" />
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                            placeholder="********" />
                    </div>

                    <div style="text-align: center;">
                        <button type="submit" class="btn btn-primary btn-large">
                            Sign In
                        </button>
                    </div>
                </form>

                <div class="text-center" style="margin-top: 1.5rem;">
                    Don't have an account?
                    <a href="<?= ROOT ?>/register">Register here</a>
                </div>

                <div class="text-center" style="margin-top: 0.5rem;">
                    <a href="mailto:support@agrolink.lk?subject=AgroLink%20Password%20Reset%20Request" class="text-muted">
                        Forgot your password? Contact support@agrolink.lk
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.APP_ROOT = "<?= ROOT ?>";
    </script>
    <script src="<?= ROOT ?>/assets/js/main.js"></script>
    <script>
        // Show success toast if redirected with ?registered=1 (no server flash needed)
        (function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('registered') === '1') {
                const msg = 'Registration successful!';
                document.addEventListener('DOMContentLoaded', function() {
                    showFloatingAlert(msg, 'success');
                });
                if (typeof showNotification === 'function') {
                    /* showNotification(msg, 'success'); */
                } else {
                    // simple fallback alert box if notification util isn't yet available
                    var box = document.createElement('div');
                    box.className = 'alert';
                    box.textContent = msg;
                    var formBox = document.querySelector('.form-box');
                    if (formBox) formBox.insertBefore(box, formBox.firstChild);
                }
                // Clean URL to avoid re-trigger on refresh
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('registered');
                    window.history.replaceState({}, document.title, url.toString());
                } catch (e) {
                    /* no-op */
                }
            }
        })();
    </script>
    <!-- Debug logging for login submit (does not prevent submit) -->
    <script>
        (function() {
            const form = document.getElementById('loginForm');
            if (!form) {
                console.warn('[LoginForm] not found on page');
                return;
            }
            console.log('[LoginForm] script attached', {
                method: form.method,
                action: form.action || 'current URL',
                time: new Date().toISOString()
            });
            form.addEventListener('submit', function() {
                try {
                    const fd = new FormData(form);
                    const email = fd.get('email');
                    const password = fd.get('password');
                    const emailInput = document.getElementById('email');
                    const passwordInput = document.getElementById('password');
                    console.log('[LoginForm] submit event', {
                        hasEmail: !!email,
                        hasPassword: !!password,
                        emailValue: email,
                        passwordLength: password ? String(password).length : 0,
                        rawEmailInput: emailInput ? emailInput.value : null,
                        rawPasswordLength: passwordInput ? String(passwordInput.value).length : null,
                        method: form.method,
                        action: form.action || window.location.href,
                        userAgent: navigator.userAgent,
                        time: new Date().toISOString()
                    });
                } catch (err) {
                    console.error('[LoginForm] error reading form data', err);
                }
            });
        })();
    </script>
</body>

</html>
