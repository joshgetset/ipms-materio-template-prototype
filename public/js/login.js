document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.login-container');
  const modal = document.getElementById('loadingModal');
  const statusText = document.getElementById('loadingText');
  const authGlobalMessage = document.querySelector('.auth-global-message');

  const hideLoadingModal = () => {
    if (!modal) {
      return;
    }

    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
  };

  const showLoadingModal = form => {
    if (!modal || !statusText) {
      return;
    }

    const buttonText = form.querySelector('button[type="submit"]')?.textContent?.trim();
    statusText.textContent = form.dataset.loadingText || buttonText || 'Loading...';
    modal.classList.add('is-visible');
    modal.setAttribute('aria-hidden', 'false');
  };

  const showAuthGlobalMessage = (message, type = 'error') => {
    const existingMessage = document.querySelector('.auth-global-message');

    if (existingMessage) {
      existingMessage.remove();
    }

    const messageElement = document.createElement('div');
    messageElement.className = `auth-global-message auth-global-message-${type}`;
    messageElement.setAttribute('role', 'alert');
    messageElement.setAttribute('aria-live', 'assertive');
    messageElement.textContent = message;
    document.body.appendChild(messageElement);

    window.setTimeout(() => {
      messageElement.classList.add('is-closing');
      window.setTimeout(() => messageElement.remove(), 260);
    }, 4000);
  };

  if (authGlobalMessage) {
    hideLoadingModal();

    const closeAuthMessage = () => {
      authGlobalMessage.classList.add('is-closing');

      window.setTimeout(() => {
        authGlobalMessage.remove();
      }, 260);
    };

    window.setTimeout(closeAuthMessage, 4000);
  }

  if (!shell) {
    return;
  }

  const cards = Array.from(document.querySelectorAll('.auth-card'));

  const setActiveCard = targetMode => {
    const mode = targetMode === 'signup' ? 'signup' : 'login';

    shell.classList.toggle('signup-mode', mode === 'signup');
    shell.classList.toggle('is-signup', mode === 'signup');

    cards.forEach(card => {
      const isActive = card.dataset.authCard === mode;

      card.classList.toggle('is-active', isActive);
      card.setAttribute('aria-hidden', String(!isActive));
    });

    if (window.history && window.history.replaceState) {
      const url = new URL(window.location.href);

      if (mode === 'signup') {
        url.searchParams.set('mode', 'signup');
      } else {
        url.searchParams.delete('mode');
      }

      window.history.replaceState({}, '', url);
    }
  };

  const toggleButtons = document.querySelectorAll('[data-auth-toggle]');

  toggleButtons.forEach(button => {
    button.addEventListener('click', event => {
      event.preventDefault();
      const nextMode = button.dataset.authToggle === 'signup' ? 'signup' : 'login';
      setActiveCard(nextMode);
    });
  });

  const passwordButtons = document.querySelectorAll('[data-password-toggle]');

  passwordButtons.forEach(button => {
    const fieldId = button.dataset.passwordToggle;
    const input = document.getElementById(fieldId);

    if (!input) {
      return;
    }

    const updateButtonState = showPassword => {
      button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
      button.classList.toggle('is-visible', showPassword);
    };

    button.addEventListener('click', () => {
      const shouldShowPassword = input.type === 'password';

      input.type = shouldShowPassword ? 'text' : 'password';
      updateButtonState(shouldShowPassword);
    });

    updateButtonState(input.type === 'text');
  });

  const activeCard = document.querySelector('.auth-card.is-active');

  if (activeCard) {
    setActiveCard(activeCard.dataset.authCard || 'login');
  }

  const getPasswordStrength = value => {
    const trimmed = value.trim();
    let score = 0;

    if (trimmed.length > 8) {
      score += 1;
    }

    if (/[a-z]/.test(trimmed)) {
      score += 1;
    }

    if (/[A-Z]/.test(trimmed)) {
      score += 1;
    }

    if (/[0-9]/.test(trimmed)) {
      score += 1;
    }

    if (/[^A-Za-z0-9]/.test(trimmed)) {
      score += 1;
    }

    const isStrong =
      trimmed.length > 8 && /[a-z]/.test(trimmed) && /[A-Z]/.test(trimmed) && /[^A-Za-z0-9]/.test(trimmed);

    if (isStrong) {
      return { level: 'strong', label: 'Strong' };
    }

    if (trimmed.length >= 8 && score >= 4) {
      return { level: 'medium', label: 'Medium' };
    }

    return { level: 'weak', label: 'Weak' };
  };

  const passwordStrength = document.querySelector('[data-password-strength]');
  const passwordInput = document.getElementById('signup_password');

  if (passwordStrength && passwordInput) {
    const fill = passwordStrength.querySelector('[data-password-strength-fill]');
    const status = passwordStrength.querySelector('[data-password-strength-status]');

    const setStrengthVisibility = isVisible => {
      passwordStrength.hidden = !isVisible;
      if (!isVisible && fill) {
        fill.style.width = '0%';
      }
    };

    const updatePasswordStrength = () => {
      const value = passwordInput.value;

      if (!value.trim()) {
        setStrengthVisibility(false);
        return;
      }

      setStrengthVisibility(true);

      const strength = getPasswordStrength(value);
      passwordStrength.dataset.strength = strength.level;
      status.textContent = strength.label;

      if (fill) {
        fill.style.width = strength.level === 'weak' ? '33%' : strength.level === 'medium' ? '66%' : '100%';
      }
    };

    setStrengthVisibility(false);
    passwordInput.addEventListener('input', updatePasswordStrength);
  }

  const confirmPasswordInput = document.getElementById('signup_password_confirmation');
  const confirmPasswordStatus = document.querySelector('[data-password-confirmation-status]');
  let updatePasswordConfirmation = () => {};

  if (passwordInput && confirmPasswordInput && confirmPasswordStatus) {
    updatePasswordConfirmation = () => {
      const passwordValue = passwordInput.value;
      const confirmValue = confirmPasswordInput.value;

      if (!confirmValue.length) {
        confirmPasswordStatus.textContent = '';
        confirmPasswordStatus.hidden = true;
        confirmPasswordStatus.classList.remove('is-match', 'is-mismatch');
        return;
      }

      if (passwordValue === confirmValue) {
        confirmPasswordStatus.textContent = 'Passwords match';
        confirmPasswordStatus.classList.remove('is-mismatch');
        confirmPasswordStatus.classList.add('is-match');
      } else {
        confirmPasswordStatus.textContent = 'Password do not match';
        confirmPasswordStatus.classList.remove('is-match');
        confirmPasswordStatus.classList.add('is-mismatch');
      }

      confirmPasswordStatus.hidden = false;
    };

    passwordInput.addEventListener('input', updatePasswordConfirmation);
    confirmPasswordInput.addEventListener('input', updatePasswordConfirmation);
  }

  const signupForm = document.querySelector('.signup-form');

  const validateSignupPasswordFields = () => {
    if (!passwordInput || !confirmPasswordInput) {
      return true;
    }

    const passwordValue = passwordInput.value;
    const confirmValue = confirmPasswordInput.value;
    const strength = getPasswordStrength(passwordValue);

    if (!passwordValue) {
      passwordInput.setCustomValidity('');
    } else if (strength.level !== 'strong') {
      passwordInput.setCustomValidity(
        'Password must be more than 8 characters and include uppercase, lowercase, and a special character.'
      );
    } else {
      passwordInput.setCustomValidity('');
    }

    if (!confirmValue) {
      confirmPasswordInput.setCustomValidity('');
    } else if (passwordValue !== confirmValue) {
      confirmPasswordInput.setCustomValidity('Password do not match');
    } else {
      confirmPasswordInput.setCustomValidity('');
    }

    updatePasswordConfirmation?.();

    return passwordInput.checkValidity() && confirmPasswordInput.checkValidity();
  };

  if (passwordInput) {
    passwordInput.addEventListener('input', validateSignupPasswordFields);
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', validateSignupPasswordFields);
  }

  if (signupForm && passwordInput && confirmPasswordInput) {
    signupForm.addEventListener('submit', event => {
      const passwordValue = passwordInput.value;
      const confirmValue = confirmPasswordInput.value;
      const strength = getPasswordStrength(passwordValue);

      const requiredFields = signupForm.querySelectorAll('input[required]');
      const firstEmpty = Array.from(requiredFields).find(input => !input.value.trim());

      if (firstEmpty) {
        event.preventDefault();
        firstEmpty.focus();
        showAuthGlobalMessage('Please complete all required fields before creating your account.', 'error');
        return;
      }

      if (strength.level !== 'strong' || passwordValue !== confirmValue) {
        event.preventDefault();

        if (strength.level !== 'strong') {
          showAuthGlobalMessage(
            'Password must be more than 8 characters and include uppercase, lowercase, and a special character.',
            'error'
          );
        } else if (passwordValue !== confirmValue) {
          showAuthGlobalMessage('Password do not match.', 'error');
        }

        updatePasswordConfirmation?.();
        return;
      }
    });
  }

  const loadingForms = document.querySelectorAll('[data-loading-form]');

  loadingForms.forEach(form => {
    form.addEventListener('submit', event => {
      if (event.defaultPrevented) {
        hideLoadingModal();
        return;
      }

      const requiredFields = form.querySelectorAll('input[required]');
      const firstEmpty = Array.from(requiredFields).find(input => !input.value.trim());

      if (firstEmpty) {
        event.preventDefault();
        firstEmpty.focus();
        hideLoadingModal();
        showAuthGlobalMessage('Please complete all required fields before continuing.', 'error');
        return;
      }

      showLoadingModal(form);
    });
  });

  window.addEventListener('pageshow', hideLoadingModal);
});
