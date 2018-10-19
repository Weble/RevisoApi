<?php

namespace Webleit\RevisoApi\Exceptions;

use Throwable;

class DetailedErrorResponseException extends ErrorResponseException
{

    public function __construct ($error)
    {
        $message = sprintf(
            "Error Code: %s. Message: %s. Hint: %s. Details: %s",
            $error->errorCode, $error->message,  $error->developerHint, json_encode($error->details)
        );

        if ($error->errors) {
            foreach ($error->errors as $e) {
                $message .= " " . sprintf(
                        "Error Code: %s. Message: %s. Hint: %s. Details: %s",
                        $e->errorCode, $e->message, $error->message, json_encode($error->details)
                    );
            }
        }

        parent::__construct($message);
    }
}