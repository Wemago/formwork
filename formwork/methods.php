<?php

use Formwork\Cms\App;
use Formwork\Http\Utils\Header;
use Formwork\Parsers\Markdown;
use Formwork\Utils\Date;
use Formwork\Utils\Html;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Text;
use Formwork\Utils\Uri;

return function (App $app) {
    return [
        'attr' => Html::attributes(...),

        'classes' => Html::classes(...),

        'escape' => Str::escape(...),

        'escapeAttr' => Str::escapeAttr(...),

        'removeHTML' => Str::removeHTML(...),

        'slug' => Str::slug(...),

        'append' => Str::append(...),

        'prepend' => Str::prepend(...),

        'countWords' => Text::countWords(...),

        'truncate' => Text::truncate(...),

        'truncateWords' => Text::truncateWords(...),

        'readingTime' => Text::readingTime(...),

        'redirect' => Header::redirect(...),

        'uri' => static function (string $route) use ($app): string {
            return Uri::make([], Path::join([$app->request()->root(), $route]));
        },

        'markdown' => static function (string $markdown) use ($app): string {
            $currentPage = $app->site()->currentPage();
            return Markdown::parse(
                $markdown,
                [
                    'site'      => $app->site(),
                    'safeMode'  => $app->config()->get('system.pages.content.safeMode'),
                    'baseRoute' => $currentPage !== null ? $currentPage->route() : '/',
                ]
            );
        },

        'date' => static function (int $timestamp, ?string $format = null) use ($app): string {
            return Date::formatTimestamp(
                $timestamp,
                $format ?? $app->config()->get('system.date.dateFormat'),
                $app->translations()->getCurrent()
            );
        },

        'datetime' => static function (int $timestamp) use ($app): string {
            return Date::formatTimestamp($timestamp, $app->config()->get('system.date.datetimeFormat'), $app->translations()->getCurrent());
        },

        'timedistance' => static function (int $timestamp) use ($app): string {
            return Date::formatTimestampAsDistance($timestamp, $app->translations()->getCurrent());
        },

        'translate' => fn(string $key, ...$arguments) => $app->translations()->getCurrent()->translate($key, ...$arguments),
    ];
};
