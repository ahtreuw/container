<?php declare(strict_types=1);

namespace Container;

interface ProcessHandlerInterface
{
    public function with(ProcessorInterface ...$processors): static;

    public function handle(ProcessDTO $dto): mixed;
}