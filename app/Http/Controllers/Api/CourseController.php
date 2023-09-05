<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function courseList()
    {
        try {
            $result = Course::select('name', 'thumbnail', 'lesson_num', 'price', 'id')->get();
            return response()->json([
                'code' => 200,
                'msg' => 'My course list is here',
                'data' => $result
            ], 200);
        } catch (\Throwable $throw) {
            return response()->json([
                'code' => 500,
                'msg' => $throw->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
