/**
 * useTour — thin wrapper around intro.js v8
 *
 * Usage:
 *   const { startTour } = useTour(steps)
 *
 * Each step object:
 *   { element: '#css-selector', title: 'Judul', intro: 'Penjelasan' }
 *   element is optional (floating tooltip if omitted)
 */
import introJs from 'intro.js';
import 'intro.js/introjs.css';

export function useTour(steps) {
  const startTour = () => {
    const tour = introJs();

    tour.setOptions({
      steps: steps.map((s) => ({
        element: s.element ? document.querySelector(s.element) : null,
        title: s.title || '',
        intro: s.intro || '',
      })),
      nextLabel: 'Lanjut →',
      prevLabel: '← Kembali',
      doneLabel: '✓ Selesai',
      skipLabel: '×',
      showProgress: true,
      showBullets: false,
      exitOnOverlayClick: true,
      scrollToElement: true,
      scrollPadding: 30,
      disableInteraction: false,
      tooltipClass: 'salma-tooltip',
      highlightClass: 'salma-highlight',
      buttonClass: 'salma-tour-btn',
    });

    // Scroll to top first so the first tooltip renders in the visible viewport.
    // We use requestAnimationFrame after an instant scroll so intro.js can
    // measure element positions correctly.
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    if (scrollTop > 0) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
      // Wait long enough for the smooth scroll to finish (~400ms) then start
      setTimeout(() => tour.start(), 420);
    } else {
      tour.start();
    }
  };

  return { startTour };
}
