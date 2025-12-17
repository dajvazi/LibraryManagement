<?php 
include "includes/header.php";
?>

<div class="auth-wrap d-flex align-items-center py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-5">

        <div class="auth-card">
          <div class="auth-top d-flex align-items-center gap-3">
            <div class="auth-badge auth-badge-register">
              <i class="bi bi-person-plus fs-4"></i>
            </div>
            <div>
              <h1 class="auth-title h4 mb-1">Create account</h1>
              <p class="auth-sub">Register to manage your library</p>
            </div>
          </div>

          <div class="p-4 p-md-5">

            <?php if(isset($_SESSION['message'])): ?>
              <div class="alert alert-warning text-center mb-3 auth-alert">
                <?= htmlspecialchars($_SESSION['message']); ?>
              </div>
              <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <form action="LibraryManagement/functions/authcode.php" method="POST" class="needs-validation" novalidate>

              <div class="mb-3 auth-input">
                <label for="name" class="form-label fw-semibold">Name</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input
                    type="text"
                    class="form-control form-control-lg"
                    name="name"
                    id="name"
                    placeholder="Enter your name"
                    required
                  />
                  <div class="invalid-feedback">Please enter your name.</div>
                </div>
              </div>

              <div class="mb-3 auth-input">
                <label for="email" class="form-label fw-semibold">Email</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                  <input
                    type="email"
                    class="form-control form-control-lg"
                    name="email"
                    id="email"
                    placeholder="Enter your email"
                    required
                  />
                  <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
              </div>

              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label for="password" class="form-label fw-semibold">Password</label>
                  <div class="input-group auth-input">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input
                      type="password"
                      class="form-control form-control-lg"
                      name="password"
                      id="password"
                      placeholder="••••••••"
                      required
                      minlength="6"
                    />
                    <button type="button" class="btn pw-toggle" id="togglePw1" aria-label="Show/Hide password">
                      <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback">Password must be at least 6 characters.</div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <label for="confirmPassword" class="form-label fw-semibold">Confirm Password</label>
                  <div class="input-group auth-input">
                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                    <input
                      type="password"
                      class="form-control form-control-lg"
                      name="confirmPassword"
                      id="confirmPassword"
                      placeholder="••••••••"
                      required
                      minlength="6"
                    />
                    <button type="button" class="btn pw-toggle" id="togglePw2" aria-label="Show/Hide confirm password">
                      <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback">Please confirm your password.</div>
                  </div>
                </div>
              </div>

              <div class="auth-error mt-2 d-none" id="pwMismatch">Passwords do not match.</div>

              <button type="submit" name="registerBtn" class="btn btn-auth btn-lg fw-semibold text-white w-100 mt-4">
                <i class="bi bi-check2-circle me-1"></i> Register
              </button>

              <div class="text-center mt-3">
                <small class="text-muted">
                  Already have an account?
                  <a href="login.php" class="auth-link fw-semibold">Sign in</a>
                </small>
              </div>
            </form>

          </div>
        </div>

        <p class="text-center mt-3 mb-0 auth-tip">
          <small><i class="bi bi-info-circle me-1"></i> By registering you agree to the terms.</small>
        </p>

      </div>
    </div>
  </div>
</div>

<script>
  // Bootstrap validation + password match
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach((form) => {
      form.addEventListener('submit', (event) => {
        const pw = document.getElementById('password');
        const cpw = document.getElementById('confirmPassword');
        const mismatch = document.getElementById('pwMismatch');

        if (pw && cpw && mismatch) {
          const ok = pw.value === cpw.value;
          mismatch.classList.toggle('d-none', ok);
          cpw.setCustomValidity(ok ? '' : 'Passwords do not match');
        }

        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // Show/Hide password
  (() => {
    const pw = document.getElementById('password');
    const btn1 = document.getElementById('togglePw1');
    if (pw && btn1) {
      btn1.addEventListener('click', () => {
        const hidden = pw.type === 'password';
        pw.type = hidden ? 'text' : 'password';
        btn1.innerHTML = hidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      });
    }

    const cpw = document.getElementById('confirmPassword');
    const btn2 = document.getElementById('togglePw2');
    if (cpw && btn2) {
      btn2.addEventListener('click', () => {
        const hidden = cpw.type === 'password';
        cpw.type = hidden ? 'text' : 'password';
        btn2.innerHTML = hidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      });
    }
  })();
</script>
