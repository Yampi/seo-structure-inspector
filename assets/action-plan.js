window.addEventListener('beforeprint', function() {
    var btn = document.getElementById('print-btn');
    if (btn) btn.style.display = 'none';
});
window.addEventListener('afterprint', function() {
    var btn = document.getElementById('print-btn');
    if (btn) btn.style.display = 'inline-block';
});
