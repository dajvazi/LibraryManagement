<?php
include "includes/header.php";
?>
<div class="auth-wrap d-flex align-items-center py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-5">

        <div class="auth-card">
          <div class="auth-top d-flex align-items-center gap-3">
            <div class="auth-badge">
              <i class="bi bi-shield-lock fs-4"></i>
            </div>
            <div>
              <h1 class="auth-title h4 mb-1">Welcome back</h1>
              <p class="auth-sub">Login to continue</p>
            </div>
          </div>

          <div class="p-4 p-md-5">

            <?php if(isset($_SESSION['message'])) { ?>
              <div class="alert alert-info alert-dismissible fade show auth-alert" role="alert">
                <strong>Hey! </strong><?= $_SESSION['message']; ?>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php unset($_SESSION['message']); ?>
            <?php } ?>

            <form action="functions/authcode.php" method="post" class="needs-validation" novalidate>

              <div class="mb-3 auth-input">
                <label for="email" class="form-label fw-semibold">Email</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="bi bi-envelope"></i>
                  </span>
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

              <div class="mb-3">
                <label for="password" class="form-label fw-semibold mb-1">Password</label>

                <div class="input-group auth-input">
                  <span class="input-group-text">
                    <i class="bi bi-key"></i>
                  </span>
                  <input
                    type="password"
                    class="form-control form-control-lg"
                    name="password"
                    id="password"
                    placeholder="••••••••"
                    required
                  />
                  <button type="button" class="btn pw-toggle" id="togglePw" aria-label="Show/Hide password">
                    <i class="bi bi-eye"></i>
                  </button>
                  <div class="invalid-feedback">Please enter your password.</div>
                </div>
              </div>

              <button type="submit" name="loginBtn" class="btn btn-auth btn-lg text-white fw-semibold w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                Login
              </button>

              <div class="text-center mt-3">
                <small class="text-muted">
                  Don’t have an account?
                  <a href="register.php" class="auth-link fw-semibold">Create one</a>
                </small>
              </div>
            </form>
          </div>
        </div>

        <p class="text-center mt-3 mb-0 auth-tip">
          <small><i class="bi bi-lightbulb me-1"></i> Tip: Use a strong password.</small>
        </p>

      </div>
    </div>
  </div>
</div>

<script>
  // Bootstrap validation
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach((form) => {
      form.addEventListener('submit', (event) => {
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
    const btn = document.getElementById('togglePw');
    if (!pw || !btn) return;

    btn.addEventListener('click', () => {
      const isHidden = pw.getAttribute('type') === 'password';
      pw.setAttribute('type', isHidden ? 'text' : 'password');
      btn.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });
  })();
</script>