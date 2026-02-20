(() => {
  const DEFAULT_DURATION = 700;

  const animateProgressBars = () => {
    const bars = document.querySelectorAll('[data-progress]');

    bars.forEach((container) => {
      const bar = container.querySelector('.progress__bar') || container;
      const rawTarget = Number(container.dataset.progress ?? bar.dataset.progress ?? 0);
      const target = Math.max(0, Math.min(100, rawTarget));
      const duration = Number(container.dataset.duration || DEFAULT_DURATION);
      const start = performance.now();

      const step = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const currentWidth = target * progress;
        bar.style.width = `${currentWidth}%`;

        if (progress < 1) {
          requestAnimationFrame(step);
        }
      };

      bar.style.width = '0%';
      requestAnimationFrame(step);
    });
  };

  const setupWordCounter = () => {
    const inputs = document.querySelectorAll('[data-word-count-input]');

    const countWords = (text) => {
      const trimmed = text.trim();
      return trimmed ? trimmed.split(/\s+/).length : 0;
    };

    inputs.forEach((input) => {
      const outputSelector = input.dataset.wordCountOutput;
      const output = outputSelector
        ? document.querySelector(outputSelector)
        : input.parentElement?.querySelector('[data-word-count-output]');

      if (!output) {
        return;
      }

      const update = () => {
        const words = countWords(input.value);
        output.textContent = String(words);
      };

      input.addEventListener('input', update);
      update();
    });
  };

  const setupImagePreview = () => {
    const inputs = document.querySelectorAll('input[type="file"][data-image-preview-input]');

    inputs.forEach((input) => {
      const targetSelector = input.dataset.imagePreviewTarget;
      const preview = targetSelector
        ? document.querySelector(targetSelector)
        : input.parentElement?.querySelector('[data-image-preview-target]');

      if (!preview || !(preview instanceof HTMLImageElement)) {
        return;
      }

      input.addEventListener('change', () => {
        const [file] = input.files || [];

        if (!file) {
          preview.removeAttribute('src');
          preview.classList.add('fade-hidden');
          return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
          const src = String(event.target?.result || '');
          preview.src = src;
          preview.classList.remove('fade-hidden');
          preview.classList.add('fade-visible');
        };
        reader.readAsDataURL(file);
      });
    });
  };

  const setupDeleteConfirmation = () => {
    const triggers = document.querySelectorAll('[data-confirm-delete]');

    triggers.forEach((trigger) => {
      trigger.addEventListener('click', (event) => {
        const message = trigger.getAttribute('data-confirm-message') ||
          'Möchten Sie diesen Eintrag wirklich löschen?';
        const confirmed = window.confirm(message);

        if (!confirmed) {
          event.preventDefault();
          event.stopPropagation();
        }
      });
    });
  };

  const setupNotificationDropdown = () => {
    const toggles = document.querySelectorAll('[data-notification-toggle]');

    toggles.forEach((toggle) => {
      const menuSelector = toggle.getAttribute('data-notification-target');
      const menu = menuSelector
        ? document.querySelector(menuSelector)
        : toggle.parentElement?.querySelector('.notification__menu');

      if (!menu) {
        return;
      }

      const closeMenu = () => {
        menu.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      };

      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const open = !menu.classList.contains('is-open');
        menu.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
      });

      document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Node)) {
          return;
        }

        if (!menu.contains(target) && !toggle.contains(target)) {
          closeMenu();
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          closeMenu();
        }
      });
    });
  };

  const updateBadges = () => {
    const badges = document.querySelectorAll('[data-badge-count]');

    badges.forEach((badge) => {
      const raw = badge.getAttribute('data-badge-count') ?? badge.textContent ?? '0';
      const value = Number.parseInt(raw, 10);
      const count = Number.isNaN(value) ? 0 : value;

      if (count <= 0) {
        badge.textContent = '0';
        badge.classList.add('fade-hidden');
        badge.setAttribute('aria-hidden', 'true');
      } else {
        badge.textContent = String(count);
        badge.classList.remove('fade-hidden');
        badge.classList.add('fade-visible');
        badge.removeAttribute('aria-hidden');
      }
    });
  };

  const setupFadeTransitions = () => {
    const fades = document.querySelectorAll('[data-fade-auto]');

    fades.forEach((element) => {
      const delay = Number(element.getAttribute('data-fade-delay') || 2500);
      element.classList.add('fade-transition', 'fade-visible');

      window.setTimeout(() => {
        element.classList.remove('fade-visible');
        element.classList.add('fade-hidden');
      }, delay);
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    animateProgressBars();
    setupWordCounter();
    setupImagePreview();
    setupDeleteConfirmation();
    setupNotificationDropdown();
    updateBadges();
    setupFadeTransitions();
  });
})();
