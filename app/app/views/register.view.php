<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registration - AgroLink</title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/style1.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet" />
  <style>
    .steps {
      display: flex;
      margin-bottom: 2rem;
      padding: 0 15px;
    }

    .step {
      flex: 1;
      text-align: center;
      position: relative;
    }

    .step-new {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .step-circle {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 600;
      position: relative;
      z-index: 1;
    }

    .step-circle.active {
      background: #1a7a4a;
      color: #fff;
    }

    .step-circle.done {
      background: #1D9E75;
      color: #fff;
    }

    .step-circle.idle {
      background: #eee;
      color: #999;
      border: 1px solid #ddd;
    }

    .step-label {
      font-size: 11px;
      color: #888;
      margin-top: 4px;
      display: block;
    }

    .btn-primary {
      background-color: #52a266 !important;
      color: #ffffff !important;
      border: 1px solid #458b56 !important;
      font-weight: 500 !important;
      font-size: 14px !important;
      padding: 10px 22px !important;
      border-radius: 8px !important;
      cursor: pointer !important;
      display: flex !important;
      justify-content: center !important;
      align-items: center !important;
      transition: background 0.18s, border-color 0.18s !important;
    }
    .btn-primary:hover { background-color: #458b56 !important; border-color: #3d7c4c !important; }

    .btn-secondary {
      background-color: #ffffff !important;
      color: #2f3b33 !important;
      border: 1px solid #cfd8cf !important;
      font-weight: 500 !important;
      font-size: 14px !important;
      padding: 10px 22px !important;
      border-radius: 8px !important;
      cursor: pointer !important;
      display: flex !important;
      justify-content: center !important;
      align-items: center !important;
      transition: background 0.18s, border-color 0.18s !important;
    }
    .btn-secondary:hover { background-color: #f6faf6 !important; border-color: #b6c7b6 !important; color: #203329 !important; }

    .form-step {
      display: none;
    }

    .form-step.active {
      display: block;
    }

    .role-cards {
      display: flex;
      gap: 10px;
      margin-top: 8px;
    }

    .role-card {
      border: 1.5px solid #e0e0e0;
      border-radius: 8px;
      padding: 16px 8px;
      text-align: center;
      cursor: pointer;
      transition: .15s;
    }

    .role-card:hover {
      border-color: #aaa;
      background: #f8f8f8;
    }

    .role-card.selected {
      border: 2px solid #1a7a4a;
      background: #eaf5ef;
    }

    .role-card .icon {
      font-size: 24px;
      margin-bottom: 6px;
    }

    .role-card .label {
      font-size: 13px;
      font-weight: 600;
    }

    .role-card .sub {
      font-size: 11px;
      color: #777;
      margin-top: 2px;
    }

    .upload-box {
      border: 1px dashed #bbb;
      border-radius: 8px;
      padding: 12px 14px;
      background: #fafafa;
      margin-top: 10px;
    }

    .upload-box label {
      font-weight: 600;
      font-size: 13px;
      color: #333;
      margin-bottom: 4px;
      display: block;
    }

    .upload-hint {
      font-size: 12px;
      color: #888;
      margin-bottom: 8px;
    }

    .badge-req {
      background: #ffe4e4;
      color: #c0392b;
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 4px;
      margin-left: 6px;
    }

    .info-box {
      background: #e8f5f0;
      border-left: 3px solid #1a7a4a;
      border-radius: 0 6px 6px 0;
      padding: 10px 14px;
      font-size: 13px;
      color: #155a38;
      margin-top: 12px;
    }

    .nav-row {
      display: flex;
      justify-content: space-between;
      margin-top: 1.5rem;
      gap: 10px;
    }

    .nav-row .btn-back {
      flex: 0 0 auto;
    }

    .nav-row .btn-next {
      flex: 0 0 130px;
    }

    .field-error {
      font-size: 12px;
      color: #e74c3c;
      margin-top: 3px;
      display: none;
    }
  </style>
</head>

<body>
  <div class="split-container">
    <!-- Left panel -->
    <div class="split-left"
      style="background:url('<?= ROOT ?>/assets/imgs/registerpage/register5.jpg') center/cover no-repeat;">
      <span class="quote-icon">&ldquo;</span>
      <div class="split-left-content">
        <p>The best produce comes straight from the source.<br>AgroLink connects you to Sri Lanka's freshest harvests.
        </p>
      </div>
    </div>

    <!-- Right: multi-step form -->
    <div class="split-right">
      <div class="form-box">
        <?php if (!empty($errors)): ?>
          <div class="alert"><?= implode('<br>', $errors) ?></div>
        <?php endif; ?>

        <h1>Create AgroLink Account</h1>

        <!-- Step indicator -->
        <div class="steps" id="steps-bar">
          <div class="step-new">
            <span class="step-circle active" id="sc1">1</span>
            <span class="step-label">Basic info</span>
          </div>
          <div class="step-new">
            <span class="step-circle idle" id="sc2">2</span>
            <span class="step-label">Your role</span>
          </div>
          <div class="step-new">
            <span class="step-circle idle" id="sc3">3</span>
            <span class="step-label">Documents</span>
          </div>
        </div>

        <form id="registerForm" method="POST" enctype="multipart/form-data" autocomplete="off">

          <!-- ── Step 1: Basic info ── -->
          <div class="form-step active" id="step-1">
            <div class="form-group">
              <label for="name">Full name</label>
              <input type="text" id="name" name="name" class="form-control" placeholder="Your name" />
              <div class="field-error" id="err-name">Please enter your name.</div>
            </div>
            <div class="form-group">
              <label for="email">Email address</label>
              <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" />
              <div class="field-error" id="err-email">Please enter a valid email.</div>
            </div>
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" class="form-control"
                placeholder="Minimum 8 characters" />
              <div class="field-error" id="err-pass">Password must be at least 8 characters.</div>
            </div>
            <div class="form-group">
              <label for="confirm_password">Confirm password</label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                placeholder="Repeat password" />
              <div class="field-error" id="err-confirm">Passwords do not match.</div>
            </div>
            <div class="nav-row">
              <div></div>
              <button type="button" class="btn btn-primary btn-next" onclick="nextStep(1)">Next →</button>
            </div>
          </div>

          <!-- ── Step 2: Role selection ── -->
          <div class="form-step" id="step-2">
            <p style="font-size:14px;color:#555;margin-bottom:6px;">I am a:</p>
            <div class="role-cards">
              <div class="role-card" id="rc-farmer" onclick="selectRole('farmer')">
                <div class="icon">🌾</div>
                <div class="label">Farmer</div>
                <div class="sub">Sell your harvest</div>
              </div>
              <div class="role-card" id="rc-buyer" onclick="selectRole('buyer')">
                <div class="icon">🛒</div>
                <div class="label">Buyer</div>
                <div class="sub">Purchase produce</div>
              </div>
              <div class="role-card" id="rc-transporter" onclick="selectRole('transporter')">
                <div class="icon">🚛</div>
                <div class="label">Transporter</div>
                <div class="sub">Deliver goods</div>
              </div>
            </div>
            <input type="hidden" id="role" name="role" value="" />
            <div class="field-error" id="err-role">Please select a role to continue.</div>
            <div id="role-note" style="display:none; margin-top:10px; font-size:12px; color:#555;"></div>

            <div class="nav-row">
              <button type="button" class="btn btn-back btn-secondary" onclick="prevStep(2)">← Back</button>
              <button type="button" class="btn btn-primary btn-next" onclick="nextStep(2)">Next →</button>
            </div>
          </div>

          <!-- ── Step 3: Documents ── -->
          <div class="form-step" id="step-3">

            <!-- Buyer: no docs -->
            <div id="docs-buyer" style="display:none;">
              <div class="info-box">No documents required. Your account will be activated immediately after
                registration.</div>
            </div>

            <!-- Farmer docs -->
            <div id="docs-farmer" style="display:none;">
              <div class="info-box">Your documents will be reviewed within 1–2 business days before your listings go
                live.</div>
              <div class="upload-box">
                <label>National Identity Card (NIC) <span class="badge-req">Required</span></label>
                <div class="upload-hint">Upload a clear photo or scan — front side.</div>
                <input type="file" name="nic" accept="image/jpeg,image/png,image/webp,application/pdf" />
              </div>
              <div class="upload-box">
                <label>Bank account details <span class="badge-req">Required</span></label>
                <div class="upload-hint">Bank statement or passbook page showing your name and account number.</div>
                <input type="file" name="bank_details" accept="image/jpeg,image/png,image/webp,application/pdf" />
              </div>
            </div>

            <!-- Transporter docs -->
            <div id="docs-transporter" style="display:none;">
              <div class="info-box">Your documents will be reviewed before you can accept delivery jobs.</div>
              <div class="upload-box">
                <label>Driving license <span class="badge-req">Required</span></label>
                <div class="upload-hint">Both sides preferred.</div>
                <input type="file" name="driving_license" accept="image/jpeg,image/png,image/webp,application/pdf" />
              </div>
              <div class="upload-box">
                <label>Vehicle insurance card <span class="badge-req">Required</span></label>
                <div class="upload-hint">Current valid insurance document.</div>
                <input type="file" name="vehicle_insurance" accept="image/jpeg,image/png,image/webp,application/pdf" />
              </div>
              <div class="upload-box">
                <label>Vehicle revenue license <span class="badge-req">Required</span></label>
                <div class="upload-hint">Current year revenue license.</div>
                <input type="file" name="vehicle_revenue_license"
                  accept="image/jpeg,image/png,image/webp,application/pdf" />
              </div>
            </div>

            <div class="nav-row">
              <button type="button" class="btn btn-back btn-secondary" onclick="prevStep(3)">← Back</button>
              <button type="submit" class="btn btn-primary btn-next">Create account</button>
            </div>
          </div>

        </form>

        <div class="text-center" style="margin-top:1rem;">
          Already have an account? <a href="<?= ROOT ?>/login">Sign in here</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    let currentStep = 1;
    let selectedRole = '';

    function updateBar(step) {
      for (let i = 1; i <= 3; i++) {
        const c = document.getElementById('sc' + i);
        if (i < step) {
          c.className = 'step-circle done';
          c.textContent = '✓';
        } else if (i === step) {
          c.className = 'step-circle active';
          c.textContent = i;
        } else {
          c.className = 'step-circle idle';
          c.textContent = i;
        }
      }
    }

    function showStep(n) {
      document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
      document.getElementById('step-' + n).classList.add('active');
      currentStep = n;
      updateBar(n);
    }

    function nextStep(from) {
      if (from === 1 && !validateStep1()) return;
      if (from === 2 && !validateStep2()) return;
      showStep(from + 1);
    }

    function prevStep(from) {
      showStep(from - 1);
    }

    function validateStep1() {
      let ok = true;
      const show = (id, vis) => {
        document.getElementById(id).style.display = vis ? 'block' : 'none';
      };
      const name = document.getElementById('name').value.trim();
      const email = document.getElementById('email').value.trim();
      const pass = document.getElementById('password').value;
      const conf = document.getElementById('confirm_password').value;
      show('err-name', !name);
      if (!name) ok = false;
      show('err-email', !email || !email.includes('@'));
      if (!email || !email.includes('@')) ok = false;
      show('err-pass', pass.length < 8);
      if (pass.length < 8) ok = false;
      show('err-confirm', pass !== conf);
      if (pass !== conf) ok = false;
      return ok;
    }

    function validateStep2() {
      if (!selectedRole) {
        document.getElementById('err-role').style.display = 'block';
        return false;
      }
      return true;
    }

    function selectRole(role) {
      selectedRole = role;
      document.getElementById('role').value = role;
      ['farmer', 'buyer', 'transporter'].forEach(r => {
        document.getElementById('rc-' + r).classList.toggle('selected', r === role);
      });
      document.getElementById('err-role').style.display = 'none';

      const notes = {
        farmer: 'You\'ll upload your NIC and bank details on the next step.',
        buyer: 'No documents needed — your account activates immediately.',
        transporter: 'You\'ll upload your driving license, vehicle insurance, and revenue license.'
      };
      const note = document.getElementById('role-note');
      note.textContent = notes[role];
      note.style.display = 'block';

      // Pre-show the right doc section when reaching step 3
      ['buyer', 'farmer', 'transporter'].forEach(r => {
        document.getElementById('docs-' + r).style.display = r === role ? 'block' : 'none';
      });
    }

    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('registerForm');
      form.addEventListener('submit', function (e) {
        if (!validateStep1() || !validateStep2()) {
          e.preventDefault();
          if (!validateStep1()) showStep(1);
          else if (!validateStep2()) showStep(2);
        }
      })
    })
  </script>
</body>

</html>