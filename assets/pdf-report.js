(function(){
  var btn = document.getElementById('themeToggle');
  if (!btn) return;
  var saved = localStorage.getItem('baloa_structure_auditor_seo_report_theme');
  var sysDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  if (saved === 'dark' || (!saved && sysDark)) document.body.classList.add('dark');
  btn.addEventListener('click', function(){
    var isDark = document.body.classList.toggle('dark');
    localStorage.setItem('baloa_structure_auditor_seo_report_theme', isDark ? 'dark' : 'light');
  });
})();
