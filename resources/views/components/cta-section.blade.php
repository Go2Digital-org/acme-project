{{-- Call to Action Section Component --}}
@props(['title', 'description', 'primaryButtonText', 'primaryButtonHref', 'primaryButtonIcon' => null, 'secondaryButtonText' => null, 'secondaryButtonHref' => null, 'secondaryButtonIcon' => null, 'bgClass' => 'bg-gradient-to-r from-primary to-purple-600'])

<section class="relative overflow-hidden {{ $bgClass }} py-16 sm:py-24" aria-labelledby="cta-title">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="cta-title" class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                {{ $title }}
            </h2>
            <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-white/90">
                {{ $description }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <x-button 
                    href="{{ $primaryButtonHref }}" 
                    variant="secondary" 
                    size="lg"
                    :icon="$primaryButtonIcon"
                >
                    {{ $primaryButtonText }}
                </x-button>

                @if($secondaryButtonText)
                <x-button 
                    href="{{ $secondaryButtonHref }}" 
                    variant="outline" 
                    size="lg"
                    :icon="$secondaryButtonIcon"
                >
                    {{ $secondaryButtonText }}
                </x-button>
                @endif
            </div>
        </div>
    </div>
</section>