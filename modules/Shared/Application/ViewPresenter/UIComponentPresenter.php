<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ViewPresenter;

/**
 * View presenter for UI components
 * Handles button variants, card styling, size variations, and component states.
 */
final class UIComponentPresenter extends AbstractViewPresenter
{
    private readonly string $component;

    private readonly string $variant;

    private readonly string $size;

    /**
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        string $component,
        string $variant = 'default',
        string $size = 'md',
        array $options = [],
    ) {
        $data = [
            'component' => $component,
            'variant' => $variant,
            'size' => $size,
            'options' => $options,
        ];

        parent::__construct($data);

        $this->component = $component;
        $this->variant = $variant;
        $this->size = $size;
        $this->options = $options;
    }

    /**
     * Present UI component data.
     */
    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        return [
            'component' => $this->component,
            'variant' => $this->variant,
            'size' => $this->size,
            'options' => $this->options,
            'classes' => $this->getComponentClasses(),
            'attributes' => $this->getComponentAttributes(),
            'styles' => $this->getComponentStyles(),
            'states' => $this->getComponentStates(),
        ];
    }

    /**
     * Get button CSS classes based on variant and options.
     */
    public function getButtonClasses(): string
    {
        if ($this->component !== 'button') {
            return '';
        }

        $classes = [
            'inline-flex',
            'items-center',
            'justify-center',
            'rounded',
            'font-medium',
            'transition-colors',
            'duration-200',
            $this->getButtonVariantClass(),
            $this->getButtonSizeClass(),
            $this->conditionalClass($this->isDisabled(), 'disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed'),
            $this->conditionalClass($this->isLoading(), 'opacity-50 cursor-wait'),
            $this->conditionalClass($this->isFullWidth(), 'w-full'),
            $this->getCustomClass(),
        ];

        return $this->generateClasses($classes);
    }

    /**
     * Get card CSS classes based on variant and options.
     */
    public function getCardClasses(): string
    {
        if ($this->component !== 'card') {
            return '';
        }

        $classes = [
            'card',
            $this->getCardVariantClass(),
            $this->getCardSizeClass(),
            $this->conditionalClass($this->isElevated(), 'card--elevated'),
            $this->conditionalClass($this->isBordered(), 'card--bordered'),
            $this->conditionalClass($this->isClickable(), 'card--clickable'),
            $this->conditionalClass($this->isSelected(), 'card--selected'),
            $this->getCustomClass(),
        ];

        return $this->generateClasses($classes);
    }

    /**
     * Get badge CSS classes.
     */
    public function getBadgeClasses(): string
    {
        if ($this->component !== 'badge') {
            return '';
        }

        $classes = [
            'badge',
            $this->getBadgeVariantClass(),
            $this->getBadgeSizeClass(),
            $this->conditionalClass($this->isDot(), 'badge--dot'),
            $this->conditionalClass($this->isOutlined(), 'badge--outlined'),
            $this->getCustomClass(),
        ];

        return $this->generateClasses($classes);
    }

    /**
     * Get alert CSS classes.
     */
    public function getAlertClasses(): string
    {
        if ($this->component !== 'alert') {
            return '';
        }

        $classes = [
            'alert',
            $this->getAlertVariantClass(),
            $this->getAlertSizeClass(),
            $this->conditionalClass($this->isDismissible(), 'alert--dismissible'),
            $this->conditionalClass($this->hasBorder(), 'alert--bordered'),
            $this->conditionalClass($this->hasIcon(), 'alert--with-icon'),
            $this->getCustomClass(),
        ];

        return $this->generateClasses($classes);
    }

    /**
     * Get modal CSS classes.
     */
    public function getModalClasses(): string
    {
        if ($this->component !== 'modal') {
            return '';
        }

        $classes = [
            'modal',
            $this->getModalSizeClass(),
            $this->conditionalClass($this->isCentered(), 'modal--centered'),
            $this->conditionalClass($this->isFullscreen(), 'modal--fullscreen'),
            $this->conditionalClass($this->isScrollable(), 'modal--scrollable'),
            $this->getCustomClass(),
        ];

        return $this->generateClasses($classes);
    }

    /**
     * Get generic component classes.
     */
    public function getComponentClasses(): string
    {
        return match ($this->component) {
            'button' => $this->getButtonClasses(),
            'card' => $this->getCardClasses(),
            'badge' => $this->getBadgeClasses(),
            'alert' => $this->getAlertClasses(),
            'modal' => $this->getModalClasses(),
            default => $this->getGenericClasses(),
        };
    }

    /**
     * Get component attributes.
     *
     * @return array<string, mixed>
     */
    public function getComponentAttributes(): array
    {
        $attributes = [];

        // Common attributes based on component state
        if ($this->isDisabled()) {
            $attributes['disabled'] = true;
        }

        if ($this->isAriaExpanded() !== null) {
            $attributes['aria-expanded'] = $this->isAriaExpanded() ? 'true' : 'false';
        }

        if ($this->getAriaLabel()) {
            $attributes['aria-label'] = $this->getAriaLabel();
        }

        if ($this->getRole()) {
            $attributes['role'] = $this->getRole();
        }

        // Component-specific attributes
        $attributes = array_merge($attributes, $this->getComponentSpecificAttributes());

        return $attributes;
    }

    /**
     * Get component styles.
     *
     * @return array<string, mixed>
     */
    public function getComponentStyles(): array
    {
        $styles = [];

        // Custom styles from options
        if (isset($this->options['styles'])) {
            $styles = array_merge($styles, $this->options['styles']);
        }

        // Component-specific styles
        if ($this->component === 'progress' && isset($this->options['percentage'])) {
            $styles['--progress-percentage'] = $this->options['percentage'] . '%';
        }

        return $styles;
    }

    /**
     * Get component states.
     *
     * @return array<string, bool|null>
     */
    public function getComponentStates(): array
    {
        return [
            'disabled' => $this->isDisabled(),
            'loading' => $this->isLoading(),
            'active' => $this->isActive(),
            'selected' => $this->isSelected(),
            'expanded' => $this->isAriaExpanded(),
            'visible' => $this->isVisible(),
        ];
    }

    /**
     * Create presenter for button component.
     *
     * @param  array<string, mixed>  $options
     */
    public static function button(string $variant = 'primary', string $size = 'md', array $options = []): self
    {
        return new self('button', $variant, $size, $options);
    }

    /**
     * Create presenter for card component.
     *
     * @param  array<string, mixed>  $options
     */
    public static function card(string $variant = 'default', string $size = 'md', array $options = []): self
    {
        return new self('card', $variant, $size, $options);
    }

    /**
     * Create presenter for badge component.
     *
     * @param  array<string, mixed>  $options
     */
    public static function badge(string $variant = 'default', string $size = 'md', array $options = []): self
    {
        return new self('badge', $variant, $size, $options);
    }

    /**
     * Create presenter for alert component.
     *
     * @param  array<string, mixed>  $options
     */
    public static function alert(string $variant = 'info', string $size = 'md', array $options = []): self
    {
        return new self('alert', $variant, $size, $options);
    }

    /**
     * Create presenter for modal component.
     *
     * @param  array<string, mixed>  $options
     */
    public static function modal(string $variant = 'default', string $size = 'md', array $options = []): self
    {
        return new self('modal', $variant, $size, $options);
    }

    /**
     * Get button variant class.
     */
    private function getButtonVariantClass(): string
    {
        return match ($this->variant) {
            'primary' => 'bg-primary text-white hover:bg-primary-dark focus:ring-2 focus:ring-primary',
            'secondary' => 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-2 focus:ring-gray-300',
            'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500',
            'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500',
            'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-2 focus:ring-yellow-400',
            'info' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500',
            'light' => 'bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-2 focus:ring-gray-200',
            'dark' => 'bg-gray-800 text-white hover:bg-gray-900 focus:ring-2 focus:ring-gray-700',
            'outline' => 'border border-gray-300 dark:border-gray-600 bg-transparent text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-gray-300',
            'ghost' => 'bg-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:ring-2 focus:ring-gray-300',
            'link' => 'bg-transparent text-blue-600 hover:text-blue-800 focus:ring-2 focus:ring-blue-300 underline',
            default => 'bg-gray-500 text-white hover:bg-gray-600 focus:ring-2 focus:ring-gray-400',
        };
    }

    /**
     * Get button size class.
     */
    private function getButtonSizeClass(): string
    {
        return match ($this->size) {
            'xs' => 'py-1.5 px-2.5 text-xs',
            'sm' => 'py-2 px-3 text-sm',
            'lg' => 'py-3 px-5 text-base',
            'xl' => 'py-4 px-6 text-lg',
            default => 'py-2.5 px-4',
        };
    }

    /**
     * Get card variant class.
     */
    private function getCardVariantClass(): string
    {
        return match ($this->variant) {
            'elevated' => 'card--elevated',
            'outlined' => 'card--outlined',
            'filled' => 'card--filled',
            'gradient' => 'card--gradient',
            default => 'card--default',
        };
    }

    /**
     * Get card size class.
     */
    private function getCardSizeClass(): string
    {
        return match ($this->size) {
            'xs' => 'card--xs',
            'sm' => 'card--sm',
            'lg' => 'card--lg',
            'xl' => 'card--xl',
            default => 'card--md',
        };
    }

    /**
     * Get badge variant class.
     */
    private function getBadgeVariantClass(): string
    {
        return match ($this->variant) {
            'success' => 'badge--success',
            'danger' => 'badge--danger',
            'warning' => 'badge--warning',
            'info' => 'badge--info',
            'primary' => 'badge--primary',
            'secondary' => 'badge--secondary',
            default => 'badge--default',
        };
    }

    /**
     * Get badge size class.
     */
    private function getBadgeSizeClass(): string
    {
        return match ($this->size) {
            'xs' => 'badge--xs',
            'sm' => 'badge--sm',
            'lg' => 'badge--lg',
            default => 'badge--md',
        };
    }

    /**
     * Get alert variant class.
     */
    private function getAlertVariantClass(): string
    {
        return match ($this->variant) {
            'success' => 'alert--success',
            'danger' => 'alert--danger',
            'warning' => 'alert--warning',
            'info' => 'alert--info',
            default => 'alert--default',
        };
    }

    /**
     * Get alert size class.
     */
    private function getAlertSizeClass(): string
    {
        return match ($this->size) {
            'sm' => 'alert--sm',
            'lg' => 'alert--lg',
            default => 'alert--md',
        };
    }

    /**
     * Get modal size class.
     */
    private function getModalSizeClass(): string
    {
        return match ($this->size) {
            'xs' => 'modal--xs',
            'sm' => 'modal--sm',
            'lg' => 'modal--lg',
            'xl' => 'modal--xl',
            default => 'modal--md',
        };
    }

    /**
     * Get generic component classes.
     */
    private function getGenericClasses(): string
    {
        $classes = [
            $this->component,
            $this->component . '--' . $this->variant,
            $this->component . '--' . $this->size,
            $this->getCustomClass(),
        ];

        return $this->generateClasses($classes);
    }

    /**
     * Get component-specific attributes.
     *
     * @return array<string, mixed>
     */
    private function getComponentSpecificAttributes(): array
    {
        return match ($this->component) {
            'button' => $this->getButtonAttributes(),
            'modal' => $this->getModalAttributes(),
            default => [],
        };
    }

    /**
     * Get button-specific attributes.
     *
     * @return array<string, mixed>
     */
    private function getButtonAttributes(): array
    {
        $attributes = [];

        if (isset($this->options['type'])) {
            $attributes['type'] = $this->options['type'];
        }

        return $attributes;
    }

    /**
     * Get modal-specific attributes.
     *
     * @return array<string, string>
     */
    private function getModalAttributes(): array
    {
        return [
            'tabindex' => '-1',
            'aria-hidden' => 'true',
        ];
    }

    // State check methods
    private function isDisabled(): bool
    {
        return $this->options['disabled'] ?? false;
    }

    private function isLoading(): bool
    {
        return $this->options['loading'] ?? false;
    }

    private function isActive(): bool
    {
        return $this->options['active'] ?? false;
    }

    private function isSelected(): bool
    {
        return $this->options['selected'] ?? false;
    }

    private function isVisible(): bool
    {
        return $this->options['visible'] ?? true;
    }

    private function isFullWidth(): bool
    {
        return $this->options['full_width'] ?? false;
    }

    private function hasIcon(): bool
    {
        return isset($this->options['icon']) || isset($this->options['icon_left']) || isset($this->options['icon_right']);
    }

    private function isElevated(): bool
    {
        return $this->options['elevated'] ?? false;
    }

    private function isBordered(): bool
    {
        return $this->options['bordered'] ?? false;
    }

    private function isClickable(): bool
    {
        return $this->options['clickable'] ?? false;
    }

    private function isDot(): bool
    {
        return $this->options['dot'] ?? false;
    }

    private function isOutlined(): bool
    {
        return $this->options['outlined'] ?? false;
    }

    private function isDismissible(): bool
    {
        return $this->options['dismissible'] ?? false;
    }

    private function hasBorder(): bool
    {
        return $this->options['border'] ?? false;
    }

    private function isCentered(): bool
    {
        return $this->options['centered'] ?? false;
    }

    private function isFullscreen(): bool
    {
        return $this->options['fullscreen'] ?? false;
    }

    private function isScrollable(): bool
    {
        return $this->options['scrollable'] ?? false;
    }

    private function isAriaExpanded(): ?bool
    {
        if (! isset($this->options['aria_expanded'])) {
            return null;
        }

        return $this->options['aria_expanded'];
    }

    private function getAriaLabel(): ?string
    {
        return $this->options['aria_label'] ?? null;
    }

    private function getRole(): ?string
    {
        return $this->options['role'] ?? null;
    }

    private function getCustomClass(): string
    {
        return $this->options['class'] ?? '';
    }
}
