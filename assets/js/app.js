(function () {
  'use strict';

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
