@php
    use Filament\Support\Facades\FilamentAsset;
    use Illuminate\Support\Js;
    use function Filament\Support\prepare_inherited_attributes;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixLabel = $getSuffixLabel();
    $placeholder = filled($getPlaceholder()) ? $getPlaceholder() : 'Input Phone Numbers';
    $id = $getId();
    // FIX: method_exists() cannot see methods added via ->macro() — macros are
    // resolved at runtime through __call(), not declared on the class, so
    // method_exists($field, 'getSuggestions') always returned false and
    // $suggestions was silently hard-coded to []. Use hasMacro() instead,
    // which is the Macroable trait's own registry check.
    $suggestions = $field::hasMacro('getSuggestions') ? $field->getSuggestions() : [];

    $cssUrl = FilamentAsset::getStyleHref(
        'filament-phone-input',
        package: 'ysfkaya/filament-phone-input',
    );
    $compiledCssUrl = Js::from($cssUrl);

    $options = [
        'allowDropdown' => $isAllowDropdown(),
        'autoPlaceholder' => $getAutoPlaceholder() ?: 'off',
        'containerClass' => $getContainerClass(),
        'countryOrder' => $getCountryOrder(),
        'countrySearch' => $isCountrySearch(),
        'customPlaceholder' => $getCustomPlaceholder(),
        'dropdownContainer' => $getDropdownContainer(),
        'excludeCountries' => $getExcludeCountries(),
        'fixDropdownWidth' => $isFixDropdownWidth(),
        'formatAsYouType' => $isFormatAsYouType(),
        'formatOnDisplay' => $isFormatOnDisplay(),
        'i18n' => $getI18n(),
        'initialCountry' => $getInitialCountry(),
        'nationalMode' => $isNationalMode(),
        'onlyCountries' => $getOnlyCountries(),
        'placeholderNumberType' => $getPlaceholderNumberType(),
        'showFlags' => $isShowFlags(),
        'separateDialCode' => $isSeparateDialCode(),
        'strictMode' => $isStrictMode(),
        'useFullscreenPopup' => $isUseFullscreenPopup(),
    ];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    class="fi-fo-phone-tags-input-wrp"
>
    <x-filament::input.wrapper
        :disabled="$isDisabled"
        :inline-prefix="$isPrefixInline"
        :inline-suffix="$isSuffixInline"
        :prefix="$prefixLabel"
        :prefix-actions="$prefixActions"
        :prefix-icon="$prefixIcon"
        :prefix-icon-color="$getPrefixIconColor()"
        :suffix="$suffixLabel"
        :suffix-actions="$suffixActions"
        :suffix-icon="$suffixIcon"
        :suffix-icon-color="$getSuffixIconColor()"
        :valid="! $errors->has($statePath)"
        :attributes="
            prepare_inherited_attributes($getExtraAttributeBag())
                ->class([
                    'fi-fo-phone-input',
                    'fi-fo-phone-tags-input',
                    '!min-h-[160px]',
                    '!items-start',
                ])
        "
        x-load-css="[{{ $compiledCssUrl }}]"
    >
        <div
            x-data="phoneTagsInput({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                statePath: @js($statePath),
                options: @js($options),
                locale: @js($getLocale()),
                splitKeys: @js(['Enter', 'Tab', ',']),
                intlTelInputSelectedCountryCookieName: @js($getCookieName()),
                suggestions: @js($suggestions),
            })"
            x-on:click="$refs.input?.focus()"
            class="fi-fo-phone-tags flex flex-col justify-between w-full min-h-[160px] cursor-text"
        >
            <div class="flex-1 w-full p-2.5">
                <div class="fi-fo-phone-tags-input-row w-full" wire:ignore>
                    <input
                        x-ref="input"
                        x-model="current"
                        id="{{ $id }}"
                        type="tel"
                        autocomplete="off"
                        @disabled($isDisabled)
                        placeholder="{{ $placeholder }}"
                        class="fi-input w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none placeholder:text-gray-400 disabled:text-gray-500 dark:text-white dark:placeholder:text-gray-500"
                    />
                </div>

                <div wire:ignore>
                    <template x-cloak x-if="state?.length">
                        <div class="fi-fo-phone-tags-list mt-2.5 flex flex-wrap gap-2 px-1 pb-1">
                            <template x-for="(tag, index) in state" :key="`${tag}-${index}`">
                                <x-filament::badge color="primary" size="md">
                                    <span x-text="tag"></span>
                                    <x-slot
                                        name="deleteButton"
                                        x-on:click.stop="removeTag(index)"
                                        :x-bind:aria-label="'\'Remove \' + tag'"
                                    ></x-slot>
                                </x-filament::badge>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div wire:ignore>
                <template x-cloak x-if="suggestions && suggestions.length > 0">
                    <div class="fi-fo-phone-suggestions border-t border-gray-100 dark:border-white/10 px-3 py-2.5 bg-gray-50/60 dark:bg-white/[0.02]">
                        <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-1.5 font-medium">
                            <x-filament::icon
                                icon="heroicon-m-clock"
                                class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500"
                            />
                            <span>Recent recipients:</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="number in suggestions" :key="number">
                                <button
                                    type="button"
                                    x-on:click.stop="toggleSuggestion(number)"
                                    :class="(state ?? []).includes(number)
                                        ? 'bg-primary-50 text-primary-700 ring-1 ring-primary-600/30 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-400/30 font-medium'
                                        : 'bg-white text-gray-700 hover:bg-gray-100 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:ring-white/10'"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-mono transition duration-150 ease-in-out cursor-pointer shadow-xs"
                                >
                                    <span x-show="(state ?? []).includes(number)" class="text-primary-600 dark:text-primary-400 font-bold">✓</span>
                                    <span x-show="!(state ?? []).includes(number)" class="text-gray-400 dark:text-gray-500">+</span>
                                    <span x-text="number"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>