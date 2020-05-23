<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        //Response validation Errors
        if ($exception instanceof ValidationException) {
             $res = [
                'status'    => false,
                'message'   => __('errors.error_message'),
                'errors'    => $exception->validator->getMessageBag()
            ];
            return response()->json($res, 422);
        }

        //response no result query Errors
        if ($exception instanceof ModelNotFoundException) {
            $res = [
                'status'    => false,
                'message'   => __('errors.no_result'),
            ];
            return response()->json($res, 422);
        }

        //response not found page Error
        if ($exception instanceof NotFoundHttpException) {
            $res = [
                'status'    => false,
                'message'   => __('errors.not_found'),
            ];
            return response()->json($res, 404);
        }

        if ($exception instanceof AuthenticationException) {
            $res = [
                'status'    => false,
                'message'   => __('unauthorized'),
            ];
            return response()->json($res, 401);
        }

        return parent::render($request, $exception);
    }
}
