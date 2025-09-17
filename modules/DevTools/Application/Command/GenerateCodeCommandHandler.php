<?php

declare(strict_types=1);

namespace Modules\DevTools\Application\Command;

use Modules\DevTools\Domain\Service\CodeGeneratorService;

final readonly class GenerateCodeCommandHandler
{
    public function __construct(
        private CodeGeneratorService $codeGeneratorService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(GenerateCodeCommand $command): array
    {
        $result = $this->codeGeneratorService->generate(
            type: $command->type,
            module: $command->module,
            name: $command->name,
            properties: $command->properties,
            options: $command->options
        );

        return [
            'success' => true,
            'generated_files' => $result['files'],
            'message' => "Successfully generated {$command->type} '{$command->name}' in module '{$command->module}'",
        ];
    }
}
