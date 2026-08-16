<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resubmit Verification Documents - AgroLink</title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/pendingVerification.css">
    <style>
        .pending-card-banner.rejected {
            background: var(--danger);
        }

        .alert-error {
            background: #fff5f5;
            border: 1px solid #fecaca;
            color: #7a271a;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .upload-box {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 12px;
        }

        .upload-box label {
            display: block;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .upload-hint {
            font-size: 12px;
            color: #777;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .field-error {
            font-size: 12px;
            color: #b42318;
            margin-top: 6px;
        }

        .badge-req {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            background: #fff5f5;
            border: 1px solid #fecaca;
            color: #b42318;
            margin-left: 8px;
        }
    </style>
</head>

<body>
    <?php
    $username = $_SESSION['USER']->name ?? 'User';
    $resolvedRole = $role ?? ($_SESSION['USER']->role ?? '');
    $rejections = $rejections ?? [];
    $errors = $errors ?? [];
    include '../app/views/shared/topNavBar.view.php';
    ?>

    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3 class="sidebar-title">My Account</h3>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= ROOT ?>/verification/status" class="menu-link">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 18l-6-6 6-6" />
                            </svg>
                        </div>
                        Back to status
                    </a>
                </li>
                <li>
                    <a href="<?= ROOT ?>/logout" class="menu-link">
                        <div class="menu-icon">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                        </div>
                        Log Out
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-section">
                <div class="content-header">
                    <h1 class="content-title">Resubmit verification documents</h1>
                    <p class="content-subtitle">
                        Upload updated documents to restart the admin review.
                    </p>
                </div>

                <?php if (!empty($errors['upload'])): ?>
                    <div class="alert-error">
                        <?= esc((string)$errors['upload']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($rejections)): ?>
                    <div class="alert-error">
                        <div style="font-weight:700; margin-bottom:6px;">Reason(s) provided</div>
                        <ul style="margin:0; padding-left:18px;">
                            <?php foreach ($rejections as $rej): ?>
                                <li style="margin:4px 0;">
                                    <strong><?= esc(str_replace('_', ' ', (string)($rej->doc_type ?? 'document'))) ?>:</strong>
                                    <?= esc((string)($rej->rejection_reason ?? 'No reason provided')) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="pending-card">
                    <div class="pending-card-banner rejected"></div>
                    <div class="pending-card-body">
                        <form action="<?= ROOT ?>/verification/resubmit" method="POST" enctype="multipart/form-data">
                            <?php if ($resolvedRole === 'farmer'): ?>
                                <div class="upload-box">
                                    <label>National Identity Card (NIC) <span class="badge-req">Required</span></label>
                                    <div class="upload-hint">Upload a clear photo or scan — front side.</div>
                                    <input type="file" name="nic" accept="image/jpeg,image/png,image/webp,application/pdf" />
                                    <?php if (!empty($errors['nic'])): ?><div class="field-error"><?= esc((string)$errors['nic']) ?></div><?php endif; ?>
                                </div>

                                <div class="upload-box">
                                    <label>Bank account details <span class="badge-req">Required</span></label>
                                    <div class="upload-hint">Bank statement or passbook page showing your name and account number.</div>
                                    <input type="file" name="bank_details" accept="image/jpeg,image/png,image/webp,application/pdf" />
                                    <?php if (!empty($errors['bank_details'])): ?><div class="field-error"><?= esc((string)$errors['bank_details']) ?></div><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="upload-box">
                                    <label>Driving license <span class="badge-req">Required</span></label>
                                    <div class="upload-hint">Both sides preferred.</div>
                                    <input type="file" name="driving_license" accept="image/jpeg,image/png,image/webp,application/pdf" />
                                    <?php if (!empty($errors['driving_license'])): ?><div class="field-error"><?= esc((string)$errors['driving_license']) ?></div><?php endif; ?>
                                </div>

                                <div class="upload-box">
                                    <label>Vehicle insurance card <span class="badge-req">Required</span></label>
                                    <div class="upload-hint">Current valid insurance document.</div>
                                    <input type="file" name="vehicle_insurance" accept="image/jpeg,image/png,image/webp,application/pdf" />
                                    <?php if (!empty($errors['vehicle_insurance'])): ?><div class="field-error"><?= esc((string)$errors['vehicle_insurance']) ?></div><?php endif; ?>
                                </div>

                                <div class="upload-box">
                                    <label>Vehicle revenue license <span class="badge-req">Required</span></label>
                                    <div class="upload-hint">Current year revenue license.</div>
                                    <input type="file" name="vehicle_revenue_license" accept="image/jpeg,image/png,image/webp,application/pdf" />
                                    <?php if (!empty($errors['vehicle_revenue_license'])): ?><div class="field-error"><?= esc((string)$errors['vehicle_revenue_license']) ?></div><?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:18px;">
                                <a href="<?= ROOT ?>/verification/status" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Submit documents</button>
                            </div>
                        </form>

                        <div style="margin-top:16px; font-size:12px; color:#777;">
                            Need help? Contact <strong>support@agrolink.lk</strong> and quote your registered email address.
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= ROOT ?>/assets/js/main.js"></script>
</body>

</html>
