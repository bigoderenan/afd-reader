document.addEventListener('click', (event) => {
  const btn = event.target.closest('[data-copy]');
  if (!btn) return;
  const value = btn.getAttribute('data-copy') || '';
  const popup = document.createElement('pre');
  popup.className = 'raw-popup';
  popup.textContent = value;
  popup.addEventListener('click', () => popup.remove());
  document.body.appendChild(popup);
});
