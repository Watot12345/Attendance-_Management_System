
    // Clean ?logged_out=1 or ?session_expired=1 from address bar so page refreshes don't re-trigger
    if (window.location.search.includes('logged_out=1') || window.location.search.includes('session_expired=1')) {
      window.history.replaceState({}, document.title, window.location.pathname);
      setTimeout(() => {
        const loAlert = document.getElementById('logged-out-alert');
        if (loAlert) {
          loAlert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
          loAlert.style.opacity = '0';
          loAlert.style.transform = 'translateY(-4px)';
          setTimeout(() => loAlert.remove(), 400);
        }
        const seAlert = document.getElementById('session-expired-alert');
        if (seAlert) {
          seAlert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
          seAlert.style.opacity = '0';
          seAlert.style.transform = 'translateY(-4px)';
          setTimeout(() => seAlert.remove(), 400);
        }
      }, 5000);
    }

    let resendCountdown = 60;
    let resendInterval = null;
    let modalResendCountdown = 60;
    let modalResendInterval = null;
    let resetActiveEmail = '';
    let resetActiveOtp = '';

    // Password Visibility Toggle
    function togglePasswordVisibility(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      if (!input || !icon) return;

      if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.11 6.11m3.768 3.768L6.11 6.11m0 0L3 3m3.11 3.11l4.242 4.243m6.768 6.768L21 21m-3.11-3.11l-4.243-4.243m4.243 4.243l-4.242-4.242"/>';
      } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
      }
    }

    // Alert Banner Functions
    function showAlert(msg, isError = true) {
      const banner = document.getElementById('alert-banner');
      const text = document.getElementById('alert-message');
      const icon = document.getElementById('alert-icon');
      
      banner.classList.remove('hidden', 'error', 'success');
      
      if (isError) {
        banner.classList.add('error');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
      } else {
        banner.classList.add('success');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
      }
      
      text.textContent = msg;
    }

    function hideAlert() {
      document.getElementById('alert-banner').classList.add('hidden');
    }

    // Modal Alert
    function showModalAlert(msg, isError = true) {
      const banner = document.getElementById('modal-alert-banner');
      const text = document.getElementById('modal-alert-message');
      const icon = document.getElementById('modal-alert-icon');
      
      banner.classList.remove('hidden', 'error', 'success');
      if (isError) {
        banner.classList.add('error');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
      } else {
        banner.classList.add('success');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
      }
      text.textContent = msg;
    }

    function hideModalAlert() {
      document.getElementById('modal-alert-banner').classList.add('hidden');
    }

    // Step 2 OTP Transition
    function showOtpStep(maskedEmail) {
      hideAlert();
      document.getElementById('credentials-step-container').style.display = 'none';
      document.getElementById('otp-step-container').style.display = 'block';

      document.getElementById('auth-flow-eyebrow').textContent = '2-FACTOR VERIFICATION';
      document.getElementById('auth-flow-title').textContent = 'Verify Security Code';
      document.getElementById('auth-flow-subtitle').textContent = 'Enter the 6-digit verification code sent to your registered email.';
      document.getElementById('otp-masked-email-display').textContent = maskedEmail || 'your email';

      const otpBoxes = document.querySelectorAll('.otp-digit-box');
      otpBoxes.forEach(b => { b.value = ''; b.classList.remove('filled'); });
      updateFullOtpValue();

      showAlert(`A 6-digit verification code has been sent to ${maskedEmail}. Please check your inbox.`, false);

      if (otpBoxes[0]) otpBoxes[0].focus();
      startResendCountdown();
    }

    function backToCredentialsStep() {
      hideAlert();
      document.getElementById('otp-step-container').style.display = 'none';
      document.getElementById('credentials-step-container').style.display = 'block';

      document.getElementById('auth-flow-eyebrow').textContent = 'SECURE SIGN IN';
      document.getElementById('auth-flow-title').textContent = 'Sign In to Portal';
      document.getElementById('auth-flow-subtitle').textContent = 'Use your institutional credentials to authenticate.';

      if (resendInterval) clearInterval(resendInterval);
    }

    function startResendCountdown() {
      if (resendInterval) clearInterval(resendInterval);
      resendCountdown = 60;
      const resendBtn = document.getElementById('resend-otp-btn');
      const timerText = document.getElementById('resend-timer-text');

      resendBtn.disabled = true;
      timerText.textContent = `(${resendCountdown}s)`;

      resendInterval = setInterval(() => {
        resendCountdown--;
        if (resendCountdown <= 0) {
          clearInterval(resendInterval);
          resendBtn.disabled = false;
          timerText.textContent = '';
        } else {
          timerText.textContent = `(${resendCountdown}s)`;
        }
      }, 1000);
    }

    // 6-Digit OTP Box Auto-Advance
    const otpBoxes = document.querySelectorAll('.otp-digit-box');
    otpBoxes.forEach((box, idx) => {
      box.addEventListener('input', (e) => {
        const val = e.target.value.replace(/\D/g, '');
        e.target.value = val ? val.slice(-1) : '';

        if (e.target.value) {
          box.classList.add('filled');
          if (idx < otpBoxes.length - 1) {
            otpBoxes[idx + 1].focus();
          }
        } else {
          box.classList.remove('filled');
        }

        updateFullOtpValue();
      });

      box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && idx > 0) {
          otpBoxes[idx - 1].focus();
          otpBoxes[idx - 1].value = '';
          otpBoxes[idx - 1].classList.remove('filled');
          updateFullOtpValue();
        }
      });

      box.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        if (pasteData) {
          const digits = pasteData.slice(0, 6).split('');
          digits.forEach((d, i) => {
            if (otpBoxes[i]) {
              otpBoxes[i].value = d;
              otpBoxes[i].classList.add('filled');
            }
          });
          const targetIdx = Math.min(digits.length, 5);
          otpBoxes[targetIdx].focus();
          updateFullOtpValue();

          if (digits.length === 6) {
            submitOtpVerification();
          }
        }
      });
    });

    function updateFullOtpValue() {
      let fullOtp = '';
      otpBoxes.forEach(b => { fullOtp += b.value; });
      document.getElementById('full-otp-value').value = fullOtp;
      return fullOtp;
    }

    // STEP 1: Submit Credentials
    async function submitCredentials() {
      const identifier = document.getElementById('login-identifier').value.trim();
      const password   = document.getElementById('login-password').value;

      const btn        = document.getElementById('credentials-submit-btn');
      const btnText    = document.getElementById('btn-login-text');
      const btnArrow   = document.getElementById('btn-login-arrow');
      const btnSpinner = document.getElementById('btn-login-spinner');

      if (!identifier || !password) {
        showAlert('Please provide both institutional email/ID and password.');
        return;
      }

      btn.disabled = true;
      btnText.textContent = 'Verifying Credentials...';
      if (btnArrow) btnArrow.style.display = 'none';
      if (btnSpinner) btnSpinner.style.display = 'inline-block';
      hideAlert();

      try {
        const res = await fetch('"/dummy"', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            identifier, 
            password
          })
        });

        const data = await res.json();

        if (res.ok && (data.status === 'authenticated' || data.status === 'success')) {
          showAlert(data.message || 'Authentication successful! Loading workspace...', false);
          
          if (typeof APP !== 'undefined' && APP.showLoadingScreen) {
            APP.showLoadingScreen({
              title: 'Authenticating Workspace...',
              subtitle: 'Redirecting to your dashboard...'
            });
          }

          setTimeout(() => {
            window.location.href = data.redirect_url;
          }, 300);
        } else if (res.ok && data.status === 'otp_required') {
          showOtpStep(data.masked_email);
        } else {
          showAlert(data.message || 'Invalid institutional ID/email or password.');
        }
      } catch (err) {
        console.error('Login error:', err);
        showAlert('Authentication service encountered an error. Please try again.');
      } finally {
        btn.disabled = false;
        btnText.textContent = 'Sign In';
        if (btnArrow) btnArrow.style.display = 'inline-block';
        if (btnSpinner) btnSpinner.style.display = 'none';
      }
    }

    // STEP 2: Submit OTP Verification
    async function submitOtpVerification() {
      const otp = updateFullOtpValue();
      const rememberMe = document.getElementById('otp-remember-me') ? document.getElementById('otp-remember-me').checked : true;
      const btn        = document.getElementById('otp-submit-btn');
      const btnText    = document.getElementById('btn-otp-text');
      const btnArrow   = document.getElementById('btn-otp-arrow');
      const btnSpinner = document.getElementById('btn-otp-spinner');

      if (!otp || otp.length < 6) {
        showAlert('Please enter the complete 6-digit verification code.');
        return;
      }

      btn.disabled = true;
      btnText.textContent = 'Verifying Code...';
      if (btnArrow) btnArrow.style.display = 'none';
      if (btnSpinner) btnSpinner.style.display = 'inline-block';
      hideAlert();

      try {
        const res = await fetch('"/dummy"', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            otp,
            remember_me: rememberMe
          })
        });

        const data = await res.json();

        if (res.ok && (data.status === 'success' || data.status === 'authenticated')) {
          showAlert(data.message || 'Verification successful! Loading dashboard...', false);
          
          if (typeof APP !== 'undefined' && APP.showLoadingScreen) {
            APP.showLoadingScreen({
              title: 'Authenticating Workspace...',
              subtitle: 'Redirecting to your dashboard...'
            });
          }

          setTimeout(() => {
            window.location.href = data.redirect_url;
          }, 350);
        } else {
          showAlert(data.message || 'Invalid or expired verification code.');
          btn.disabled = false;
          btnText.textContent = 'Verify & Continue';
          if (btnArrow) btnArrow.style.display = 'inline-block';
          if (btnSpinner) btnSpinner.style.display = 'none';
        }
      } catch (err) {
        console.error('OTP verify error:', err);
        showAlert('Security service error. Please try again.');
        btn.disabled = false;
        btnText.textContent = 'Verify & Continue';
        if (btnArrow) btnArrow.style.display = 'inline-block';
        if (btnSpinner) btnSpinner.style.display = 'none';
      }
    }

    // Resend OTP
    async function handleResendOtp() {
      const btn = document.getElementById('resend-otp-btn');
      btn.disabled = true;
      hideAlert();

      try {
        const res = await fetch('"/dummy"', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });

        const data = await res.json();
        if (res.ok && data.status === 'success') {
          showAlert(`A fresh verification code has been sent to ${data.masked_email}.`, false);
          const otpBoxes = document.querySelectorAll('.otp-digit-box');
          otpBoxes.forEach(b => { b.value = ''; b.classList.remove('filled'); });
          updateFullOtpValue();
          if (otpBoxes[0]) otpBoxes[0].focus();
          startResendCountdown();
        } else {
          showAlert(data.message || 'Unable to resend code at this time.');
          btn.disabled = false;
        }
      } catch (err) {
        showAlert('Failed to connect to authentication server.');
        btn.disabled = false;
      }
    }

    // ── FORGOT PASSWORD MODAL HANDLERS ──────────────────────
    function openForgotPasswordModal() {
      hideAlert();
      hideModalAlert();
      resetActiveEmail = '';
      resetActiveOtp = '';
      document.getElementById('modal-step-email').style.display = 'block';
      document.getElementById('modal-step-otp').style.display = 'none';
      document.getElementById('modal-step-new-pass').style.display = 'none';

      document.getElementById('reset-email-input').value = document.getElementById('login-identifier').value || '';
      document.getElementById('reset-otp-input').value = '';
      document.getElementById('new-pass-input').value = '';
      document.getElementById('confirm-pass-input').value = '';

      if (modalResendInterval) clearInterval(modalResendInterval);

      document.getElementById('forgot-password-modal').classList.add('open');
    }

    function closeForgotPasswordModal() {
      document.getElementById('forgot-password-modal').classList.remove('open');
      hideModalAlert();
      if (modalResendInterval) clearInterval(modalResendInterval);
    }

    // Modal Step 1: Send Reset OTP
    async function handleSendResetOtp() {
      const email = document.getElementById('reset-email-input').value.trim();
      const btn = document.getElementById('btn-send-reset-otp');
      const text = document.getElementById('btn-send-reset-text');
      const spinner = document.getElementById('btn-send-reset-spinner');

      if (!email) {
        showModalAlert('Please enter your institutional email address.');
        return;
      }

      btn.disabled = true;
      text.textContent = 'Sending Code...';
      if (spinner) spinner.style.display = 'inline-block';
      hideModalAlert();

      try {
        const res = await fetch('"/dummy"', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ email })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
          resetActiveEmail = email;
          document.getElementById('modal-masked-email').textContent = data.masked_email || email;
          document.getElementById('modal-step-email').style.display = 'none';
          document.getElementById('modal-step-otp').style.display = 'block';
          document.getElementById('modal-step-new-pass').style.display = 'none';
          document.getElementById('reset-otp-input').value = '';
          document.getElementById('reset-otp-input').focus();
          
          startModalResendCountdown();
          showModalAlert(`Verification code sent to ${data.masked_email}. Please check your inbox.`, false);
        } else {
          showModalAlert(data.message || 'No user account found with this email.');
        }
      } catch (err) {
        showModalAlert('Connection error. Please try again.');
      } finally {
        btn.disabled = false;
        text.textContent = 'Send Verification Code';
        if (spinner) spinner.style.display = 'none';
      }
    }

    // Modal Step 2: Go back to Email Step
    function backToResetEmailStep() {
      hideModalAlert();
      document.getElementById('modal-step-otp').style.display = 'none';
      document.getElementById('modal-step-new-pass').style.display = 'none';
      document.getElementById('modal-step-email').style.display = 'block';
      document.getElementById('reset-email-input').focus();
    }

    // Modal Step 2: Resend Reset OTP
    async function handleResendResetOtp() {
      const btn = document.getElementById('modal-resend-btn');
      if (!resetActiveEmail) return;

      btn.disabled = true;
      hideModalAlert();

      try {
        const res = await fetch('"/dummy"', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ email: resetActiveEmail })
        });

        const data = await res.json();
        if (res.ok && data.status === 'success') {
          document.getElementById('reset-otp-input').value = '';
          document.getElementById('reset-otp-input').focus();
          showModalAlert(`A fresh verification code has been sent to ${data.masked_email}.`, false);
          startModalResendCountdown();
        } else {
          showModalAlert(data.message || 'Unable to resend code.');
          btn.disabled = false;
        }
      } catch (err) {
        showModalAlert('Connection error resending code.');
        btn.disabled = false;
      }
    }

    function startModalResendCountdown() {
      if (modalResendInterval) clearInterval(modalResendInterval);
      modalResendCountdown = 60;
      const resendBtn = document.getElementById('modal-resend-btn');
      const timerText = document.getElementById('modal-resend-timer');

      resendBtn.disabled = true;
      timerText.textContent = `(${modalResendCountdown}s)`;

      modalResendInterval = setInterval(() => {
        modalResendCountdown--;
        if (modalResendCountdown <= 0) {
          clearInterval(modalResendInterval);
          resendBtn.disabled = false;
          timerText.textContent = '';
        } else {
          timerText.textContent = `(${modalResendCountdown}s)`;
        }
      }, 1000);
    }

    // Modal Step 2: Verify OTP
    async function handleVerifyResetOtp() {
      const otp = document.getElementById('reset-otp-input').value.trim();
      const btn = document.getElementById('btn-verify-reset-otp');
      const text = document.getElementById('btn-verify-otp-text');
      const spinner = document.getElementById('btn-verify-otp-spinner');

      if (!otp || otp.length < 6) {
        showModalAlert('Please enter the full 6-digit verification code.');
        return;
      }

      btn.disabled = true;
      text.textContent = 'Verifying Code...';
      if (spinner) spinner.style.display = 'inline-block';
      hideModalAlert();

      try {
        const res = await fetch('"/dummy"', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            email: resetActiveEmail,
            otp: otp
          })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
          resetActiveOtp = otp;
          // Transition to Step 3 (New Password + Confirm Password)
          document.getElementById('modal-verified-email').textContent = resetActiveEmail;
          document.getElementById('modal-step-email').style.display = 'none';
          document.getElementById('modal-step-otp').style.display = 'none';
          document.getElementById('modal-step-new-pass').style.display = 'block';

          document.getElementById('new-pass-input').value = '';
          document.getElementById('confirm-pass-input').value = '';
          document.getElementById('new-pass-input').focus();
          validatePasswordLive();

          showModalAlert('Code verified! Please create your new password.', false);
        } else {
          showModalAlert(data.message || 'Invalid or expired verification code.');
        }
      } catch (err) {
        showModalAlert('Verification service error. Please try again.');
      } finally {
        btn.disabled = false;
        text.textContent = 'Verify Code & Continue';
        if (spinner) spinner.style.display = 'none';
      }
    }

    // Live Password Strength Validation (Min 6 chars + 1 capital letter)
    function validatePasswordLive() {
      const pass = document.getElementById('new-pass-input').value;
      const ruleLength = document.getElementById('rule-length');
      const ruleCapital = document.getElementById('rule-capital');

      const isLongEnough = pass.length >= 6;
      const hasCapital = /[A-Z]/.test(pass);

      if (isLongEnough) {
        ruleLength.classList.add('valid');
        ruleLength.querySelector('.rule-bullet').textContent = '✓';
      } else {
        ruleLength.classList.remove('valid');
        ruleLength.querySelector('.rule-bullet').textContent = '•';
      }

      if (hasCapital) {
        ruleCapital.classList.add('valid');
        ruleCapital.querySelector('.rule-bullet').textContent = '✓';
      } else {
        ruleCapital.classList.remove('valid');
        ruleCapital.querySelector('.rule-bullet').textContent = '•';
      }

      return isLongEnough && hasCapital;
    }

    // Modal Step 3: Save New Password (after OTP is verified)
    async function handleUpdateNewPassword() {
      const otp = resetActiveOtp || document.getElementById('reset-otp-input').value.trim();
      const newPassword = document.getElementById('new-pass-input').value;
      const confirmPass = document.getElementById('confirm-pass-input').value;

      const btn = document.getElementById('btn-save-new-pass');
      const text = document.getElementById('btn-save-pass-text');
      const spinner = document.getElementById('btn-save-pass-spinner');

      if (!validatePasswordLive()) {
        showModalAlert('Password must be at least 6 characters and contain at least 1 capital letter.');
        return;
      }

      if (newPassword !== confirmPass) {
        showModalAlert('Passwords do not match. Please re-check.');
        return;
      }

      btn.disabled = true;
      text.textContent = 'Updating Password...';
      if (spinner) spinner.style.display = 'inline-block';
      hideModalAlert();

      try {
        const res = await fetch('"/dummy"', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            email: resetActiveEmail,
            otp: otp,
            new_password: newPassword,
            confirm_password: confirmPass
          })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
          closeForgotPasswordModal();
          showAlert('Password successfully updated! You can now sign in with your new password.', false);
          document.getElementById('login-identifier').value = resetActiveEmail;
          document.getElementById('login-password').value = newPassword;
        } else {
          showModalAlert(data.message || 'Invalid or expired OTP code.');
        }
      } catch (err) {
        showModalAlert('Server error updating password.');
      } finally {
        btn.disabled = false;
        text.textContent = 'Save Password & Sign In';
        if (spinner) spinner.style.display = 'none';
      }
    }
  