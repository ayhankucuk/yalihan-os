<?php

namespace App\Helpers;

/**
 * Form Standards Helper
 *
 * Tüm admin panelindeki form elemanları için standart CSS class'ları sağlar.
 * Context7 ve WCAG AAA (21:1 kontrast) standartlarına uygun.
 * ⚠️ Context7: Tüm utility class'lar dark: variantı ile mirror edilmiştir.
 *
 * @version 1.0.1
 */
class FormStandards
{
    /**
     * Standard INPUT field classes
     */
    public static function input(): string
    {
        return 'w-full dark:w-full px-4 dark:px-4 py-2.5 dark:py-2.5 bg-white dark:bg-gray-800 border dark:border border-gray-300 dark:border-gray-600 rounded-lg dark:rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none dark:focus:outline-none focus:ring-2 dark:focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent dark:focus:border-transparent transition-all dark:transition-all duration-200 dark:duration-200 hover:border-blue-400 dark:hover:border-blue-400 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed dark:disabled:cursor-not-allowed';
    }

    /**
     * Standard SELECT dropdown classes
     */
    public static function select(): string
    {
        return 'w-full dark:w-full px-4 dark:px-4 py-2.5 dark:py-2.5 border dark:border border-gray-300 dark:border-gray-600 rounded-lg dark:rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 dark:focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all dark:transition-all duration-200 dark:duration-200 cursor-pointer dark:cursor-pointer hover:border-blue-400 dark:hover:border-blue-400';
    }

    /**
     * Standard TEXTAREA classes
     */
    public static function textarea(): string
    {
        return 'w-full dark:w-full px-4 dark:px-4 py-2.5 dark:py-2.5 border dark:border border-gray-300 dark:border-gray-600 rounded-lg dark:rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none dark:focus:outline-none focus:ring-2 dark:focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent dark:focus:border-transparent transition-all dark:transition-all duration-200 dark:duration-200 hover:border-blue-400 dark:hover:border-blue-400 resize-y dark:resize-y';
    }

    /**
     * Standard CHECKBOX classes
     */
    public static function checkbox(): string
    {
        return 'w-4 dark:w-4 h-4 dark:h-4 text-blue-600 dark:text-blue-500 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded dark:rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:focus:ring-2';
    }

    /**
     * Standard RADIO button classes
     */
    public static function radio(): string
    {
        return 'w-4 dark:w-4 h-4 dark:h-4 text-blue-600 dark:text-blue-500 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:focus:ring-2';
    }

    /**
     * Standard LABEL classes
     */
    public static function label(): string
    {
        return 'block dark:block text-sm dark:text-sm font-medium dark:font-medium text-gray-900 dark:text-white mb-2 dark:mb-2 transition-colors dark:transition-colors duration-200 dark:duration-200';
    }

    /**
     * Standard ERROR message classes
     */
    public static function error(): string
    {
        return 'mt-1 dark:mt-1 text-sm dark:text-sm text-red-600 dark:text-red-400';
    }

    /**
     * Standard HELP text classes
     */
    public static function help(): string
    {
        return 'mt-1 dark:mt-1 text-xs dark:text-xs text-gray-500 dark:text-gray-400';
    }

    /**
     * Primary BUTTON classes (Blue)
     */
    public static function buttonPrimary(): string
    {
        return 'px-4 dark:px-4 py-2.5 dark:py-2.5 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white dark:text-white font-medium dark:font-medium rounded-lg dark:rounded-lg focus:outline-none dark:focus:outline-none focus:ring-2 dark:focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-2 transition-all dark:transition-all duration-200 dark:duration-200';
    }

    /**
     * Secondary BUTTON classes (Gray)
     */
    public static function buttonSecondary(): string
    {
        return 'px-4 dark:px-4 py-2.5 dark:py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-medium dark:font-medium rounded-lg dark:rounded-lg focus:outline-none dark:focus:outline-none focus:ring-2 dark:focus:ring-2 focus:ring-gray-400 dark:focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-2 transition-all dark:transition-all duration-200 dark:duration-200';
    }

    /**
     * Danger BUTTON classes (Red)
     */
    public static function buttonDanger(): string
    {
        return 'px-4 dark:px-4 py-2.5 dark:py-2.5 bg-red-600 dark:bg-red-500 hover:bg-red-700 dark:hover:bg-red-600 text-white dark:text-white font-medium dark:font-medium rounded-lg dark:rounded-lg focus:outline-none dark:focus:outline-none focus:ring-2 dark:focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:ring-offset-2 dark:focus:ring-offset-2 transition-all dark:transition-all duration-200 dark:duration-200';
    }

    /**
     * SELECT OPTION classes
     */
    public static function option(): string
    {
        return 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 dark:py-2 font-medium dark:font-medium';
    }

    /**
     * SELECT OPTION classes (disabled)
     */
    public static function optionDisabled(): string
    {
        return 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 py-2 dark:py-2';
    }
}
