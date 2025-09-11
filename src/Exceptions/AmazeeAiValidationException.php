<?php

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Exceptions;

use CuyZ\Valinor\Mapper\MappingError;

class AmazeeAiValidationException extends AmazeeAiClientException
{
    public function __construct(string $message, MappingError $mappingError, int $code = 0, ?\Throwable $previous = null)
    {
        $detailedMessage = $message.': '.$this->formatMappingError($mappingError);
        parent::__construct($detailedMessage, $code, $previous);
    }

    private function formatMappingError(MappingError $error): string
    {
        $messages = [];
        foreach ($error->node()->messages() as $message) {
            $messages[] = (string) $message;
        }

        return implode('; ', $messages);
    }
}
