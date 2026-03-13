<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>

    <link href="{{ asset('assets/css/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body,
        button,
        input,
        select,
        textarea {
            font-family: 'Poppins', sans-serif;
        }

        body.page-loading {
            overflow: hidden;
        }

        .shoreline-loader {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.28);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            opacity: 1;
            visibility: visible;
            transition: opacity .28s ease, visibility .28s ease;
        }

        .shoreline-loader.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .shoreline-loader__panel {
            width: min(92vw, 280px);
            padding: 1.6rem 1.4rem 1.2rem;
            text-align: center;
        }

        .shoreline-loader__mark {
            font-weight: 800;
            font-size: 1.65rem;
            line-height: 1;
            letter-spacing: .01em;
            color: #111827;
        }

        .shoreline-loader__eyebrow {
            margin: .35rem 0 0;
            font-size: .75rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #111827;
            opacity: .76;
        }

        .shoreline-loader__bar {
            position: relative;
            width: 72px;
            height: 72px;
            margin: 1.1rem auto .95rem;
            border-radius: 50%;
            border: 6px solid rgba(17, 24, 39, 0.16);
            border-top-color: #111827;
            border-right-color: #111827;
            background: transparent;
            animation: shoreline-spin 0.85s linear infinite;
        }

        .shoreline-loader__bar::after {
            content: "";
            position: absolute;
            inset: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.58);
            box-shadow: inset 0 0 0 1px rgba(17, 24, 39, 0.04);
        }

        .shoreline-loader__text {
            margin: 0;
            font-size: .92rem;
            color: #111827;
        }

        @keyframes shoreline-spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    @stack('styles')
</head>
<body class="page-loading">

        <div id="pageLoader" class="shoreline-loader" aria-live="polite" aria-busy="true">
        <div class="shoreline-loader__panel">
            <div class="shoreline-loader__mark">Shoreline</div>
            <p class="shoreline-loader__eyebrow">Curated Fashion</p>
            <div class="shoreline-loader__bar"></div>
            <p class="shoreline-loader__text">Memuat halaman...</p>
        </div>
    </div>

    @include('components.web.navbar')

    <div>
        @yield('content')
    </div>

    @include('components.web.footer')

    <script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>

    {{-- SWEET ALERT GLOBAL --}}
    @include('components.swal')
    <script>
        (function() {
            const loader = document.getElementById('pageLoader');
            let navigationCommitted = false;
            let pendingShowTimer = null;

            const hideLoader = () => {
                if (!loader) return;
                navigationCommitted = false;
                if (pendingShowTimer) {
                    clearTimeout(pendingShowTimer);
                    pendingShowTimer = null;
                }
                loader.classList.add('is-hidden');
                document.body.classList.remove('page-loading');
            };

            const showLoader = () => {
                if (!loader) return;
                loader.classList.remove('is-hidden');
                document.body.classList.add('page-loading');
            };

            const queueShowLoader = () => {
                if (!loader || navigationCommitted) return;
                if (pendingShowTimer) {
                    clearTimeout(pendingShowTimer);
                }

                pendingShowTimer = setTimeout(() => {
                    pendingShowTimer = null;
                    if (!navigationCommitted) {
                        showLoader();
                    }
                }, 120);

                // If the page never actually navigates, recover automatically.
                setTimeout(() => {
                    if (!navigationCommitted && document.visibilityState === 'visible') {
                        hideLoader();
                    }
                }, 1500);
            };

            window.showPageLoader = showLoader;
            window.hidePageLoader = hideLoader;

            document.addEventListener('DOMContentLoaded', hideLoader);

            window.addEventListener('load', () => {
                setTimeout(hideLoader, 120);
            });

            window.addEventListener('pageshow', hideLoader);
            window.addEventListener('pagehide', () => {
                navigationCommitted = true;
            });
            window.addEventListener('error', hideLoader);
            window.addEventListener('unhandledrejection', hideLoader);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    hideLoader();
                }
            });

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a[href]');

                if (!link) return;
                if (link.target === '_blank') return;
                if (link.hasAttribute('download')) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

                const href = link.getAttribute('href') || '';

                if (!href || href === '#' || href.startsWith('javascript:')) return;

                const isSamePageAnchor = href.startsWith('#');
                if (isSamePageAnchor) return;

                queueShowLoader();
            });

            document.addEventListener('submit', (event) => {
                const form = event.target;

                if (!(form instanceof HTMLFormElement)) return;

                const noLoader = form.hasAttribute('data-no-loader');
                if (noLoader) return;

                queueShowLoader();
            });
        })();

        // Confirm submit form (paling aman & gampang)
        function confirmDeleteForm(formId, options = {}) {
            const {
                title = 'Yakin ingin menghapus?'
                    , text = 'Data yang sudah dihapus tidak dapat dikembalikan.'
                    , confirmText = 'Ya, hapus'
                    , cancelText = 'Batal'
                    , confirmColor = '#dc3545'
            } = options;

            SwalConfirm({
                title
                , text
                , icon: 'warning'
                , confirmText
                , cancelText
                , confirmColor
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });

            return false; // penting: cegah submit langsung
        }

    </script>

    @stack('scripts')
</body>
</html>
