/*
 * Google Reviews slider — vanilla ES module, no dependencies.
 * Progressive enhancement over a native CSS scroll-snap track: without JS the
 * track still scrolls; with JS you get arrows, dots, optional shuffle/autoplay.
 */
(function initGoogleReviewsSliders() {
    const bReduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const shuffleInPlace = (arNodeList) => {
        for (let iIndex = arNodeList.length - 1; iIndex > 0; iIndex--) {
            const iSwap = Math.floor(Math.random() * (iIndex + 1));
            [arNodeList[iIndex], arNodeList[iSwap]] = [arNodeList[iSwap], arNodeList[iIndex]];
        }
        return arNodeList;
    };

    const setupSlider = (obSlider) => {
        const obTrack = obSlider.querySelector('.gr-slider__track');
        const obDots = obSlider.querySelector('.gr-slider__dots');
        const obPrev = obSlider.querySelector('.gr-slider__nav--prev');
        const obNext = obSlider.querySelector('.gr-slider__nav--next');
        if (!obTrack) {
            return;
        }

        if (obSlider.hasAttribute('data-gr-shuffle')) {
            shuffleInPlace(Array.from(obTrack.children)).forEach((obSlide) => obTrack.appendChild(obSlide));
        }

        const arSlides = Array.from(obTrack.children);
        obSlider.setAttribute('data-gr-ready', '');

        const slideStep = () => {
            const obFirst = arSlides[0];
            if (!obFirst) {
                return obTrack.clientWidth;
            }
            const fGap = parseFloat(getComputedStyle(obTrack).columnGap || '0') || 0;
            return obFirst.getBoundingClientRect().width + fGap;
        };

        const activeIndex = () => Math.round(obTrack.scrollLeft / slideStep());

        const buildDots = () => {
            if (!obDots) {
                return [];
            }
            obDots.innerHTML = '';
            return arSlides.map((obSlide, iIndex) => {
                const obDot = document.createElement('button');
                obDot.type = 'button';
                obDot.className = 'gr-slider__dot';
                obDot.setAttribute('role', 'tab');
                obDot.setAttribute('aria-label', String(iIndex + 1));
                obDot.addEventListener('click', () => {
                    obTrack.scrollTo({ left: iIndex * slideStep(), behavior: bReduceMotion ? 'auto' : 'smooth' });
                });
                obDots.appendChild(obDot);
                return obDot;
            });
        };

        const arDotList = buildDots();

        const syncControls = () => {
            const iActive = activeIndex();
            arDotList.forEach((obDot, iIndex) => obDot.classList.toggle('is-active', iIndex === iActive));
            const iMaxScroll = obTrack.scrollWidth - obTrack.clientWidth - 1;
            if (obPrev) {
                obPrev.disabled = obTrack.scrollLeft <= 1;
            }
            if (obNext) {
                obNext.disabled = obTrack.scrollLeft >= iMaxScroll;
            }
        };

        const scrollByStep = (iDirection) => {
            obTrack.scrollBy({ left: iDirection * slideStep(), behavior: bReduceMotion ? 'auto' : 'smooth' });
        };

        if (obPrev) {
            obPrev.addEventListener('click', () => scrollByStep(-1));
        }
        if (obNext) {
            obNext.addEventListener('click', () => scrollByStep(1));
        }

        let iScrollTimer = 0;
        obTrack.addEventListener('scroll', () => {
            window.clearTimeout(iScrollTimer);
            iScrollTimer = window.setTimeout(syncControls, 80);
        }, { passive: true });

        window.addEventListener('resize', syncControls);
        syncControls();

        startAutoplay(obSlider, obTrack, scrollByStep, syncControls, bReduceMotion);
    };

    const startAutoplay = (obSlider, obTrack, fnScrollByStep, fnSync, bReduce) => {
        if (bReduce || !obSlider.hasAttribute('data-gr-autoplay')) {
            return;
        }
        const iInterval = Math.max(2000, parseInt(obSlider.getAttribute('data-gr-interval') || '5000', 10));
        let iTimer = 0;

        const tick = () => {
            const iMaxScroll = obTrack.scrollWidth - obTrack.clientWidth - 1;
            if (obTrack.scrollLeft >= iMaxScroll) {
                obTrack.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
                fnScrollByStep(1);
            }
            fnSync();
        };

        const play = () => {
            iTimer = window.setInterval(tick, iInterval);
        };
        const pause = () => {
            window.clearInterval(iTimer);
        };

        obSlider.addEventListener('mouseenter', pause);
        obSlider.addEventListener('mouseleave', play);
        obSlider.addEventListener('focusin', pause);
        obSlider.addEventListener('focusout', play);
        play();
    };

    const boot = () => {
        document.querySelectorAll('[data-gr-slider]').forEach(setupSlider);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
