<?php

namespace Webleit\RevisoApi\Exceptions;

use Throwable;

class DetailedErrorResponseException extends ErrorResponseException
{
    /**
     * @var
     */
    protected $error;

    /**
     * DetailedErrorResponseException constructor.
     * @param $error
     */
    public function __construct ($error)
    {
        $this->error = $error;

        $details = null;
        if (isset($error->details)) {
            $details = $error->details;
        }

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

    /**
     * @return mixed
     */
    public function getError ()
    {
        return $this->error;
    }
}