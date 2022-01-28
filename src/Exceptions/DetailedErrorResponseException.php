<?php

namespace Weble\RevisoApi\Exceptions;

class DetailedErrorResponseException extends ErrorResponseException
{
    protected object $error;

    public function __construct (object $error)
    {
        $this->error = $error;

        $details = $error->details ?? null;

        $message = sprintf(
            "Error Code: %s. Message: %s. Hint: %s. Details: %s",
            $error->errorCode, $error->message,  $error->developerHint, json_encode($details, JSON_THROW_ON_ERROR)
        );

        if ($error->errors) {
            foreach ($error->errors as $e) {
                $details = null;
                if (isset($e->details)) {
                    $details = $e->details;
                }

                $message .= " " . sprintf(
                        "Error Code: %s. Message: %s. Hint: %s. Details: %s",
                        $e->errorCode, $e->message, $error->message, json_encode($details, JSON_THROW_ON_ERROR)
                    );
            }
        }

        parent::__construct($message);
    }

    public function getError (): object
    {
        return $this->error;
    }
}
