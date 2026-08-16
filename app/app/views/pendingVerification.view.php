<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification - AgroLink</title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/pendingVerification.css">
    <style>
        .pending-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e8e8e8;
            overflow: hidden;
        }

        .pending-card-banner {
            height: 6px;
            background: var(--primary-color);
        }

        .pending-card-body {
            padding: 2rem;
        }

        .pending-user-strip {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .pending-avatar {
            width: 52px;
            height: 52px;
            background: #e8f8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pending-avatar svg {
            width: 28px;
            height: 28px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 1.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .badge-pending-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff9e6;
            color: #b7791f;
            border: 1px solid #f6d860;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            margin-top: 6px;
        }

        .badge-rejected-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff5f5;
            color: #b42318;
            border: 1px solid #fecaca;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            margin-top: 6px;
        }

        .badge-pulse {
            width: 7px;
            height: 7px;
            background: #f39c12;
            border-radius: 50%;
            animation: badgePulse 1.8s ease-in-out infinite;
        }

        .pending-card-banner.rejected {
            background: var(--danger);
        }

        @keyframes badgePulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.3;
                transform: scale(0.8);
            }
        }

        .pending-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 1.5rem 0;
        }

        .pending-step {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 14px;
            border: 1px solid #eee;
        }

        .pending-step.active {
            background: #fffdf0;
            border-color: #f6d860;
        }

        .pending-step.rejected {
            background: #fff5f5;
            border-color: #fecaca;
        }

        .pending-step.locked {
            opacity: 0.45;
        }

        .step-num-label {
            font-size: 12px;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        .step-icon-box {
            width: 32px;
            height: 32px;
            background: #e8f8f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .pending-step.active .step-icon-box {
            background: #fff3cd;
        }

        .pending-step.rejected .step-icon-box {
            background: #fee2e2;
        }
        .step-icon-box svg {
            width: 16px;
            height: 16px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .pending-step.active .step-icon-box svg {
            stroke: #d4851a;
        }

        .pending-step.rejected .step-icon-box svg {
            stroke: #b42318;
        }

        .pending-reject-box {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 1.5rem 0;
        }

        .pending-reject-box h3 {
            font-size: 13px;
            margin: 0 0 8px 0;
            color: #7a271a;
        }

        .pending-reject-box ul {
            margin: 0;
            padding-left: 18px;
        }

        .pending-reject-box li {
            font-size: 13px;
            color: #7a271a;
            line-height: 1.6;
            margin: 4px 0;
        }
        .step-title {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .step-desc {
            font-size: 12px;
            color: #999;
            line-height: 1.5;
        }

        .pending-info-box {
            background: #f0faf5;
            border: 1px solid #a8e6c8;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin: 1.5rem 0;
        }

        .pending-info-box svg {
            width: 16px;
            height: 16px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .pending-info-box p {
            font-size: 13px;
            color: #1a6e45;
            line-height: 1.6;
            margin: 0;
        }

        .pending-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 1.5rem;
        }

        .pending-help {
            font-size: 12px;
            color: #aaa;
        }

        .pending-help a {
            color: var(--primary-color);
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .pending-steps {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php
    $username = $_SESSION['USER']->name ?? 'User';
    $role     = $_SESSION['USER']->role ?? '';
    $verificationStatus = $verification_status ?? ($_SESSION['verification_status'] ?? 'pending');
    $isRejected = $verificationStatus === 'rejected';
    $rejections = $rejections ?? [];
    include '../app/views/shared/topNavBar.view.php';
    ?>

    <div class="dashboard">
        <!-- Minimal sidebar so layout matches the rest of the app -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3 class="sidebar-title">My Account</h3>
            </div>
            <ul class="sidebar-menu">
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
                    <h1 class="content-title">Account Verification</h1>
                    <p class="content-subtitle">
                        <?php if ($isRejected): ?>
                            Your verification was rejected. Please resubmit your documents to continue.
                        <?php else: ?>
                            Your account is currently under review by our admin team.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="pending-card">
                    <div class="pending-card-banner <?= $isRejected ? 'rejected' : '' ?>"></div>
                    <div class="pending-card-body">

                        <!-- User identity strip -->
                        <div class="pending-user-strip">
                            <div class="pending-avatar">
                                <svg viewBox="0 0 24 24">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 15px; color: #2c3e50;">
                                    <?= esc($username) ?>
                                </div>
                                <div style="font-size: 12px; color: #999;">
                                    <?= esc($_SESSION['USER']->email ?? '') ?>
                                </div>
                                <?php if ($isRejected): ?>
                                    <div class="badge-rejected-status">
                                        Rejected
                                    </div>
                                <?php else: ?>
                                    <div class="badge-pending-status">
                                        <div class="badge-pulse"></div>
                                        Pending verification
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($isRejected): ?>
                            <p style="font-size: 14px; color: #7f8c8d; line-height: 1.7; margin-bottom: 1rem;">
                                Unfortunately, your verification request was rejected by an admin.
                                Please resubmit the required documents to restart the review process.
                            </p>

                            <?php if (!empty($rejections)): ?>
                                <div class="pending-reject-box">
                                    <h3>Reason(s) provided</h3>
                                    <ul>
                                        <?php foreach ($rejections as $rej): ?>
                                            <li>
                                                <strong><?= esc(str_replace('_', ' ', (string)($rej->doc_type ?? 'document'))) ?>:</strong>
                                                <?= esc((string)($rej->rejection_reason ?? 'No reason provided')) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="font-size: 14px; color: #7f8c8d; line-height: 1.7; margin-bottom: 1.5rem;">
                                Your documents have been submitted and are currently under review.
                                You'll have full access to your
                                <?= esc(ucfirst($role)) ?> dashboard once an admin verifies your account.
                            </p>
                        <?php endif; ?>

                        <!-- Progress steps -->
                        <div class="pending-steps">
                            <div class="pending-step">
                                <div class="step-num-label">Step 1</div>
                                <div class="step-icon-box">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                    </svg>
                                </div>
                                <div class="step-title">Documents submitted</div>
                                <div class="step-desc">Your documents have been received successfully.</div>
                            </div>

                            <?php if ($isRejected): ?>
                                <div class="pending-step rejected">
                                    <div class="step-num-label">Step 2</div>
                                    <div class="step-icon-box">
                                        <svg viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" />
                                            <line x1="15" y1="9" x2="9" y2="15" />
                                            <line x1="9" y1="9" x2="15" y2="15" />
                                        </svg>
                                    </div>
                                    <div class="step-title">Rejected</div>
                                    <div class="step-desc">An admin rejected your verification. Please update and resubmit your documents.</div>
                                </div>

                                <div class="pending-step active">
                                    <div class="step-num-label">Step 3</div>
                                    <div class="step-icon-box">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="7 10 12 15 17 10" />
                                            <line x1="12" y1="15" x2="12" y2="3" />
                                        </svg>
                                    </div>
                                    <div class="step-title">Resubmit documents</div>
                                    <div class="step-desc">Upload updated documents and wait for a new review.</div>
                                </div>
                            <?php endif; ?>

                            <?php if (!$isRejected): ?>
                                <div class="pending-step active">
                                <div class="step-num-label">Step 2</div>
                                <div class="step-icon-box">
                                    <svg viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8" />
                                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                    </svg>
                                </div>
                                <div class="step-title">Admin review</div>
                                <div class="step-desc">An admin is reviewing your documents. This typically takes 1–2 business days.</div>
                            </div>

                            <div class="pending-step locked">
                                <div class="step-num-label">Step 3</div>
                                <div class="step-icon-box">
                                    <svg viewBox="0 0 24 24">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                </div>
                                <div class="step-title">Access granted</div>
                                <div class="step-desc">Once verified, you'll have full access to all platform features.</div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info box -->
                        <div class="pending-info-box" style="<?= $isRejected ? 'background:#fff5f5;border-color:#fecaca;' : '' ?>">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                            <p>
                                <?php if ($isRejected): ?>
                                    To continue, resubmit your documents. If you need help, contact us at
                                    <strong>support@agrolink.lk</strong> and quote your registered email address.
                                <?php else: ?>
                                    Need to update your documents or have a question? Contact us at
                                    <strong>support@agrolink.lk</strong> and quote your registered email address.
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="pending-actions">
                            <div>
                                <?php if ($isRejected): ?>
                                    <a href="<?= ROOT ?>/verification/resubmit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; margin-right: 10px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="7 10 12 15 17 10" />
                                            <line x1="12" y1="15" x2="12" y2="3" />
                                        </svg>
                                        Resubmit documents
                                    </a>
                                <?php endif; ?>
                                <a href="<?= ROOT ?>/logout" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <polyline points="16 17 21 12 16 7" />
                                        <line x1="21" y1="12" x2="9" y2="12" />
                                    </svg>
                                    Log out
                                </a>
                            </div>
                            <div class="pending-help">
                                Wrong account? <a href="<?= ROOT ?>/login">Switch account</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= ROOT ?>/assets/js/main.js"></script>
</body>

</html>
