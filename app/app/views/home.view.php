<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroLink - Farm to Market</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/style1.css">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/components.css">
    <style>
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-50:  #EAF3DE;
            --green-100: #C0DD97;
            --green-200: #97C459;
            --green-400: #639922;
            --green-600: #3B6D11;
            --green-800: #27500A;
            --green-900: #173404;

            --white: #ffffff;
            --gray-50: #F7F6F2;
            --gray-100: #EDEDEA;
            --gray-300: #C8C7C0;
            --gray-500: #888780;
            --gray-700: #444441;
            --gray-900: #1A1A18;

            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-xl: 24px;

            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.10);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.12);

            --font-display: 'Fraunces', Georgia, serif;
            --font-body: 'DM Sans', system-ui, sans-serif;

            --transition: 0.2s cubic-bezier(0.4,0,0.2,1);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-body);
            color: var(--gray-900);
            background: #fafaf7;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; color: inherit; }
        img { display: block; max-width: 100%; }

        /* ── NAVBAR ─────────────────────────────────── */
        .topnav {
            position: sticky;
            top: 0;
            z-index: 200;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--gray-100);
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 2rem;
        }

        .topnav-inner {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topnav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 600;
            color: var(--green-900);
            letter-spacing: -0.3px;
        }

        .topnav-logo-icon {
            width: 34px; height: 34px;
            background: var(--green-600);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        .topnav-logo-icon svg {
            width: 18px; height: 18px;
            fill: none; stroke: #fff;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }

        .topnav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .topnav-link {
            padding: 6px 14px;
            font-size: 14px;
            font-weight: 400;
            color: var(--gray-700);
            border-radius: var(--radius-sm);
            transition: background var(--transition), color var(--transition);
        }

        .topnav-link:hover {
            background: var(--gray-50);
            color: var(--gray-900);
        }

        .topnav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: var(--font-body);
            font-size: 16px;
            font-weight: 500;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition);
            white-space: nowrap;
        }

        .btn-ghost {
            padding: 8px 18px;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
            background: transparent;
        }
        .btn-ghost:hover { background: var(--gray-50); border-color: var(--gray-500); }

        .btn-primary {
            padding: 8px 20px;
            background: var(--green-600);
            color: #fff;
            border: 1px solid transparent;
        }
        .btn-primary:hover { background: var(--green-800); }

        .btn-white {
            padding: 12px 28px;
            background: #fff;
            color: var(--green-800);
            border: 1px solid transparent;
            font-size: 15px;
        }
        .btn-white:hover { background: var(--green-50); }

        .btn-outline-white {
            padding: 12px 28px;
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.45);
            font-size: 15px;
        }
        .btn-outline-white:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.7); }

        /* ── HERO ───────────────────────────────────── */
        .hero {
            background: var(--green-600);
            padding: 88px 2rem 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-grid-pattern {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        .hero-glow {
            position: absolute;
            top: -60px; left: 50%;
            transform: translateX(-50%);
            width: 700px; height: 400px;
            background: radial-gradient(ellipse at center, rgba(255,255,255,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            max-width: 720px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 100px;
            padding: 6px 16px;
            font-size: 12px;
            color: var(--green-100);
            margin-bottom: 28px;
            letter-spacing: 0.5px;
        }

        .hero-badge-dot {
            width: 6px; height: 6px;
            background: var(--green-200);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.85); }
        }

        .hero h1 {
            font-family: var(--font-display);
            font-size: clamp(40px, 6vw, 60px);
            font-weight: 600;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .hero h1 em {
            font-style: italic;
            color: var(--green-200);
        }

        .hero-sub {
            font-size: 17px;
            color: rgba(255,255,255,0.65);
            max-width: 500px;
            margin: 0 auto 36px;
            font-weight: 300;
            line-height: 1.7;
        }

        .hero-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 56px;
        }

        .hero-btns .btn {
            font-size: 18px;  
        }

        .hero-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 36px;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 0;
        }

        .hero-stat {
            padding: 0 40px;
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        .hero-stat:last-child { border-right: none; }

        .hero-stat-num {
            font-family: var(--font-display);
            font-size: 30px;
            font-weight: 600;
            color: var(--green-200);
            line-height: 1.1;
        }

        .hero-stat-label {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            margin-top: 4px;
        }

        /* ── SECTIONS ───────────────────────────────── */
        .section {
            padding: 72px 2rem;
        }

        .section-inner {
            max-width: 1120px;
            margin: 0 auto;
        }

        .section-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--green-600);
            margin-bottom: 10px;
        }

        .section-title {
            font-family: var(--font-display);
            font-size: clamp(26px, 3.5vw, 36px);
            font-weight: 500;
            color: var(--gray-900);
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }

        .section-sub {
            font-size: 16px;
            color: var(--gray-500);
            max-width: 500px;
            line-height: 1.65;
            font-weight: 300;
            margin-bottom: 48px;
        }

        .section-header { text-align: center; }
        .section-header .section-sub { margin-left: auto; margin-right: auto; }

        .bg-light { background: #d8e8c8; }
        .bg-dark { background: var(--green-900); }

        /* ── HOW IT WORKS ───────────────────────────── */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--gray-100);
            border: 1px solid var(--gray-100);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .step-card {
            background: var(--white);
            padding: 36px 32px;
            position: relative;
        }

        .step-num {
            font-family: var(--font-display);
            font-size: 56px;
            font-weight: 600;
            color: var(--green-200);
            line-height: 1;
            margin-bottom: 20px;
            user-select: none;
        }

        .step-dot {
            width: 8px; height: 8px;
            background: var(--green-400);
            border-radius: 50%;
            margin-bottom: 14px;
        }

        .step-card h3 {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--gray-900);
        }

        .step-card p {
            font-size: 14px;
            color: var(--gray-500);
            line-height: 1.65;
            font-weight: 400;
        }

        /* ── FEATURES ───────────────────────────────── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .feature-card {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: var(--radius-lg);
            padding: 28px;
            display: flex;
            gap: 18px;
            align-items: flex-start;
            transition: border-color var(--transition), box-shadow var(--transition);
        }

        .feature-card:hover {
            border-color: var(--green-100);
            box-shadow: var(--shadow-md);
        }

        .feature-icon-wrap {
            width: 44px; height: 44px;
            flex-shrink: 0;
            background: var(--green-50);
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
        }

        .feature-icon-wrap svg {
            width: 20px; height: 20px;
            stroke: var(--green-600);
            fill: none;
            stroke-width: 1.75;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .feature-card h3 {
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--gray-900);
        }

        .feature-card p {
            font-size: 14px;
            color: var(--gray-500);
            line-height: 1.6;
            font-weight: 400;
        }

        /* ── ROLES ──────────────────────────────────── */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .role-card {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: var(--radius-xl);
            padding: 32px 28px;
            transition: transform var(--transition), box-shadow var(--transition);
        }

        .role-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .role-card.featured {
            border-color: var(--green-200);
            border-width: 2px;
        }

        .role-pill {
            display: inline-block;
            background: var(--green-50);
            color: var(--green-800);
            font-size: 11px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 100px;
            margin-bottom: 16px;
            letter-spacing: 0.3px;
        }

        .role-avatar {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
            background: var(--green-50);
        }

        .role-card h3 {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 20px;
            color: var(--gray-900);
        }

        .role-list {
            list-style: none;
        }

        .role-list li {
            font-size: 14px;
            color: var(--gray-500);
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 400;
        }

        .role-list li:last-child { border-bottom: none; }

        .role-check {
            width: 16px; height: 16px;
            background: var(--green-50);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 9px;
            color: var(--green-600);
        }

        .roles-cta {
            text-align: center;
            margin-top: 36px;
        }

        /* ── TESTIMONIALS ───────────────────────────── */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .testimonial-card {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: var(--radius-xl);
            padding: 32px;
        }

        .testimonial-quote {
            font-family: var(--font-display);
            font-size: 44px;
            color: var(--green-100);
            line-height: 1;
            margin-bottom: 12px;
        }

        .testimonial-card p {
            font-size: 15px;
            color: var(--gray-700);
            line-height: 1.7;
            margin-bottom: 24px;
            font-style: italic;
            font-family: var(--font-display);
            font-weight: 400;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 40px; height: 40px;
            background: var(--green-600);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            font-weight: 500;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .author-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-900);
        }

        .author-meta {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 1px;
        }

        /* ── FAQ ────────────────────────────────────── */
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .faq-item {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: border-color var(--transition);
        }

        .faq-item.open { border-color: var(--green-200); }

        .faq-question {
            padding: 18px 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            user-select: none;
            color: var(--gray-900);
        }

        .faq-question:hover { background: var(--gray-50); }

        .faq-icon {
            width: 20px; height: 20px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 12px;
            color: var(--gray-700);
            transition: transform var(--transition), background var(--transition);
        }

        .faq-item.open .faq-icon {
            transform: rotate(45deg);
            background: var(--green-50);
            color: var(--green-600);
        }

        .faq-answer {
            display: none;
            padding: 0 20px 18px;
            font-size: 14px;
            color: var(--gray-500);
            line-height: 1.65;
            font-weight: 400;
            border-top: 1px solid var(--gray-100);
            padding-top: 14px;
        }

        .faq-item.open .faq-answer { display: block; }

        .contact-grid {
            max-width: 500px;
            margin: 0 auto;
        }

        .contact-info-panel {
            background: var(--green-600);
            border-radius: var(--radius-xl);
            padding: 36px 32px;
            position: relative;
            overflow: hidden;
        }

        .contact-info-pattern {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .contact-info-panel h3 {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 500;
            color: #fff;
            margin-bottom: 8px;
            position: relative;
        }

        .contact-info-panel > p {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            margin-bottom: 32px;
            font-weight: 300;
            position: relative;
        }

        .contact-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
        }

        .contact-item-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .contact-item-icon svg {
            width: 15px; height: 15px;
            stroke: var(--green-200);
            fill: none;
            stroke-width: 1.75;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .contact-item-label {
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .contact-item-val {
            font-size: 13px;
            color: rgba(255,255,255,0.8);
            font-weight: 300;
            line-height: 1.5;
        }

        .contact-form-panel {
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: var(--radius-xl);
            padding: 36px 32px;
        }

        .contact-form-panel h3 {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .contact-form-panel > p {
            font-size: 13px;
            color: var(--gray-500);
            margin-bottom: 28px;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 6px;
            letter-spacing: 0.2px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-family: var(--font-body);
            font-size: 14px;
            color: var(--gray-900);
            background: var(--white);
            transition: border-color var(--transition), box-shadow var(--transition);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--green-400);
            box-shadow: 0 0 0 3px rgba(99,153,34,0.12);
        }

        .form-control::placeholder { color: var(--gray-300); }

        textarea.form-control {
            resize: vertical;
            min-height: 90px;
            line-height: 1.5;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--green-600);
            color: #fff;
            border: none;
            border-radius: var(--radius-md);
            font-family: var(--font-body);
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: background var(--transition);
            margin-top: 4px;
        }

        .btn-submit:hover { background: var(--green-800); }

        /* ── CTA STRIP ──────────────────────────────── */
        .cta-section {
            background: var(--green-600);
            padding: 64px 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.05) 0%, transparent 60%),
                              radial-gradient(circle at 80% 50%, rgba(255,255,255,0.05) 0%, transparent 60%);
        }

        .cta-inner { position: relative; z-index: 1; }

        .cta-section h2 {
            font-family: var(--font-display);
            font-size: clamp(26px, 3.5vw, 38px);
            font-weight: 500;
            color: #fff;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .cta-section h2 em {
            font-style: italic;
            color: var(--green-100);
        }

        .cta-section p {
            font-size: 16px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 32px;
            font-weight: 300;
        }

        .cta-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ── FOOTER ─────────────────────────────────── */
        .footer {
            background: var(--green-900);
            padding: 60px 2rem 28px;
        }

        .footer-inner {
            max-width: 1120px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.8fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }

        .footer-brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 500;
            color: var(--green-200);
            margin-bottom: 14px;
        }

        .footer-brand-icon {
            width: 30px; height: 30px;
            background: var(--green-600);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }

        .footer-brand-icon svg {
            width: 16px; height: 16px;
            fill: none; stroke: #fff;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }

        .footer-brand-desc {
            font-size: 13px;
            color: rgba(255,255,255,0.35);
            line-height: 1.6;
            font-weight: 300;
            max-width: 260px;
        }

        .footer-col h4 {
            font-size: 11px;
            font-weight: 500;
            color: rgba(255,255,255,0.35);
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .footer-link {
            font-size: 13px;
            color: rgba(255,255,255,0.55);
            font-weight: 300;
            transition: color var(--transition);
        }

        .footer-link:hover { color: var(--green-200); }

        .footer-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.07);
            margin-bottom: 24px;
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .footer-copy {
            font-size: 12px;
            color: rgba(255,255,255,0.25);
            font-weight: 300;
        }

        /* ── ANIMATIONS ─────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-inner > * {
            animation: fadeUp 0.6s ease both;
        }
        .hero-inner > *:nth-child(1) { animation-delay: 0.05s; }
        .hero-inner > *:nth-child(2) { animation-delay: 0.15s; }
        .hero-inner > *:nth-child(3) { animation-delay: 0.25s; }
        .hero-inner > *:nth-child(4) { animation-delay: 0.35s; }
        .hero-inner > *:nth-child(5) { animation-delay: 0.45s; }
        .hero-inner > *:nth-child(6) { animation-delay: 0.55s; }

        /* ── RESPONSIVE ─────────────────────────────── */
        @media (max-width: 900px) {
            .steps-grid, .roles-grid, .footer-grid { grid-template-columns: 1fr; }
            .features-grid, .testimonials-grid, .faq-grid, .contact-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .hero-stats { gap: 0; flex-wrap: wrap; }
            .hero-stat { padding: 12px 24px; }
            .topnav-links { display: none; }
        }

        @media (max-width: 600px) {
            .section { padding: 48px 1.25rem; }
            .hero { padding: 64px 1.25rem 56px; }
            .footer-grid { grid-template-columns: 1fr; }
            .contact-form-panel, .contact-info-panel { padding: 24px 20px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <!-- ═══════════════ NAVBAR ═══════════════ -->
    <?php
    $isHomePage = true;
    require 'shared/topnavbar.view.php';
    ?>

    <!-- ═══════════════ HERO ═══════════════ -->
    <section id="home" class="hero">
        <div class="hero-grid-pattern"></div>
        <div class="hero-glow"></div>
        <div class="hero-inner">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                Sri Lanka's Agricultural Marketplace
            </div>
            <h1>Trade Smarter.<br><em>Deliver Faster.</em></h1>
            <p class="hero-sub">Connecting farmers, buyers, and transporters to make agricultural trade simple across Sri Lanka.</p>
            <div class="hero-btns">
                <a href="<?= ROOT ?>/register" class="btn btn-white">Get Started</a>
                <a href="<?= ROOT ?>/login" class="btn btn-outline-white">Login</a>
            </div>
            <hr class="hero-divider">
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-num">500+</div>
                    <div class="hero-stat-label">Registered Farmers</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-num">1,200+</div>
                    <div class="hero-stat-label">Orders Completed</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-num">25</div>
                    <div class="hero-stat-label">Districts Covered</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ HOW IT WORKS ═══════════════ -->
    <section id="about" class="section">
        <div class="section-inner">
            <div class="section-header">
                <span class="section-tag">How it works</span>
                <h2 class="section-title">How it works</h2>
                <p class="section-sub">A quick look at how AgroLink works.</p>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-num">01</div>
                    <div class="step-dot"></div>
                    <h3>List & Browse Produce</h3>
                    <p>Farmers list fresh products; buyers browse regionally available produce with detailed information and pricing.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">02</div>
                    <div class="step-dot"></div>
                    <h3>Place Orders & Schedule Delivery</h3>
                    <p>Buyers order directly from farmers; transporters coordinate and manage delivery logistics seamlessly.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">03</div>
                    <div class="step-dot"></div>
                    <h3>Rate & Review</h3>
                    <p>Build trust with a feedback-based ecosystem that ensures quality and reliability for everyone involved.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ FEATURES ═══════════════ -->
    <section id="features" class="section bg-light">
        <div class="section-inner">
            <div class="section-header">
                <span class="section-tag">Why AgroLink</span>
                <h2 class="section-title">Everything you need to trade</h2>
                <p class="section-sub">Tools built for farmers, buyers, and transporters.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <div>
                        <h3>Direct Farm-to-Buyer Sales</h3>
                        <p>Skip the middlemen — farmers and buyers connect and trade directly.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div>
                        <h3>Transport Coordination</h3>
                        <p>Manage and track deliveries from one place.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    </div>
                    <div>
                        <h3>Role-Based Dashboards</h3>
                        <p>Each user type gets their own dashboard and tools.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    </div>
                    <div>
                        <h3>Track Orders & Revenue</h3>
                        <p>Follow your orders, deliveries, and earnings in real time.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ ROLES ═══════════════ -->
    <section id="roles" class="section">
        <div class="section-inner">
            <div class="section-header">
                <span class="section-tag">Who it's for</span>
                <h2 class="section-title">A platform for everyone</h2>
                <p class="section-sub">AgroLink is built for three types of users.</p>
            </div>
            <div class="roles-grid">
                <div class="role-card">
                    <div class="role-avatar">🌾</div>
                    <h3>Farmers</h3>
                    <ul class="role-list">
                        <li><span class="role-check">✓</span> Easy product listing & updates</li>
                        <li><span class="role-check">✓</span> Get orders instantly</li>
                        <li><span class="role-check">✓</span> Track sales & payments</li>
                        <li><span class="role-check">✓</span> Connect directly with buyers</li>
                    </ul>
                </div>
                <div class="role-card featured">
                    <div class="role-avatar">🛒</div>
                    <h3>Buyers</h3>
                    <ul class="role-list">
                        <li><span class="role-check">✓</span> Browse fresh products</li>
                        <li><span class="role-check">✓</span> Secure online checkout</li>
                        <li><span class="role-check">✓</span> Track deliveries in real-time</li>
                        <li><span class="role-check">✓</span> Request specific crops</li>
                    </ul>
                </div>
                <div class="role-card">
                    <div class="role-avatar">🚚</div>
                    <h3>Transporters</h3>
                    <ul class="role-list">
                        <li><span class="role-check">✓</span> Accept & manage delivery tasks</li>
                        <li><span class="role-check">✓</span> Update delivery status</li>
                        <li><span class="role-check">✓</span> Earn more with reliable clients</li>
                    </ul>
                </div>
            </div>
            <div class="roles-cta">
                <a href="<?= ROOT ?>/register" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px; border-radius: var(--radius-lg);">Register Now</a>
            </div>
        </div>
    </section>

    <!-- ═══════════════ TESTIMONIALS ═══════════════ -->
    <section class="section bg-light">
        <div class="section-inner">
            <div class="section-header">
                <span class="section-tag">Testimonials</span>
                <h2 class="section-title">What our users say</h2>
                <p class="section-sub">Trusted by farmers, buyers, and transporters across Sri Lanka.</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-quote">"</div>
                    <p>Finally, a platform that gives farmers full control over pricing! AgroLink has transformed how I sell my produce — no more depending on middlemen.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">RF</div>
                        <div>
                            <div class="author-name">Ranjith Fernando</div>
                            <div class="author-meta">Farmer · Matale</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-quote">"</div>
                    <p>Our restaurant sources reliably through AgroLink — no middlemen needed! Fresh produce delivered on time, every time. It's been a game changer for us.</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">DR</div>
                        <div>
                            <div class="author-name">Duleeka Rathnayake</div>
                            <div class="author-meta">Buyer · Colombo</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ FAQ ═══════════════ -->
    <section class="section">
        <div class="section-inner">
            <div class="section-header">
                <span class="section-tag">FAQ</span>
                <h2 class="section-title">Frequently asked questions</h2>
                <p class="section-sub">Got questions? We've got answers.</p>
            </div>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        Is AgroLink free to use?
                        <span class="faq-icon">+</span>
                    </div>
                    <div class="faq-answer">
                        Yes, AgroLink is free to use. Buyers and farmers can register and use the platform without any cost. Small service or delivery fees may apply when placing orders.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        Can I sign up as both a buyer and a farmer?
                        <span class="faq-icon">+</span>
                    </div>
                    <div class="faq-answer">
                        No, you need to create separate accounts for each role. A user cannot act as both a buyer and a farmer using the same account.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        How do I track my deliveries?
                        <span class="faq-icon">+</span>
                    </div>
                    <div class="faq-answer">
                        You can track your orders from your dashboard. It shows the current status like pending, dispatched, or delivered.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        What areas does AgroLink cover?
                        <span class="faq-icon">+</span>
                    </div>
                    <div class="faq-answer">
                        AgroLink covers all districts across Sri Lanka, connecting buyers and farmers from different regions.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ CONTACT ═══════════════ -->
    <section id="contact" class="section bg-light">
        <div class="section-inner">
            <div class="section-header">
                <span class="section-tag">Contact</span>
                <h2 class="section-title">Get in touch</h2>
                <p class="section-sub">Have a question or want to learn more? We'd love to hear from you.</p>
            </div>
            <div class="contact-grid">
                <div class="contact-info-panel">
                    <div class="contact-info-pattern"></div>
                    <h3>Contact information</h3>
                    <p>Reach out anytime — we'll get back to you promptly.</p>
                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <div class="contact-item-label">Email</div>
                            <div class="contact-item-val">agrolink.lk@gmail.com</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.35 1.9.67 2.81a2 2 0 0 1-.45 2.11L7.91 8.95a16 16 0 0 0 6 6l.81-.81a2 2 0 0 1 2.11-.45c.91.32 1.85.54 2.81.67A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div>
                            <div class="contact-item-label">Phone</div>
                            <div class="contact-item-val">+94 11 2559 259</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <div class="contact-item-label">Address</div>
                            <div class="contact-item-val">UCSC Building Complex, Reid Avenue,<br>Colombo 07, Sri Lanka</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════ CTA ═══════════════ -->
    <section class="cta-section">
        <div class="cta-inner">
            <h2>Start using AgroLink<br><em>today</em></h2>
            <p>Free to register. Start buying and selling in minutes.</p>
            <div class="cta-btns">
                <a href="<?= ROOT ?>/register" class="btn btn-white">Create Free Account</a>
                <a href="<?= ROOT ?>/login" class="btn btn-outline-white">Login</a>
            </div>
        </div>
    </section>

    <!-- ═══════════════ FOOTER ═══════════════ -->
    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand-logo">
                        <div class="footer-brand-icon">
                            <svg viewBox="0 0 24 24"><path d="M12 2a9 9 0 0 0-9 9c0 4.97 9 13 9 13s9-8.03 9-13a9 9 0 0 0-9-9z"/></svg>
                        </div>
                        AgroLink
                    </div>
                    <p class="footer-brand-desc">Connecting farmers and buyers across Sri Lanka.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <div class="footer-links">
                        <a class="footer-link" href="#home">Home</a>
                        <a class="footer-link" href="#about">About</a>
                        <a class="footer-link" href="#features">Features</a>
                        <a class="footer-link" href="<?= ROOT ?>/products">Browse Products</a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Users</h4>
                    <div class="footer-links">
                        <a class="footer-link" href="<?= ROOT ?>/register">Register as Farmer</a>
                        <a class="footer-link" href="<?= ROOT ?>/register">Register as Buyer</a>
                        <a class="footer-link" href="<?= ROOT ?>/register">Register as Transporter</a>
                        <a class="footer-link" href="<?= ROOT ?>/login">Login</a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <div class="footer-links">
                        <a class="footer-link" href="#terms">Terms & Conditions</a>
                        <a class="footer-link" href="#privacy">Privacy Policy</a>
                        <a class="footer-link" href="#contact">Contact Us</a>
                        <a class="footer-link" href="#support">Support</a>
                    </div>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="footer-bottom">
                <span class="footer-copy">© 2025 AgroLink — CS22 Project. All rights reserved.</span>
            </div>
        </div>
    </footer>

    <script>
        window.APP_ROOT = "<?= ROOT ?>";
    </script>
    <script src="<?= ROOT ?>/assets/js/main.js"></script>
    <script src="<?= ROOT ?>/assets/js/home.js"></script>
    <script src="<?= ROOT ?>/assets/js/topnavbar.js"></script>
    </script>
</body>

</html>
