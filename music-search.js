(() => {
    const input = document.querySelector('#artist-search');
    const wrapper = document.querySelector('.music-search-wrap');
    const popover = document.querySelector('#artist-search-popover');
    const list = document.querySelector('#artist-search-results');
    const message = document.querySelector('#artist-search-message');
    const status = document.querySelector('#artist-search-status');

    if (!input || !wrapper || !popover || !list || !message || !status) return;

    let timer = 0;
    let requestId = 0;
    let controller = null;
    let activeIndex = -1;

    const options = () => Array.from(list.querySelectorAll('[role="option"]'));

    function closeSuggestions() {
        window.clearTimeout(timer);
        if (controller) {
            controller.abort();
            controller = null;
        }
        requestId++;
        popover.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    }

    function setActiveOption(nextIndex) {
        const items = options();
        if (!items.length) return;
        activeIndex = (nextIndex + items.length) % items.length;
        items.forEach((item, index) => {
            const selected = index === activeIndex;
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
            if (selected) {
                input.setAttribute('aria-activedescendant', item.id);
                item.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function showSuggestions(items) {
        list.replaceChildren();
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');

        if (!items.length) {
            list.hidden = true;
            message.textContent = 'Совпадений пока нет.';
            message.hidden = false;
        } else {
            list.hidden = false;
            message.hidden = true;
            items.forEach((artist) => {
                const link = document.createElement('a');
                link.className = 'music-search-result';
                link.href = `f.php?companyid=${encodeURIComponent(artist.id)}`;
                link.id = `artist-option-${artist.id}`;
                link.setAttribute('role', 'option');
                link.setAttribute('aria-selected', 'false');

                if (artist.image) {
                    const image = document.createElement('img');
                    image.src = artist.image;
                    image.alt = '';
                    image.loading = 'lazy';
                    link.append(image);
                } else {
                    const initial = document.createElement('span');
                    initial.className = 'music-search-result-initial';
                    initial.setAttribute('aria-hidden', 'true');
                    initial.textContent = (artist.name || 'S').slice(0, 1).toUpperCase();
                    link.append(initial);
                }

                const copy = document.createElement('span');
                copy.className = 'music-search-result-copy';
                const name = document.createElement('strong');
                name.textContent = artist.name;
                const category = document.createElement('small');
                category.textContent = artist.category;
                copy.append(name, category);

                const arrow = document.createElement('span');
                arrow.className = 'music-search-result-arrow';
                arrow.setAttribute('aria-hidden', 'true');
                arrow.textContent = '↗';
                link.append(copy, arrow);
                list.append(link);
            });
        }

        popover.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        status.textContent = items.length
            ? `Найдено вариантов: ${items.length}. Используйте стрелки для выбора.`
            : 'Совпадений не найдено.';
    }

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        if (controller) controller.abort();
        const currentRequestId = ++requestId;
        const query = input.value.trim();

        if (!query) {
            closeSuggestions();
            status.textContent = '';
            return;
        }

        timer = window.setTimeout(async () => {
            controller = new AbortController();
            try {
                const response = await fetch(`artist-suggestions.php?q=${encodeURIComponent(query)}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Search request failed');
                const data = await response.json();
                if (currentRequestId !== requestId || input.value.trim() !== query) return;
                showSuggestions(Array.isArray(data.items) ? data.items : []);
            } catch (error) {
                if (error.name === 'AbortError' || currentRequestId !== requestId) return;
                closeSuggestions();
                status.textContent = 'Поиск временно недоступен.';
            }
        }, 160);
    });

    input.addEventListener('keydown', (event) => {
        const items = options();
        if (event.key === 'Escape') {
            closeSuggestions();
        } else if (event.key === 'ArrowDown' && !popover.hidden && items.length) {
            event.preventDefault();
            setActiveOption(activeIndex + 1);
        } else if (event.key === 'ArrowUp' && !popover.hidden && items.length) {
            event.preventDefault();
            setActiveOption(activeIndex < 0 ? items.length - 1 : activeIndex - 1);
        } else if (event.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
            event.preventDefault();
            window.location.assign(items[activeIndex].href);
        }
    });

    document.addEventListener('pointerdown', (event) => {
        if (!wrapper.contains(event.target)) closeSuggestions();
    });
})();
