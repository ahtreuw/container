<?php declare(strict_types=1);

namespace Container\Processor;

use Closure;
use Container\ProcessHandlerInterface;
use Container\ProcessorInterface;
use Container\ProcessDTO;

class ClosureProcessor implements ProcessorInterface
{

    public function process(ProcessDTO $dto, ProcessHandlerInterface $handler): mixed
    {
        if ($dto->value instanceof Closure === false) {
            return $handler->handle($dto);
        }

        $result = ($dto->value)($dto->container, $dto->id, ...$dto->arguments);

        $dto->container->set($dto->id, $result);

        return $result;
    }
}
