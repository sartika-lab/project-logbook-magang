const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');

if (menuToggle && sidebar) {
  menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', (event) => {
    if (
      window.innerWidth <= 800 &&
      sidebar.classList.contains('open') &&
      !sidebar.contains(event.target) &&
      !menuToggle.contains(event.target)
    ) {
      sidebar.classList.remove('open');
    }
  });
}

if (sidebar) {
  const sectionLinks = Array.from(sidebar.querySelectorAll('[data-sidebar-anchor]'));
  const dashboardLink = sidebar.querySelector('.nav-link[href="index.php"]');
  const filterLink = sectionLinks.find((link) => link.dataset.sidebarAnchor === 'filter-kegiatan');
  const breadcrumbPageName = document.getElementById('breadcrumbPageName');
  const sectionNames = {
    'daftar-kegiatan': 'Dokumentasi Kegiatan',
    'filter-kegiatan': 'Filter & Rekap',
  };

  function updateSidebarSection() {
    if (window.location.pathname.split('/').pop() !== 'index.php') return;

    const activeSection = window.location.hash.slice(1);
    const activeLink = sectionLinks.find((link) => link.dataset.sidebarAnchor === activeSection);
    sidebar.querySelectorAll('.nav-link').forEach((link) => {
      link.classList.remove('active');
      link.removeAttribute('aria-current');
    });

    const currentLink = activeLink || (window.location.search ? filterLink : dashboardLink);
    currentLink?.classList.add('active');
    currentLink?.setAttribute('aria-current', 'page');
    if (breadcrumbPageName) {
      breadcrumbPageName.textContent = sectionNames[activeSection] || 'Dashboard';
    }
  }

  sectionLinks.forEach((link) => {
    link.addEventListener('click', () => {
      sidebar.querySelectorAll('.nav-link').forEach((item) => {
        item.classList.remove('active');
        item.removeAttribute('aria-current');
      });
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
      if (breadcrumbPageName) {
        breadcrumbPageName.textContent = sectionNames[link.dataset.sidebarAnchor] || 'Dashboard';
      }
    });
  });
  window.addEventListener('hashchange', updateSidebarSection);
  updateSidebarSection();
}

const detailModal = document.getElementById('detailToast');
const detailClose = document.getElementById('detailClose');
let previousFocus = null;
const photoLightbox = document.getElementById('photoLightbox');
const lightboxImage = document.getElementById('lightboxImage');
const lightboxCaption = document.getElementById('lightboxCaption');
const lightboxTitle = document.getElementById('lightboxTitle');
const lightboxDescription = document.getElementById('lightboxDescription');
const lightboxClose = document.getElementById('lightboxClose');
const lightboxPrev = document.getElementById('lightboxPrev');
const lightboxNext = document.getElementById('lightboxNext');
let lightboxPhotos = [];
let lightboxIndex = 0;
let lightboxPreviousFocus = null;

function showLightboxPhoto() {
  const photo = lightboxPhotos[lightboxIndex];
  if (!photo || !lightboxImage) return;
  lightboxImage.src = photo.src;
  lightboxImage.alt = photo.alt;
  lightboxTitle.textContent = photo.title || photo.alt;
  lightboxDescription.textContent = photo.description || '';
  lightboxDescription.hidden = !photo.description;
  lightboxCaption.textContent = `${lightboxIndex + 1} dari ${lightboxPhotos.length}`;
  const hasMultiple = lightboxPhotos.length > 1;
  lightboxPrev.hidden = !hasMultiple;
  lightboxNext.hidden = !hasMultiple;
}

function openLightbox(photos, index, trigger) {
  if (!photoLightbox || !photos.length) return;
  lightboxPhotos = photos;
  lightboxIndex = index;
  lightboxPreviousFocus = trigger;
  showLightboxPhoto();
  photoLightbox.hidden = false;
  lightboxClose.focus();
}

function closeLightbox() {
  if (!photoLightbox) return;
  photoLightbox.hidden = true;
  lightboxImage.src = '';
  if (lightboxPreviousFocus && typeof lightboxPreviousFocus.focus === 'function') {
    lightboxPreviousFocus.focus();
  }
}

function renderPhotos(container, photos, label, detail = {}) {
  container.replaceChildren();
  if (!photos || photos.length === 0) {
    const empty = document.createElement('p');
    empty.className = 'gallery-empty';
    empty.textContent = 'Belum ada foto.';
    container.appendChild(empty);
    return;
  }

  photos.forEach((photo, index) => {
    const image = document.createElement('img');
    image.src = `uploads/${encodeURIComponent(photo)}`;
    image.alt = `${label} ${index + 1}`;
    image.loading = 'lazy';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'photo-zoom';
    button.setAttribute('aria-label', `Perbesar ${image.alt}`);
    button.dataset.detailTitle = detail.title || image.alt;
    button.dataset.detailDescription = detail.description || '';
    button.addEventListener('click', () => {
      const galleryPhotos = Array.from(container.querySelectorAll('.photo-zoom img')).map((galleryImage) => ({
        src: galleryImage.src,
        alt: galleryImage.alt,
        title: galleryImage.closest('.photo-zoom')?.dataset.detailTitle || galleryImage.alt,
        description: galleryImage.closest('.photo-zoom')?.dataset.detailDescription || '',
      }));
      openLightbox(galleryPhotos, index, button);
    });
    button.appendChild(image);
    container.appendChild(button);
  });
}

document.querySelectorAll('.activity-thumbnails .photo-zoom, .attendance-thumbnails .photo-zoom').forEach((button) => {
  button.addEventListener('click', (event) => {
    event.stopPropagation();
    const gallery = button.closest('.attendance-photo-list, .activity-thumbnails');
    const buttons = Array.from(gallery.querySelectorAll('.photo-zoom'));
    const activityCard = button.closest('.day-activity-card');
    const dayCard = button.closest('.day-group-card');
    const activityTitle = activityCard?.querySelector('h5')?.textContent.trim();
    const activityDescription = activityCard?.querySelector(':scope > p')?.textContent.trim();
    const dayTitle = dayCard?.querySelector('.day-group-header h4')?.textContent.trim() || 'Absensi harian';
    const detailTitle = activityTitle || dayTitle;
    const detailDescription = activityDescription || 'Dokumentasi pendukung kegiatan magang.';
    const photos = buttons.map((galleryButton) => {
      const image = galleryButton.querySelector('img');
      return {
        src: image.src,
        alt: image.alt,
        title: detailTitle,
        description: detailDescription,
      };
    });
    openLightbox(photos, buttons.indexOf(button), button);
  });
});

lightboxClose?.addEventListener('click', closeLightbox);
lightboxPrev?.addEventListener('click', () => {
  lightboxIndex = (lightboxIndex - 1 + lightboxPhotos.length) % lightboxPhotos.length;
  showLightboxPhoto();
});
lightboxNext?.addEventListener('click', () => {
  lightboxIndex = (lightboxIndex + 1) % lightboxPhotos.length;
  showLightboxPhoto();
});
photoLightbox?.addEventListener('click', (event) => {
  if (event.target === photoLightbox) closeLightbox();
});

function openDetail(button) {
  if (!detailModal) return;
  let detail;
  try {
    detail = JSON.parse(button.dataset.detail || '{}');
  } catch (error) {
    console.error('Detail kegiatan tidak dapat dibaca.', error);
    return;
  }

  const activities = Array.isArray(detail.activities) ? detail.activities : [];
  document.getElementById('detailTitle').textContent = detail.dateLabel || 'Detail kegiatan';
  document.getElementById('detailDate').textContent = `${activities.length} kegiatan pada hari ini`;
  document.getElementById('detailMeta').textContent = '';
  document.getElementById('detailMeta').hidden = true;
  renderDayActivities(document.getElementById('detailActivities'), activities);
  renderPhotos(document.getElementById('detailCheckIn'), detail.checkIn, 'Absen masuk', {
    title: 'Absensi masuk · pagi',
    description: 'Dokumentasi kehadiran sebelum kegiatan magang dimulai.',
  });
  renderPhotos(document.getElementById('detailCheckOut'), detail.checkOut, 'Absen pulang', {
    title: 'Absensi pulang · sore',
    description: 'Dokumentasi kehadiran setelah kegiatan magang selesai.',
  });
  const hasCheckInPhotos = Array.isArray(detail.checkIn) && detail.checkIn.length > 0;
  const hasCheckOutPhotos = Array.isArray(detail.checkOut) && detail.checkOut.length > 0;
  document.getElementById('checkInColumn').hidden = !hasCheckInPhotos;
  document.getElementById('checkOutColumn').hidden = !hasCheckOutPhotos;
  document.getElementById('attendanceDetailSection').hidden = !hasCheckInPhotos && !hasCheckOutPhotos;

  previousFocus = document.activeElement;
  detailModal.hidden = false;
  document.getElementById('detailClose').focus();
}

function renderDayActivities(container, activities) {
  container.replaceChildren();
  activities.forEach((activity) => {
    const frame = document.createElement('section');
    frame.className = 'modal-activity-card';

    const topLine = document.createElement('div');
    topLine.className = 'modal-activity-topline';
    const category = document.createElement('span');
    category.className = 'category-pill';
    category.textContent = activity.category || 'Kegiatan';
    const time = document.createElement('span');
    time.className = 'day-activity-time';
    time.textContent = activity.time || '';
    topLine.append(category, time);

    const title = document.createElement('h4');
    title.textContent = activity.title || 'Kegiatan';
    const participants = document.createElement('p');
    participants.className = 'modal-activity-participants';
    participants.textContent = `Peserta: ${activity.participants || 'Belum tercatat'}`;
    const descriptionHeading = document.createElement('h5');
    descriptionHeading.textContent = 'Deskripsi pekerjaan';
    const description = document.createElement('p');
    description.textContent = activity.description || 'Belum ada deskripsi.';
    const learningHeading = document.createElement('h5');
    learningHeading.textContent = 'Hasil pembelajaran';
    const learning = document.createElement('p');
    learning.textContent = activity.learning || 'Belum ada hasil pembelajaran.';

    frame.append(topLine, title, participants, descriptionHeading, description, learningHeading, learning);
    if (Array.isArray(activity.photos) && activity.photos.length > 0) {
      const photoHeading = document.createElement('h5');
      photoHeading.textContent = 'Dokumentasi kegiatan';
      const gallery = document.createElement('div');
      gallery.className = 'detail-gallery';
      renderPhotos(gallery, activity.photos, `Dokumentasi ${activity.title || 'kegiatan'}`, {
        title: activity.title || 'Dokumentasi kegiatan',
        description: [activity.description, activity.learning ? `Hasil pembelajaran: ${activity.learning}` : '']
          .filter(Boolean)
          .join('\n\n'),
      });
      frame.append(photoHeading, gallery);
    }
    container.appendChild(frame);
  });
}

function closeDetail() {
  if (!detailModal) return;
  detailModal.hidden = true;
  if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
}

document.querySelectorAll('.day-detail-trigger').forEach((button) => {
  button.addEventListener('click', () => openDetail(button));
  const card = button.closest('.day-group-card');
  if (card) {
    card.addEventListener('click', (event) => {
      if (event.target.closest('button, a')) return;
      openDetail(button);
    });
    card.addEventListener('keydown', (event) => {
      if (event.target === card && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        openDetail(button);
      }
    });
  }
});

if (detailClose) detailClose.addEventListener('click', closeDetail);
if (detailModal) {
  detailModal.addEventListener('click', (event) => {
    if (event.target === detailModal) closeDetail();
  });
}
document.addEventListener('keydown', (event) => {
  if (photoLightbox && !photoLightbox.hidden) {
    if (event.key === 'Escape') closeLightbox();
    if (event.key === 'ArrowLeft' && lightboxPhotos.length > 1) {
      lightboxIndex = (lightboxIndex - 1 + lightboxPhotos.length) % lightboxPhotos.length;
      showLightboxPhoto();
    }
    if (event.key === 'ArrowRight' && lightboxPhotos.length > 1) {
      lightboxIndex = (lightboxIndex + 1) % lightboxPhotos.length;
      showLightboxPhoto();
    }
    return;
  }
  if (event.key === 'Escape' && detailModal && !detailModal.hidden) closeDetail();
});

const searchStart = document.getElementById('start');
const searchEnd = document.getElementById('end');
if (searchStart && searchEnd) {
  searchStart.addEventListener('change', () => {
    searchEnd.min = searchStart.value;
    if (searchEnd.value && searchEnd.value < searchStart.value) searchEnd.value = '';
  });
  searchEnd.addEventListener('change', () => {
    searchStart.max = searchEnd.value;
    if (searchStart.value && searchStart.value > searchEnd.value) searchStart.value = '';
  });
}

const rangeForm = document.getElementById('dateRangeForm');
const rangeStart = document.getElementById('rangeStart');
const rangeEnd = document.getElementById('rangeEnd');
const rangeInput = document.getElementById('dateRangePicker');
if (rangeForm && rangeStart && rangeEnd && rangeInput && typeof flatpickr !== 'undefined') {
  const initialDates = [rangeStart.value, rangeEnd.value]
    .filter(Boolean)
    .map((value) => {
      const [year, month, day] = value.split('-').map(Number);
      return new Date(year, month - 1, day);
    });
  flatpickr(rangeInput, {
    mode: 'range',
    dateFormat: 'j M Y',
    locale: 'id',
    disableMobile: true,
    defaultDate: initialDates,
    onChange(dates, _dateString, instance) {
      if (dates.length === 2) {
        rangeStart.value = instance.formatDate(dates[0], 'Y-m-d');
        rangeEnd.value = instance.formatDate(dates[1], 'Y-m-d');
        rangeForm.submit();
      }
    },
    onReady(_dates, _dateString, instance) {
      const clearButton = document.createElement('button');
      clearButton.type = 'button';
      clearButton.className = 'calendar-clear';
      clearButton.textContent = 'Hapus rentang';
      clearButton.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        instance.clear();
        rangeStart.value = '';
        rangeEnd.value = '';
        rangeForm.submit();
      });
      instance.calendarContainer.appendChild(clearButton);
    },
  });
}
