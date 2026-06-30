<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title') - Yalıhan Emlak</title>

    <style>
        .bg-white {
            --bg-opacity: 1;
            background-color: #fff;
            background-color: rgba(255, 255, 255, var(--bg-opacity));
        }

        // context7-ignore
        .dark .bg-white {
            background-color: #0f172a !important;
        }

        .bg-gray-100 {
            --bg-opacity: 1;
            background-color: #f7fafc;
            background-color: rgba(247, 250, 252, var(--bg-opacity));
        }

        // context7-ignore

        /* Context7 Standards */
        @media (prefers-color-scheme: dark) {
            .bg-white {
                background-color: #010409 !important;
            }

            .text-gray-900 {
                color: #f0f6fc !important;
            }

            .border-gray-200 {
                border-color: #30363d !important;
            }
        }

        .dark\:bg-slate-900 {
            background-color: #010409;
        }

        .dark\:text-white {
            color: #f0f6fc;
        }

        .dark\:border-slate-800 {
            border-color: #30363d;
        }
    </style>

    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }
    </style>
</head>

<body class="antialiased">
    <div
        class="items-top relative flex min-h-screen justify-center bg-gray-100 dark:bg-slate-900 sm:items-center sm:pt-0">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="flex flex-col items-center pt-8 sm:justify-start sm:pt-0">
                <div class="mb-4 flex items-center">
                    <div
                        class="border-r border-gray-400 px-4 text-6xl font-bold tracking-wider text-blue-600 dark:border-slate-700 dark:text-blue-400">
                        @yield('code')
                    </div>

                    <div class="ml-4 text-lg uppercase tracking-wider text-gray-700 dark:text-slate-300">
                        @yield('message')
                    </div>
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('admin.dashboard') ?? url('/') }}"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white shadow-md transition-all duration-200 hover:bg-blue-700 hover:shadow-lg dark:shadow-none">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
