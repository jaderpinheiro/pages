/* T Imóveis — Admin JS */

function tiMaskMoney(el) {
  var digits = el.value.replace(/\D/g, '');
  if (!digits) { el.value = ''; return; }
  var cents = parseInt(digits, 10);
  var val = (cents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  el.value = 'R$ ' + val;
}

function tiInitMoneyField(el) {
  var raw = parseFloat(el.value);
  if (!isNaN(raw) && raw > 0) {
    el.value = 'R$ ' + raw.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
}

jQuery(function ($) {
  document.querySelectorAll('.ti-money').forEach(tiInitMoneyField);
  // Kanban drag highlight
  document.querySelectorAll('.ti-col-body').forEach(function (col) {
    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      col.style.background = '#EEF2FF';
    });
    col.addEventListener('dragleave', function () {
      col.style.background = '';
    });
    col.addEventListener('drop', function () {
      col.style.background = '';
    });
  });

  document.querySelectorAll('.ti-lead-card').forEach(function (card) {
    card.addEventListener('dragstart', function () { card.classList.add('dragging'); });
    card.addEventListener('dragend',   function () { card.classList.remove('dragging'); });
  });

  // Fechar modal com ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var m = document.getElementById('tiLeadModal');
      if (m) m.style.display = 'none';
    }
  });
});
