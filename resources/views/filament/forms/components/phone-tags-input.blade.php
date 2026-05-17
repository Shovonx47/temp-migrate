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
    $placeholder = $getPlaceholder();
    $id = $getId();

    $cssUrl = FilamentAsset::getStyleHref(
        'filament-phone-input',
        package: 'ysfkaya/filament-phone-input',
    );
    $compiledCssUrl = Js::from($cssUrl);

    $options = [
        'allowDropdown' => $isAllowDropdown(),
        'autoPlaceholder' => $getAutoPlaceholder(),
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
                ])
        "
        x-load-css="[{{ $compiledCssUrl }}]"
    >
        <div
            x-data="phoneTagsInput({
                state: $wire.$entangle('{{ $statePath }}'),
                statePath: @js($statePath),
                options: @js($options),
                locale: @js($getLocale()),
                splitKeys: @js(['Enter', 'Tab', ',']),
                intlTelInputSelectedCountryCookieName: @js($getCookieName()),
            })"
            class="fi-fo-phone-tags w-full"
        >
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
                <template x-cloak x-if="state.length">
                    <div class="fi-fo-phone-tags-list mt-2 flex flex-wrap gap-1.5 px-2 pb-2">
                        <template x-for="(tag, index) in state" :key="`${tag}-${index}`">
                            <x-filament::badge color="primary">
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
    </x-filament::input.wrapper>
</x-dynamic-component>
