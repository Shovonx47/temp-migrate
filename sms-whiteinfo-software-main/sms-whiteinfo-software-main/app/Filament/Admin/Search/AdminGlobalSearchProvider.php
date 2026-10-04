<?php

namespace App\Filament\Admin\Search;

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Str;

class AdminGlobalSearchProvider implements GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $query = trim($query);

        if (blank($query)) {
            return null;
        }

        $builder = GlobalSearchResults::make();
        $lowercaseQuery = Str::lower($query);
        $searchTerms = array_filter(explode(' ', $lowercaseQuery));

        // 1. Search Navigation Menus and Pages
        $menuResults = [];
        $seenUrls = [];

        try {
            $navigation = Filament::getNavigation();

            foreach ($navigation as $group) {
                $groupLabel = $group instanceof NavigationGroup ? ($group->getLabel() ?? '') : '';

                $items = $group->getItems();
                $this->processNavigationItems($items, $groupLabel, $searchTerms, $lowercaseQuery, $menuResults, $seenUrls);
            }
        } catch (\Throwable) {
            // Fallback gracefully if navigation tree fails
        }

        // Also check discovered pages directly if not already in navigation results
        try {
            $pages = Filament::getPages();

            foreach ($pages as $page) {
                if (! method_exists($page, 'canAccess') || ! $page::canAccess()) {
                    continue;
                }

                $label = method_exists($page, 'getNavigationLabel') ? $page::getNavigationLabel() : '';
                $title = method_exists($page, 'getTitle') ? $page::getTitle() : '';
                $group = method_exists($page, 'getNavigationGroup') ? (string) ($page::getNavigationGroup() ?? '') : '';

                $searchText = Str::lower("{$label} {$title} {$group}");

                $matches = true;
                foreach ($searchTerms as $term) {
                    if (! str_contains($searchText, $term)) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    try {
                        $url = $page::getUrl();

                        if (! in_array($url, $seenUrls, true)) {
                            $seenUrls[] = $url;
                            $menuResults[] = new GlobalSearchResult(
                                title: $label ?: $title ?: class_basename($page),
                                url: $url,
                                details: $group ? ['Menu Group' => $group] : [],
                            );
                        }
                    } catch (\Throwable) {
                        // Skip if route resolution requires arguments
                    }
                }
            }
        } catch (\Throwable) {
            //
        }

        if (! empty($menuResults)) {
            $builder->category('Navigation & Pages', array_slice($menuResults, 0, 15));
        }

        // 2. Search Resource Records (Customers, Senders, Transactions, etc.)
        try {
            $resources = Filament::getResources();

            usort(
                $resources,
                fn (string $a, string $b): int => ($a::getGlobalSearchSort() ?? 0) <=> ($b::getGlobalSearchSort() ?? 0),
            );

            foreach ($resources as $resource) {
                if (! $resource::canGloballySearch()) {
                    continue;
                }

                $resourceResults = $resource::getGlobalSearchResults($query);

                if (! $resourceResults || ! $resourceResults->count()) {
                    continue;
                }

                $builder->category($resource::getPluralModelLabel(), $resourceResults);
            }
        } catch (\Throwable) {
            //
        }

        return $builder;
    }

    /**
     * @param  array<NavigationItem>  $items
     * @param  array<string>  $searchTerms
     * @param  array<GlobalSearchResult>  $menuResults
     * @param  array<string>  $seenUrls
     */
    protected function processNavigationItems(
        array $items,
        string $groupLabel,
        array $searchTerms,
        string $lowercaseQuery,
        array &$menuResults,
        array &$seenUrls
    ): void {
        foreach ($items as $item) {
            if (! $item->isVisible()) {
                continue;
            }

            $label = $item->getLabel();
            $url = $item->getUrl();

            if (filled($url) && ! in_array($url, $seenUrls, true)) {
                $itemText = Str::lower("{$label} {$groupLabel}");

                $matches = true;
                foreach ($searchTerms as $term) {
                    if (! str_contains($itemText, $term)) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    $seenUrls[] = $url;
                    $menuResults[] = new GlobalSearchResult(
                        title: $label,
                        url: $url,
                        details: filled($groupLabel) ? ['Menu' => $groupLabel] : [],
                    );
                }
            }

            if ($childItems = $item->getChildItems()) {
                $this->processNavigationItems($childItems, $groupLabel ?: $label, $searchTerms, $lowercaseQuery, $menuResults, $seenUrls);
            }
        }
    }
}
