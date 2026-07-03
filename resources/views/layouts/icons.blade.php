<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tailor icons</title>
    @livewireStyles
    <style>
        :root {
            color-scheme: light dark;
            --bg: #ffffff;
            --fg: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --surface: #f9fafb;
            --accent: #4f46e5;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b0f19;
                --fg: #e5e7eb;
                --muted: #9ca3af;
                --border: #1f2937;
                --surface: #111827;
                --accent: #818cf8;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--fg);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }

        .tailor { max-width: 960px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

        .tailor-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .tailor-header h1 { margin: 0; font-size: 1.5rem; }
        .tailor-subtitle { margin: .35rem 0 0; color: var(--muted); font-size: .9rem; }
        .tailor-subtitle code { font-size: .85em; }

        .tailor-count {
            flex: none;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .8rem;
            color: var(--muted);
            white-space: nowrap;
        }

        .tailor-count--sm { padding: .05rem .5rem; font-size: .7rem; }

        .tailor-search {
            position: relative;
            margin-bottom: 2rem;
        }

        .tailor-search svg {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: var(--muted);
        }

        .tailor-search input {
            width: 100%;
            padding: .65rem .9rem .65rem 2.4rem;
            font-size: .95rem;
            color: var(--fg);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: .6rem;
            outline: none;
        }

        .tailor-search input:focus { border-color: var(--accent); }

        .tailor-section { margin-bottom: 2.25rem; }

        .tailor-section h2 {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin: 0 0 .6rem;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .tailor-group { color: var(--accent); font-weight: 700; }
        .tailor-set { color: var(--muted); font-weight: 500; }

        /* Fixed layout gives every section's table identical 50/50 columns, so
           the Lucide column lines up across sections instead of sizing to each
           section's own content. */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }

        th, td {
            text-align: left;
            padding: .55rem .75rem;
            border-bottom: 1px solid var(--border);
            font-size: .9rem;
        }

        th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
            font-weight: 600;
        }

        tbody tr:hover { background: var(--surface); }

        .tailor-icon-cell {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
        }

        .tailor-glyph {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: none;
            width: 1.75rem;
            height: 1.75rem;
            color: var(--fg);
        }

        /* Size every rendered icon (Lucide swaps <i> for <svg>; Heroicons are
           inlined as <svg>) to one size so the columns line up for comparison. */
        .tailor-glyph svg { width: 1.15rem; height: 1.15rem; }
        .tailor-glyph--empty { color: var(--muted); }
        .tailor-glyph--spin { animation: tailor-spin 0.7s linear infinite; }

        /* Flux's animated "loading" pseudo-icon, drawn as a spinning ring. */
        .tailor-spinner {
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 50%;
            border: 2px solid var(--border);
            border-top-color: var(--accent);
            animation: tailor-spin 0.7s linear infinite;
        }

        @keyframes tailor-spin {
            to { transform: rotate(360deg); }
        }

        /* Heroicons have no auto-render JS, so the SVG is fetched and inlined
           (see the script below). Inline SVGs use stroke="currentColor", so
           they inherit the text color and theme correctly in every browser. */
        .tailor-heroicon { display: inline-flex; }

        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .85em;
        }

        .tailor-muted { color: var(--muted); font-style: italic; font-size: .85rem; }
        .tailor-empty { color: var(--muted); padding: 2rem 0; }
    </style>
</head>
<body>
    {{ $slot }}

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        (function () {
            // Fetch each Heroicon SVG once and inline it so it inherits the
            // current text color (theme-aware) and renders in every browser.
            const heroCache = {};

            const renderHeroicons = () => {
                document.querySelectorAll('.tailor-heroicon[data-hero]').forEach((el) => {
                    const name = el.getAttribute('data-hero');
                    el.removeAttribute('data-hero');

                    if (heroCache[name] !== undefined) {
                        el.innerHTML = heroCache[name];

                        return;
                    }

                    fetch('https://unpkg.com/heroicons@2/24/outline/' + name + '.svg')
                        .then((response) => (response.ok ? response.text() : ''))
                        .then((svg) => {
                            heroCache[name] = svg;
                            el.innerHTML = svg;
                        })
                        .catch(() => {});
                });
            };

            const render = () => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }

                renderHeroicons();
            };

            const observe = () => {
                render();

                const root = document.querySelector('.tailor');

                if (!root) {
                    return;
                }

                let scheduled = false;

                new MutationObserver(() => {
                    if (scheduled) {
                        return;
                    }

                    scheduled = true;
                    requestAnimationFrame(() => {
                        scheduled = false;
                        render();
                    });
                }).observe(root, { childList: true, subtree: true });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', observe);
            } else {
                observe();
            }
        })();
    </script>
    @livewireScripts
</body>
</html>
