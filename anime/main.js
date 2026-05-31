// ============================================================
// ANIME VAULT - Main JavaScript
// File: /var/www/html/anime/js/main.js
// ============================================================

(function () {
  'use strict';

  // ── Utility ─────────────────────────────────────────────────
  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return [...(ctx || document).querySelectorAll(sel)]; }

  // ── Navbar scroll shadow ────────────────────────────────────
  const navbar = $('.navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.style.boxShadow = window.scrollY > 10 ? '0 4px 30px rgba(0,0,0,0.4)' : '';
    }, { passive: true });
  }

  // ── Lazy-load images with fade-in ───────────────────────────
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.style.opacity = '0';
          img.style.transition = 'opacity 0.4s ease';
          img.addEventListener('load', () => { img.style.opacity = '1'; }, { once: true });
          if (img.dataset.src) img.src = img.dataset.src;
          observer.unobserve(img);
        }
      });
    }, { rootMargin: '100px' });

    $$('img[loading="lazy"]').forEach(img => observer.observe(img));
  }

  // ── Alert auto-dismiss ──────────────────────────────────────
  $$('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease, max-height 0.5s ease';
      alert.style.opacity = '0';
      alert.style.maxHeight = '0';
      alert.style.overflow = 'hidden';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  // ── Mobile nav toggle ───────────────────────────────────────
  const mobileToggle = $('#mobile-menu-toggle');
  const navMenu      = $('#mobile-nav-menu');
  if (mobileToggle && navMenu) {
    mobileToggle.addEventListener('click', () => {
      navMenu.classList.toggle('open');
      mobileToggle.setAttribute('aria-expanded', navMenu.classList.contains('open'));
    });
  }

  // ── Smooth scroll for anchor links ──────────────────────────
  $$('a[href^="#"]').forEach(link => {
    link.addEventListener('click', e => {
      const target = document.getElementById(link.getAttribute('href').slice(1));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ── Card hover glow ─────────────────────────────────────────
  $$('.anime-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-4px)';
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
    });
  });

  // ── Image poster placeholder on error ───────────────────────
  $$('img.anime-card-poster, img.anime-detail-poster').forEach(img => {
    img.addEventListener('error', () => {
      img.style.display = 'none';
      const ph = document.createElement('div');
      ph.className = img.className + '-placeholder';
      ph.style.cssText = 'display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--border);background:var(--bg-card);aspect-ratio:2/3;width:100%;';
      ph.textContent = '⛩';
      img.parentNode.insertBefore(ph, img);
    });
  });

  // ── Search input focus shortcut (press /) ───────────────────
  document.addEventListener('keydown', e => {
    if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) {
      e.preventDefault();
      const searchInput = $('input[name="q"]');
      if (searchInput) searchInput.focus();
    }
  });

  // ── Confirm dangerous actions ───────────────────────────────
  $$('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Anime card: prefetch on hover ───────────────────────────
  if ('requestIdleCallback' in window) {
    $$('.anime-card').forEach(card => {
      const link = card.closest('a[href]') || card.querySelector('a[href]');
      if (!link) return;
      card.addEventListener('mouseenter', () => {
        requestIdleCallback(() => {
          const prefetch = document.createElement('link');
          prefetch.rel  = 'prefetch';
          prefetch.href = link.href;
          document.head.appendChild(prefetch);
        });
      }, { once: true });
    });
  }

  // ── Toast notifications (triggered by URL params) ───────────
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('msg') === 'deleted') {
    showToast('Item deleted successfully.', 'success');
  }

  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;min-width:240px;animation:slideIn 0.3s ease;';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.4s';
      setTimeout(() => toast.remove(), 400);
    }, 3500);
  }

  // ── Simple star rating display enhancement ──────────────────
  $$('.star-rating-input').forEach(container => {
    const inputs = $$('input[type=radio]', container);
    const labels = $$('label', container);
    inputs.forEach(input => {
      input.addEventListener('change', () => {
        const val = parseInt(input.value);
        labels.forEach((label, i) => {
          // Labels are reversed (flex-direction:row-reverse)
          label.style.color = (labels.length - i) <= val ? 'var(--neon-gold)' : 'var(--border)';
        });
      });
    });
  });

})();
