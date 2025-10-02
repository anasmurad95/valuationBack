<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

trait HelperTrait
{
    public function lang__()
    {
        return app()->getLocale();
    }

    public function returnInvalidate($exception)
    {
        return response()->json([
            'status'     => false,
            'error_code' => $exception->status ?? 422,
            'error_msg'  => $exception->getMessage(),
            'data'       => $exception->errors()
        ], $exception->status ?? 422);
    }

    public function returnError($error_msg, $error_code = -1, $status = 200)
    {
        return response()->json([
            'status'     => false,
            'error_code' => $error_code,
            'error_msg'  => $error_msg,
        ], $status);
    }

    public function unAuthenticated()
    {
        return response()->json([
            'status'     => false,
            'error_code' => 401,
            'error_msg'  => __('messages.unauthenticated'),
        ], 401);
    }

    public function unAuthorized()
    {
        return response()->json([
            'status'     => false,
            'error_code' => 403,
            'error_msg'  => __('messages.unauthorized'),
        ], 403);
    }

    public function returnPaginateData($data)
    {
        return response()->json(collect([
            'status'     => true,
            'error_code' => 0,
            'error_msg'  => __('messages.successfully'),
        ])->merge($data));
    }

    public function returnPaginateDataWithOther($data, $other, $otherName = 'other')
    {
        return response()->json(collect([
            'status'     => true,
            'error_code' => 0,
            'error_msg'  => __('messages.successfully'),
            $otherName   => $other,
        ])->merge($data));
    }

    public function returnDataArray($data, $error_msg = null)
    {
        return response()->json([
            'status'     => true,
            'error_code' => 0,
            'error_msg'  => $error_msg ?? __('messages.successfully'),
            'data'       => $data
        ]);
    }

    public function returnDataArrayWithOther($data, $other, $otherName = 'other')
    {
        return response()->json([
            'status'     => true,
            'error_code' => 0,
            'error_msg'  => __('messages.successfully'),
            $otherName   => $other,
            'data'       => $data
        ]);
    }

    public function returnSuccess($error_msg = null)
    {
        return response()->json([
            'status'     => true,
            'error_code' => 0,
            'error_msg'  => $error_msg ?? __('messages.successfully'),
        ]);
    }

    public function saveImageUrl($photoUrl, $folder)
    {
        $fileName = Str::random(5) . time() . '.png';
        $path = $folder . '/' . $fileName;
        $contents = file_get_contents($photoUrl);
        file_put_contents(public_path($path), $contents);
        return $path;
    }

    public function saveImage($photo, $folder)
    {
        $fileName = Str::random(5) . time() . '.' . $photo->getClientOriginalExtension();
        $photo->move(public_path($folder), $fileName);
        return $folder . '/' . $fileName;
    }
}
