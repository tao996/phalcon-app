<?php

namespace Phax\Filter\Validation;

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\AbstractValidator;
use Phalcon\Messages\Message;
use Phax\Support\Facades\Helper;

abstract class AbstractValidation extends AbstractValidator
{

    protected function addMessage(Validation $validation, array $placeholders, string $filed)
    {
        $message = $this->getOption('message');
        if (!isset($placeholders['field'])) {
            $placeholders['field'] = $filed;
        }

        $validation->appendMessage(
            new Message(
                Helper::interpolate($message, $placeholders),
            )
        );
        return false;
    }
}