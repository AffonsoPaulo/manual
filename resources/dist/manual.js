(function () {
    'use strict';

    /* ── Search ──────────────────────────────────────────────────────────────── */
    (function initSearch() {
        var shell    = document.querySelector('[data-search-endpoint]');
        var input    = document.querySelector('[data-manual-search]');
        var results  = document.querySelector('[data-manual-search-results]');

        if (!shell || !input || !results) { return; }

        var endpoint = shell.getAttribute('data-search-endpoint');
        if (!endpoint) { return; }

        var searchIndex = null;
        var activeIndex = -1;

        function getLinks() {
            return Array.prototype.slice.call(results.querySelectorAll('a'));
        }

        function setActive(index) {
            var links = getLinks();
            links.forEach(function (link, i) {
                link.setAttribute('data-active', i === index ? 'true' : 'false');
            });
            activeIndex = index;
        }

        function clearResults() {
            while (results.firstChild) { results.removeChild(results.firstChild); }
            results.setAttribute('data-open', 'false');
            activeIndex = -1;
        }

        function isSafeUrl(url) {
            return typeof url === 'string' && /^https?:\/\//i.test(url);
        }

        function buildResult(item) {
            if (!isSafeUrl(item.url)) { return null; }

            var link    = document.createElement('a');
            var title   = document.createElement('strong');
            var excerpt = document.createElement('small');

            link.setAttribute('href', item.url);
            title.textContent = item.title || '';
            excerpt.textContent = item.excerpt || '';

            link.appendChild(title);
            link.appendChild(excerpt);

            return link;
        }

        function render(items) {
            clearResults();

            if (!items.length) {
                var empty = document.createElement('p');
                empty.className   = 'manual-search-empty';
                empty.textContent = 'Nenhum resultado encontrado.';
                results.appendChild(empty);
                results.setAttribute('data-open', 'true');
                return;
            }

            items.slice(0, 8).forEach(function (item) {
                var el = buildResult(item);
                if (el) { results.appendChild(el); }
            });
            results.setAttribute('data-open', 'true');
        }

        function doSearch() {
            var query = input.value.trim().toLowerCase();

            if (!query) { clearResults(); return; }

            if (searchIndex) {
                render(searchIndex.documents.filter(function (item) {
                    return [
                        item.title,
                        item.description || '',
                        item.content     || '',
                        (item.headings   || []).join(' '),
                    ].join(' ').toLowerCase().indexOf(query) !== -1;
                }));
                return;
            }

            fetch(endpoint)
                .then(function (r) { if (!r.ok) { throw new Error('Search failed'); } return r.json(); })
                .then(function (payload) { searchIndex = payload; input.dispatchEvent(new Event('input')); })
                .catch(clearResults);
        }

        input.addEventListener('input', doSearch);

        input.addEventListener('keydown', function (e) {
            var links = getLinks();

            if (e.key === 'Escape') {
                clearResults();
                input.blur();
                return;
            }

            if (!links.length) { return; }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(Math.min(activeIndex + 1, links.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(Math.max(activeIndex - 1, 0));
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                links[activeIndex].click();
            }
        });

        document.addEventListener('click', function (e) {
            if (!results.contains(e.target) && e.target !== input) {
                clearResults();
            }
        });

        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                input.focus();
                input.select();
            }
        });
    }());

    /* ── Mobile sidebar toggle ───────────────────────────────────────────────── */
    (function initMobileSidebar() {
        var toggle  = document.querySelector('[data-sidebar-toggle]');
        var sidebar = document.querySelector('.manual-sidebar');
        var overlay = document.querySelector('[data-sidebar-overlay]');

        if (!toggle || !sidebar) { return; }

        function open() {
            sidebar.setAttribute('data-open', 'true');
            toggle.setAttribute('aria-expanded', 'true');
            if (overlay) { overlay.setAttribute('data-visible', 'true'); }
            document.body.style.overflow = 'hidden';
        }

        function close() {
            sidebar.setAttribute('data-open', 'false');
            toggle.setAttribute('aria-expanded', 'false');
            if (overlay) { overlay.setAttribute('data-visible', 'false'); }
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', function () {
            sidebar.getAttribute('data-open') === 'true' ? close() : open();
        });

        if (overlay) {
            overlay.addEventListener('click', close);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.getAttribute('data-open') === 'true') {
                close();
                toggle.focus();
            }
        });
    }());

    /* ── Navigation expand / collapse ───────────────────────────────────────── */
    (function initNavToggles() {
        document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var expanded   = btn.getAttribute('data-expanded') === 'true';
                var next       = expanded ? 'false' : 'true';
                var group      = btn.closest('.manual-nav-group');
                var children   = group ? group.querySelector('.manual-nav-children') : null;

                btn.setAttribute('data-expanded', next);
                btn.setAttribute('aria-expanded', next);
                if (children) { children.setAttribute('data-expanded', next); }
            });
        });
    }());

    /* ── Copy to clipboard ───────────────────────────────────────────────────── */
    (function initCopyButtons() {
        if (!navigator.clipboard) { return; }

        document.querySelectorAll('.manual-page pre').forEach(function (pre) {
            var btn = document.createElement('button');
            btn.className              = 'manual-copy-btn';
            btn.setAttribute('aria-label', 'Copiar código');
            btn.setAttribute('type', 'button');
            btn.textContent            = 'Copiar';

            btn.addEventListener('click', function () {
                var code = pre.querySelector('code');
                var text = code ? (code.innerText || code.textContent) : (pre.innerText || pre.textContent);

                navigator.clipboard.writeText(text).then(function () {
                    btn.textContent = 'Copiado!';
                    btn.setAttribute('data-copied', 'true');
                    setTimeout(function () {
                        btn.textContent = 'Copiar';
                        btn.removeAttribute('data-copied');
                    }, 2000);
                }).catch(function () {});
            });

            pre.appendChild(btn);
        });
    }());

    /* ── Heading anchor links ────────────────────────────────────────────────── */
    (function initHeadingAnchors() {
        var selectors = [
            '.manual-page h1[id]',
            '.manual-page h2[id]',
            '.manual-page h3[id]',
        ].join(', ');

        document.querySelectorAll(selectors).forEach(function (heading) {
            var anchor = document.createElement('a');
            anchor.href         = '#' + heading.id;
            anchor.className    = 'manual-heading-anchor';
            anchor.textContent  = '#';
            anchor.setAttribute('aria-hidden', 'true');
            anchor.setAttribute('tabindex', '-1');
            heading.appendChild(anchor);
        });
    }());

}());
