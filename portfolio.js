const portfolioModal = document.getElementById('portfolioDetailModal');
const portfolioClose = document.getElementById('portfolioDetailClose');
const portfolioTitle = document.getElementById('portfolioDetailTitle');
const portfolioDescription = document.getElementById('portfolioDetailDescription');
const portfolioImageWrap = document.getElementById('portfolioDetailImageWrap');
const portfolioImage = document.getElementById('portfolioDetailImage');
let portfolioPreviousFocus = null;
let previousBodyOverflow = '';

function openPortfolioDetail(card) {
  if (!portfolioModal) return;

  portfolioPreviousFocus = card;
  portfolioTitle.textContent = card.dataset.title || 'Detail hasil kerja';
  portfolioDescription.textContent = card.dataset.description || 'Belum ada deskripsi.';

  const imageSource = card.dataset.image || '';
  portfolioImageWrap.hidden = !imageSource;
  portfolioImage.src = imageSource;
  portfolioImage.alt = card.dataset.imageAlt || '';

  previousBodyOverflow = document.body.style.overflow;
  document.body.style.overflow = 'hidden';
  portfolioModal.hidden = false;
  portfolioClose.focus();
}

function closePortfolioDetail() {
  if (!portfolioModal || portfolioModal.hidden) return;

  portfolioModal.hidden = true;
  portfolioImage.removeAttribute('src');
  document.body.style.overflow = previousBodyOverflow;

  if (portfolioPreviousFocus && typeof portfolioPreviousFocus.focus === 'function') {
    portfolioPreviousFocus.focus();
  }
}

document.querySelectorAll('.portfolio-detail-trigger').forEach((card) => {
  card.addEventListener('click', () => openPortfolioDetail(card));
  card.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      openPortfolioDetail(card);
    }
  });
});

portfolioClose?.addEventListener('click', closePortfolioDetail);
portfolioModal?.addEventListener('click', (event) => {
  if (event.target === portfolioModal) closePortfolioDetail();
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && portfolioModal && !portfolioModal.hidden) {
    closePortfolioDetail();
  }
});
