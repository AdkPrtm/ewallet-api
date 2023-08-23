<?php

namespace App\Helpers;

/**
 * Format response.
 */
class ResponseFormatter
{
    /**
     * API Response
     *
     * @var array
     */
    protected static $response = [
        'status' => 'success',
        'message' => null,
        'data' => null,
    ];

    /**
     * Give success response.
     */
    public static function success($data = null, $message = null, $code = 200)
    {
        self::$response['message'] = $message;
        self::$response['data'] = $data;

        return response()->json(self::$response, $code);
    }

    /**
     * Give error response.
     */
    public static function error($data = null, $message = null, $code = 400)
    {
        self::$response['status'] = 'error';
        self::$response['message'] = $message;
        self::$response['data'] = $data;

        return response()->json(self::$response, $code);
    }
}
