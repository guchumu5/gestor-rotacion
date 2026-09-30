(function () {
  'use strict';

  function isStandalone() {
    if (window.navigator && window.navigator.standalone === true) {
      return true;
    }
    try {
      return window.matchMedia('(display-mode: standalone)').matches;
    } catch (e) {
      return false;
    }
  }

  function setStandaloneCookie(on) {
    var maxAge = on ? 60 * 60 * 24 * 400 : 0;
    var value = on ? '1' : '';
    document.cookie =
      'gr_standalone=' +
      value +
      '; path=/; max-age=' +
      maxAge +
      '; SameSite=Lax';
  }

  function ensureStandaloneField(form) {
    if (!form || form.tagName !== 'FORM') return;
    var existing = form.querySelector('input[name="standalone"]');
    if (existing) {
      existing.value = '1';
      return;
    }
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'standalone';
    input.value = '1';
    form.appendChild(input);
  }

  function shouldInterceptLink(anchor) {
    if (!anchor || !anchor.href) return false;
    if (anchor.hasAttribute('download')) return false;
    var target = (anchor.getAttribute('target') || '').toLowerCase();
    if (target === '_blank') return false;

    var hrefAttr = (anchor.getAttribute('href') || '').trim();
    if (!hrefAttr || hrefAttr.charAt(0) === '#') return false;
    if (/^(mailto:|tel:|sms:|javascript:)/i.test(hrefAttr)) return false;

    var url;
    try {
      url = new URL(anchor.href, window.location.href);
    } catch (e) {
      return false;
    }
    if (url.origin !== window.location.origin) return false;
    return true;
  }

  if (isStandalone()) {
    setStandaloneCookie(true);

    document.addEventListener(
      'click',
      function (event) {
        if (event.defaultPrevented) return;
        if (event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        var node = event.target;
        while (node && node.nodeType === 1 && node.tagName !== 'A') {
          node = node.parentElement;
        }
        if (!node || node.tagName !== 'A') return;
        if (!shouldInterceptLink(node)) return;

        event.preventDefault();
        window.location.assign(node.href);
      },
      true
    );

    document.querySelectorAll('form').forEach(ensureStandaloneField);
    document.addEventListener(
      'submit',
      function (event) {
        if (event.target && event.target.tagName === 'FORM') {
          ensureStandaloneField(event.target);
        }
      },
      true
    );
  } else {
    setStandaloneCookie(false);
  }

  // Login: fetch + location.replace para no salir del standalone iOS tras el 302.
  var loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var alertBox = document.getElementById('login-error');
      var submitBtn = loginForm.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      var body = new FormData(loginForm);
      body.set('ajax', '1');
      if (isStandalone()) {
        body.set('standalone', '1');
      }

      fetch(loginForm.getAttribute('action') || window.location.href, {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      })
        .then(function (res) {
          return res.json().then(
            function (data) {
              return { okHttp: res.ok, data: data };
            },
            function () {
              throw new Error('Respuesta no válida');
            }
          );
        })
        .then(function (result) {
          if (result.data && result.data.ok) {
            window.location.replace(result.data.redirect || 'index.php');
            return;
          }
          var msg =
            (result.data && result.data.error) || 'Contraseña incorrecta.';
          if (alertBox) {
            alertBox.textContent = msg;
            alertBox.hidden = false;
          } else {
            window.alert(msg);
          }
          if (submitBtn) submitBtn.disabled = false;
        })
        .catch(function () {
          // Sin JS usable / error de red: envío clásico (fallback).
          loginForm.submit();
        });
    });
  }

  function parseNumber(value) {
    var normalized = String(value || '').replace(',', '.').trim();
    var n = Number(normalized);
    return Number.isFinite(n) ? n : NaN;
  }

  document.querySelectorAll('[data-qty-step]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-qty-target');
      var input = document.getElementById(targetId);
      if (!input) return;
      var delta = Number(btn.getAttribute('data-qty-step')) || 0;
      var current = parseNumber(input.value);
      var next = Math.max(1, (Number.isFinite(current) ? current : 1) + delta);
      input.value = String(next);
      input.focus();
    });
  });

  var modeExisting = document.getElementById('mode-existing');
  var modeNew = document.getElementById('mode-new');
  var blockExisting = document.getElementById('client-existing-block');
  var blockNew = document.getElementById('client-new-block');
  var clientModeInput = document.getElementById('client_mode');

  function setClientMode(mode) {
    if (!blockExisting || !blockNew || !clientModeInput) return;
    var isNew = mode === 'new';
    blockExisting.hidden = isNew;
    blockNew.hidden = !isNew;
    clientModeInput.value = isNew ? 'new' : 'existing';
    if (modeExisting && modeNew) {
      modeExisting.classList.toggle('btn-brand', !isNew);
      modeExisting.classList.toggle('btn-outline-brand', isNew);
      modeNew.classList.toggle('btn-brand', isNew);
      modeNew.classList.toggle('btn-outline-brand', !isNew);
    }
    if (isNew) {
      var nameInput = document.getElementById('new_client_name');
      if (nameInput) nameInput.focus();
    }
  }

  if (modeExisting) {
    modeExisting.addEventListener('click', function () {
      setClientMode('existing');
    });
  }
  if (modeNew) {
    modeNew.addEventListener('click', function () {
      setClientMode('new');
    });
  }

  document.querySelectorAll('[data-product-chip]').forEach(function (chip) {
    chip.addEventListener('click', function () {
      var select = document.getElementById('product_id');
      var value = chip.getAttribute('data-product-chip');
      if (select && value) {
        select.value = value;
      }
      document.querySelectorAll('[data-product-chip]').forEach(function (c) {
        c.classList.toggle('is-active', c === chip);
      });
    });
  });

  var noteToggle = document.getElementById('toggle-note');
  var noteBlock = document.getElementById('note-block');
  if (noteToggle && noteBlock) {
    noteToggle.addEventListener('click', function () {
      noteBlock.hidden = !noteBlock.hidden;
      if (!noteBlock.hidden) {
        var note = document.getElementById('note');
        if (note) note.focus();
      }
    });
  }
})();
