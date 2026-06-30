{{--
    FORM STANDARTLARI - GLOBAL CSS CLASSES
    Bu component tüm admin panelindeki form elemanları için standart CSS class'ları tanımlar.

    Kullanım:
    @php
        $formClasses = view('components.form-standards')->getData();
    @endphp

    <input class="{{ $formClasses['input'] }}" />
--}}

@php
    // STANDART INPUT CLASSES
    $inputClass = "w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 hover:border-blue-400 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed";

    // STANDART SELECT CLASSES
    $selectClass = "w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer hover:border-blue-400";

    // STANDART TEXTAREA CLASSES
    $textareaClass = "w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 hover:border-blue-400 resize-y";

    // STANDART CHECKBOX CLASSES
    $checkboxClass = "w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600";

    // STANDART RADIO CLASSES
    $radioClass = "w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600";

    // STANDART LABEL CLASSES
    $labelClass = "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors duration-200";

    // STANDART ERROR MESSAGE CLASSES
    $errorClass = "mt-1 text-sm text-red-600 dark:text-red-400";

    // STANDART HELP TEXT CLASSES
    $helpClass = "mt-1 text-xs text-gray-500 dark:text-gray-400";

    // STANDART BUTTON CLASSES
    $buttonPrimaryClass = "px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200";

    $buttonSecondaryClass = "px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-all duration-200";

    $buttonDangerClass = "px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200";

    // SELECT OPTION CLASSES
    $optionClass = "bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 font-medium";
    $optionDisabledClass = "bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 py-2";
@endphp

{{-- Export classes as data --}}
@php
    $this->data = [
        'input' => $inputClass,
        'select' => $selectClass,
        'textarea' => $textareaClass,
        'checkbox' => $checkboxClass,
        'radio' => $radioClass,
        'label' => $labelClass,
        'error' => $errorClass,
        'help' => $helpClass,
        'buttonPrimary' => $buttonPrimaryClass,
        'buttonSecondary' => $buttonSecondaryClass,
        'buttonDanger' => $buttonDangerClass,
        'option' => $optionClass,
        'optionDisabled' => $optionDisabledClass,
    ];
@endphp
